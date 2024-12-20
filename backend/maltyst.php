<?php
/*
Plugin Name: Maltyst
Plugin URI: https://www.maltyst.com
Description: Maltyst - Unofficial Mautic WordPress integration for easy newsletter setup.
Version: 0.1.3
Author: linuxd3v
Author URI: https://github.com/linuxd3v
License: GPL-3.0-only
Text Domain: maltyst
*/

// ============================================================================
// Namespace
// ============================================================================
namespace Maltyst;

defined('ABSPATH') || exit; // Exit if accessed directly.

// ============================================================================
// Constants
// ============================================================================
define('MALTYST_VERSION', '0.1.3');
define('MALTYST_PLUGIN_DIR', \plugin_dir_path(__FILE__));
define('MALTYST_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MALTYST_FETCH_URL', admin_url('admin-fetch.php', is_ssl() ? 'https' : 'http'));
define('MALTYST_PREFIX', 'maltyst');
define('MALTYST_MANIFEST', MALTYST_PLUGIN_DIR . '/.vite/manifest.json');
define('MALTYST_ROUTE', MALTYST_PREFIX . "/v1");


// ============================================================================
// Utils
// ============================================================================
if (!function_exists('mb_trim')) {
    //Trims a string, considering multibyte characters.
    function mb_trim(string $string, string $characterMask = '\s', string $encoding = 'UTF-8'): string
    {
        $pattern = '/^[' . $characterMask . ']+|[' . $characterMask . ']+$/u';
        return preg_replace($pattern, '', $string) ?? $string;
    }
}



// ============================================================================
// Autoload Dependencies
// ============================================================================
if (!file_exists(MALTYST_PLUGIN_DIR . 'vendor/autoload.php')) {
    wp_die(__('Missing Composer dependencies. Please run `composer install`.', 'maltyst'));
}
require_once MALTYST_PLUGIN_DIR . 'vendor/autoload.php';

// // Include core files
// foreach (['Utils', 'SettingsUtils', 'AdminMessage', 'ViewController', 'FetchController', 'SettingsController', 'Database', 'MauticAccess'] as $class) {
//     require_once MALTYST_PLUGIN_DIR . "src/{$class}.php";
// }

// ============================================================================
// Main Plugin Class
// ============================================================================
final class Plugin
{
    private static ?Plugin $instance = null;

    private $publicUtils;
    private $settingsUtils;
    private $database;
    private $mauticAccess;
    private $viewController;
    private $publicFetchController;
    private $settingsController;
    private $adminFetchController;

    private function __construct()
    {
        // Initialize dependencies
        $this->publicUtils = new publicUtils();
        $this->settingsUtils = new SettingsUtils();
        $this->database = new Database();
        $this->mauticAccess = new MauticAccess($this->settingsUtils);
        $this->viewController = new ViewController($this->database, $this->publicUtils, $this->mauticAccess, $this->settingsUtils);
        $this->publicFetchController = new PublicFetchController($this->database, $this->publicUtils, $this->mauticAccess, $this->settingsUtils);
        $this->settingsController = new SettingsController($this->database, $this->publicUtils, $this->mauticAccess, $this->settingsUtils);

        $this->adminFetchController = new AdminFetchController($this->database, $this->publicUtils, $this->mauticAccess, $this->settingsUtils);


        $this->registerHooks();
        $this->registerShortcodes();
    }

    public static function getInstance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function registerShortcodes()
    {
        // ============================================================================
        // Registering shortcodes
        // ============================================================================
        $forms = [
            'maltyst_optin_form'         => [$this->viewController, 'renderOptinForm'],
            'maltyst_preference_center'  => [$this->viewController, 'renderPreferenceCenter'],
            'maltyst_optin_confirmation' => [$this->viewController, 'renderConfirmation'],
            'maltyst_post_browser_view'  => [$this->viewController, 'emailPostBrowserView'],
        ];
        foreach($forms as $shortcode => $callable) {
            add_shortcode( $shortcode, $callable );
        }
    }



