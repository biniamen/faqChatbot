<?php
// Register the admin menu
add_action('admin_menu', 'faq_chatbot_admin_menu');

function faq_chatbot_admin_menu() {
    add_menu_page('FAQ Chatbot Settings', 'FAQ Chatbot', 'manage_options', 'faq-chatbot', 'faq_chatbot_settings_page');
}

// Settings page content
function faq_chatbot_settings_page() {
    ?>
    <div class="wrap">
        <h1>FAQ Chatbot Settings</h1>
        <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('faq_chatbot_settings_save', 'faq_chatbot_nonce'); ?>
            <input type="hidden" name="action" value="faq_chatbot_save">

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Upload FAQ PDF</th>
                    <td>
                        <input type="file" name="faq_pdf" />
                        <?php
                        $pdf_id = get_option('faq_pdf_attachment_id');
                        if ($pdf_id) {
                            $pdf_url = wp_get_attachment_url($pdf_id);
                            echo "<p>Current PDF: <a href='$pdf_url' target='_blank'>$pdf_url</a></p>";
                        }
                        ?>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Chat Widget Appearance</th>
                    <td>
                        <input type="text" name="chat_widget_color" value="<?php echo esc_attr(get_option('chat_widget_color', '')); ?>" />
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
        <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') : ?>
            <div id="message" class="updated notice is-dismissible">
                <p>Settings saved successfully.</p>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// Handle settings save
function faq_chatbot_save_settings() {
    if (!isset($_POST['faq_chatbot_nonce']) || !wp_verify_nonce($_POST['faq_chatbot_nonce'], 'faq_chatbot_settings_save')) {
        return;
    }

    if (isset($_FILES['faq_pdf']) && !empty($_FILES['faq_pdf']['tmp_name'])) {
        $uploaded = media_handle_upload('faq_pdf', 0);
        if (is_wp_error($uploaded)) {
            add_settings_error('faq_chatbot', 'faq_pdf_error', 'Error uploading PDF: ' . $uploaded->get_error_message(), 'error');
        } else {
            update_option('faq_pdf_attachment_id', $uploaded);
            add_settings_error('faq_chatbot', 'faq_pdf_success', 'PDF uploaded successfully', 'updated');
        }
    }

    if (isset($_POST['chat_widget_color'])) {
        update_option('chat_widget_color', sanitize_text_field($_POST['chat_widget_color']));
    }

    wp_redirect(admin_url('admin.php?page=faq-chatbot&settings-updated=true'));
    exit;
}
add_action('admin_post_faq_chatbot_save', 'faq_chatbot_save_settings');
?>
