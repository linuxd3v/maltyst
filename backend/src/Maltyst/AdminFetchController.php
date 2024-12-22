<?php 

namespace Maltyst;

use WP_REST_Response;
use WP_Error;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

use Fgribreau\MailChecker;

class AdminFetchController
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

    public function getNonce(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response(['nonce' => wp_create_nonce('wp_rest')]);
    }

    //===========================================================================
    // Getting a setting
    //===========================================================================
    public function getSettings(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $params = $request->get_json_params();
    
        // Check if 'option_names' is provided and is an array
        if (!isset($params['option_names']) || !is_array($params['option_names'])) {
            return new WP_Error('invalid_data', 'Invalid data provided. Expected an array of option names.', ['status' => 400]);
        }
    
        $option_names = array_map('sanitize_text_field', $params['option_names']); // Sanitize each option name
        $options = [];
    
        // Fetch each option value
        foreach ($option_names as $option_name) {
            $options[$option_name] = get_option($option_name, null); // Default to null if the option does not exist
        }
    
        // Return the options as a response
        return rest_ensure_response([
            'message' => 'Settings retrieved successfully',
            'options' => $options,
        ]);
    }
    


    //===========================================================================
    // Saving setting
    //===========================================================================
    public function saveSettings(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $params = $request->get_json_params();

        if (!isset($params['option_name']) || !isset($params['option_value'])) {
            return new WP_Error('invalid_data', 'Invalid data provided', ['status' => 400]);
        }
    
        $option_name = sanitize_text_field($params['option_name']);
        $option_value = sanitize_text_field($params['option_value']);
    
        if (update_option($option_name, $option_value)) {
            return rest_ensure_response(['message' => 'Settings saved successfully']);
        }
    
        return new WP_Error('save_failed', 'Failed to save settings', ['status' => 500]);
    }

}