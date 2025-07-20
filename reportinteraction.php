<?php
// Path to your interaction.json file
$jsonFilePath = 'interaction.json';
$outputFilePath = 'interactionlatest.txt';

// Check if the file exists
if (!file_exists($jsonFilePath)) {
    die("File not found: $jsonFilePath");
}

// Read and decode the JSON data
$jsonData = file_get_contents($jsonFilePath);
$interactions = json_decode($jsonData, true);

// Check if JSON data is valid
if ($interactions === null) {
    die("Error decoding JSON data");
}

// Initialize variables to keep track of the newest interaction
$newestInteraction = null;
$newestPublished = null;

// Loop through all interactions
foreach ($interactions as $interaction) {
    // Convert the 'published' date to a DateTime object for comparison
    $publishedDate = new DateTime($interaction['published']);

    // Update the newest interaction if this one is newer
    if ($newestPublished === null || $publishedDate > $newestPublished) {
        $newestInteraction = $interaction;
        $newestPublished = $publishedDate;
    }
}

// Check if a newest interaction was found
if ($newestInteraction !== null) {
    // Convert the newest interaction to a readable format (You can customize this format as needed)
    $newestInteractionText = json_encode($newestInteraction, JSON_PRETTY_PRINT);

    // Write the newest interaction to the output file
    file_put_contents($outputFilePath, $newestInteractionText);

    echo "Newest interaction written to $outputFilePath\n";
} else {
    echo "No interactions found.\n";
}
?>
