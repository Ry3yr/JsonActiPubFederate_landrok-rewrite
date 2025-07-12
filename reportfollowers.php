<?php
$target_username = 'alceawis@alceawis.com';
$access_token = 'YOUR_ACCESS_TOKEN';
function getFollowersFromUrl($url) {
    $response = file_get_contents($url);
    if ($response === false) {
        echo 'Error fetching followers data from ' . $url;
        exit;
    }
    $followers_data = json_decode($response, true);
    return $followers_data['orderedItems'] ?? [];
}
function getUserId($instance_url, $username, $access_token) {
    $user_url = $instance_url . '/api/v1/accounts/lookup?acct=' . urlencode($username);
    $user_headers = ['Authorization: Bearer ' . $access_token];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $user_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $user_headers);
    $user_response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
        exit;
    }
    curl_close($ch);
    $user_data = json_decode($user_response, true);
    return $user_data['id'] ?? null;
}
function checkFollowings($instance_url, $user_id, $target_username, $access_token) {
    $followings_url = $instance_url . '/api/v1/accounts/' . $user_id . '/following';
    $user_headers = ['Authorization: Bearer ' . $access_token];
    $found_target = false;
    do {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $followings_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $user_headers);
        $followings_response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'cURL error: ' . curl_error($ch);
            exit;
        }
        curl_close($ch);
        $followings_data = json_decode($followings_response, true);
        if (!$followings_data) {
            echo "Error fetching followings data.";
            exit;
        }
        foreach ($followings_data as $following) {
            if ($following['acct'] === $target_username) {
                $found_target = true;
                break;
            }
        }
        $headers = get_headers($followings_url, 1);
        if (isset($headers['Link'])) {
            preg_match('/<([^>]+)>; rel="next"/', $headers['Link'], $matches);
            $followings_url = $matches[1] ?? null;
        } else {
            $followings_url = null;
        }
    } while ($followings_url);
    return $found_target;
}
echo "Followers:<br><br>";
$followers_url = 'https://alceawis.com/alceawis/followers';
$followers = getFollowersFromUrl($followers_url);
foreach ($followers as $follower_url) {
    preg_match('#https?://([a-zA-Z0-9.-]+)/users/([^/]+)#', $follower_url, $matches);
    if (count($matches) === 3) {
        $instance_url = 'https://' . $matches[1];
        $username = $matches[2];
        $domain = $matches[1];
        $user_id = getUserId($instance_url, $username, $access_token);
        if ($user_id) {
            $found_target = checkFollowings($instance_url, $user_id, $target_username, $access_token);
            $found_target_str = $found_target ? 'Yes' : 'No';
            echo "@$username@$domain: $found_target_str<br>\n";
        } else {
            echo "User @$username not found on instance $domain<br>\n";
        }
    }
}
?>
