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
define('PREFIX', 'maltyst');

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

    private $utils;
    private $settingsUtils;
    private $database;
    private $mauticAccess;
    private $viewController;
    private $fetchController;
    private $settingsController;

    private function __construct()
    {
        // Initialize dependencies
        $this->utils = new Utils();
        $this->settingsUtils = new SettingsUtils();
        $this->database = new Database();
        $this->mauticAccess = new MauticAccess($this->settingsUtils);
        $this->viewController = new ViewController($this->database, $this->utils, $this->mauticAccess, $this->settingsUtils);
        $this->fetchController = new FetchController($this->database, $this->utils, $this->mauticAccess, $this->settingsUtils);
        $this->settingsController = new SettingsController($this->database, $this->utils, $this->mauticAccess, $this->settingsUtils);

        $this->registerHooks();
    }

    public static function getInstance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function registerHooks()
    {
        // ============================================================================
        // Registering Fetch handlers
        // ============================================================================        
        
        // yes the "wp_ajax_" prefix is mandatory in wordpress, cannot be "wp_fetch_" 
        add_action('wp_ajax_nopriv_maltystFetchAcceptOptin', [$this->fetchController, 'maltystFetchAcceptOptin']);
        add_action('wp_ajax_maltystFetchAcceptOptin', [$this->fetchController, 'maltystFetchAcceptOptin']);

        add_action( 'wp_ajax_nopriv_maltystFetchGetSubscriptions', [$this->fetchController, 'maltystFetchGetSubscriptions'] );
        add_action( 'wp_ajax_maltystFetchGetSubscriptions', [$this->fetchController, 'maltystFetchGetSubscriptions'] );
    
        add_action( 'wp_ajax_nopriv_maltystUpdateSubscriptions', [$$this->fetchController, 'maltystUpdateSubscriptions'] );
        add_action( 'wp_ajax_maltystUpdateSubscriptions', [$this->fetchController, 'maltystUpdateSubscriptions'] );
    
        add_action( 'wp_ajax_nopriv_maltystFetchPostOptinConfirmation', [$this->fetchController, 'maltystFetchPostOptinConfirmation'] );
        add_action( 'wp_ajax_maltystFetchPostOptinConfirmation', [$this->fetchController, 'maltystFetchPostOptinConfirmation'] );
        

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
        // @fixme - load assets from a manifest

        wp_enqueue_style('maltyst', MALTYST_PLUGIN_URL . 'dist/css/maltyst.min.css', [], MALTYST_VERSION);
        wp_enqueue_script('maltyst', MALTYST_PLUGIN_URL . 'dist/js/maltyst.min.js', ['jquery'], MALTYST_VERSION, true);
        wp_localize_script('maltyst', 'maltyst_data', [
            'fetch_url' => MALTYST_FETCH_URL,
            'prefix'   => PREFIX,
            'nonce' => wp_create_nonce('fetch-nonce'),
        ]);
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
