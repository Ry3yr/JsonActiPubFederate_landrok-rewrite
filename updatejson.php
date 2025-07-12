<?php
$remoteUrl = 'https://alceawis.de/other/extra/scripts/fakesocialmedia/data_part_alcea.json';
$localFile = 'data_alcea.json';

// 𒐫text𒐫 → <blockquote>text</blockquote>
function convertToBlockquoteTags(string $text): string {
    return preg_replace('/𒐫(.*?)𒐫/u', '<blockquote>$1</blockquote>', $text);
}

// <blockquote>text</blockquote> → 𒐫text𒐫
function convertToUnicodeEnclosure(string $text): string {
    return preg_replace('/<blockquote>(.*?)<\/blockquote>/u', '𒐫$1𒐫', $text);
}

// Apply blockquote conversion for saving
function convertDataToOutputFormat(array $data): array {
    foreach ($data as $key => $val) {
        if (is_array($val)) {
            $data[$key] = convertDataToOutputFormat($val);
        } elseif ($key === 'value' && is_string($val)) {
            $data[$key] = convertToBlockquoteTags($val);
        }
    }
    return $data;
}

// Apply unicode enclosure for comparison
function convertDataToCompareFormat(array $data): array {
    foreach ($data as $key => $val) {
        if (is_array($val)) {
            $data[$key] = convertDataToCompareFormat($val);
        } elseif ($key === 'value' && is_string($val)) {
            $data[$key] = convertToUnicodeEnclosure($val);
        }
    }
    return $data;
}

// Check if entry exists (deep comparison using json_encode)
function entryExists(array $entry, array $local): bool {
    $needle = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    foreach ($local as $item) {
        $haystack = json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($needle === $haystack) return true;
    }
    return false;
}

// --- Load remote ---
$remoteJson = file_get_contents($remoteUrl);
if (!$remoteJson) die(" Failed to load remote JSON.");
$remoteRaw = json_decode($remoteJson, true);
if (!is_array($remoteRaw)) die(" Failed to decode remote JSON.");

// --- Load local ---
if (!file_exists($localFile)) die(" Local JSON file '$localFile' not found.");
$localJson = file_get_contents($localFile);
$localRaw = json_decode($localJson, true);
if (!is_array($localRaw)) die(" Failed to decode local JSON.");

// Normalize local for comparison
$localForCompare = array_map('convertDataToCompareFormat', $localRaw);

// --- Find new entries ---
$newRemoteEntries = [];

foreach ($remoteRaw as $entry) {
    // Only consider if any 'value' contains •acws
    foreach ($entry as $payload) {
        if (isset($payload['value']) && strpos($payload['value'], '•acws') !== false) {
            $compareEntry = convertDataToCompareFormat($entry);
            if (!entryExists($compareEntry, $localForCompare)) {
                $newRemoteEntries[] = $entry;
            }
            break; // stop after finding •acws in this entry
        }
    }
}

// --- If form confirmed ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    if (count($newRemoteEntries) > 0) {
        // Convert 𒐫 to <blockquote> for saving
        $convertedForSave = array_map('convertDataToOutputFormat', $newRemoteEntries);
        $newData = array_merge($convertedForSave, $localRaw);

        $written = file_put_contents(
            $localFile,
            json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        if ($written === false) {
            echo "<p style='color:red;'>Failed to write to local file.</p>";
        } else {
            echo "<p style='color:green;'>Added " . count($convertedForSave) . " new entries to <code>$localFile</code>.</p>";
            $newRemoteEntries = []; // prevent re-showing
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review New •acws Entries</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 2em auto; }
        pre { background: #f5f5f5; padding: 1em; border-radius: 5px; white-space: pre-wrap; }
        button { padding: 0.6em 1em; font-size: 1em; cursor: pointer; }
    </style>
</head>
<body>

<h1>New <code>•acws</code> Entries</h1>

<?php if (count($newRemoteEntries) > 0): ?>
    <p><strong><?php echo count($newRemoteEntries); ?></strong> new entries found in remote file not present in local.</p>
    <pre><?php echo htmlspecialchars(json_encode($newRemoteEntries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></pre>

    <form method="post">
        <button name="confirm" type="submit"> Confirm Append</button>
    </form>
<?php else: ?>
    <p>No new <code>•acws</code> entries found.</p>
<?php endif; ?>

</body>
</html>
