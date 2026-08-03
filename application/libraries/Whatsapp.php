<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Whatsapp {

    protected $CI;
    private $access_token;
    private $phone_number_id;
    private $api_version;
    private $enabled;

    public function __construct() {
        // CodeIgniter Super-Object instance
        $this->CI =& get_instance();

        // Load whatsapp config file
        $this->CI->config->load('whatsapp', TRUE);

        // Load configurations
        $this->access_token    = $this->CI->config->item('whatsapp_access_token', 'whatsapp');
        $this->phone_number_id = $this->CI->config->item('whatsapp_phone_number_id', 'whatsapp');
        $this->api_version     = $this->CI->config->item('whatsapp_api_version', 'whatsapp');
        $this->enabled         = $this->CI->config->item('whatsapp_enabled', 'whatsapp');

        if (empty($this->api_version)) {
            $this->api_version = 'v20.0';
        }
    }

    /**
     * Format and sanitize phone numbers (removes non-numeric characters, handles country code prefixing)
     * 
     * @param string $phone
     * @param string $country_code
     * @return string
     */
    public function format_phone_number($phone, $country_code = '') {
        $clean_phone   = preg_replace('/[^0-9]/', '', (string)$phone);
        $clean_country = preg_replace('/[^0-9]/', '', (string)$country_code);

        if (!empty($clean_country)) {
            // Trim leading zero from mobile number if country code is supplied
            $clean_phone = ltrim($clean_phone, '0');
            // If phone doesn't already start with country code, prepend it
            if (strpos($clean_phone, $clean_country) !== 0) {
                $clean_phone = $clean_country . $clean_phone;
            }
        }

        return $clean_phone;
    }

    /**
     * Send Template Message via WhatsApp Cloud API (cURL)
     * 
     * @param string $to_phone_number Phone number with country code (e.g. 919876543210 or 447123456789)
     * @param string $template_name   Meta dashboard approved template name
     * @param string $language_code   Language code (default: 'en')
     * @param array  $parameters       Indexed array of parameters (e.g. array('John', 'ORD-123'))
     * @return array
     */
    public function send_template_message($to_phone_number, $template_name, $language_code = 'en', $parameters = array()) {
        if ($this->enabled === FALSE) {
            return array('success' => FALSE, 'message' => 'WhatsApp messaging is disabled in config.');
        }

        if (empty($this->access_token) || empty($this->phone_number_id)) {
            return array('success' => FALSE, 'message' => 'WhatsApp configuration variables (access_token / phone_number_id) are missing.');
        }

        $formatted_to = $this->format_phone_number($to_phone_number);
        if (empty($formatted_to)) {
            return array('success' => FALSE, 'message' => 'Invalid or empty phone number provided.');
        }

        $url = "https://graph.facebook.com/{$this->api_version}/{$this->phone_number_id}/messages";

        // Build body parameters array
        $body_params = array();
        if (is_array($parameters)) {
            foreach ($parameters as $param) {
                $body_params[] = array(
                    'type' => 'text',
                    'text' => (string)$param
                );
            }
        }

        // Payload structure
        $payload = array(
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $formatted_to,
            'type'              => 'template',
            'template'          => array(
                'name'     => $template_name,
                'language' => array(
                    'code' => $language_code
                )
            )
        );

        if (!empty($body_params)) {
            $payload['template']['components'] = array(
                array(
                    'type'       => 'body',
                    'parameters' => $body_params
                )
            );
        }

        return $this->execute_curl($url, $payload);
    }

    /**
     * Send Standard Text Message via WhatsApp Cloud API (cURL)
     * 
     * @param string $to_phone_number Phone number with country code
     * @param string $message_text    Plain text message body
     * @return array
     */
    public function send_text_message($to_phone_number, $message_text) {
        if ($this->enabled === FALSE) {
            return array('success' => FALSE, 'message' => 'WhatsApp messaging is disabled in config.');
        }

        if (empty($this->access_token) || empty($this->phone_number_id)) {
            return array('success' => FALSE, 'message' => 'WhatsApp configuration variables (access_token / phone_number_id) are missing.');
        }

        $formatted_to = $this->format_phone_number($to_phone_number);
        if (empty($formatted_to)) {
            return array('success' => FALSE, 'message' => 'Invalid or empty phone number provided.');
        }

        $url = "https://graph.facebook.com/{$this->api_version}/{$this->phone_number_id}/messages";

        $payload = array(
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $formatted_to,
            'type'              => 'text',
            'text'              => array(
                'preview_url' => false,
                'body'        => (string)$message_text
            )
        );

        return $this->execute_curl($url, $payload);
    }

    /**
     * Execute cURL request to Meta Cloud API
     * 
     * @param string $url
     * @param array $payload
     * @return array
     */
    private function execute_curl($url, $payload) {
        $json_payload = json_encode($payload);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer {$this->access_token}",
            "Content-Type: application/json"
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error     = curl_error($ch);
        curl_close($ch);

        if ($error) {
            log_message('error', 'WhatsApp API cURL Error: ' . $error);
            return array('success' => FALSE, 'message' => 'cURL Error: ' . $error);
        }

        $result = json_decode($response, true);

        if ($http_code === 200) {
            return array('success' => TRUE, 'data' => $result);
        } else {
            $err_msg = isset($result['error']['message']) ? $result['error']['message'] : 'Failed to send WhatsApp message';
            log_message('error', 'WhatsApp API Error: ' . $err_msg . ' Response: ' . $response);
            return array('success' => FALSE, 'message' => $err_msg, 'data' => $result);
        }
    }
}
