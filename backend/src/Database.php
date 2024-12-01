<?php if ( ! defined( 'ABSPATH' ) ) exit;

class Database
{
    private $tableOptins;
    private $tableConfTokens;

    public function __construct()
    {
        global $wpdb;
        $this->tableOptins = $wpdb->prefix . 'maltyst_optins';
        $this->tableConfTokens = $wpdb->prefix . 'maltyst_email_confirmation_tokens';
    }

    public function removeTokenRecordsByOptinId($optinId)
    {
        try {

            global $wpdb;

            $table = $this->tableConfTokens;
            $query = $wpdb->delete($table, 
                ["fk_optins_id" => $optinId], 
                ['%d']
            );
    
            return true;

        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function removeTokenRecordById($tokenId)
    {
        try {

            global $wpdb;

            $table = $this->tableConfTokens;
            $query = $wpdb->delete($table, 
                ["id" => $tokenId], 
                ['%d']
            );
    
            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    public function removeEmailRecordById($optinId)
    {
        try {

            global $wpdb;

            $table = $this->tableOptins;
            $query = $wpdb->delete($table, 
                ["id" => $optinId], 
                ['%d']
            );
    
            return true;

        } catch (\Exception $e) {
            return false;
        }
    }


    /*
     * Create email confirmation token
     * 
     * @param int    $emailId - email id
     * @param string $token - email confirmation token
     * @param string $algo - token algo
     * 
     * @return boolean
     */
    public function createEmailConfirmationToken($emailId, $token, $algo)
    {
        try {

            global $wpdb;
            $prefix          = $wpdb->prefix;

            $table = $this->tableConfTokens;
            $query = $wpdb->insert($table, 
                [
                    "fk_optins_id" => $emailId,
                    'hashed_token' => $token,
                    'hash_algo'    => $algo,
                
                ], 
                ['%s', '%s', '%s']
            );
            
            
            $id = $wpdb->insert_id;
    
            return $id;

        } catch (\Exception $e) {
            return false;
        }
    }

    public function createEmailOptin($email)
    {
        global $wpdb;
        
        $table = $this->tableOptins;
        $query = $wpdb->insert($table, ['email' => $email], ['%s']);
        
        
        $id = $wpdb->insert_id;

        return $id;
    }


    public function getEmailOptinRecordById($emailId)
    {
        global $wpdb;
        
        $table = $this->tableOptins;
        $query = "SELECT * FROM $table WHERE id = %d";
        $query = $wpdb->prepare($query, $emailId);

        $results = $wpdb->get_results($query, ARRAY_A);

        return empty($results) ? null : $results[0];
    }


    public function getConfirmationTokenRecord($token)
    {
        global $wpdb;
        
        $table = $this->tableConfTokens;
        $query = "SELECT * FROM $table WHERE hashed_token = %s";
        $query = $wpdb->prepare($query, $token);

        $results = $wpdb->get_results($query, ARRAY_A);

        return empty($results) ? null : $results[0];
    }


    public function getEmailOptinRecordByEmail($email)
    {
        global $wpdb;
        
        $table = $this->tableOptins;
        $query = "SELECT * FROM $table WHERE email = %s";
        $query = $wpdb->prepare($query, $email);

        $results = $wpdb->get_results($query, ARRAY_A);

        return empty($results) ? [] : $results[0];
    }
    

    public function doesEmailOptinExist($email)
    {
        global $wpdb;
        
        $table = $this->tableOptins;
        $query = "SELECT * FROM $table WHERE email = %s";
        $query = $wpdb->prepare($query, $email);

        $results = $wpdb->get_results($query, ARRAY_A);

        if (count($results)) {
            return true;
        }

        return false;
    }

    public function getAllEmailOptinRecords()
    {
        global $wpdb;
        
        $table = $this->tableOptins;
        $query = "SELECT * FROM $table";
        //$query = $wpdb->prepare($query, $emailId);

        $results = $wpdb->get_results($query, ARRAY_A);

        return $results;
    }

    public function getAllEmailConfTokens()
    {
        global $wpdb;
        
        $table = $this->tableConfTokens;
        $query = "SELECT * FROM $table";
        //$query = $wpdb->prepare($query, $emailId);

        $results = $wpdb->get_results($query, ARRAY_A);

        return $results;
    }




    
    //Lte's cleanup tokens and email accounts added more then a month ago
    public function cleanupExpired()
    {
        global $wpdb;
        
        //Delete all optin records created more then a month ago
        $table = $this->tableOptins;
        $query = "DELETE FROM $table WHERE created < DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $wpdb->query($query);

        //Get all optin records
        $optinRecords = $this->getAllEmailOptinRecords();
        $optinIds1 = array_column($optinRecords, 'id');

        //Get all tokens
        $tokens = $this->getAllEmailConfTokens();
        $optinIds2 = array_unique(array_column($tokens, 'fk_optins_id'));
        
        //Finding Non existant optin ids - and removign tokens if any for those
        //Typically I'd just use foreign keys, but this is ... cms that it is.
        $nonExistant = array_diff($optinIds2, $optinIds1);
        foreach($nonExistant as $optinId) {
            $this->removeTokenRecordsByOptinId($optinId);
        }

        return true;
    }

    public function initSchema()
    {
        global $wpdb;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $prefix          = $wpdb->prefix;
        $charset_collate = $wpdb->get_charset_collate();

$maltystSchemaSql1 = "CREATE TABLE IF NOT EXISTS {$prefix}maltyst_optins (
    id bigint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
    email varchar(255),
    created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY (email)
) $charset_collate;";

$maltystSchemaSql2 = "CREATE TABLE IF NOT EXISTS  {$prefix}maltyst_email_confirmation_tokens (
    id bigint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
    fk_optins_id BIGINT UNSIGNED NOT NULL,
    hashed_token varchar(255) NOT NULL,
    hash_algo varchar(20) NOT NULL,
    created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY (hashed_token)
) $charset_collate;";

        dbDelta($maltystSchemaSql1);
        dbDelta($maltystSchemaSql2);
    }
}