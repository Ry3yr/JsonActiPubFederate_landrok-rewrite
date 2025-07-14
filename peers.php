<?php
// Set content type to JSON
header('Content-Type: application/json');

// Optional: allow cross-origin requests (if needed)
header('Access-Control-Allow-Origin: *');

// Define static list of peers
$peers = [
    "woof.tech",
    "mastodon.social",
    "mas.to",
    "mastodon.cloud"
];

// Return as JSON
echo json_encode($peers, JSON_PRETTY_PRINT);
