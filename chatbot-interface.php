<?php
add_shortcode('faq_chatbot', 'faq_chatbot_shortcode');

function faq_chatbot_shortcode() {
    ?>
    <div id="faq-chatbot">
        <div id="faq-chatbot-window">
            <div id="faq-chatbot-messages"></div>
            <input type="text" id="faq-chatbot-input" placeholder="Type your question...">
            <button id="faq-chatbot-send">Send</button>
        </div>
    </div>
    <style>
        #faq-chatbot { /* Add styles here */ }
    </style>
    <script>
        document.getElementById('faq-chatbot-send').addEventListener('click', function() {
            var message = document.getElementById('faq-chatbot-input').value;
            var pdfUrl = "<?php echo wp_get_attachment_url(get_option('faq_pdf_attachment_id')); ?>";

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: 'action=faq_chatbot_respond&message=' + encodeURIComponent(message) + '&pdf_url=' + encodeURIComponent(pdfUrl)
            })
            .then(response => response.json())
            .then(data => {
                var messagesDiv = document.getElementById('faq-chatbot-messages');
                messagesDiv.innerHTML += '<div class="message user">' + message + '</div>';
                messagesDiv.innerHTML += '<div class="message bot">' + data.response + '</div>';
                document.getElementById('faq-chatbot-input').value = '';
            })
            .catch(error => console.error('Error:', error));
        });
    </script>
    <?php
}

add_action('wp_ajax_faq_chatbot_respond', 'faq_chatbot_handle_ajax');
add_action('wp_ajax_nopriv_faq_chatbot_respond', 'faq_chatbot_handle_ajax');

function faq_chatbot_handle_ajax() {
    if (isset($_POST['message']) && isset($_POST['pdf_url'])) {
        $message = sanitize_text_field($_POST['message']);
        $pdf_url = esc_url_raw($_POST['pdf_url']);

        $response = faq_chatbot_get_response($message, $pdf_url);

        echo json_encode(['response' => $response]);
    } else {
        echo json_encode(['response' => 'Invalid request.']);
    }

    wp_die();
}
?>
