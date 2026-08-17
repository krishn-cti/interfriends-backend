<?php
defined('BASEPATH') or exit('No direct script access allowed');


/*
|--------------------------------------------------------------------------
| Get CodeIgniter Instance
|--------------------------------------------------------------------------
|
| This PHPDoc helps Intelephense understand the CodeIgniter 3
| super object and removes errors for:
|
| $CI->load
| $CI->db
| $CI->whatsapp
|
*/


/*
|--------------------------------------------------------------------------
| Send WhatsApp Notification
|--------------------------------------------------------------------------
|
| Accepts either:
| - Phone number
| - Email address
|
| If email is provided, user's phone number is fetched from database.
|
*/

if (!function_exists('send_whatsapp_notification')) {

    function send_whatsapp_notification(
        $recipient,
        $message,
        $template_name = '',
        $parameters = array(),
        $language_code = 'en_UK'
    ) {
        /**
         * @var load
         * @var db
         * @var whatsapp
         */
        $CI = &get_instance();

        if (!isset($CI->whatsapp)) {
            $CI->load->library('whatsapp');
        }

        $CI->config->load('whatsapp_templates', TRUE);
        $templates = $CI->config->item('whatsapp_templates', 'whatsapp_templates');

        if (!is_array($templates)) {
            $templates = array();
        }

        $template_config = !empty($templates[$template_name]) && is_array($templates[$template_name])
            ? $templates[$template_name]
            : array();

        if (!empty($template_config['name'])) {
            $template_name = $template_config['name'];
        }

        if (!empty($template_config['language'])) {
            $language_code = $template_config['language'];
        }

        $phone_number = trim($recipient);

        if (filter_var($phone_number, FILTER_VALIDATE_EMAIL)) {


            $db = $CI->db;


            $user = $db
                ->select(
                    'country_code, phone_number, mobile_number'
                )
                ->get_where(
                    'user',
                    array(
                        'email' => $phone_number
                    )
                )
                ->row_array();

            if (!empty($user)) {

                /*
                |--------------------------------------------------------------------------
                | Phone Number Priority
                |--------------------------------------------------------------------------
                |
                | 1. mobile_number
                | 2. phone_number
                |
                */

                $user_phone = !empty($user['mobile_number'])
                    ? $user['mobile_number']
                    : (
                        !empty($user['phone_number'])
                        ? $user['phone_number']
                        : ''
                    );

                if (empty($user_phone)) {

                    return array(
                        'success' => FALSE,
                        'skipped' => TRUE,
                        'message' => 'User WhatsApp phone number not found.'
                    );
                }

                $country_code = !empty($user['country_code'])
                    ? $user['country_code']
                    : '';

                $phone_number = $CI->whatsapp->format_phone_number(
                    $user_phone,
                    $country_code
                );
            } else {

                return array(
                    'success' => FALSE,
                    'skipped' => TRUE,
                    'message' => 'User not found for email: ' . $recipient
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Phone Number
        |--------------------------------------------------------------------------
        */

        if (empty($phone_number)) {

            return array(
                'success' => FALSE,
                'skipped' => TRUE,
                'message' => 'WhatsApp phone number is empty.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Send Template Message
        |--------------------------------------------------------------------------
        */

        if (!empty($template_name)) {

            return $CI->whatsapp->send_template_message(
                $phone_number,
                $template_name,
                $language_code,
                $parameters
            );
        } else {

            /*
            |--------------------------------------------------------------------------
            | Send Normal Text Message
            |--------------------------------------------------------------------------
            */

            $clean_message = trim(
                strip_tags($message)
            );


            if (empty($clean_message)) {

                return array(
                    'success' => FALSE,
                    'skipped' => TRUE,
                    'message' => 'WhatsApp message is empty.'
                );
            }


            return $CI->whatsapp->send_text_message(
                $phone_number,
                $clean_message
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| Send WhatsApp To User
|--------------------------------------------------------------------------
|
| Main reusable function for Interfriends users.
|
| Example:
|
| send_whatsapp_to_user(
|     $recipient_number,
|     $country_code,
|     'outstanding_payment_reminder',
|     array(
|         'John Smith',
|         'Saving',
|         '150.00',
|         'August 2026'
|     ),
|     'en_UK'
| );
|
*/

if (!function_exists('send_whatsapp_to_user')) {

    function send_whatsapp_to_user(
        $recipient_number,
        $country_code,
        $template_name,
        $parameters = array(),
        $language_code = 'en_UK'
    ) {

        /**
         * @var load
         */
        $CI = &get_instance();


        /*
        |--------------------------------------------------------------------------
        | Validate Recipient Number
        |--------------------------------------------------------------------------
        */

        if (empty($recipient_number)) {

            return array(
                'success' => FALSE,
                'skipped' => TRUE,
                'message' => 'WhatsApp recipient number is missing.'
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
                'skipped' => TRUE,
                'message' => 'WhatsApp template name is missing.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Load WhatsApp Library
        |--------------------------------------------------------------------------
        */

        if (!isset($CI->whatsapp)) {
            $CI->load->library('whatsapp');
        }

        $CI->config->load('whatsapp_templates', TRUE);
        $templates = $CI->config->item('whatsapp_templates', 'whatsapp_templates');

        if (!is_array($templates)) {
            $templates = array();
        }

        $template_config = !empty($templates[$template_name]) && is_array($templates[$template_name])
            ? $templates[$template_name]
            : array();

        if (!empty($template_config['name'])) {
            $template_name = $template_config['name'];
        }

        if (!empty($template_config['language'])) {
            $language_code = $template_config['language'];
        }


        /*
        |--------------------------------------------------------------------------
        | Format Phone Number
        |--------------------------------------------------------------------------
        */

        $phone_number = $CI->whatsapp->format_phone_number(
            $recipient_number,
            $country_code
        );


        /*
        |--------------------------------------------------------------------------
        | Validate Formatted Phone Number
        |--------------------------------------------------------------------------
        */

        if (empty($phone_number)) {

            return array(
                'success' => FALSE,
                'skipped' => TRUE,
                'message' => 'Invalid WhatsApp phone number.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Make Sure Parameters Are Array
        |--------------------------------------------------------------------------
        |
        | Supports any number of parameters:
        |
        | 1 parameter
        | 2 parameters
        | 4 parameters
        | 6 parameters
        | etc.
        |
        */

        if (!is_array($parameters)) {
            $parameters = array($parameters);
        }


        /*
        |--------------------------------------------------------------------------
        | Send WhatsApp Template
        |--------------------------------------------------------------------------
        */

        $result = $CI->whatsapp->send_template_message(
            $phone_number,
            $template_name,
            $language_code,
            $parameters
        );


        /*
        |--------------------------------------------------------------------------
        | Add Additional Information
        |--------------------------------------------------------------------------
        */

        $result['phone_number'] = $phone_number;
        $result['country_code'] = $country_code;
        $result['template_name'] = $template_name;


        return $result;
    }
}


/*
|--------------------------------------------------------------------------
| Send WhatsApp To Emergency Contact
|--------------------------------------------------------------------------
*/

if (!function_exists('send_whatsapp_to_emergency')) {

    function send_whatsapp_to_emergency(
        $user_id_or_email,
        $message,
        $template_name = '',
        $parameters = array(),
        $language_code = 'en_UK'
    ) {

        /**
         * @var load
         */
        $CI = &get_instance();


        /*
        |--------------------------------------------------------------------------
        | Load WhatsApp Library
        |--------------------------------------------------------------------------
        */

        if (!isset($CI->whatsapp)) {
            $CI->load->library('whatsapp');
        }


        /*
        |--------------------------------------------------------------------------
        | Find User
        |--------------------------------------------------------------------------
        */

        $where = is_numeric($user_id_or_email)
            ? array(
                'user_id' => $user_id_or_email
            )
            : array(
                'email' => $user_id_or_email
            );


        /**
         * @var CI_DB_query_builder $db
         */
        $db = $CI->db;


        $user = $db
            ->select(
                'emergency_country_code,
                 emergency_number'
            )
            ->get_where(
                'user',
                $where
            )
            ->row_array();


        /*
        |--------------------------------------------------------------------------
        | Emergency Number Not Found
        |--------------------------------------------------------------------------
        */

        if (
            empty($user) ||
            empty($user['emergency_number'])
        ) {

            return array(
                'success' => FALSE,
                'skipped' => TRUE,
                'message' => 'Emergency contact number not found for user.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Country Code
        |--------------------------------------------------------------------------
        */

        $country_code = !empty($user['emergency_country_code'])
            ? $user['emergency_country_code']
            : '';


        /*
        |--------------------------------------------------------------------------
        | Format Number
        |--------------------------------------------------------------------------
        */

        $phone_number = $CI->whatsapp->format_phone_number(
            $user['emergency_number'],
            $country_code
        );


        if (empty($phone_number)) {

            return array(
                'success' => FALSE,
                'skipped' => TRUE,
                'message' => 'Invalid emergency contact number.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Send Template
        |--------------------------------------------------------------------------
        */

        if (!empty($template_name)) {

            return $CI->whatsapp->send_template_message(
                $phone_number,
                $template_name,
                $language_code,
                $parameters
            );
        } else {

            $clean_message = trim(
                strip_tags($message)
            );


            if (empty($clean_message)) {

                return array(
                    'success' => FALSE,
                    'skipped' => TRUE,
                    'message' => 'WhatsApp message is empty.'
                );
            }


            return $CI->whatsapp->send_text_message(
                $phone_number,
                $clean_message
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| Send WhatsApp To Next Of Kin
|--------------------------------------------------------------------------
*/

if (!function_exists('send_whatsapp_to_kin')) {

    function send_whatsapp_to_kin(
        $user_id_or_email,
        $message,
        $template_name = '',
        $parameters = array(),
        $language_code = 'en_UK'
    ) {

        /**
         * @var load
         */
        $CI = &get_instance();


        /*
        |--------------------------------------------------------------------------
        | Load WhatsApp Library
        |--------------------------------------------------------------------------
        */

        if (!isset($CI->whatsapp)) {
            $CI->load->library('whatsapp');
        }


        /*
        |--------------------------------------------------------------------------
        | Find User
        |--------------------------------------------------------------------------
        */

        $where = is_numeric($user_id_or_email)
            ? array(
                'user_id' => $user_id_or_email
            )
            : array(
                'email' => $user_id_or_email
            );


        /**
         * @var CI_DB_query_builder $db
         */
        $db = $CI->db;


        $user = $db
            ->select(
                'kin_country_code,
                 kin_number'
            )
            ->get_where(
                'user',
                $where
            )
            ->row_array();


        /*
        |--------------------------------------------------------------------------
        | Next Of Kin Number Not Found
        |--------------------------------------------------------------------------
        */

        if (
            empty($user) ||
            empty($user['kin_number'])
        ) {

            return array(
                'success' => FALSE,
                'skipped' => TRUE,
                'message' => 'Next of kin contact number not found for user.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Country Code
        |--------------------------------------------------------------------------
        */

        $country_code = !empty($user['kin_country_code'])
            ? $user['kin_country_code']
            : '';


        /*
        |--------------------------------------------------------------------------
        | Format Number
        |--------------------------------------------------------------------------
        */

        $phone_number = $CI->whatsapp->format_phone_number(
            $user['kin_number'],
            $country_code
        );


        if (empty($phone_number)) {

            return array(
                'success' => FALSE,
                'skipped' => TRUE,
                'message' => 'Invalid next of kin contact number.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Send Template
        |--------------------------------------------------------------------------
        */

        if (!empty($template_name)) {

            return $CI->whatsapp->send_template_message(
                $phone_number,
                $template_name,
                $language_code,
                $parameters
            );
        } else {

            $clean_message = trim(
                strip_tags($message)
            );


            if (empty($clean_message)) {

                return array(
                    'success' => FALSE,
                    'skipped' => TRUE,
                    'message' => 'WhatsApp message is empty.'
                );
            }


            return $CI->whatsapp->send_text_message(
                $phone_number,
                $clean_message
            );
        }
    }
}
