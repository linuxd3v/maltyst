<?php if ( ! defined( 'ABSPATH' ) ) exit;

use \Mautic\Auth\ApiAuth;
use \Mautic\MauticApi;

class MauticAccess
{

    private $settingsUtils; 

    private $mauticApi;
    private $contactsApi;
    private $segmentApi;
    private $emailApi;


    private $segmentsPulledFromApi = false;
    private $preferenceCenterSegments = [];


    public function __construct($settingsUtils)
    {

        $this->settingsUtils = $settingsUtils;

        //Not sure why - but whatever
        //session_start();
    
        //Preference center supported segments
        $this->preferenceCenterSegments = $this->settingsUtils->getSettingsValue('maltystPcSegments');


        // Mautic settings
        $apiUrl = $this->settingsUtils->getSettingsValue('maltystMauticApiUrl');
        $user = $this->settingsUtils->getSettingsValue('maltystMauticBasicUsername');
        $pass = $this->settingsUtils->getSettingsValue('maltystMauticBasicPassword');
        
        if (!empty($apiUrl) && !empty($user) && !empty($pass)) {
            $mauticBasic = [
                'userName'   => $user,
                'password'   => $pass,
            ];
    
            // Initiate Mautic Basic auth
            $initAuth = new ApiAuth();
            $auth     = $initAuth->newAuth($mauticBasic, 'BasicAuth');
            $auth->setCurlTimeout(5);
            
            // Diferent mautic apis
            $this->mauticApi   = new MauticApi();
            $this->contactsApi = $this->mauticApi->newApi('contacts', $auth, $apiUrl);
            $this->segmentApi  = $this->mauticApi->newApi('segments', $auth, $apiUrl);
            $this->emailApi    = $this->mauticApi->newApi('emails', $auth, $apiUrl);
        } else {
            new AdminMessage('Unable to configure mautic api - please provide all required configuration options');
        }
    }

    //Let's pull all segments from api and filter to only leave
    //those that we support via config
    public function getPreferenceCenterSegments()
    {
        if (!$this->segmentsPulledFromApi) {

            //Start and limit numbers are arbitrary, I doubt anyone will hit these limits,
            //If so we can reassess.
            $apiResponse = $this->segmentApi->getList($searchFilter = null, $start=0, $limit=1000, $orderBy=null, $orderByDir=null, $publishedOnly=true, $minimal=null);
            if (isset($apiResponse['errors'])) {
                throw new \Exception('Api error: unable to retrieve membership lists');
            } else {
                //Iterate over all segments
                $apiSegments = isset($apiResponse['lists']) ? $apiResponse['lists']: [];
                foreach($apiSegments as $k => $apiSegmentData) {
                    $segmentAlias = $apiSegmentData['alias'];
                    //$segmentId    = $apiSegmentData['id'];

                    if (isset($this->preferenceCenterSegments[$segmentAlias])) {
                        $this->preferenceCenterSegments[$segmentAlias] = $apiSegmentData;
                    }
                }
            }

            //Now - after we pulled segment data from the api - if any of the requested segments were not found
            //we should trigger exception.
            foreach($this->preferenceCenterSegments as $segmentAlias => $segmentData) {
                if (!is_array($segmentData)) {
                    throw new \Exception('Api error: unable to retrieve all segments');
                }
            }


            //Label as pulled
            $this->segmentsPulledFromApi = true;
        }

        return $this->preferenceCenterSegments;
    }


    public function getUserSegments($contactId)
    {

        //Start and limit numbers are arbitrary, I doubt anyone will hit these limits,
        //If so we can reassess.
        $apiResponse = $this->contactsApi->getContactSegments($contactId);

        if (isset($apiResponse['errors'])) {
            return [false, $apiResponse];
        } else {
            $segments = isset($apiResponse['lists']) ? array_values($apiResponse['lists']) : [];

            return [true, $segments];
        }
    }

