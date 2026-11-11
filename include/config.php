<?php
/**
 * 复习系统配置
 */

return array(

    // SQLite数据库
    "db" => __DIR__ . "/review.db",

    // 日期权重
    // 未复习天数 × 权重
    // 数值越大，越重视遗忘时间
    "day_weight" => 0.5

);