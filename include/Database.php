<?php

class Database
{
    private $pdo;


    public function __construct($file)
    {
        if (!file_exists($file)) {
            throw new Exception("数据库不存在: " . $file);
        }

        $this->pdo = new PDO("sqlite:" . $file);

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    }

    public function getReviewById($id)
    {
        $field = '*';

        $sql = "SELECT {$field} FROM review WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([":id" => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getReviewList($category, $mainTitle)
    {
        $field = 'id,category,main_title,sub_title,last_review_date,review_count,total_review_count';
        $order = 'ORDER BY RANDOM()';
       // $order = '';

        // 仅有分类
        if (!empty($category) && empty($mainTitle)) {
            $sql = "SELECT {$field} FROM review WHERE category = :category {$order}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([":category" => $category]);
        }

        // 仅有主标题
        if (!empty($mainTitle) && empty($category)) {
            $sql = "SELECT {$field} FROM review WHERE main_title = :main {$order}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([":main" => $mainTitle]);
        }

        // 有分类和主标题
        if (!empty($category) && !empty($mainTitle)) {
            $sql = "SELECT {$field} FROM review WHERE category = :category AND main_title = :main {$order}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([":category" => $category,":main" => $mainTitle]);
        }

        // 无分类和主标题
        if (empty($category) && empty($mainTitle)) {
            $sql = "SELECT {$field} FROM review {$order}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateReviewById($id)
    {
        $today = date('Y-m-d');

        // 先插入历史记录
        $insertSql = "INSERT INTO review_history (review_id, review_date) VALUES (:review_id, :review_date)";
        $insertStmt = $this->pdo->prepare($insertSql);
        $insertStmt->execute([
            ":review_id" => $id,
            ":review_date" => $today
        ]);

        // 再更新
        $sql = "UPDATE review SET 
                  cancel_review_date = last_review_date,
                  last_review_date = '{$today}', 
                  review_count = review_count + 1, 
                  total_review_count = total_review_count + 1 
              WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([":id" => $id]);
        return $stmt->rowCount();
    }

    public function cancelReviewById($id)
    {
        $today = date('Y-m-d');

        // 先删除历史记录
        $delSql = "DELETE FROM review_history WHERE review_id = :review_id AND review_date = :review_date";
        $delStmt = $this->pdo->prepare($delSql);
        $delStmt->execute([
            ":review_id" => $id,
            ":review_date" => $today,
        ]);

        // 再更新
        $sql = "UPDATE review SET 
                  last_review_date = cancel_review_date,
                  cancel_review_date = '',
                  review_count = review_count - 1, 
                  total_review_count = total_review_count - 1 
              WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([":id" => $id]);
        return $stmt->rowCount();
    }

    public function resetReview($id)
    {
        // 更新
        $sql = "UPDATE review SET 
                  last_review_date = cancel_review_date,
                  cancel_review_date = '',
                  review_count = 0
              WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([":id" => $id]);
        return $stmt->rowCount();
    }
}