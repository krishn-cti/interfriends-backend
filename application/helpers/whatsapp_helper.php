<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('send_whatsapp_notification')) {
    /**
     * Reusable Helper function to send WhatsApp Notification
     * Accepts either phone number OR email address as recipient.
     * 
     * @param string $recipient     Phone number (with country code) or User Email Address
     * @param string $message       Plain text message OR email body HTML
     * @param string $template_name Optional Meta template name
     * @param array  $parameters    Optional template parameters array
     * @param string $language_code Optional template language code (default 'en')
     * @return array Status array with 'success', 'message', and optional 'data'
     */
    function send_whatsapp_notification($recipient, $message, $template_name = '', $parameters = array(), $language_code = 'en') {
        $CI =& get_instance();

        // Ensure whatsapp library is loaded
        if (!isset($CI->whatsapp)) {
            $CI->load->library('whatsapp');
        }

        $phone_number = trim($recipient);

        // If recipient is an email address, look up user phone details from database
        if (filter_var($phone_number, FILTER_VALIDATE_EMAIL)) {
            $user = $CI->db->select('country_code, phone_number')
                           ->get_where('user', array('email' => $phone_number))
                           ->row_array();

            if (!empty($user) && !empty($user['phone_number'])) {
                $country_code = isset($user['country_code']) ? $user['country_code'] : '';
                $phone_number = $CI->whatsapp->format_phone_number($user['phone_number'], $country_code);
            } else {
                return array('success' => FALSE, 'message' => 'User phone number not found in database for email: ' . $recipient);
            }
        }

        // Dispatch Meta Template message if template name is provided
        if (!empty($template_name)) {
            return $CI->whatsapp->send_template_message($phone_number, $template_name, $language_code, $parameters);
        } else {
            // Strip HTML tags for standard text message
            $clean_message = trim(strip_tags($message));
            return $CI->whatsapp->send_text_message($phone_number, $clean_message);
        }
    }
}
