<?php
/**
 * Plugin Name: Elementor WhatsApp Form Sender
 * Description: Sends Elementor form submissions to WhatsApp via CallMeBot API.
 * Version: 1.9
 * Author: Sajad
 */

if (!defined('ABSPATH')) exit; // Prevent direct access

// Admin WhatsApp number and API key from CallMeBot
define('WHATSAPP_NUMBER', 'phonrnumber'); // Number with country code (without +)
define('WHATSAPP_API_KEY', 'apikey'); // Replace with your API key

// Field translations from Elementor form fields to Persian
$field_translations = [
    'name' => 'نام و نام خانوادگی',
    'adress' => 'آدرس',
    'file' => 'پیوست فایل',
    'description' => 'شرح',
    'phone' => 'شماره تلفن',
    'request' => 'نوع درخواست',
    'Reagent' => 'معرف',
    'consultation' => 'مشاوره در خصوص',
];

add_action('elementor_pro/forms/new_record', function ($record, $handler) use ($field_translations) {
    if (!class_exists('\ElementorPro\Modules\Forms\Classes\Ajax_Handler')) {
        return;
    }

    // Retrieve form data
    $form_data = $record->get('fields');
    $form_settings = $record->get('form_settings');
    $form_name = !empty($form_settings['form_name']) ? esc_html($form_settings['form_name']) : 'Unnamed Form';
    
    // Retrieve form fields from settings if available
    $form_fields = $form_settings['form_fields'] ?? [];

    // Check if form data exists
    if (empty($form_data) || !is_array($form_data)) {
        error_log("⚠️ Empty form submission received.");
        return;
    }

    // WhatsApp message content
    $message = "📝 *فرم جدید آمادس برای شما مهندس فریدی عزیز*\n";
    $message .= "📌 *نام فرم:* " . $form_name . "\n\n";
    $message .= "🔹 *اطلاعات فرم:*\n";

    foreach ($form_data as $field_id => $field_data) {
        if (!isset($field_data['value']) || empty($field_data['value'])) {
            continue; // Skip empty fields
        }

        // Get the translated field name or fallback to Elementor label
        $label = $field_translations[$field_id] ?? ($field_data['label'] ?? ($form_fields[$field_id]['label'] ?? esc_html($field_id)));
        $value = esc_html($field_data['value']);

        // Check if value is a URL, send it without escape
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $message .= "▪️ *$label:* \n$value\n";
        } else {
            $message .= "▪️ *$label:* $value\n";
        }
    }

    // Ensure the message contains form details
    if (strpos($message, "🔹 *Form Details:*") === strlen($message) - strlen("🔹 *Form Details:*\n")) {
        error_log("⚠️ No valid data in the form, message not sent to WhatsApp.");
        return;
    }

    // Encode message for WhatsApp
    $message = urlencode($message);
    $whatsapp_url = "https://api.callmebot.com/whatsapp.php?phone=" . WHATSAPP_NUMBER . "&text=" . $message . "&apikey=" . WHATSAPP_API_KEY;

    // Send message via HTTP request
    $response = wp_remote_get($whatsapp_url);

    // Log any errors
    if (is_wp_error($response)) {
        error_log("❌ WhatsApp message sending error: " . $response->get_error_message());
    } else {
        error_log("✅ WhatsApp message sent successfully.");
    }
}, 10, 2);
