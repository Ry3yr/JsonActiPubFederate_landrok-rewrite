<?php
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("Error: Autoload file not found at $autoloadPath\n");
}
require $autoloadPath;

use ActivityPhp\Server;

// --- CONFIGURATION ---
$domain = 'yusaao.com';
$username = 'yusaao';
$baseUrl = "https://$domain";

// Instantiate ActivityPub server (optional)
$server = new Server();

// Load posts from JSON file
$data = json_decode(file_get_contents(__DIR__ . '/data_alcea.json'), true);
$pushedFile = __DIR__ . '/pushed.json';
$pushed = file_exists($pushedFile) ? json_decode(file_get_contents($pushedFile), true) : [];

$outboxItems = [];

// Prepare outbox and detect new posts to push
foreach ($data as $entry) {
    foreach ($entry as $date => $content) {
        $hash = substr(md5($content['value']), 0, 8);
        $text = formatEmojis($content['value']);
        $hashtags = array_filter(array_map('trim', explode(',', $content['hashtags'] ?? '')));

        if (count($hashtags) > 0) {
            $text .= ' ' . implode(' ', array_map(fn($t) => "#$t", $hashtags));
        }

        $tags = array_map(function($tag) use ($domain) {
            return [
                'type' => 'Hashtag',
                'name' => "#$tag",
                'href' => "https://$domain/tags/$tag"
            ];
        }, $hashtags);

        $noteId = "$baseUrl/$username/status/{$date}-$hash";

        $note = [
            'id' => $noteId,
            'type' => 'Note',
            'published' => date(DATE_ATOM, strtotime($date)),
            'attributedTo' => "$baseUrl/$username",
            'to' => ['https://www.w3.org/ns/activitystreams#Public'],
            'content' => nl2br($text),
            'tag' => $tags,
        ];

        $outboxItems[] = $note;

        // Push Create activity if this note wasn't pushed before
        if (!in_array($noteId, $pushed)) {
            sendCreateActivity($note);
            $pushed[] = $noteId;
            file_put_contents($pushedFile, json_encode($pushed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }
}

$uri = $_SERVER['REQUEST_URI'] ?? '';
$uri = explode('?', $uri)[0];

// === ACTOR ENDPOINT ===
if ($uri === "/$username" || $uri === "/$username/") {
    header('Content-Type: application/activity+json');
    header('Vary: Accept');
    echo json_encode([
        '@context' => [
            'https://www.w3.org/ns/activitystreams',
            [
                'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers',
                'toot' => 'http://joinmastodon.org/ns#',
                'featured' => [
                    '@id' => 'toot:featured',
                    '@type' => '@id'
                ]
            ]
        ],
        'id' => "$baseUrl/$username",
        'type' => 'Person',
        'name' => 'Alcea Bot',
        'preferredUsername' => $username,
        'inbox' => "$baseUrl/$username/inbox",
        'outbox' => "$baseUrl/$username/outbox",
        'followers' => "$baseUrl/$username/followers",
        'publicKey' => [
            'id' => "$baseUrl/$username#main-key",
            'owner' => "$baseUrl/$username",
            'publicKeyPem' => file_get_contents(__DIR__ . '/public.pem'),
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

// === OUTBOX ENDPOINT ===
if ($uri === "/$username/outbox" || $uri === "/$username/outbox/") {
    header('Content-Type: application/activity+json');

    if (isset($_GET['page']) && $_GET['page'] === 'true') {
        echo json_encode([
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
                [
                    'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers',
                    'toot' => 'http://joinmastodon.org/ns#',
                    'featured' => [
                        '@id' => 'toot:featured',
                        '@type' => '@id'
                    ]
                ]
            ],
            'id' => "$baseUrl/$username/outbox?page=true",
            'type' => 'OrderedCollectionPage',
            'partOf' => "$baseUrl/$username/outbox",
            'orderedItems' => $outboxItems,
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
                [
                    'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers',
                    'toot' => 'http://joinmastodon.org/ns#',
                    'featured' => [
                        '@id' => 'toot:featured',
                        '@type' => '@id'
                    ]
                ]
            ],
            'id' => "$baseUrl/$username/outbox",
            'type' => 'OrderedCollection',
            'totalItems' => count($outboxItems),
            'first' => "$baseUrl/$username/outbox?page=true"
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    exit;
}

// === FOLLOWERS ENDPOINT ===
if ($uri === "/$username/followers" || $uri === "/$username/followers/") {
    header('Content-Type: application/activity+json');
    header('Vary: Accept');

    $followersFile = __DIR__ . '/followers.json';
    $followers = file_exists($followersFile) ? json_decode(file_get_contents($followersFile), true) : [];

    echo json_encode([
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => "$baseUrl/$username/followers",
        'type' => 'OrderedCollection',
        'totalItems' => count($followers),
        'orderedItems' => $followers
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

// === INBOX ENDPOINT ===
if ($uri === "/$username/inbox" || $uri === "/$username/inbox/") {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payload = file_get_contents('php://input');
        file_put_contents(__DIR__ . '/inbox.log', "[" . date('c') . "] Received payload:\n$payload\n\n", FILE_APPEND);

        $activity = json_decode($payload, true);
        if (!$activity) {
            file_put_contents(__DIR__ . '/inbox.log', "[" . date('c') . "] ERROR: Could not decode JSON payload.\n\n", FILE_APPEND);
            http_response_code(400);
            echo "Bad Request: Invalid JSON";
            exit;
        }

        if ($activity['type'] === 'Follow') {
            $follower = $activity['actor'] ?? null;
            $followId = $activity['id'] ?? null;

            if ($follower && $followId) {
                file_put_contents(__DIR__ . '/followed.log', $follower . "\n", FILE_APPEND);
                $followersFile = __DIR__ . '/followers.json';
                $followers = file_exists($followersFile) ? json_decode(file_get_contents($followersFile), true) : [];

                if (!in_array($follower, $followers)) {
                    $followers[] = $follower;
                    file_put_contents($followersFile, json_encode($followers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }

                $accept = [
                    '@context' => 'https://www.w3.org/ns/activitystreams',
                    'id' => "$baseUrl/$username/accepts/" . uniqid(),
                    'type' => 'Accept',
                    'actor' => "$baseUrl/$username",
                    'object' => $activity,
                ];

                $followerInbox = discoverInbox($follower);
                if ($followerInbox) {
                    sendSignedRequest($followerInbox, $accept);
                } else {
                    file_put_contents(__DIR__ . '/inbox.log', "[" . date('c') . "] Could not discover inbox for follower: $follower\n", FILE_APPEND);
                }
            }
        }

        http_response_code(202);
        exit;
    } else {
        http_response_code(405);
        header('Allow: POST');
        echo "Method Not Allowed";
        exit;
    }
}

// === 404 ===
http_response_code(404);
echo "Not found";

// === HELPER FUNCTIONS ===

function formatEmojis($text) {
    return preg_replace_callback('/:([a-zA-Z0-9_]+):/', function ($matches) {
        $name = $matches[1];
        $url = "https://yusaao.com/z_files/emojis/{$name}.gif";
        return "<img src=\"$url\" alt=\":$name:\" style=\"height:1em;\">";
    }, $text);
}

function discoverInbox($actorUrl) {
    if (!str_ends_with($actorUrl, '.json')) {
        $actorUrl = rtrim($actorUrl, '/') . '.json';
    }

    $opts = ['http' => ['method' => 'GET', 'header' => "Accept: application/activity+json, application/ld+json\r\n"]];
    $context = stream_context_create($opts);

    $json = @file_get_contents($actorUrl, false, $context);
    if (!$json) {
        file_put_contents(__DIR__ . '/inbox.log', "[" . date('c') . "] Failed to fetch actor JSON from $actorUrl\n", FILE_APPEND);
        return null;
    }

    $actor = json_decode($json, true);
    return $actor['inbox'] ?? null;
}

function sendSignedRequest($inboxUrl, $body) {
    $keyId = "https://yusaao.com/yusaao#main-key";
    $privateKeyPem = file_get_contents(__DIR__ . '/private.pem');
    $date = gmdate('D, d M Y H:i:s \G\M\T');
    $bodyJson = json_encode($body, JSON_UNESCAPED_SLASHES);
    $digest = 'SHA-256=' . base64_encode(hash('sha256', $bodyJson, true));

    $parsed = parse_url($inboxUrl);
    $host = $parsed['host'];
    $path = $parsed['path'];
    $signatureString = "(request-target): post $path\nhost: $host\ndate: $date\ndigest: $digest";

    openssl_sign($signatureString, $signature, $privateKeyPem, OPENSSL_ALGO_SHA256);
    $signature_b64 = base64_encode($signature);
    $signatureHeader = 'keyId="' . $keyId . '",algorithm="rsa-sha256",headers="(request-target) host date digest",signature="' . $signature_b64 . '"';

    $headers = [
        "Host: $host",
        "Date: $date",
        "Digest: $digest",
        "Content-Type: application/activity+json",
        "Signature: $signatureHeader"
    ];

    $ch = curl_init($inboxUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyJson);
    $response = curl_exec($ch);
    curl_close($ch);

    file_put_contents(__DIR__ . '/inbox_response.log', "[" . date('c') . "] Sent activity to $inboxUrl\nResponse:\n$response\n\n", FILE_APPEND);
}

// --- New function to send Create activity to all followers ---
function sendCreateActivity(array $note) {
    global $username, $baseUrl;

    $followersFile = __DIR__ . '/followers.json';
    $followers = file_exists($followersFile) ? json_decode(file_get_contents($followersFile), true) : [];

    $activity = [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $note['id'] . '/activity/' . uniqid(),
        'type' => 'Create',
        'actor' => "$baseUrl/$username",
        'object' => $note,
        'to' => ['https://www.w3.org/ns/activitystreams#Public'],
    ];

    foreach ($followers as $follower) {
        $inbox = discoverInbox($follower);
        if ($inbox) {
            sendSignedRequest($inbox, $activity);
        } else {
            file_put_contents(__DIR__ . '/inbox.log', "[" . date('c') . "] Could not discover inbox for follower: $follower\n", FILE_APPEND);
        }
    }
}
