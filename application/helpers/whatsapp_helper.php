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
            $user = $CI->db->select('country_code, phone_number, mobile_number')
                           ->get_where('user', array('email' => $phone_number))
                           ->row_array();

            if (!empty($user)) {
                $user_phone = !empty($user['mobile_number']) ? $user['mobile_number'] : (!empty($user['phone_number']) ? $user['phone_number'] : '');
                $country_code = !empty($user['country_code']) ? $user['country_code'] : '';
                $phone_number = $CI->whatsapp->format_phone_number($user_phone, $country_code);
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

if (!function_exists('send_whatsapp_to_emergency')) {
    /**
     * Send WhatsApp notification to user's emergency contact number
     */
    function send_whatsapp_to_emergency($user_id_or_email, $message, $template_name = '', $parameters = array(), $language_code = 'en') {
        $CI =& get_instance();
        if (!isset($CI->whatsapp)) {
            $CI->load->library('whatsapp');
        }

        $where = is_numeric($user_id_or_email) ? array('user_id' => $user_id_or_email) : array('email' => $user_id_or_email);
        $user = $CI->db->select('emergency_country_code, emergency_number')
                       ->get_where('user', $where)
                       ->row_array();

        if (empty($user) || empty($user['emergency_number'])) {
            return array('success' => FALSE, 'message' => 'Emergency contact number not found for user.');
        }

        $country_code = !empty($user['emergency_country_code']) ? $user['emergency_country_code'] : '';
        $phone_number = $CI->whatsapp->format_phone_number($user['emergency_number'], $country_code);

        if (!empty($template_name)) {
            return $CI->whatsapp->send_template_message($phone_number, $template_name, $language_code, $parameters);
        } else {
            $clean_message = trim(strip_tags($message));
            return $CI->whatsapp->send_text_message($phone_number, $clean_message);
        }
    }
}

if (!function_exists('send_whatsapp_to_kin')) {
    /**
     * Send WhatsApp notification to user's next of kin contact number
     */
    function send_whatsapp_to_kin($user_id_or_email, $message, $template_name = '', $parameters = array(), $language_code = 'en') {
        $CI =& get_instance();
        if (!isset($CI->whatsapp)) {
            $CI->load->library('whatsapp');
        }

        $where = is_numeric($user_id_or_email) ? array('user_id' => $user_id_or_email) : array('email' => $user_id_or_email);
        $user = $CI->db->select('kin_country_code, kin_number')
                       ->get_where('user', $where)
                       ->row_array();

        if (empty($user) || empty($user['kin_number'])) {
            return array('success' => FALSE, 'message' => 'Next of kin contact number not found for user.');
        }

        $country_code = !empty($user['kin_country_code']) ? $user['kin_country_code'] : '';
        $phone_number = $CI->whatsapp->format_phone_number($user['kin_number'], $country_code);

        if (!empty($template_name)) {
            return $CI->whatsapp->send_template_message($phone_number, $template_name, $language_code, $parameters);
        } else {
            $clean_message = trim(strip_tags($message));
            return $CI->whatsapp->send_text_message($phone_number, $clean_message);
        }
    }
}
