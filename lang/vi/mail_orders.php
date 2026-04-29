<?php

return [
    'greeting' => 'Xin chao :name,',
    'created' => [
        'subject' => '[:store] Don hang da duoc tao #:order_no',
        'heading' => 'Don Hang Cua Ban Da Duoc Gui',
        'intro' => 'Chung toi da nhan don hang cua ban tai **:store**.',
    ],
    'store_new_order' => [
        'subject' => '[DineFlow] Don hang moi cua cua hang',
        'heading' => 'Don hang moi cua cua hang',
        'intro' => 'Co don hang moi vua duoc tao. Vui long mo trang quan tri de xem chi tiet moi nhat.',
        'cta' => 'Mo trang quan tri',
    ],
    'completed' => [
        'subject' => '[:store] Don hang da hoan thanh #:order_no',
        'heading' => 'Don Hang Cua Ban Da San Sang',
        'intro' => 'Don hang cua ban tai **:store** da hoan thanh.',
    ],
    'cancelled' => [
        'subject' => '[:store] Don hang da bi huy #:order_no',
        'heading' => 'Don Hang Cua Ban Da Bi Huy',
        'intro' => 'Don hang cua ban tai **:store** da bi huy.',
    ],
    'fields' => [
        'order_no' => 'Ma don hang: ',
        'order_type' => 'Loai don: ',
        'status' => 'Trang thai: ',
        'total' => 'Tong tien: ',
        'cancel_reason' => 'Ly do huy: ',
    ],
    'order_type' => [
        'takeout' => 'Mang di',
        'dine_in' => 'Tai quan',
    ],
    'cta' => [
        'view_order' => 'Xem Don Hang',
    ],
    'footer' => [
        'thanks' => 'Cam on ban da dat hang',
    ],
    'status' => [
        'awaiting_payment' => 'Cho thanh toan',
        'pending' => 'Da gui',
        'accepted' => 'Da nhan don',
        'preparing' => 'Dang chuan bi',
        'completed' => 'Mon an da hoan thanh',
        'picked_up' => 'Da nhan mon',
        'cancelled' => 'Da huy',
        'updating' => 'Dang cap nhat trang thai',
    ],
];