    public function getSegmentsToIdReference()
    {
        $segmentRefs = [];

        //Start and limit numbers are arbitrary, I doubt anyone will hit these limits,
        //If so we can reassess.
        $apiResponse = $this->segmentApi->getList($searchFilter = null, $start=0, $limit=1000, $orderBy=null, $orderByDir=null, $publishedOnly=true, $minimal=null);
        if (isset($apiResponse['errors'])) {
            return [false, $apiResponse];
        } else {
        
            $segments = isset($apiResponse['lists']) ? $apiResponse['lists']: [];
            foreach($segments as $k => $segment) {
                $alias = $segment['alias'];
                $segmentRefs[$alias] = $segment['id'];
            }

            return [true, $segmentRefs];
        }
    }

    public function addSubscriberToSegmentsUsingSegmentAliases($contactId, $aliases=[])
    {
        $aliases = array_unique($aliases);

        list($apiStatus1, $apiResult1) = $this->getSegmentsToIdReference();
        if (!$apiStatus1) {
            return [false, 'Unable to retrieve segments lists from mautic api'];
        }

        //Let's see if aliases requested are present in mautic
        foreach($aliases as $alias) {
            if (!is_string($alias) || empty($alias)) {
                return [false, "Segment aliases are given in unexpected format"];
            }

            if (!isset($apiResult1[$alias])) {
                return [false, "Reqested segment: $alias is not found"];
            }
        }

        //Adding to segments
        $segmentsFailedToAdd = []; 
        $segmentsAdded = [];
        foreach($aliases as $alias) {
            $segId = $apiResult1[$alias];

            $apiResponse = $this->segmentApi->addContact($segId, $contactId);
            if (!isset($apiResponse['success'])) {
                $segmentsFailedToAdd[] = $alias;
            } else {
                $segmentsAdded[$segId] = $alias;
            }
        }

        if (!empty($segmentsFailedToAdd)) {
            return [false, "Failed to add contact to these segments: " . implode(', ', $segmentsFailedToAdd)];
        }

        return [true, $segmentsAdded];
    }



    public function removeSubscriberFromSegmentsUsingSegmentAliases($contactId, $aliases=[])
    {
        $aliases  =array_unique($aliases);

        list($apiStatus1, $apiResult1) = $this->getSegmentsToIdReference();
        if (!$apiStatus1) {
            return [false, 'Unable to retrieve segments lists from mautic api'];
        }

        //Let's see if aliases requested are present in mautic
        foreach($aliases as $alias) {
            if (!is_string($alias) || empty($alias)) {
                return [false, "Segment aliases are given in unexpected format"];
            }

            if (!isset($apiResult1[$alias])) {
                return [false, "Reqested segment: $alias is not found"];
            }
        }

        //Removing from segments
        $segmentsFailedToRemove = []; 
        $segmentsRemoved = [];
        foreach($aliases as $alias) {
            $segId = $apiResult1[$alias];

            $apiResponse = $this->segmentApi->removeContact($segId, $contactId);
            if (!isset($apiResponse['success'])) {
                $segmentsFailedToRemove[] = $alias;
            } else {
                $segmentsRemoved[$segId] = $alias;
            }
        }

        if (!empty($segmentsFailedToRemove)) {
            return [false, "Failed to remove contact from these segments: " . implode(', ', $segmentsFailedToRemove)];
        }

        return [true, $segmentsRemoved];
    }


    public function findEmailIdByName($emailName)
    {
        $emailIdsRef = [];

        $apiResponse = $this->emailApi->getList($searchFilter=null, $start=0, $limit=10000, $orderBy=null, $orderByDir=null, $publishedOnly=true, $minimal=true);
        
        if (!isset($apiResponse['emails'])) {
            return [false, 'Unable to fetch list of emails from mautic'];
        } else {
            $emails = $apiResponse['emails'];
            foreach($emails as $k => $emailData) {
                $eid    = $emailData['id'];
                $ename  = $emailData['name'];
        
                $emailIdsRef[$ename] = $eid;
            }
        }
        
        if (!isset($emailIdsRef[$emailName])) {
            return [false, 'Email with this name is not found'];
        }

        return [true, $emailIdsRef[$emailName]];
    }



