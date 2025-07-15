<?php
// API endpoints
$followersUrl = 'https://alceawis.com/alceawis/followers';
$outboxUrl = 'https://alceawis.com/alceawis/outbox';
$profileInfoUrl = 'https://alceawis.com/profileinfo.json';  // Path to the profileinfo.json file

// Function to fetch JSON data
function fetchJsonData($url) {
    $jsonData = file_get_contents($url);
    return json_decode($jsonData, true);
}

// Fetch followers count
$followersData = fetchJsonData($followersUrl);
$followersCount = isset($followersData['orderedItems']) ? count($followersData['orderedItems']) : 0;

// Fetch post count
$outboxData = fetchJsonData($outboxUrl);
$postCount = isset($outboxData['totalItems']) ? $outboxData['totalItems'] : 0;

// Fetch profile description and other info from profileinfo.json
$profileInfo = fetchJsonData($profileInfoUrl);

// Prepare the response structure
$response = [
    "id" => "0001",
    "username" => "alceawis",
    "acct" => "alceawis@alceawis.com",
    "display_name" => "Alcea Bot",
    "locked" => false,
    "bot" => false,
    "discoverable" => $profileInfo['discoverable'],  // Dynamic fetch from profileinfo.json
    "indexable" => false,
    "group" => false,
    "created_at" => "2025-07-11T00:00:00.000Z",
    "note" => $profileInfo['description'],  // Dynamic fetch from profileinfo.json
    "url" => "https://alceawis.com/alceawis",
    "uri" => "https://alceawis.com/alceawis",
    "avatar" => "https://alceawis.com/z_files/emojis/alceawis.gif",
    "avatar_static" => "https://media.mas.to/cache/accounts/avatars/114/836/452/575/377/152/static/c76dddef19970a66.png",
    "header" => "https://mas.to/headers/original/missing.png",
    "header_static" => "https://mas.to/headers/original/missing.png",
    "followers_count" => $followersCount,
    "following_count" => 0,
    "statuses_count" => $postCount,
    "last_status_at" => "2025-07-15",
    "hide_collections" => true,
    "emojis" => [],
    "fields" => []
];

// Output the response as JSON
header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);
