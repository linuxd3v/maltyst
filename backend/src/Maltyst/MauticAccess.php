<?php 

namespace Maltyst;

use \Mautic\Auth\ApiAuth;
use \Mautic\Auth\BasicAuth;
use \Mautic\MauticApi;
use \Mautic\Api\Contacts;
use \Mautic\Api\Segments;
use \Mautic\Api\Emails;
use Ramsey\Uuid\Uuid;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

class MauticAccess
{

    private SettingsUtils $settingsUtils; 
    private MauticApi $mauticApi;
    private Contacts $contactsApi;
    private Segments $segmentApi;
    private Emails $emailApi;


    private BasicAuth $basicAuth;


    private bool $segmentsPulledFromApi = false;
    private array $preferenceCenterSegments = [];


    public function __construct(SettingsUtils $settingsUtils)
    {

        $this->settingsUtils = $settingsUtils;
    
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
            $this->basicAuth = $initAuth->newAuth($mauticBasic, 'BasicAuth');
            $this->basicAuth->setCurlTimeout(5);
            
            // Diferent mautic apis
            $this->mauticApi   = new MauticApi();
            $this->contactsApi = $this->mauticApi->newApi('contacts',  $this->basicAuth, $apiUrl);
            $this->segmentApi  = $this->mauticApi->newApi('segments',  $this->basicAuth, $apiUrl);
            $this->emailApi    = $this->mauticApi->newApi('emails',  $this->basicAuth, $apiUrl);
        } else {
            new AdminMessage('Unable to access mautic api. You need to fill in the mautic instance address, and access details.');
        }
    }

    //Let's pull all segments from api and filter to only leave
    //those that we support via config
    public function getPreferenceCenterSegments(): array
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


    public function getUserSegments(string $contactId): array
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

    public function addSubscriberToSegmentsUsingSegmentAliases(string $contactId, array $aliases=[]): array
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



    public function removeSubscriberFromSegmentsUsingSegmentAliases(string $contactId, array $aliases=[]): array
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


    public function findEmailIdByName(string $emailName): array
    {
        // Attempt to get the list of emails from mautic - up to 3 times
        $maxRetries = 3;
        $attempt = 0;
        $apiResponse = null;
        while ($attempt < $maxRetries && $apiResponse === null) {
            try {
                // Attempt to fetch the email list
                $apiResponse = $this->emailApi->getList(
                    $searchFilter = null,
                    $start = 0,
                    $limit = 10000,
                    $orderBy = null,
                    $orderByDir = null,
                    $publishedOnly = true,
                    $minimal = true
                );
        
                // If the request is successful, break out of the loop
                if ($apiResponse !== null) {
                    break;
                }
            } catch (Throwable $e) {
                // Log the exception (you can replace this with actual logging)
                error_log('maltyst error: cannot access mautic api to fetch emails list: ' . $e->getMessage());
            }
        
            // Increment attempt counter and introduce a small delay before retrying
            $attempt++;
        }
        

        if (!isset($apiResponse['emails'])) {
            error_log('maltyst error: Unable to fetch list of emails from mautic');

            return [false, 'Unable to fetch list of emails from mautic'];
        }


        $emailIdsRef = array_column($apiResponse['emails'], 'id', 'name');

        if (!isset($emailIdsRef[$emailName])) {
            return [false, 'Email with this name is not found'];
        }
        
        return [true, $emailIdsRef[$emailName]];
        
    }



    public function sendEmailToSubscriberByEmailName(string $contactId, string $emailName, array $tokens=[]): array
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



    // @fixme - I think the reason I did it this way - is because there is 
    // no way to create email as mjml in mautic via api.  
    // https://forum.mautic.org/t/allow-email-created-via-api-to-be-mjml/22257  
    
    // Even if you create a segment template in mautic manually    
    // - mautic "send to segment" api does not support passing tokens.    
    // https://developer.mautic.org/#send-email-to-segment    
    // Which means we must render mjml here. ☹️☹️☹️  
    public function sendPostNotificicationToSegment(array $emailData): array
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







    public function createSubscriber(string $email, ?string $maltystUqId = null): array
    {
        // Create a uuid v7  - we will use that for unique identifiers in mautic
        $uuid = Uuid::uuid7();

        //Create a contact
        $data = [
            //'firstname'   => 'Jane',
            //'lastname'    => 'Doe',
            //'ipAddress'   => '1.2.3.4',
            'email'              => $email,
            'maltyst_contact_uqid'   => is_null($maltystUqId) ? $uuid->toString() : $maltystUqId,
            'overwriteWithBlank' => true,
        ];
        $contactApiResponse = $this->contactsApi->create($data);
        
        if (!isset($contactApiResponse['contact'])) {
            return [false, $contactApiResponse];
        } else {
            return [true, $contactApiResponse];
        }
    }


    public function doesEmailExist(string $email): bool
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

    public function getEmailRecordByEmail(string $email): ?array
    {
        $where = [ [ 'col' => 'email', 'expr' => 'eq', 'val' => $email, ] ];
        $contacts = $this->contactsApi->getList("email:$email", 0, 30, $orderBy='email', $orderByDir='asc', $publishedOnly=false, $minimal=false);
        $total = is_array($contacts) && isset($contacts['total']) ? (int)$contacts['total'] : null;
        
        if (is_null($total)) {
            throw new \Exception('Unrecognized mautic response received');
        }

        if ($total != 1) {
            return null;
        }

        $contacts = array_values($contacts['contacts']);

        return $contacts[0];
    }

    public function getEmailRecordByMaltystUniqueId(string $maltystUqId): array
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