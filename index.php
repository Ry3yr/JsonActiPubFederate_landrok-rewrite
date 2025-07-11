<?php
$currentDomain = $_SERVER['HTTP_HOST'];
$requestUri = $_SERVER['REQUEST_URI'];
$rootDomain = 'alceawis.com'; // Replace with your actual root domain
if ($currentDomain == $rootDomain && $requestUri == '/') {
    echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>' . PHP_EOL;
    echo '<script type="text/javascript">$(document).ready(function(){$("#tl").load("https://alceawis.com/fakesocialrender_limited.html");});</script>' . PHP_EOL;
    echo '<div class="formClass"><div id="tl"></div></div>' . PHP_EOL;}
?>
<?php
// Get the current domain
$currentDomain = $_SERVER['HTTP_HOST'];

// Check if the domain is the root domain
$rootDomain = 'example.com'; // Replace with your actual root domain

if ($currentDomain == $rootDomain) {
    ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function(){
            $("#tl").load("https://alceawis.com/fakesocialrender_limited.html");
        });
    </script>
    <div class="formClass">
        <div id="tl"></div>
    </div>
    <?php
} else {
    // Optionally, di
}
?>




<?php
file_put_contents(__DIR__ . '/debug.log', "[" . date('c') . "] REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? '') . "\n", FILE_APPEND);
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("Error: Autoload file not found at $autoloadPath\n");
}
require $autoloadPath;

use ActivityPhp\Server;

$domain = 'alceawis.com';
$username = 'alceawis';
$baseUrl = "https://$domain";

$server = new Server();

$data = json_decode(file_get_contents(__DIR__ . '/data_alcea.json'), true);
$pushedFile = __DIR__ . '/pushed.json';
$pushed = file_exists($pushedFile) ? json_decode(file_get_contents($pushedFile), true) : [];

$outboxItems = [];

// Helper function to format emoji (unchanged)
function formatEmojis($text) {
    return $text; // Don't replace emoji shortcodes
}

function formatDescriptionLinks(string $text): string {
    $escaped = htmlspecialchars($text);
    $html = preg_replace(
        '~(https?://[^\s<]+)~i',
        '<a href="$1" target="_blank" rel="nofollow noopener noreferrer">$1</a>',
        $escaped
    );
    return nl2br($html);
}