    private function registerHooks()
    {

        // ============================================================================
        // Registering admin fetch handlers
        // ============================================================================    
        add_action('rest_api_init', function () {
            register_rest_route(MALTYST_ROUTE, '/save-settings', [
                'methods' => 'POST',
                'callback' => [$this->adminFetchController, 'saveSettings'],
                'permission_callback' => function () {
                    return current_user_can('manage_options'); // Ensure the user has the correct permissions
                },
            ]);
        });

        // Wordpress nonce can expire so we need to get a new one once in a while. 🤷‍♂️ 
        add_action('rest_api_init', function () {
            register_rest_route(MALTYST_ROUTE, '/get-nonce', [
                'methods' => 'GET',
                'callback' => [$this->adminFetchController, 'getNonce'],
                'permission_callback' => '__return_true',
            ]);
        });
        



        // ============================================================================
        // Registering public fetch handlers
        // ============================================================================        
        register_rest_route(MALTYST_ROUTE, '/start-optin', [
            'methods' => 'POST',
            'callback' => [$this->publicFetchController, 'maltystStartOptin'],
            'permission_callback' => '__return_true', // Publicly accessible
        ]);

        // Route for getting subscriptions
        register_rest_route(MALTYST_ROUTE, '/get-subscriptions', [
            'methods' => 'GET',
            'callback' => [$this->publicFetchController, 'maltystGetSubscriptions'],
            'permission_callback' => '__return_true', // Publicly accessible
        ]);

        // Route for updating subscriptions
        register_rest_route(MALTYST_ROUTE, '/update-subscriptions', [
            'methods' => 'POST',
            'callback' => [$this->publicFetchController, 'maltystUpdateSubscriptions'],
            'permission_callback' => '__return_true', // Publicly accessible
        ]);
    

        // Route for posting opt-in confirmation
        register_rest_route(MALTYST_ROUTE, '/process-doubleoptin-confirmation', [
            'methods' => 'POST',
            'callback' => [$this->publicFetchController, 'maltystProcessDoubleOptinConfirmation'],
            'permission_callback' => '__return_true', // Publicly accessible
        ]);
        

        // ============================================================================
        // Registering Assets
        // ============================================================================
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);


        // ============================================================================
        // Registering New post publish notification
        // ============================================================================
        if ($this->settingsUtils->getSettingsValue('maltystPostPublishNotify') == 1) {
            add_action( 'transition_post_status', [$this->viewController, 'notifyOfNewPost'], 10, 3 );
        }


        // ============================================================================
        // Enable settings area
        // ============================================================================
        add_action('admin_menu', [$this->settingsController, 'maltystRegisterSettings']);


        // ============================================================================
        // CRON jobs
        // ============================================================================
        add_action('maltyst_cron_hook', [$this->database, 'cleanupExpired']);
        if (!wp_next_scheduled('maltyst_cron_hook')) {
            wp_schedule_event(time(), 'daily', 'maltyst_cron_hook');
        }

        // Activation and deactivation hooks
        register_activation_hook(__FILE__, [$this->database, 'initSchema']);
        register_deactivation_hook(__FILE__, [$this, 'deactivatePlugin']);
    }

    public function enqueueAssets()
    {
        // Read and decode the manifest file
        if (file_exists(MALTYST_MANIFEST)) {
            $manifest = json_decode(file_get_contents(MALTYST_MANIFEST), true);
        } else {
            throw new \RuntimeException("Vite manifest file not found at " . MALTYST_MANIFEST);
        }
    
        // Extract asset paths
        $cssFile = isset($manifest['scss/maltyst.scss']['file'])
            ? MALTYST_PLUGIN_URL . $manifest['scss/maltyst.scss']['file']
            : null;
    
        $jsFile = isset($manifest['js/maltyst.mjs']['file'])
            ? MALTYST_PLUGIN_URL . $manifest['js/maltyst.mjs']['file']
            : null;
    
        // Enqueue CSS if available
        if ($cssFile) {
            wp_enqueue_style('maltyst', $cssFile, [], MALTYST_VERSION);
        }
    
        // Enqueue JavaScript if available
        if ($jsFile) {
            wp_enqueue_script('maltyst', $jsFile, ['jquery'], MALTYST_VERSION, true);
        }
    

        // Pass backend data to JS
        $maltystData = [
            'MALTYST_PREFIX' => MALTYST_PREFIX,
            'nonce' => wp_create_nonce('fetch-nonce'),
            'MALTYST_ROUTE' => MALTYST_ROUTE
        ];
        wp_add_inline_script(
            'maltyst',
            'window.maltystData = ' . json_encode($maltystData) . ';',
            'before'
        );
    }
    

    public function deactivatePlugin()
    {
        $timestamp = wp_next_scheduled('maltyst_cron_hook');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'maltyst_cron_hook');
        }
    }
}

// ============================================================================
// Initialize Plugin
// ============================================================================
function maltyst_init()
{
    Plugin::getInstance();
}

add_action('plugins_loaded', 'Maltyst\\maltyst_init');
