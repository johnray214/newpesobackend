<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$notifs = \App\Models\NotificationRead::with('notification.jobListing.employer')
    ->whereHas('notification', function($q) { $q->where('type', 'invitation'); })
    ->get();
file_put_contents('test_notif.json', json_encode($notifs, JSON_PRETTY_PRINT));
