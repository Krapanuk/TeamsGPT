<?php
require_once 'api-credentials.php';

// Function to send a message to ChatGPT and get a response
function chatGptResponse($messageText) {
    global $api_key;
    $curl = curl_init('https://api.openai.com/v1/chat/completions');

    // Adjusting the payload to match the structure used in your JS example
    $postData = json_encode([
        'model' => 'gpt-4-turbo-preview',
        'messages' => [
            ["role" => "system", "content" => "You are a helpful assistant in the Chat of a professional agile community of friends called Coaching:OWL. The OWL is for Ostwestfalen Lippe. As agile coaches with years of experience in people and team coaching we need your assistance in all topics of coaching, mostyl but not only in agile, and ai. "],
            ["role" => "user", "content" => $messageText]
        ],
        'temperature' => 1.0,
    ]);

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
        ],
    ]);

    $response = curl_exec($curl);
    curl_close($curl);
    $decodedResponse = json_decode($response, true);

    // Extracting the text from the ChatGPT response
    return $decodedResponse['choices'][0]['message']['content'] ?? 'Sorry, I could not process that.';
}

// Function to post a message back to Slack
function postToSlack($channelId, $messageText) {
    global $slackToken;
    $curl = curl_init('https://slack.com/api/chat.postMessage');
    $postData = http_build_query([
        'channel' => $channelId,
        'text' => $messageText,
    ]);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $slackToken,
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
}

// Main logic to handle Slack events
$content = file_get_contents("php://input");
$event = json_decode($content, true);

if ($event['type'] === 'url_verification') {
    echo $event['challenge'];
    exit;
} elseif ($event['type'] === 'event_callback') {
    // Check for the specific channel ID before proceeding
    if ($event['event']['channel'] !== 'C06S7B44W0N') {
        // If the event is not from the specific channel, ignore it
        exit;
    }

    // Ignore messages from bots, including your own bot's messages
    if (isset($event['event']['subtype']) && $event['event']['subtype'] === 'bot_message' || isset($event['event']['bot_id'])) {
        return; // Ignore bot messages entirely
    }
    
    $channelId = $event['event']['channel'];
    $userMessage = $event['event']['text'];
    
    // Avoid responding to messages not containing text or other bot messages
    if (empty($userMessage) || isset($event['event']['bot_id'])) {
        return;
    }
    
    // Get response from ChatGPT
    $chatGptResponse = chatGptResponse($userMessage);
    
    // Post response back to Slack
    postToSlack($channelId, $chatGptResponse);
}

?>