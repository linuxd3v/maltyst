<?php 

namespace Maltyst;

if (!defined('ABSPATH')) {
    exit;
}

use Fgribreau\MailChecker;

class PublicFetchController
{

    private Database $db;
    private MauticAccess $mauticAccess;

    private PublicUtils $publicUtils;
    private SettingsUtils $settingsUtils;

    public function __construct(Database $db, PublicUtils $publicUtils, MauticAccess $mauticAccess, SettingsUtils $settingsUtils)
    {
        $this->db = $db;
        $this->mauticAccess = $mauticAccess;

        $this->publicUtils = $publicUtils;
        $this->settingsUtils = $settingsUtils;
    }

    //===========================================================================
    // Updating subscriptions for user
    //===========================================================================
    public function maltystUpdateSubscriptions(): void
    {
        // Params
        $nonce = $_POST['security'] ?? null;
        $snames = is_array($_POST['snames'] ?? null) ? $_POST['snames'] : [];
        $maltystUqId = is_string($_POST['maltystContactUqid'] ?? null) ? $_POST['maltystContactUqid'] : null;

        $defaultResponse = [];
    
        // 0. Validate nonce
        if ( ! wp_verify_nonce( $nonce, 'fetch-nonce' ) ) {
            $defaultResponse['error'] = 'Form failed validation';
            wp_send_json_error($defaultResponse, 400);
        }


        //1. Pull contact data from mautic using this unique identifier
        $contact = $this->mauticAccess->getEmailRecordByMaltystUniqueId($maltystUqId);
        $contactId = $contact['id'];
        $email = isset($contact['fields']['core']['email']['value']) ? $contact['fields']['core']['email']['value'] : null;
        if (is_null($email)) {
            throw new \Exception('Unrecognized response - cannot find email field');
        }


        //2. Pull user segments
        list($apiStatus1, $apiResult1) = $this->mauticAccess->getUserSegments($contactId);
        if (!$apiStatus1) {
            $defaultResponse['error'] = "Unable to retrieve subscription information.";
            wp_send_json_error($defaultResponse, 500);
        }
        $userSegments = $apiResult1;
        $userSegmentsAliases = array_column($userSegments, 'alias');


        //3. Let's get PC segments (as registered in WP). 
        //and then let's filter out those not labeled for preference center managament as per mautic api.
        $pcSegments = $this->mauticAccess->getPreferenceCenterSegments();
        foreach($pcSegments as $pcSegmentAlias => $pcSegmentData) {
            if (!$pcSegmentData['isPreferenceCenter']) {
                unset($pcSegments[$pcSegmentAlias]);
                continue;
            }
        }
        $pcSegmentsAliases = array_column($pcSegments, 'alias');

        
        //4. Now - lets figure out: 
        //   a) segments we need to subscribe to
        //   b) segments we need to unsubscribe from
        $subscribeSegments   = array_diff($snames, $userSegmentsAliases);
        $unsubscribeSegments = array_diff($userSegmentsAliases, $snames);

        //Let's also make sure we only make operations on segments mautic user enabled in WP config: 
        $subscribeSegments = array_intersect($subscribeSegments, $pcSegmentsAliases);
        $unsubscribeSegments = array_intersect($unsubscribeSegments, $pcSegmentsAliases);


        //5. Add user to segments
        if (!empty($subscribeSegments)) {
            list($apiStatus2, $apiResult2) = $this->mauticAccess->addSubscriberToSegmentsUsingSegmentAliases($contactId, $subscribeSegments);
            if (!$apiStatus2) {
                $defaultResponse['error'] = "Unable to activate newsletter lists.";
                wp_send_json_error($defaultResponse, 500);
            }
        }


        //6. Remove user from segments:
        if (!empty($unsubscribeSegments)) {
            list($apiStatus3, $apiResult3) = $this->mauticAccess->removeSubscriberFromSegmentsUsingSegmentAliases($contactId, $unsubscribeSegments);

            if (!$apiStatus3) {
                $defaultResponse['error'] = "Unable to remove from newsletter lists.";
                wp_send_json_error($defaultResponse, 500);
            }
        }

        $defaultResponse['message'] = "Email preferences updated successfully.";
        wp_send_json($defaultResponse, 200);
    }



