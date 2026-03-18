<?php
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$userMessage = $data["message"] ?? "";

$apiKey = "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"; 

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://openrouter.ai/api/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $apiKey"
]);

$postData = [
    "model" => "openai/gpt-3.5-turbo",
    "messages" => [
        ["role" => "system", "content" => "You are a helpful assistant for a rental fashion website."],
        ["role" => "user", "content" => $userMessage]
    ]
];

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
$reply = $result["choices"][0]["message"]["content"] ?? "Sorry, something went wrong.";

echo json_encode(["reply" => $reply]);