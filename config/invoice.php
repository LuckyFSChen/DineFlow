<?php

return [
    'upload_deadline_hours' => (int) env('INVOICE_UPLOAD_DEADLINE_HOURS', 48),
    'allowance_upload_deadline_hours' => (int) env('INVOICE_ALLOWANCE_UPLOAD_DEADLINE_HOURS', 48),
    'simulate_failures' => (bool) env('INVOICE_SIMULATE_FAILURES', false),
    'failure_rates' => [
        'issue' => (float) env('INVOICE_FAIL_RATE_ISSUE', 0),
        'upload' => (float) env('INVOICE_FAIL_RATE_UPLOAD', 0),
        'void' => (float) env('INVOICE_FAIL_RATE_VOID', 0),
        'allowance' => (float) env('INVOICE_FAIL_RATE_ALLOWANCE', 0),
    ],
];