    //===========================================================================
    // Retrieving subscriptions for a user
    //===========================================================================
    public function maltystGetSubscriptions(): void
    {
        // Params
        $nonce   = isset($_GET['security']) ? $_GET['security'] : null;
        $maltystUqId = isset($_GET['maltystContactUqid']) && is_string($_GET['maltystContactUqid']) ? $_GET['maltystContactUqid'] : null;


        $defaultResponse = [];

        // 0. Validate nonce
        if ( ! wp_verify_nonce( $nonce, 'fetch-nonce' ) ) {
            $defaultResponse['error'] = 'Form failed validation';
            wp_send_json_error($defaultResponse, 400);
        }

        //1. Pull contact data from mautic using this unique identifier
        $contact = $this->mauticAccess->getEmailRecordByMaltystUniqueId($maltystUqId);
        $contactId = $contact['id'];
        $email = isset($contact['fields']['core']['email']['value']) ? $contact['fields']['core']['email']['value'] : null;
        $dnc   = array_values($contact['doNotContact']); 
        if (is_null($email)) {
            throw new \Exception('Unrecognized response - cannot find email field');
        }


        //2. Pull user segments
        list($apiStatus1, $apiResult1) = $this->mauticAccess->getUserSegments($contactId);
        if (!$apiStatus1) {
            $defaultResponse['error'] = "Unable to retrieve subscription information.";
            wp_send_json_error($defaultResponse, 500);
        }
        $userSegments = $apiResult1;
        $userSegmentsAliases = array_column($userSegments, 'alias');


        //3. Let's get PC segments - as registered in WP. 
        //and then let's filter out those not labeled for preference center managament as per mautic api.
        $pcSegments = $this->mauticAccess->getPreferenceCenterSegments();
        $pcSegmentsReal = $pcSegments;
        foreach($pcSegments as $pcSegmentAlias => $pcSegmentData) {
            if (!$pcSegmentData['isPreferenceCenter']) {
                unset($pcSegments[$pcSegmentAlias]);
                continue;
            }
        }
        $pcSegmentsAliases = array_column($pcSegments, 'alias');


        //4. Let's calcualte aliases intersect - aliases that user has that are also in preference center.
        $intersect = array_values(array_intersect($pcSegmentsAliases, $userSegmentsAliases));


        //5. DNC - there are multiple (or at least 2) DNC channels - email && sms.
        //Here we only care about email scenario.
        // $dncEmail = false;
        // foreach($dnc as $dncEntry) {
        //     if ($dncEntry['channel'] === 'email') {
        //         $dncEmail = true;
        //         break;
        //     }
        // }

        //6. Preparing response
        //6 a. All preference center segments 
        $defaultResponse['pcSegments']     = array_values($pcSegments);
        $defaultResponse['userAliases']    = $intersect;
        
        
        //Let's not return  DNC status. 
        // Adding user to DNC should be nuclear option and done by customer support upon consumer request.
        // Typical unsubscribe scenario should be via adding/removing from segments.
        //$defaultResponse['dnc']      = $dncEmail;
        
        wp_send_json($defaultResponse, 200);
    }


