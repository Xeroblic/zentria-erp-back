<?php

return [
    'dedup_ttl' => (int) env('NOTIF_DEDUP_TTL', 7200),
    'digest_times' => (array) json_decode(env('NOTIF_DIGEST_TIMES', '["07:00","16:00"]'), true),
    'email_from' => env('NOTIF_EMAIL_FROM', env('MAIL_FROM_ADDRESS')),
];

