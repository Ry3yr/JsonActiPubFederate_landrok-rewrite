<?php
header('Content-Type: application/json');

// Fetch post count from actor's outbox
$outboxUrl = 'https://alceawis.com/alceawis/outbox';
$postCount = 0;

$outboxJson = @file_get_contents($outboxUrl);
if ($outboxJson !== false) {
    $outboxData = json_decode($outboxJson, true);
    if (isset($outboxData['totalItems'])) {
        $postCount = (int)$outboxData['totalItems'];
    }
} else {
    error_log("Failed to fetch outbox JSON from $outboxUrl");
}

// Build instance info JSON
$instance_info = [
    "uri" => "alceawis.com",
    "title" => "Alceawis Fediverse",
    "short_description" => "A Fediverse instance powered by Alceawis",
    "description" => "Welcome to the Alceawis Fediverse instance!",
    "version" => "1.0.0",
    "stats" => [
        "user_count" => 42,           // <-- update manually or dynamically later
        "status_count" => $postCount,
        "domain_count" => 10          // <-- update manually or dynamically later
    ],
    "languages" => ["en"],
    "registrations" => true,
    "approval_required" => false,
    "email_required" => true,
    "urls" => [
        "streaming_api" => "wss://alceawis.com/streaming"
    ],
    "thumbnail" => "https://alceawis.com/logo.png",
    "contact_account" => [
        "username" => "admin",
        "acct" => "admin@alceawis.com",
        "url" => "https://alceawis.com/@admin"
    ]
];

// Output JSON pretty and unescaped slashes
echo json_encode($instance_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
