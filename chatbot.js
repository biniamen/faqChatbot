// FAQ Chatbot Script

jQuery(document).ready(function($) {
    var $chatbot = $('#faq-chatbot');
    var $messages = $('#faq-chatbot-messages');
    var $input = $('#faq-chatbot-input input');

    function appendMessage(content, className) {
        var $message = $('<div>').addClass(className).text(content);
        $messages.append($message);
        $messages.scrollTop($messages.prop('scrollHeight'));
    }

    $('#faq-chatbot-input button').on('click', function() {
        var message = $input.val();
        if (message.trim() === '') return;

        appendMessage(message, 'user-message');
        $input.val('');

        // Send message to server for processing
        $.ajax({
            url: faq_chatbot.ajax_url,
            method: 'POST',
            data: {
                action: 'faq_chatbot_ask',
                message: message
            },
            success: function(response) {
                if (response.success) {
                    appendMessage(response.data, 'bot-message');
                } else {
                    appendMessage('An error occurred.', 'bot-message');
                }
            },
            error: function() {
                appendMessage('An error occurred.', 'bot-message');
            }
        });
    });
});
