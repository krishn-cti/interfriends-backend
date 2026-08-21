<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Whatsapp
{
    /**
     * CodeIgniter Super Object
     *
     * @var CI_Controller
     */
    protected $CI;

    private $access_token;
    private $phone_number_id;
    private $api_version;
    private $enabled;

    /**
     * Constructor
     */
    public function __construct()
    {
        /**
         * @var config for WhatsApp Cloud API
         */
        $this->CI = &get_instance();

        $this->CI->config->load('whatsapp', TRUE);

        $this->access_token = $this->CI->config->item(
            'whatsapp_access_token',
            'whatsapp'
        );

        $this->phone_number_id = $this->CI->config->item(
            'whatsapp_phone_number_id',
            'whatsapp'
        );

        $this->api_version = $this->CI->config->item(
            'whatsapp_api_version',
            'whatsapp'
        );

        $this->enabled = $this->CI->config->item(
            'whatsapp_enabled',
            'whatsapp'
        );

        if (empty($this->api_version)) {
            $this->api_version = 'v20.0';
        }
    }

    /**
     * Format Phone Number
     *
     * Converts phone number into WhatsApp Cloud API format.
     *
     * Example:
     * 07123456789 + 44
     * becomes:
     * 447123456789
     *
     * @param string $phone
     * @param string $country_code
     * @return string
     */
    public function format_phone_number($phone, $country_code = '')
    {
        /*
        |--------------------------------------------------------------------------
        | Remove Non-Numeric Characters
        |--------------------------------------------------------------------------
        */

        $clean_phone = preg_replace(
            '/[^0-9]/',
            '',
            (string) $phone
        );

        $clean_country = preg_replace(
            '/[^0-9]/',
            '',
            (string) $country_code
        );

        /*
        |--------------------------------------------------------------------------
        | Validate Phone
        |--------------------------------------------------------------------------
        */

        if (empty($clean_phone)) {
            return '';
        }

        /*
        |--------------------------------------------------------------------------
        | Add Country Code
        |--------------------------------------------------------------------------
        */

        if (!empty($clean_country)) {

            // Remove leading zero
            $clean_phone = ltrim($clean_phone, '0');

            // Add country code only if it isn't already present
            if (strpos($clean_phone, $clean_country) !== 0) {
                $clean_phone = $clean_country . $clean_phone;
            }
        }

        return $clean_phone;
    }


    /**
     * Send WhatsApp Template Message
     *
     * Supports any number of template parameters.
     *
     * Example:
     *
     * send_template_message(
     *     '447123456789',
     *     'outstanding_payment_reminder',
     *     'en_GB',
     *     array(
     *         'John Smith',
     *         'Saving',
     *         '100.00',
     *         'August 2026'
     *     )
     * );
     *
     * @param string $to_phone_number
     * @param string $template_name
     * @param string $language_code
     * @param array $parameters
     * @return array
     */
    public function send_template_message(
        $to_phone_number,
        $template_name,
        $language_code = 'en_GB',
        $parameters = array()
    ) {
        /*
        |--------------------------------------------------------------------------
        | WhatsApp Disabled
        |--------------------------------------------------------------------------
        */

        if ($this->enabled === FALSE) {
            return array(
                'success' => FALSE,
                'skipped' => TRUE,
                'message' => 'WhatsApp messaging is disabled in config.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Configuration
        |--------------------------------------------------------------------------
        */

        if (
            empty($this->access_token) ||
            empty($this->phone_number_id)
        ) {
            return array(
                'success' => FALSE,
                'message' => 'WhatsApp access token or phone number ID is missing.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Template
        |--------------------------------------------------------------------------
        */

        if (empty($template_name)) {
            return array(
                'success' => FALSE,
                'message' => 'WhatsApp template name is required.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Format Recipient Phone
        |--------------------------------------------------------------------------
        */

        $formatted_to = $this->format_phone_number(
            $to_phone_number
        );

        /*
        |--------------------------------------------------------------------------
        | Validate Recipient
        |--------------------------------------------------------------------------
        */

        if (empty($formatted_to)) {
            return array(
                'success' => FALSE,
                'skipped' => TRUE,
                'message' => 'Invalid or empty WhatsApp phone number.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Meta WhatsApp API URL
        |--------------------------------------------------------------------------
        */

        $url =
            'https://graph.facebook.com/' .
            $this->api_version .
            '/' .
            $this->phone_number_id .
            '/messages';

        /*
        |--------------------------------------------------------------------------
        | Prepare Template Parameters
        |--------------------------------------------------------------------------
        */

        $body_params = array();

        if (!empty($parameters)) {

            // Allow single parameter as well
            if (!is_array($parameters)) {
                $parameters = array($parameters);
            }

            foreach ($parameters as $param) {

                $body_params[] = array(
                    'type' => 'text',
                    'text' => (string) $param
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Build WhatsApp Template Payload
        |--------------------------------------------------------------------------
        */

        $payload = array(
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $formatted_to,
            'type' => 'template',
            'template' => array(
                'name' => $template_name,
                'language' => array(
                    'code' => $language_code
                )
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Add Body Parameters
        |--------------------------------------------------------------------------
        */

        if (!empty($body_params)) {

            $payload['template']['components'] = array(
                array(
                    'type' => 'body',
                    'parameters' => $body_params
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Send Request
        |--------------------------------------------------------------------------
        */

        return $this->execute_curl(
            $url,
            $payload
        );
    }


    /**
     * Send Standard Text Message
     *
     * Note:
     * Normal text messages are subject to WhatsApp
     * conversation/messaging window rules.
     *
     * @param string $to_phone_number
     * @param string $message_text
     * @return array
     */
    public function send_text_message(
        $to_phone_number,
        $message_text
    ) {
        /*
        |--------------------------------------------------------------------------
        | WhatsApp Disabled
        |--------------------------------------------------------------------------
        */

        if ($this->enabled === FALSE) {
            return array(
                'success' => FALSE,
                'skipped' => TRUE,
                'message' => 'WhatsApp messaging is disabled in config.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Configuration
        |--------------------------------------------------------------------------
        */

        if (
            empty($this->access_token) ||
            empty($this->phone_number_id)
        ) {
            return array(
                'success' => FALSE,
                'message' => 'WhatsApp access token or phone number ID is missing.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Message
        |--------------------------------------------------------------------------
        */

        if (empty(trim($message_text))) {
            return array(
                'success' => FALSE,
                'skipped' => TRUE,
                'message' => 'WhatsApp message is empty.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Format Phone Number
        |--------------------------------------------------------------------------
        */

        $formatted_to = $this->format_phone_number(
            $to_phone_number
        );

        /*
        |--------------------------------------------------------------------------
        | Validate Phone
        |--------------------------------------------------------------------------
        */

        if (empty($formatted_to)) {
            return array(
                'success' => FALSE,
                'skipped' => TRUE,
                'message' => 'Invalid or empty WhatsApp phone number.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Meta WhatsApp API URL
        |--------------------------------------------------------------------------
        */

        $url =
            'https://graph.facebook.com/' .
            $this->api_version .
            '/' .
            $this->phone_number_id .
            '/messages';

        /*
        |--------------------------------------------------------------------------
        | Build Payload
        |--------------------------------------------------------------------------
        */

        $payload = array(
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $formatted_to,
            'type' => 'text',
            'text' => array(
                'preview_url' => FALSE,
                'body' => (string) $message_text
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Send Request
        |--------------------------------------------------------------------------
        */

        return $this->execute_curl(
            $url,
            $payload
        );
    }


    /**
     * Execute cURL Request
     *
     * @param string $url
     * @param array $payload
     * @return array
     */
    private function execute_curl(
        $url,
        $payload
    ) {
        /*
        |--------------------------------------------------------------------------
        | Convert Payload To JSON
        |--------------------------------------------------------------------------
        */

        $json_payload = json_encode($payload);

        if ($json_payload === FALSE) {

            return array(
                'success' => FALSE,
                'message' => 'Unable to encode WhatsApp request.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Initialize cURL
        |--------------------------------------------------------------------------
        */

        if (!function_exists('curl_init')) {

            log_message(
                'error',
                'WhatsApp API Error: PHP cURL extension is not enabled.'
            );

            return array(
                'success' => FALSE,
                'message' => 'PHP cURL extension is not enabled.'
            );
        }

        $ch = curl_init($url);

        if ($ch === FALSE) {

            return array(
                'success' => FALSE,
                'message' => 'Unable to initialize WhatsApp cURL request.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | cURL Options
        |--------------------------------------------------------------------------
        */

        curl_setopt(
            $ch,
            CURLOPT_RETURNTRANSFER,
            TRUE
        );

        curl_setopt(
            $ch,
            CURLOPT_POST,
            TRUE
        );

        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            $json_payload
        );

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Authorization: Bearer ' . $this->access_token,
                'Content-Type: application/json'
            )
        );

        curl_setopt(
            $ch,
            CURLOPT_TIMEOUT,
            30
        );

        /*
        |--------------------------------------------------------------------------
        | Execute Request
        |--------------------------------------------------------------------------
        */

        $response = curl_exec($ch);

        /*
        |--------------------------------------------------------------------------
        | Get HTTP Status
        |--------------------------------------------------------------------------
        */

        $http_code = curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

        /*
        |--------------------------------------------------------------------------
        | Get cURL Error
        |--------------------------------------------------------------------------
        */

        $error = curl_error($ch);

        /*
        |--------------------------------------------------------------------------
        | Close cURL
        |--------------------------------------------------------------------------
        */

        curl_close($ch);

        /*
        |--------------------------------------------------------------------------
        | Handle cURL Error
        |--------------------------------------------------------------------------
        */

        if (!empty($error)) {

            log_message(
                'error',
                'WhatsApp API cURL Error: ' . $error
            );

            return array(
                'success' => FALSE,
                'message' => 'WhatsApp cURL Error: ' . $error
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Decode Response
        |--------------------------------------------------------------------------
        */

        $result = json_decode(
            $response,
            TRUE
        );

        /*
        |--------------------------------------------------------------------------
        | Successful Response
        |--------------------------------------------------------------------------
        */

        if (
            $http_code >= 200 &&
            $http_code < 300
        ) {

            return array(
                'success' => TRUE,
                'message' => 'WhatsApp message sent successfully.',
                'data' => $result
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Meta API Error
        |--------------------------------------------------------------------------
        */

        $err_msg = 'Failed to send WhatsApp message.';

        if (
            isset($result['error']['message']) &&
            !empty($result['error']['message'])
        ) {
            $err_msg = $result['error']['message'];
        }

        /*
        |--------------------------------------------------------------------------
        | Log Error
        |--------------------------------------------------------------------------
        */

        log_message(
            'error',
            'WhatsApp API Error: ' .
                $err_msg .
                ' HTTP Code: ' .
                $http_code .
                ' Response: ' .
                $response
        );

        /*
        |--------------------------------------------------------------------------
        | Return Error
        |--------------------------------------------------------------------------
        */

        return array(
            'success' => FALSE,
            'message' => $err_msg,
            'http_code' => $http_code,
            'request' => array(
                'to' => isset($payload['to']) ? $payload['to'] : '',
                'type' => isset($payload['type']) ? $payload['type'] : '',
                'template' => isset($payload['template']) ? $payload['template'] : array()
            ),
            'data' => $result
        );
    }
}
