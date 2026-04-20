<?php

return [
    'greeting' => '您好:name：',
    'created' => [
        'subject' => '[:store] 订单已送出 #:order_no',
        'heading' => '您的订单已送出',
        'intro' => '我们已收到您在 **:store** 的订单。',
    ],
    'completed' => [
        'subject' => '[:store] 订单已完成 #:order_no',
        'heading' => '您的订单已完成',
        'intro' => '您在 **:store** 的订单已完成，欢迎前往取餐。',
    ],
    'cancelled' => [
        'subject' => '[:store] 订单已取消 #:order_no',
        'heading' => '您的订单已取消',
        'intro' => '您在 **:store** 的订单已取消。',
    ],
    'fields' => [
        'order_no' => '订单编号：',
        'order_type' => '订单类型：',
        'status' => '订单状态：',
        'total' => '订单金额：',
        'cancel_reason' => '取消原因：',
    ],
    'order_type' => [
        'takeout' => '外带',
        'dine_in' => '内用',
    ],
    'cta' => [
        'view_order' => '查看订单',
    ],
    'footer' => [
        'thanks' => '感谢您的订购',
    ],
    'status' => [
        'awaiting_payment' => '待收款',
        'pending' => '已送出',
        'accepted' => '已接单',
        'preparing' => '制作中',
        'completed' => '餐点完成',
        'picked_up' => '已取餐',
        'cancelled' => '订单已取消',
        'updating' => '订单状态更新中',
    ],
];