foreach ($data as $entry) {
    foreach ($entry as $date => $content) {
        $hash = substr(md5($content['value']), 0, 8);
        $text = formatEmojis($content['value']);
        $hashtags = array_filter(array_map('trim', explode(',', $content['hashtags'] ?? '')));
        $escapedText = htmlspecialchars($text);
        $htmlText = preg_replace(
            '~(https?://[^\s<]+)~i',
            '<a href="$1" target="_blank" rel="nofollow noopener noreferrer">$1</a>',
            $escapedText
        );
        $htmlText = preg_replace_callback('/#([\w-]+)/', function($matches) use ($domain) {
            $tag = $matches[1];
            $url = "https://$domain/tags/" . urlencode($tag);
            return "<a href=\"$url\" rel=\"tag nofollow noopener noreferrer\">#" . htmlspecialchars($tag) . "</a>";
        }, $htmlText);
        $htmlText = nl2br($htmlText);
        $tags = array_map(function($tag) use ($domain) {
            return [
                'type' => 'Hashtag',
                'name' => "#$tag",
                'href' => "https://$domain/tags/$tag"
            ];
        }, $hashtags);

        // Emoji tags
        preg_match_all('/:([a-zA-Z0-9_]+):/', $content['value'], $emojiMatches);
        $emojiTags = [];
        foreach ($emojiMatches[1] as $shortcode) {
            $emojiTags[] = [
                'type' => 'Emoji',
                'name' => ":$shortcode:",
                'icon' => [
                    'type' => 'Image',
                    'mediaType' => 'image/gif',
                    'url' => "https://$domain/z_files/emojis/$shortcode.gif"
                ]
            ];
        }

        $tags = array_merge($tags, $emojiTags);

        $noteId = "$baseUrl/$username/status/{$date}-$hash";

        $note = [
            'id' => $noteId,
            'type' => 'Note',
            'published' => date(DATE_ATOM, strtotime($date)),
            'attributedTo' => "$baseUrl/$username",
            'to' => ['https://www.w3.org/ns/activitystreams#Public'],

            // THIS IS THE IMPORTANT PART:
            'content' => $htmlText,  // content with clickable links

            'contentMap' => [
                'und' => $text,       // plain text version
                'html' => $htmlText,  // html version with links
            ],
            'tag' => $tags,
        ];

        $outboxItems[] = $note;

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

    $descriptionText = "This is **Alcea's** semit automated profile! It fetches from a local timeline from my website Visit https://alceawis.com for more info.";
    $descriptionHtml = formatDescriptionLinks($descriptionText);

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
        'summary' => $descriptionHtml,        // Add description with clickable links
        'icon' => [                         // Add profile picture
            'type' => 'Image',
            'mediaType' => 'image/gif',
            'url' => "$baseUrl/z_files/emojis/alceawis.gif",
        ],
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


// === GET POST ENDPOINT ===
if (preg_match('/^\/' . $username . '\/status\/([a-z0-9\-]+)$/', $uri, $matches)) {
    $postId = $matches[1];
    $data = json_decode(file_get_contents(__DIR__ . '/data_alcea.json'), true);
    $post = null;
    foreach ($data as $entry) {
        foreach ($entry as $date => $content) {
            $hash = substr(md5($content['value']), 0, 8);
            if ("$date-$hash" === $postId) {
                $post = $content;
                break 2;
            }
        }
    }
    if ($post) {
        $text = formatEmojis($post['value']);
        $escapedText = htmlspecialchars($text);
        $htmlText = preg_replace(
            '~(https?://[^\s<]+)~i',
            '<a href="$1" target="_blank" rel="nofollow noopener noreferrer">$1</a>',
            $escapedText
        );
        $noteId = "$baseUrl/$username/status/{$date}-$hash"; // Build the ID
        $tags = array_map(function($tag) use ($domain) {
            return [
                'type' => 'Hashtag',
                'name' => "#$tag",
                'href' => "https://$domain/tags/$tag"
            ];
        }, explode(',', $post['hashtags'] ?? ''));
        $note = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $noteId,
            'type' => 'Note',
            'published' => date(DATE_ATOM, strtotime($date)),
            'attributedTo' => "$baseUrl/$username",
            'to' => ['https://www.w3.org/ns/activitystreams#Public'],
            'content' => $htmlText,  // HTML formatted content with clickable links
            'contentMap' => [
                'und' => $post['value'],       // plain text version
                'html' => $htmlText,  // html version with links
            ],
            'tag' => $tags,
        ];
        header('Content-Type: application/activity+json');
        echo json_encode($note, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    } else {
        http_response_code(404);
        echo "Not found: The post does not exist.";
        exit;
    }
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
    $keyId = "https://alceawis.com/alceawis#main-key";
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
        "Signature: $signatureHeader",
        "Content-Type: application/activity+json"
    ];
    $ch = curl_init($inboxUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyJson);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        file_put_contents(__DIR__ . '/inbox.log', "[" . date('c') . "] cURL error: " . curl_error($ch) . "\n", FILE_APPEND);
    }
    curl_close($ch);
    return $response;
}
function sendCreateActivity(array $note) {
    global $baseUrl, $username;
    $activity = [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $note['id'] . '/activity',
        'type' => 'Create',
        'actor' => "$baseUrl/$username",
        'object' => $note,
        'to' => ['https://www.w3.org/ns/activitystreams#Public'],
    ];
    $followersFile = __DIR__ . '/followers.json';
    $followers = file_exists($followersFile) ? json_decode(file_get_contents($followersFile), true) : [];
    foreach ($followers as $follower) {
        $inbox = discoverInbox($follower);
        if ($inbox) {
            sendSignedRequest($inbox, $activity);
        }
    }
}
