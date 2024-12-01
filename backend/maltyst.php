<?php
/*
Plugin Name: Maltyst
Plugin URI: https://gitlab.com/linuxd3v/maltyst
Description: Maltyst - Unofficial Mautic wordpress integration. Allows easy newsletter setup with mautic as a backend
Version: 0.1.3
Author: linuxd3v
Author URI: https://gitlab.com/linuxd3v
License: GPL-3.0-only
*/


// ============================================================================
// Constants
// ============================================================================
define('PREFIX', 'maltyst');
if (is_ssl()) {
    $ajaxUrl = admin_url( 'admin-ajax.php', 'https' );
} else {
    $ajaxUrl = admin_url( 'admin-ajax.php', 'http' );
}
define('AJAX_URL', $ajaxUrl);
define('IS_SSL', is_ssl());


// ============================================================================
// Dependencies
// ============================================================================
//Vendor dependencies
include "vendor/autoload.php";

//Our depencencies
require 'src/Utils.php';
require 'src/SettingsUtils.php';
require 'src/AdminMessage.php';

require 'src/ViewController.php';
require 'src/AjaxController.php';
require 'src/SettingsController.php';

require 'src/Database.php';
require 'src/MauticAccess.php';


try {
    // ============================================================================
    // Instantiating
    // ============================================================================
    
    //Utils
    $utils              = new Utils(); 
    $settingsUtils      = new SettingsUtils(); 
    
    //Data services
    $database           = new Database();
    $mauticAccess       = new MauticAccess($settingsUtils); 

    //Controllers
    $viewController     = new ViewController($database, $utils, $mauticAccess, $settingsUtils);
    $ajaxController     = new AjaxController($database, $utils, $mauticAccess, $settingsUtils);
    $settingsController = new SettingsController($database, $utils, $mauticAccess, $settingsUtils);


    // ============================================================================
    // Registering Ajax handlers
    // ============================================================================
    add_action( 'wp_ajax_nopriv_maltystAjaxAcceptOptin', [$ajaxController, 'maltystAjaxAcceptOptin'] );
    add_action( 'wp_ajax_maltystAjaxAcceptOptin', [$ajaxController, 'maltystAjaxAcceptOptin'] );

    add_action( 'wp_ajax_nopriv_maltystAjaxGetSubscriptions', [$ajaxController, 'maltystAjaxGetSubscriptions'] );
    add_action( 'wp_ajax_maltystAjaxGetSubscriptions', [$ajaxController, 'maltystAjaxGetSubscriptions'] );

    add_action( 'wp_ajax_nopriv_maltystAjaxPostSubscriptions', [$ajaxController, 'maltystAjaxPostSubscriptions'] );
    add_action( 'wp_ajax_maltystAjaxPostSubscriptions', [$ajaxController, 'maltystAjaxPostSubscriptions'] );

    add_action( 'wp_ajax_nopriv_maltystAjaxPostOptinConfirmation', [$ajaxController, 'maltystAjaxPostOptinConfirmation'] );
    add_action( 'wp_ajax_maltystAjaxPostOptinConfirmation', [$ajaxController, 'maltystAjaxPostOptinConfirmation'] );
    



    // ============================================================================
    // Registering Assets
    // ============================================================================
    // wp_enqueue_scripts — front-end
    // admin_enqueue_scripts — admin page
    // login_enqueue_scripts — login page
    function maltyst_frontend_assets() {
        //wp_enqueue_style( 'admin-css', get_stylesheet_directory_uri() . '/admin/css/admin.css' );
        //wp_enqueue_script( 'admin-js', get_stylesheet_directory_uri() . '/admin/js/admin.js', true );

        wp_enqueue_style( 'maltyst', plugin_dir_url(__FILE__) . 'dist/css/maltyst.min.css' );
        wp_enqueue_script('maltyst', plugin_dir_url(__FILE__) . 'dist/js/maltyst.min.js', ['jquery']);
        wp_localize_script(
            'maltyst',
            'maltyst_data',
            [
                'ajax_url' => AJAX_URL,
                'prefix'   => PREFIX,
                'nonce'    => wp_create_nonce('ajax-nonce')
            ]
        );
    }
    add_action( 'wp_enqueue_scripts', 'maltyst_frontend_assets' );



    // ============================================================================
    // Registering New post publish notification
    // ============================================================================
    if ($settingsUtils->getSettingsValue('maltystPostPublishNotify') == 1) {
        add_action( 'transition_post_status', [$viewController, 'notifyOfNewPost'], 10, 3 );
    }


    // ============================================================================
    // Enable settings area
    // ============================================================================
    $callable = [$settingsController, 'maltystRegisterSettings'];
    add_action('admin_menu', $callable);


    
    // ============================================================================
    // Registering activation/deactivation hooks
    // ============================================================================

    //On deactivation - we should at least disable cron job
    function maltyst_deactivate() {
        $timestamp = wp_next_scheduled( 'maltyst_cron_hook' );
        wp_unschedule_event( $timestamp, 'maltyst_cron_hook' );
    }


    
    //Hooks
    register_activation_hook( __FILE__, [$database, 'initSchema'] );
    register_deactivation_hook( __FILE__, 'maltyst_deactivate' );


    // ============================================================================
    // Registering CRONs
    // ============================================================================
    //Cleanup cron - removing records older then 1 month
    add_action('maltyst_cron_hook', [$database, 'cleanupExpired']);
    if ( ! wp_next_scheduled( 'maltyst_cron_hook' ) ) {
        wp_schedule_event( time(), 'daily', 'maltyst_cron_hook' );
    }
    

    // ============================================================================
    // Registering shortcodes
    // ============================================================================
    $forms = [
        'maltyst_optin_form'         => [$viewController, 'renderOptinForm'],
        'maltyst_preference_center'  => [$viewController, 'renderPreferenceCenter'],
        'maltyst_optin_confirmation' => [$viewController, 'renderConfirmation'],
        'maltyst_post_browser_view'  => [$viewController, 'emailPostBrowserView'],
    ];
    foreach($forms as $shortcode => $callable) {
        add_shortcode( $shortcode, $callable );
    }
} catch (\Exception $e) {

    //Register new message to show
    new AdminMessage($e->getMessage());

    //Send to default php logging mechanism
    error_log($e->getMessage());

    //Let's respond just to make sure we give some feedback to UI
    if (wp_doing_ajax()) {
        $ajaxResponse = [];
        $ajaxResponse['e']     = $e->getMessage();
        $ajaxResponse['error'] = "Encountered unrecoverable error.";
        wp_send_json_error($ajaxResponse, 500);
    }

}

?>