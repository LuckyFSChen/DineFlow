<?php

return [
    'greeting' => 'Hello:name,',
    'created' => [
        'subject' => '[:store] Order Submitted #:order_no',
        'heading' => 'Your Order Has Been Submitted',
        'intro' => 'We have received your order at **:store**.',
    ],
    'store_new_order' => [
        'subject' => '[DineFlow] New Store Order',
        'heading' => 'New Store Order',
        'intro' => 'A new store order has been created. Please open the backend to review the latest order details.',
        'cta' => 'Open Backend',
    ],
    'completed' => [
        'subject' => '[:store] Order Completed #:order_no',
        'heading' => 'Your Order Is Ready',
        'intro' => 'Your order at **:store** is complete.',
    ],
    'cancelled' => [
        'subject' => '[:store] Order Cancelled #:order_no',
        'heading' => 'Your Order Has Been Cancelled',
        'intro' => 'Your order at **:store** has been cancelled.',
    ],
    'fields' => [
        'order_no' => 'Order No: ',
        'order_type' => 'Order Type: ',
        'status' => 'Order Status: ',
        'total' => 'Order Total: ',
        'cancel_reason' => 'Cancellation reason: ',
    ],
    'order_type' => [
        'takeout' => 'Takeout',
        'dine_in' => 'Dine-in',
    ],
    'cta' => [
        'view_order' => 'View Order',
    ],
    'footer' => [
        'thanks' => 'Thank you for your order',
    ],
    'status' => [
        'awaiting_payment' => 'Awaiting Payment',
        'pending' => 'Submitted',
        'accepted' => 'Accepted',
        'preparing' => 'Preparing',
        'completed' => 'Order Complete',
        'picked_up' => 'Picked Up',
        'cancelled' => 'Cancelled',
        'updating' => 'Status Updating',
    ],
];
