<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$notifs = \App\Models\Notification::all()->toArray();
file_put_contents('notifs.json', json_encode($notifs, JSON_PRETTY_PRINT));
echo "DONE\n";