    public function sendEmailToSubscriberByEmailName($contactId, $emailName, $tokens=[])
    {
        list($status, $data) = $this->findEmailIdByName($emailName);
        if (!$status) {
            return [false, 'Unable to find email with this name'];
        }

        $emailId = $data;

        $apiResponse = $this->emailApi->sendToContact($emailId, $contactId, $parameters=['tokens' => $tokens]);

        if (isset($apiResponse['errors'])) {
            return [false, json_encode($apiResponse['errors'])];
        } else {
            return [true, $apiResponse];
        }
    }




    public function sendPostNotificicationToSegment($emailData)
    {
        //Create email
        $createResponse = $this->emailApi->create($emailData);
        if (isset($createResponse['errors'])) {
            return [false, json_encode($createResponse['errors'])];
        }

        //Get email id
        $emailId = $createResponse['email']['id'];


        // Send email to a segment
        $sendResponse = $this->emailApi->send($emailId);
        if (isset($sendResponse['errors'])) {
            return [false, json_encode($createResponse['errors'])];
        }
    }







    public function createSubscriber($email, $maltystUqId = null)
    {
        //Create a contact
        $data = [
            //'firstname'   => 'Jane',
            //'lastname'    => 'Doe',
            //'ipAddress'   => '1.2.3.4',
            'email'              => $email,
            'maltyst_contact_uqid'   => is_null($maltystUqId) ? uniqid() : $maltystUqId,
            'overwriteWithBlank' => true,
        ];
        $contactApiResponse = $this->contactsApi->create($data);
        
        if (!isset($contactApiResponse['contact'])) {
            return [false, $contactApiResponse];
        } else {
            return [true, $contactApiResponse];
        }
    }


    public function doesEmailExist($email)
    {
        $where = [ [ 'col' => 'email', 'expr' => 'eq', 'val' => $email, ] ];
        $contacts = $this->contactsApi->getList("email:$email", 0, 30, $orderBy='email', $orderByDir='asc', $publishedOnly=false, $minimal=false);
        $total = is_array($contacts) && isset($contacts['total']) ? (int)$contacts['total'] : null;
        
        if (is_null($total)) {
            throw new \Exception('Unrecognized mautic response received');
        }

        if ($contacts['total'] > 0) {
            return true;
        }

        return false;
    }

    public function getEmailRecordByEmail($email)
    {
        $where = [ [ 'col' => 'email', 'expr' => 'eq', 'val' => $email, ] ];
        $contacts = $this->contactsApi->getList("email:$email", 0, 30, $orderBy='email', $orderByDir='asc', $publishedOnly=false, $minimal=false);
        $total = is_array($contacts) && isset($contacts['total']) ? (int)$contacts['total'] : null;
        
        if (is_null($total)) {
            throw new \Exception('Unrecognized mautic response received');
        }

        if ($total != 1) {
            return false;
        }

        $contacts = array_values($contacts['contacts']);

        return $contacts[0];
    }

    public function getEmailRecordByMaltystUniqueId($maltystUqId)
    {
        $where = [ [ 'col' => 'maltyst_contact_uqid', 'expr' => 'eq', 'val' => $maltystUqId, ] ];
        $contacts = $this->contactsApi->getList("maltyst_contact_uqid:$maltystUqId", 0, 30, $orderBy='id', $orderByDir='asc', $publishedOnly=false, $minimal=false);
        $total = is_array($contacts) && isset($contacts['total']) ? (int)$contacts['total'] : null;
        
        if (is_null($total)) {
            throw new \Exception('Unrecognized mautic response received');
        }

        if ($total != 1) {
            throw new \Exception('Only single record is expected');
        }

        $contacts = array_values($contacts['contacts']);

        return $contacts[0];
    }


    
}