<?php

return [
    'name' => env('COMPANY_NAME', 'Chindeka Tuck Shop'),
    'tagline' => env('COMPANY_TAGLINE', 'Stock, sales and invoices'),
    'address' => env('COMPANY_ADDRESS', 'Harare, Zimbabwe'),
    'email' => env('COMPANY_EMAIL', 'billing@chindeka.test'),
    'phone' => env('COMPANY_PHONE', '+263 77 000 0000'),
    'currency' => env('COMPANY_CURRENCY', 'USD'),
    'currency_symbol' => env('COMPANY_CURRENCY_SYMBOL', '$'),
    'default_tax_rate' => (float) env('COMPANY_TAX_RATE', 15),
    'receipt_footer' => env('COMPANY_RECEIPT_FOOTER', 'Thank you'),
];
