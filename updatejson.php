


<?php
$remoteUrl = 'https://alceawis.de/other/extra/scripts/fakesocialmedia/data_part_alcea.json';
$localFile = 'data_alcea.json';

// Load remote data
$remoteJson = file_get_contents($remoteUrl);
if ($remoteJson === false) {
    die("Failed to load remote JSON.\n");
}
$remoteData = json_decode($remoteJson, true);
if ($remoteData === null) {
    die("Failed to decode remote JSON.\n");
}

// Load local data
if (!file_exists($localFile)) {
    die("Local JSON file '$localFile' does not exist.\n");
}
$localJson = file_get_contents($localFile);
if ($localJson === false) {
    die("Failed to read local file '$localFile'.\n");
}
$localData = json_decode($localJson, true);
if ($localData === null) {
    die("Failed to decode local JSON.\n");
}

// Compare function
function entryExistsInLocalData(array $entry, array $localData): bool {
    $entryStr = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    foreach ($localData as $localEntry) {
        if (json_encode($localEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) === $entryStr) {
            return true;
        }
    }
    return false;
}

// Sanitize content
function sanitizeValue($value) {
    return preg_replace_callback('/𒐫(.*?)𒐫/u', function ($matches) {
        return "<blockquote>" . htmlspecialchars(trim($matches[1])) . "</blockquote>";
    }, $value);
}

// Find new entries
$newEntries = [];
foreach ($remoteData as $entry) {
    foreach ($entry as $date => &$data) {
        if (isset($data['value']) && strpos($data['value'], '•acws') !== false) {
            if (!entryExistsInLocalData($entry, $localData)) {
                $data['value'] = sanitizeValue($data['value']);
                $newEntries[] = [$date => $data];
            }
        }
    }
}

// If accessed via POST, write data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (count($newEntries) > 0) {
        $updatedData = array_merge($newEntries, $localData);
        $saved = file_put_contents($localFile, json_encode($updatedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ($saved === false) {
            die("Failed to write updated data to local file.\n");
        }
        echo "<p><strong>Prepended " . count($newEntries) . " new '•acws' entries to $localFile.</strong></p>";
    } else {
        echo "<p>No new entries to prepend.</p>";
    }
    echo '<p><a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">Back</a></p>';
    exit;
}

// Show preview if GET
echo "<h2>New '•acws' Entries Preview</h2>";
if (count($newEntries) === 0) {
    echo "<p>No new entries to prepend.</p>";
    exit;
}

echo "<form method='post'>";
echo "<pre>" . htmlspecialchars(json_encode($newEntries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "</pre>";
echo "<button type='submit'>Confirm and Prepend</button>";
echo "</form>";
?>

