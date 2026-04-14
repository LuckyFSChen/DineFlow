<?php

return [
    'greeting' => 'Hello:name,',
    'created' => [
        'subject' => '[:store] Order Submitted #:order_no',
        'heading' => 'Your Order Has Been Submitted',
        'intro' => 'We have received your order at **:store**.',
    ],
    'completed' => [
        'subject' => '[:store] Order Completed #:order_no',
        'heading' => 'Your Order Is Ready',
        'intro' => 'Your order at **:store** has been completed. Please come pick it up.',
    ],
    'fields' => [
        'order_no' => 'Order No: ',
        'order_type' => 'Order Type: ',
        'status' => 'Order Status: ',
        'total' => 'Order Total: ',
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
        'completed' => 'Ready for Pickup',
        'picked_up' => 'Picked Up',
        'cancelled' => 'Cancelled',
        'updating' => 'Status Updating',
    ],
];
