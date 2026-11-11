<?php
// 用法：php review.php -c 分类 -m 主标题 -n 数量
// 用法示例：php review.php -c 默认 -m 原子习惯 -n 10

$config = require __DIR__ . "/include/Config.php";

require __DIR__ . "/include/Database.php";
require __DIR__ . "/include/ReviewManager.php";

//$options = getopt("c:m:n:", [ "cate","main:","number:"]);

$options = $_GET;

if (!empty($options['action']) && $options['action'] == 'done_review') {
    // 当日复习+1
    doneReview($config,$options);
} elseif (!empty($options['action']) && $options['action'] == 'cancel_review') {
    // 当日复习-1
    cancelReview($config,$options);
} elseif (!empty($options['action']) && $options['action'] == 'reset_review') {
    // 重置复习次数
    resetReview($config,$options);
} else {
    showIndex($config,$options);
}

function showIndex($config,$options)
{
    if(isset($options["c"])) {
        $category = $options["c"];
    } elseif(isset($options["cate"])) {
        $category = $options["cate"];
    } else {
        $category = '';
    }

    if(isset($options["m"])) {
        $mainTitle = $options["m"];
    } elseif(isset($options["main"])) {
        $mainTitle = $options["main"];
    } else {
        $mainTitle = '';
    }

    if(isset($options["n"])) {
        $number = intval($options["n"]);
    } elseif(isset($options["number"])) {
        $number = intval($options["number"]);
    } else {
        $number = 1000;
    }

    try{
        $db = new Database($config["db"]);

        // 获取数据
        $records = $db->getReviewList($category, $mainTitle);
   
        // 复习算法
        $manager = new ReviewManager($config["day_weight"]);
        $result = $manager->calculate($records);
        $list = array_slice($result["records"], 0, $number);

//    echo '<pre>';
//    print_r($list);

        $navs = [
            ['c' => '默认','m' => '新概念2','n' => $number],
            ['c' => '默认','m' => '新概念3','n' => $number],
            ['c' => '默认','m' => '6分钟2026','n' => $number],
            //['c' => '默认','m' => '地道英语-2026下','n' => $number],
            //['c' => '默认','m' => 'TED2026','n' => $number],
            //['c' => '默认','m' => 'Daily English','n' => $number],
            //['c' => '默认','m' => '卫报','n' => $number],
            //['c' => '默认','m' => '原子习惯','n' => $number],
        ];

        require __DIR__ . "/view/index.php";
    } catch(Exception $e) {
        echo "错误:" . $e->getMessage() . "\n";
    }
}
function doneReview($config,$options){
    if (!empty($options['id'])) {
        try{
            $db = new Database($config["db"]);

            $row = $db->getReviewById($options['id']);
            if ($row['last_review_date'] != date('Y-m-d')) {
                $db->updateReviewById($options['id']);
            } else {
                exit('doneReview 操作失败');
            }

            header("Location:?c={$options['c']}&m={$options['m']}", true, 302);

        } catch(Exception $e) {
            echo "错误:" . $e->getMessage() . "\n";
        }
    } else {
        exit('id 不存在');
    }
}

function cancelReview($config,$options){
    if (!empty($options['id'])) {
        try{
            $db = new Database($config["db"]);

            $row = $db->getReviewById($options['id']);
            if ($row['last_review_date'] == date('Y-m-d')) {
                $db->cancelReviewById($options['id']);
            } else {
                exit('cancelReview 操作失败');
            }

            header("Location:?c={$options['c']}&m={$options['m']}", true, 302);

        } catch(Exception $e) {
            echo "错误:" . $e->getMessage() . "\n";
        }
    } else {
        exit('id 不存在');
    }
}

function resetReview($config,$options){
    if (!empty($options['id'])) {
        try{
            $db = new Database($config["db"]);

            $row = $db->getReviewById($options['id']);
            if (!empty($row) && $row['review_count'] > 0) {
                $db->resetReview($options['id']);
            } else {
                exit('resetReview 操作失败');
            }

            header("Location:?c={$options['c']}&m={$options['m']}", true, 302);

        } catch(Exception $e) {
            echo "错误:" . $e->getMessage() . "\n";
        }
    } else {
        exit('id 不存在');
    }
}

