<?php
/*
Plugin Name: FAQ Chatbot
Description: A chatbot widget that answers questions based on a FAQ PDF.
Version: 1.1
Author: Biniyam K
*/
require 'vendor/autoload.php'; // Make sure to include the Composer autoload file

use Smalot\PdfParser\Parser;
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
//require_once plugin_dir_path(__FILE__) . 'admin-settings.php';
//require_once plugin_dir_path(__FILE__) . 'chatbot-interface.php';
//require_once plugin_dir_path(__FILE__) . 'pdf-parser.php';
// Enqueue necessary scripts and styles
function faq_chatbot_enqueue_scripts() {
    wp_enqueue_style('faq-chatbot-style', plugins_url('style.css', __FILE__));
    wp_enqueue_script('faq-chatbot-script', plugins_url('chatbot.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('faq_chatbot-script', 'faq_chatbot', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
}
add_action('wp_enqueue_scripts', 'faq_chatbot_enqueue_scripts');

// Create admin menu for PDF upload
function faq_chatbot_admin_menu() {
    add_menu_page('FAQ Chatbot', 'FAQ Chatbot', 'manage_options', 'faq-chatbot', 'faq_chatbot_admin_page', 'dashicons-format-chat', 100);
}
add_action('admin_menu', 'faq_chatbot_admin_menu');

// Admin page content
function faq_chatbot_admin_page() {
    ?>
    <div class="wrap">
        <h1>FAQ Chatbot Settings</h1>
        <form method="post" enctype="multipart/form-data" action="">
            <?php wp_nonce_field('faq_chatbot_settings_save', 'faq_chatbot_nonce'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Upload FAQ PDF</th>
                    <td><input type="file" name="faq_pdf" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Chat Widget Appearance</th>
                    <td>
                        <label for="chat_widget_color">Color:</label>
                        <input type="text" id="chat_widget_color" name="chat_widget_color" value="<?php echo get_option('chat_widget_color'); ?>" />
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Changes'); ?>
        </form>
        <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']) : ?>
            <div id="message" class="updated notice is-dismissible"><p>Settings saved successfully.</p></div>
        <?php endif; ?>
    </div>
    <?php
}

// Handle file upload and save settings
function faq_chatbot_save_settings() {
    if (!isset($_POST['faq_chatbot_nonce']) || !wp_verify_nonce($_POST['faq_chatbot_nonce'], 'faq_chatbot_settings_save')) {
        return;
    }

    if (isset($_FILES['faq_pdf']) && !empty($_FILES['faq_pdf']['tmp_name'])) {
        $uploaded = media_handle_upload('faq_pdf', 0);
        if (is_wp_error($uploaded)) {
            echo "Error uploading PDF: " . $uploaded->get_error_message();
        } else {
            update_option('faq_pdf_attachment_id', $uploaded);
        }
    }

    if (isset($_POST['chat_widget_color'])) {
        update_option('chat_widget_color', sanitize_text_field($_POST['chat_widget_color']));
    }

    wp_redirect(admin_url('admin.php?page=faq-chatbot&settings-updated=true'));
    exit;
}
add_action('admin_post_faq_chatbot_save', 'faq_chatbot_save_settings');

// Register settings
function faq_chatbot_register_settings() {
    register_setting('faq_chatbot_settings', 'chat_widget_color');
}
add_action('admin_init', 'faq_chatbot_register_settings');

// Handle AJAX Request
add_action('wp_ajax_faq_chatbot_ask', 'faq_chatbot_ask');
add_action('wp_ajax_nopriv_faq_chatbot_ask', 'faq_chatbot_ask');

function faq_chatbot_ask() {
    if (!isset($_POST['message'])) {
        wp_send_json_error('No message provided');
        return;
    }

    $message = sanitize_text_field($_POST['message']);
    $pdf_id = get_option('faq_pdf_attachment_id');
    $pdf_path = get_attached_file($pdf_id);

    // TODO: Add logic to process the message using the GPT model and the parsed PDF content
    // Example: Use an external API or a custom function to get the response
    $response = faq_chatbot_get_response($message, $pdf_path);

    wp_send_json_success($response);
}

function faq_chatbot_get_response($message, $pdf_path) {
    // Load and parse the PDF content
    $parser = new Parser();
    $pdf = $parser->parseFile($pdf_path);
    $text = $pdf->getText();

    // Use GPT to get the response
    // Replace this with actual GPT integration
    $response = generate_gpt_response($message, $text);

    return $response;
}

function generate_gpt_response($message, $text) {
    $api_url = 'https://api.replicate.com/v1/predictions';
    $api_key = 'r8_NcU5OtzvwpIOmyQzMp8HT9jxnVyUmQT1WpvQR';  // Replace with your actual API key

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

// Shortcode to display the chatbot
function faq_chatbot_shortcode() {
    ob_start();
    ?>
    <div id="faq-chatbot">
        <div id="faq-chatbot-header">Chat with us</div>
        <div id="faq-chatbot-messages"></div>
        <div id="faq-chatbot-input">
            <input type="text" placeholder="Type a message..." />
            <button>Send</button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('faq_chatbot', 'faq_chatbot_shortcode');
?>
