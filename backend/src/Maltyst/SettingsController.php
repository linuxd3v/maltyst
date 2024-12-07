<?php

namespace Maltyst;

use League\Plates\Engine;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SettingsController
{
    private string $htmlDir;
    private Engine $platesEngine;

    private Database $db;
    private Utils $utils;
    private MauticAccess $mauticAccess;
    private SettingsUtils $settingsUtils;

    public function __construct(Database $db, Utils $utils, MauticAccess $mauticAccess, SettingsUtils $settingsUtils)
    {
        // Initialize HTML rendering engine
        $this->htmlDir = __DIR__ . '/../html-views';
        $this->platesEngine = new Engine($this->htmlDir, 'phtml');

        // Inject dependencies
        $this->db = $db;
        $this->utils = $utils;
        $this->mauticAccess = $mauticAccess;
        $this->settingsUtils = $settingsUtils;
    }

    /**
     * Renders a template with provided data
     */
    private function render(string $template, array $data = []): string
    {
        ob_start();
        $html = $this->platesEngine->render($template, $data);
        echo $html;
        return ob_get_clean();
    }

    /**
     * Registers Maltyst settings page and fields
     */
    public function maltystRegisterSettings(): void
    {
        $pageTitle = 'Maltyst Settings';
        $menuTitle = 'Maltyst';
        $capability = 'manage_options';
        $menuSlug = 'maltyst';

        // Add settings page to the WordPress admin menu
        add_options_page($pageTitle, $menuTitle, $capability, $menuSlug, [$this, 'maltystShowSettings']);

        // Register settings fields dynamically
        $settingsFields = $this->settingsUtils->getSettingsFields();
        foreach ($settingsFields as $fieldName => $fieldArgs) {
            register_setting('maltyst-settings', $fieldName, $fieldArgs);
        }
    }

    /**
     * Displays the Maltyst settings page
     */
    public function maltystShowSettings(): void
    {
        $template = 'settings';

        $data = [
            'prefix' => PREFIX, // Pass any necessary data to the template
        ];

        echo $this->render($template, $data);
    }
}