    // Accepting user optin - this will dispatch double-optin email
    public function maltystStartOptin (): void
    {
        // Check for nonce security
        $nonce = $_POST['security'];
    
        $defaultResponse = [];
    
        // Validate nonce
        if ( ! wp_verify_nonce( $nonce, 'fetch-nonce' ) ) {
            $defaultResponse['error'] = 'Form failed validation';
            wp_send_json_error($defaultResponse, 400);
        }
    
        // Validate email
        $email = isset($_POST['email']) && is_string($_POST['email']) && filter_var($_POST['email'], \FILTER_VALIDATE_EMAIL) ? $_POST['email'] : null;
        if (is_null($email)) {
            $defaultResponse['error'] = 'Invalid email';
            wp_send_json_error($defaultResponse, 400);
        }
        $email = mb_strtolower($email);
    
        // Throwaway check
        if ($this->settingsUtils->getSettingsValue('maltystThrowawayDetection') == 1) {
            if (! MailChecker::isValid($email)) {
                $defaultResponse['error'] = 'This email appears to be from a throwaway domain';
                wp_send_json_error($defaultResponse, 400);
            }
        }

        //Let's check if mautic record already exists, we just dont want to update
        //field: maltyst_contact_uqid on update call?
        $maltystUqId = null;
        $contact = $this->mauticAccess->getEmailRecordByEmail($email);
        if ($contact !== null) {
            $maltystUqId = isset($contact['fields']['core']['maltyst_contact_uqid']['value']) ? $contact['fields']['core']['maltyst_contact_uqid']['value'] : null;
        }

        // Creating mautic record.
        // Note - mautic will simply return account data back to us if it's already present, no error will be thrown
        // @fixme: wait -but if we do double optin - should we even create account at this stage? 
        // I think we should wait for double optin to finish before we do this
        list($apiStatus1, $apiResult1) = $this->mauticAccess->createSubscriber($email, $maltystUqId);
        if (!$apiStatus1) {
            $defaultResponse['error'] = "Unable to add `$email` subscriber to newsletter";
            //$defaultResponse['res'] = $apiResult1;
            wp_send_json_error($defaultResponse, 500);
        }
        $contactId = $apiResult1['contact']['id'];


        // Here - we will support simple resending of confirmation email if user is not 
        // in mautic but is in the maltyst optin list.
        $exists = $this->db->doesEmailOptinExist($email);
        if ($exists) {
            $record  = $this->db->getEmailOptinRecordByEmail($email);
            $emailId = $record['id'];
        } else {
            // Otherwise - create maltyst email optin record
            $emailId = $this->db->createEmailOptin($email);
        }
        
        // And then create a new confirmation token
        [$emailConfirmationTokenHash, $emailConfirmationTokenPublic] = $this->publicUtils->createToken();

        // Record maltyst double-optin confirmation token into the database
        $isTokenCreated = $this->db->createEmailConfirmationToken($emailId, $emailConfirmationTokenHash, PublicUtils::TOKEN_ALGO);
        if (!$isTokenCreated) {
            $defaultResponse['error'] = 'Error generating confirmation email.';
            wp_send_json_error($defaultResponse, 500);
        }


        //Send double-optin email.
        // It will be sent from Mautic. The template 
        $maltystOptinConfirmationSlug = $this->settingsUtils->getSettingsValue('maltystOptinConfirmationUrl');
        $tplDoubleOptin = trim($this->settingsUtils->getSettingsValue('maltystPostPublishNotifyMauticTemplateDbl'));
        $tplDoubleOptin = empty($tplDoubleOptin) || !is_string($tplDoubleOptin) ? 'double-optin' : $tplDoubleOptin;
        $maltystPcSlug = $this->settingsUtils->getSettingsValue('maltystPcSlug');

        $optinUrl = get_bloginfo('url') . $maltystOptinConfirmationSlug . '?maltyst_optin_confirmationToken=' . $emailConfirmationTokenPublic;
        
        $tokens = [
            //For double optin 
            'confirmationToken' => $emailConfirmationTokenPublic,
            'maltystOptinConfirmationSlug' => $maltystOptinConfirmationSlug,
            'optinUrl' => $optinUrl,


            //Unsubscribe && preference center links
            'unsubUrl'  => get_bloginfo('url') . $maltystPcSlug . '?maltyst_contact_uqid={contactfield=maltyst_contact_uqid}&unsubscribe-from-all=true',
            'pcUrl'     => get_bloginfo('url') . $maltystPcSlug . '?maltyst_contact_uqid={contactfield=maltyst_contact_uqid}',

            //Blog info
            'blogTitle' => get_bloginfo('name'),
            'blogDesc'  => get_bloginfo('description'),
            'blogUrl'   => get_bloginfo('url'),
        ];
        list($apiStatus2, $apiResult2) = $this->mauticAccess->sendEmailToSubscriberByEmailName($contactId, $tplDoubleOptin, $tokens);
        if (!$apiStatus2) {
            $defaultResponse['error'] = 'Error dispatching confirmation email.';
            wp_send_json_error($defaultResponse, 500);
        }


        $defaultResponse['message'] = "Thank you. Please check your email to confirm email delivery.";
        wp_send_json($defaultResponse, 200);
    }




