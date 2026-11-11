<?php
/**
 * 复习算法管理类
 *
 * 【梯队排序规则（优先级最高，不受priority影响）】
 * 梯队0：review_count = 0（新卡片，最先）
 * 梯队1：review_count > 0 并且 abhs_date 不为空（有艾宾浩斯计划）
 * 梯队2：review_count > 0 并且 abhs_date 为空（走完周期，放最后）
 *
 * 同一梯队内部：按 priority 降序 → days降序 → id升序
 *
 * priority公式：
 * priority = max(-10, min(10, 平均复习次数 - 当前次数)) + sqrt(距离上次复习天数) × dayWeight + abhsScore
 * abhsScore：逾期天数 × abhsWeight；未到期 / abhs为空 = 0
 */
class ReviewManager
{
    private $dayWeight;
    private $abhsWeight;

    /**
     * @param float $dayWeight 遗忘间隔权重
     * @param float $abhsWeight 艾宾浩斯逾期权重
     */
    public function __construct($dayWeight, $abhsWeight = 2)
    {
        $this->dayWeight = $dayWeight;
        $this->abhsWeight = $abhsWeight;
    }

    /**
     * 计算复习优先级并排序
     */
    public function calculate($records)
    {
        $today = new DateTime("today");
        // 处理基础数据
        foreach ($records as &$item) {
            $item["review_count"] = intval($item["review_count"]);
            if ($item["review_count"] < 0) {
                $item["review_count"] = 0;
            }
            $item["days"] = $this->daysBetween($item["last_review_date"], $today);
        }
        unset($item);

        // 计算平均复习次数
        $total = 0;
        foreach ($records as $item) {
            $total += $item["review_count"];
        }
        $avg = count($records) ? $total / count($records) : 0;

        // 计算优先级
        foreach ($records as &$item) {
            $days = $item["days"] === null ? 0 : $item["days"];

            // 1. 次数评分 [-10,10]
            $countScore = max(-10, min(10, ($avg - $item["review_count"])));
            // 2. 遗忘间隔分
            $forgetScore = sqrt($days) * $this->dayWeight;
            // 3. 艾宾浩斯逾期分
            $abhsScore = $this->calcAbhsScore($item['abhs_date'] ?? '', $today);

            $item["priority"] = $countScore + $forgetScore + $abhsScore;

            // 生成本轮新的艾宾浩斯应复习日期（用于下一次排序）
            switch ($item['review_count']) {
                case 1:
                    $item['abhs_date'] = $this->addDay($item['last_review_date'], 1);
                    break;
                case 2:
                    $item['abhs_date'] = $this->addDay($item['last_review_date'], 3);
                    break;
                case 3:
                    $item['abhs_date'] = $this->addDay($item['last_review_date'], 7);
                    break;
                case 4:
                    $item['abhs_date'] = $this->addDay($item['last_review_date'], 15);
                    break;
                case 5:
                    $item['abhs_date'] = $this->addDay($item['last_review_date'], 30);
                    break;
                default:
                    $item['abhs_date'] = '';
            }
        }
        unset($item);

        // 分层排序：梯队优先，同梯队内按priority
        usort($records, function ($a, $b) {
            $getTier = function ($row) {
                if ($row['review_count'] === 0) {
                    return 0;
                }
                if (!empty($row['abhs_date'])) {
                    return 1;
                }
                return 2;
            };
            $tierA = $getTier($a);
            $tierB = $getTier($b);
            if ($tierA !== $tierB) {
                return $tierA - $tierB;
            }

            // 同一梯队：priority 高的在前
            if ($a["priority"] != $b["priority"]) {
                return $a["priority"] < $b["priority"] ? 1 : -1;
            }

            // priority相同：上次复习间隔久的在前
            $daysA = $a["days"] === null ? PHP_INT_MAX : $a["days"];
            $daysB = $b["days"] === null ? PHP_INT_MAX : $b["days"];
            if ($daysA != $daysB) {
                return $daysA < $daysB ? 1 : -1;
            }

            // 最后id升序，稳定排序
            return $a["id"] - $b["id"];
        });

        return [
            "avg" => $avg,
            "records" => $records
        ];
    }

    /**
     * 计算艾宾浩斯逾期加分
     * abhs_date为空 / 未到复习日 返回0；逾期越久分数越高
     */
    private function calcAbhsScore($abhsDateStr, DateTime $today)
    {
        if (trim($abhsDateStr) === '') {
            return 0;
        }
        try {
            $abhsDt = new DateTime($abhsDateStr);
            $abhsDt->setTime(0, 0, 0);
            if ($abhsDt > $today) {
                return 0;
            }
            $diffDays = $abhsDt->diff($today)->days;
            return $diffDays * $this->abhsWeight;
        } catch (Exception $e) {
            return 0;
        }
    }

    private function addDay($inputDate, $addDays)
    {
        $dt = new DateTime($inputDate);
        $dt->modify('+' . $addDays . ' day');
        return $dt->format('Y-m-d');
    }

    /**
     * 计算距离今天多少天
     * 无复习记录返回 null
     */
    private function daysBetween($date, $today)
    {
        if (trim($date) == "") {
            return null;
        }
        try {
            $d = new DateTime($date);
            $d->setTime(0, 0, 0);
            if ($d > $today) {
                return 0;
            }
            return $d->diff($today)->days;
        } catch (Exception $e) {
            return null;
        }
    }
}
