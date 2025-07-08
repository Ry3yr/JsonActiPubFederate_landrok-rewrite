<?php
// Load Composer autoload
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("Error: Autoload file not found at $autoloadPath\n");
}
require $autoloadPath;

use ActivityPhp\Server;
// use ActivityPhp\Type\Extended\Actor\Person;  // Not used now for actor output

// --- CONFIGURATION ---
$domain = 'yusaao.com';
$username = 'yusaao';
$baseUrl = "https://$domain";

// Instantiate ActivityPub server (you may still use for other things if needed)
$server = new Server();

// Load posts from JSON file
$data = json_decode(file_get_contents(__DIR__ . '/data_alcea.json'), true);
$outboxItems = [];

foreach ($data as $entry) {
    foreach ($entry as $date => $content) {
        $hash = substr(md5($content['value']), 0, 8);
        $text = formatEmojis($content['value']);
        $hashtags = array_filter(array_map('trim', explode(',', $content['hashtags'] ?? '')));

        // Append hashtags as text to content
        if (count($hashtags) > 0) {
            $text .= ' ' . implode(' ', array_map(fn($t) => "#$t", $hashtags));
        }

        // Build tag objects as arrays
        $tags = array_map(function($tag) use ($domain) {
            return [
                'type' => 'Hashtag',
                'name' => "#$tag",
                'href' => "https://$domain/tags/$tag"
            ];
        }, $hashtags);

        $note = [
            'id' => "$baseUrl/$username/status/{$date}-$hash",
            'type' => 'Note',
            'published' => date(DATE_ATOM, strtotime($date)),
            'attributedTo' => "$baseUrl/$username",
            'to' => ['https://www.w3.org/ns/activitystreams#Public'],
            'content' => nl2br($text),
            'tag' => $tags,
        ];

        $outboxItems[] = $note;
    }
}

// Simple routing
$uri = $_SERVER['REQUEST_URI'] ?? '';

// Remove query string if any
$uri = explode('?', $uri)[0];

if ($uri === "/$username" || $uri === "/$username/") {
    header('Content-Type: application/activity+json');
    $actorData = [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => "$baseUrl/$username",
        'type' => 'Person',
        'name' => 'Alcea Bot',
        'preferredUsername' => $username,
        'inbox' => "$baseUrl/$username/inbox",
        'outbox' => "$baseUrl/$username/outbox",
        'publicKey' => [
            'id' => "$baseUrl/$username#main-key",
            'owner' => "$baseUrl/$username",
            'publicKeyPem' => file_get_contents(__DIR__ . '/public.pem'),
        ],
    ];
    echo json_encode($actorData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

if ($uri === "/$username/outbox" || $uri === "/$username/outbox/") {
    header('Content-Type: application/activity+json');
    echo json_encode([
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => "$baseUrl/$username/outbox",
        'type' => 'OrderedCollection',
        'totalItems' => count($outboxItems),
        'orderedItems' => $outboxItems,
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

// You can add inbox and other endpoints here if needed

http_response_code(404);
echo "Not found";

// --- HELPERS ---

function formatEmojis($text) {
    return preg_replace_callback('/:([a-zA-Z0-9_]+):/', function ($matches) {
        $name = $matches[1];
        $url = "https://yusaao.com/z_files/emojis/{$name}.gif";
        return "<img src=\"$url\" alt=\":$name:\" style=\"height:1em;\">";
    }, $text);
}
