<?php
use Smalot\PdfParser\Parser;

function faq_chatbot_get_response($message, $pdf_path) {
    require 'vendor/autoload.php';
    $parser = new Parser();
    $pdf = $parser->parseFile($pdf_path);
    $text = $pdf->getText();

    $response = generate_gpt_response($message, $text);

    return $response;
}

function generate_gpt_response($message, $text) {
    $api_url = 'https://api.replicate.com/v1/predictions';
    $api_key = 'your_replicate_api_key';  // Replace with your actual API key

    $data = [
        'version' => 'mistralai/mistral-7b-v0.1',  // Ensure this matches the model version on Replicate
        'input' => [
            'prompt' => "The following is a FAQ document:\n\n$text\n\nUser question: $message",
            'max_length' => 100,
        ],
    ];

    $response = wp_remote_post($api_url, [
        'body' => json_encode($data),
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Token ' . $api_key,
        ],
    ]);

    if (is_wp_error($response)) {
        return "Error communicating with GPT API.";
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);

    if (isset($result['error'])) {
        return "Error: " . $result['error'];
    }

    return $result['choices'][0]['text'] ?? "Sorry, I couldn't find an answer to your question.";
}
?>
