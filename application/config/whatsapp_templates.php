<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['whatsapp_templates'] = array(

    'outstanding_payment_reminder' => array(
        'name' => 'outstanding_payment_reminder',
        'message' => 'Hello {{1}},
 
            This is a reminder that you have an outstanding payment.

            Payment Type: {{2}}
            Outstanding Amount: £{{3}}
            Overdue Month: {{4}}

            Please make the payment at your earliest convenience to avoid any disruption to your Interfriends benefits.

            If you have already made this payment, kindly ignore this message.

            Regards
            Interfriends Membership Relations',
        'language' => 'en_GB'
    ),

    'payment_received_for_savings' => array(
        'name' => 'payment_received_for_savings',
        'message' => 'Hello {{1}},

            Your payment was received and recorded.
                        
            Payment Date: {{2}}
            Payment Type: {{3}}
            Amount Received: £{{4}}
            Payment Status: {{5}}
                        
            Regards
            Interfriends Membership Relations',
        'language' => 'en_GB'
    ),

    'payment_received_for_loans' => array(
        'name' => 'payment_received_for_loans',
        'message' => 'Hello {{1}},
 
            Your payment was received and recorded.

            Payment Date: {{2}}
            Payment Type: {{3}}
            Amount Received: £{{4}}
            Payment Status: {{5}}

            Regards
            Interfriends Membership Relations',
        'language' => 'en_GB'
    ),

    'payment_received_for_miscellaneous' => array(
        'name' => 'payment_received_for_miscellaneous',
        'message' => 'Hello {{1}},
 
            Your payment was received and recorded.

            Payment Date: {{2}}
            Payment Type: {{3}}
            Amount Received: £{{4}}
            Payment Status: {{5}}

            Regards
            Interfriends Membership Relations',
        'language' => 'en_GB'
    ),
    
    'payment_received_for_emergency' => array(
        'name' => 'payment_received_for_emergency',
        'message' => 'Hello {{1}},
 
            Your payment was received and recorded.
                        
            Payment Date: {{2}}
            Payment Type: {{3}}
            Amount Received: £{{4}}
            Payment Status: {{5}}

            Regards
            Interfriends Membership Relations',
        'language' => 'en_GB'
    ),

    'payment_approved_for_loans' => array(
        'name' => 'payment_approved_for_loans',
        'message' => 'Hello {{1}},

            Your {{2}} application was approved. Payment will arrive within 24 hours.

            Regards
            Interfriends Membership Relations',
        'language' => 'en_GB'
    ),

    'payout_request_created' => array(
        'name' => 'payout_request_created',
        'message' => 'Hello {{1}},
 
            Your payout for the cycle has been processed. Expect payment into your account within 24 hours.

            Regards
            Interfriends Membership Relations',
        'language' => 'en_GB'
    ),

    'safekeeping_request_created' => array(
        'name' => 'safekeeping_request_created',
        'message' => 'Hello {{1}},
 
            Your payout for the cycle has been processed into Safekeeping.

            Regards
            Interfriends Membership Relations',
        'language' => 'en_GB'
    ),

    'loan_request_declined' => array(
        'name' => 'loan_request_declined',
        'message' => 'Hello {{1}},

            Your {{2}} application has been declined.
            Check your email for more details.

            Regards
            Interfriends Membership Relations',
        'language' => 'en_GB'
    ),

    'missed_payment_created' => array(
        'name' => 'missed_payment_created',
        'message' => 'Hello {{1}},

            We have not received your {{2}} payment yet. We know this may be an oversight. Please arrange to pay as soon as possible or contact Membership Relations.

            Regards
            Interfriends Membership Relations',
        'language' => 'en_GB'
    ),

);
