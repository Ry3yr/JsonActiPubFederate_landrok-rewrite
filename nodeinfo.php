<?php
header('Content-Type: application/json; charset=utf-8');

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Handle discovery endpoint
if (preg_match('#/nodeinfo/?$#', $path)) {
    $discovery = [
        "links" => [
            [
                "rel" => "http://nodeinfo.diaspora.software/ns/schema/2.0",
                "href" => "https://alceawis.com/nodeinfo/2.0"
            ]
        ]
    ];
    echo json_encode($discovery, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Handle NodeInfo 2.0 endpoint
if (preg_match('#/nodeinfo/2\.0/?$#', $path)) {

    // Your custom logic: fetch outbox to get post count
    $outboxUrl = 'https://alceawis.com/alceawis/outbox';
    $outboxJson = @file_get_contents($outboxUrl);
    $postCount = 0;

    if ($outboxJson !== false) {
        $outboxData = json_decode($outboxJson, true);
        if (isset($outboxData['totalItems'])) {
            $postCount = (int)$outboxData['totalItems'];
        }
    } else {
        error_log("Failed to fetch outbox JSON from $outboxUrl");
    }

    // Replace these with real user stats or fetch dynamically as well
    $totalUsers = 1;
    $activeMonth = 1;

    $nodeinfo = [
        "version" => "2.0",
        "software" => [
            "name" => "mastodon",
            "version" => "4.2.0"
        ],
        "protocols" => ["activitypub"],
        "services" => [
            "inbound" => [],
            "outbound" => []
        ],
        "openRegistrations" => true,
        "usage" => [
            "users" => [
                "total" => $totalUsers,
                "activeMonth" => $activeMonth
            ],
            "localPosts" => $postCount
        ],
        "metadata" => [
            "nodeName" => "Alcea's Mastodon",
            "description" => "A Mastodon-compatible landrok/Activitypub based instance",
            "instanceStarted" => "2025-07-11T00:00:00Z"
        ]
    ];

    echo json_encode($nodeinfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// If none of the above, 404 Not Found
header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
echo "404 Not Found";
exit;
