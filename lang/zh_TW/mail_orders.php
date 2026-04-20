<?php

return [
    'greeting' => '您好:name：',
    'created' => [
        'subject' => '[:store] 訂單已送出 #:order_no',
        'heading' => '您的訂單已送出',
        'intro' => '我們已收到您在 **:store** 的訂單。',
    ],
    'completed' => [
        'subject' => '[:store] 訂單已完成 #:order_no',
        'heading' => '您的訂單已完成',
        'intro' => '您在 **:store** 的訂單已完成，歡迎前往取餐。',
    ],
    'cancelled' => [
        'subject' => '[:store] 訂單已取消 #:order_no',
        'heading' => '您的訂單已取消',
        'intro' => '您在 **:store** 的訂單已取消。',
    ],
    'fields' => [
        'order_no' => '訂單編號：',
        'order_type' => '訂單類型：',
        'status' => '訂單狀態：',
        'total' => '訂單金額：',
        'cancel_reason' => '取消原因：',
    ],
    'order_type' => [
        'takeout' => '外帶',
        'dine_in' => '內用',
    ],
    'cta' => [
        'view_order' => '查看訂單',
    ],
    'footer' => [
        'thanks' => '感謝您的訂購',
    ],
    'status' => [
        'awaiting_payment' => '待收款',
        'pending' => '已送出',
        'accepted' => '已接單',
        'preparing' => '製作中',
        'completed' => '餐點完成',
        'picked_up' => '已取餐',
        'cancelled' => '訂單已取消',
        'updating' => '訂單狀態更新中',
    ],
];
