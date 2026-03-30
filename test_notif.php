<?php
$notifs = \App\Models\NotificationRead::with('notification.jobListing.employer')
    ->whereHas('notification', function($q) { $q->where('type', 'invitation'); })
    ->get();
echo json_encode($notifs, JSON_PRETTY_PRINT);
