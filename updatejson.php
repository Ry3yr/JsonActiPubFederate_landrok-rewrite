<?php

$remoteUrl = 'https://alceawis.de/other/extra/scripts/fakesocialmedia/data_part_alcea.json';
$localFile = 'data_alcea.json';

// Load remote JSON
$remoteJson = file_get_contents($remoteUrl);
if ($remoteJson === false) {
    die("Failed to load remote JSON.\n");
}
$remoteData = json_decode($remoteJson, true);
if ($remoteData === null) {
    die("Failed to decode remote JSON.\n");
}

// Ensure local JSON file exists
if (!file_exists($localFile)) {
    die("Local JSON file '$localFile' does not exist.\n");
}

// Load local JSON
$localJson = file_get_contents($localFile);
if ($localJson === false) {
    die("Failed to read local file '$localFile'.\n");
}

// Decode local JSON
$localData = json_decode($localJson, true);
if ($localData === null) {
    die("Failed to decode local JSON.\n");
}

// Function to compare if an entry exists in the local data
function entryExistsInLocalData(array $entry, array $localData): bool {
    // Convert the entry to a string to compare it exactly as it is
    $entryStr = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    foreach ($localData as $localEntry) {
        // Compare string representations exactly
        if (json_encode($localEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) === $entryStr) {
            return true;
        }
    }
    return false;
}

// Find new entries from remote with '•acws' in their value that don't exist locally
$newEntries = [];
foreach ($remoteData as $entry) {
    foreach ($entry as $date => $data) {
        if (isset($data['value']) && strpos($data['value'], '•acws') !== false) {
            // If entry does not already exist in local data, append it
            if (!entryExistsInLocalData($entry, $localData)) {
                $newEntries[] = $entry;
            }
        }
    }
}

// If new entries exist, prepend them to the local data
if (count($newEntries) > 0) {
    // Prepend new entries in original remote order
    $updatedData = array_merge($newEntries, $localData);

    // Save back to local file with exact formatting and no changes
    $saved = file_put_contents($localFile, json_encode($updatedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    if ($saved === false) {
        die("Failed to write updated data to local file.\n");
    }

    // Display the prepended entries
    echo "Prepended " . count($newEntries) . " new '•acws' entries to $localFile:\n";
    echo json_encode($newEntries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else {
    echo "No new '•acws' entries to prepend.\n";
}

