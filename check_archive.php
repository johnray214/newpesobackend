<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = \App\Models\Event::onlyTrashed()->count();
$paginated = \App\Models\Event::onlyTrashed()->orderByDesc('deleted_at')->paginate(15)->toArray();

echo json_encode(['count' => $count, 'paginated' => $paginated], JSON_PRETTY_PRINT);
