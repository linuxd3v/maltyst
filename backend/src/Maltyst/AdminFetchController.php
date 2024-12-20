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

    private Utils $utils;
    private SettingsUtils $settingsUtils;

    public function __construct(Database $db, Utils $utils, MauticAccess $mauticAccess, SettingsUtils $settingsUtils)
    {
        $this->db = $db;
        $this->mauticAccess = $mauticAccess;

        $this->utils = $utils;
        $this->settingsUtils = $settingsUtils;
    }

    //===========================================================================
    // Updating subscriptions for user
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