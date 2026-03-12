<?php

return [
    'channels' => [
        'email' => \App\Notify\Notifiers\EmailNotifier::class,
        'sms' => \App\Notify\Notifiers\SmsNotifier::class,
        //'telegram' => \App\Notify\Notifiers\TelegramNotifier::class,
    ],
];