    //===========================================================================
    // Processing double optin confirmation
    //===========================================================================
    public function maltystProcessDoubleOptinConfirmation(): void
    {

        // Params
        $nonce = $_POST['security'];
        $token = isset($_POST['maltyst_optin_confirmationToken']) ? $_POST['maltyst_optin_confirmationToken'] : '';

        $defaultResponse = [];
    
        // Validate nonce
        if ( ! wp_verify_nonce( $nonce, 'fetch-nonce' ) ) {
            $defaultResponse['error'] = 'Form failed validation';
            wp_send_json_error($defaultResponse, 400);
        }

        //Validation
        $token = is_string($token) ? $token : '';
        $token = trim($token);

        //Token is empty - instruct to resignup.
        if (empty($token)) {
            $defaultResponse['error'] = 'Invalid signup token, try signup form again.';
            wp_send_json_error($defaultResponse, 400);
        }

        //Processing token && generating an encrypted hash
        [$tokenAlgo, $emailConfirmationTokenClear] = $this->publicUtils->processPublicToken($token);

        if (is_null($tokenAlgo) || is_null($emailConfirmationTokenClear)) {
            $defaultResponse['error'] = 'Invalid email confirmation token.';
            wp_send_json_error($defaultResponse, 400);
        }
        $emailConfirmationTokenHash = hash($tokenAlgo, $emailConfirmationTokenClear);

        //Checking if records exists in database
        $tokenRecord = $this->db->getConfirmationTokenRecord($emailConfirmationTokenHash);
        if (is_null($tokenRecord)) {
            $defaultResponse['error'] = 'Email confirmation token not found.';
            wp_send_json_error($defaultResponse, 400);
        }
        $emailId = $tokenRecord["fk_optins_id"];

        $optinRecord = $this->db->getEmailOptinRecordById($emailId);
        if (is_null($optinRecord)) {
            $defaultResponse['error'] = 'Email Confirmation data is invalid.';
            wp_send_json_error($defaultResponse, 400);
        }
        $email = $optinRecord['email'];



        //If records exist in database: 
        // a) create a subscriber in mautic
        list($apiStatus1, $apiResult1) = $this->mauticAccess->createSubscriber($email);
        if (!$apiStatus1) {
            $defaultResponse['error'] = 'Unable to add subscriber';
            wp_send_json_error($defaultResponse, 500);
        }
        $contactId = $apiResult1['contact']['id'];


        // b) Add contact to segments
        // inability to do this should generate an error
        $maltystPcSegments = $this->settingsUtils->getSettingsValue('maltystPcSegments');
        $maltystPcSegments = array_keys($maltystPcSegments);
        list($apiStatus2, $apiResult2) = $this->mauticAccess->addSubscriberToSegmentsUsingSegmentAliases($contactId, $maltystPcSegments);
        if (!$apiStatus2) {
            $defaultResponse['error'] = 'Unable to activate newsletter lists';
            wp_send_json_error($defaultResponse, 500);
        }

        // c) send a welcome email
        //If welcome email wasnt sent - hey- whatever. Do not show an error message.
        if ($this->settingsUtils->getSettingsValue('maltystWelcomeEmail') == 1) {
            $tplWelcome = trim($this->settingsUtils->getSettingsValue('maltystPostPublishNotifyMauticTemplateWelcome'));
            $tplWelcome = empty($tplWelcome) || !is_string($tplWelcome) ? 'welcome' : $tplWelcome;
            list($apiStatus3, $apiResult3) = $this->mauticAccess->sendEmailToSubscriberByEmailName($contactId, $tplWelcome);
        }

        // d) remove confirmation && email record from database
        // Note: failure to do so should not generate errors for consumer 
        $result1 = $this->db->removeTokenRecordsByOptinId($emailId);
        $result2 = $this->db->removeEmailRecordById($emailId);

        $defaultResponse['message'] = "Welcome to our newsletter!";
        wp_send_json($defaultResponse, 200);
    }

}