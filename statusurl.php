<?php
// Function to make the API request and search for the post using the API key
function searchPostWithAPIKey($apiKey, $pbUrl, $searchUrl) {
    // Prepare the search URL by URL-encoding the input URL
    $encodedSearchUrl = urlencode($searchUrl);

    // Build the full API request URL
    $apiUrl = $pbUrl . "/api/v2/search/?q=" . $encodedSearchUrl . "&limit=1&resolve=true";

    // Initialize cURL
    $ch = curl_init();

    // Set the cURL options
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $apiKey
    ]);

    // Execute the cURL request and get the response
    $response = curl_exec($ch);

    // Check for errors
    if(curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
        exit;
    }

    // Close the cURL session
    curl_close($ch);

    // Decode the JSON response
    $responseData = json_decode($response, true);

    // Check if post exists and return the ID if found
    if (isset($responseData['statuses']) && count($responseData['statuses']) > 0) {
        $postId = $responseData['statuses'][0]['id'];
        echo $postId;
    } else {
        echo "No post found or no matching post for the given URL.";
    }
}

// Get the `url` parameter from the query string
if (isset($_GET['url']) && !empty($_GET['url'])) {
    $searchUrl = $_GET['url'];  // Get the URL from the query string
} else {
    echo "Error: No URL provided in query string.";
    exit;
}

// Your Mastodon instance URL and API Key
$apiKey = "zV0asC4bcomUuS8pIXHNDJZ4R8";  // Replace with your actual API key
$pbUrl = "https://mas.to";  // Replace with your Mastodon instance URL

// Call the search function with the provided URL
searchPostWithAPIKey($apiKey, $pbUrl, $searchUrl);
?>
