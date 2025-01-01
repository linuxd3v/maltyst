<?php

namespace Maltyst;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SettingsUtils
{
    // Define all supported fields and their attributes
    private array $settingsFields = [
        // Mautic API details
        'maltystMauticApiUrl' => [
            'type' => 'string',
            'description' => 'Full URL of a Mautic instance',
            'show_in_rest' => false,
            'default' => '',
        ],
        'maltystMauticBasicUsername' => [
            'type' => 'string',
            'description' => 'Username for Mautic basic auth',
            'show_in_rest' => false,
            'default' => '',
        ],
        'maltystMauticBasicPassword' => [
            'type' => 'string',
            'description' => 'Password for Mautic basic auth',
            'show_in_rest' => false,
            'default' => '',
        ],
        // Templates
        'maltystPostPublishNotifyMauticTemplateWelcome' => [
            'type' => 'string',
            'description' => 'Template for the welcome email',
            'show_in_rest' => false,
            'default' => '',
        ],
        'maltystPostPublishNotifyMauticTemplateDbl' => [
            'type' => 'string',
            'description' => 'Template for the double opt-in email',
            'show_in_rest' => false,
            'default' => '',
        ],
        // Double opt-in and throwaway detection
        'maltystThrowawayDetection' => [
            'type' => 'number',
            'description' => 'Enable throwaway email detection',
            'show_in_rest' => false,
            'default' => 0,
        ],
        'maltystWelcomeEmail' => [
            'type' => 'number',
            'description' => 'Enable welcome email functionality',
            'show_in_rest' => false,
            'default' => 0,
        ],
        // Excerpt configuration
        'maltystPostPublishNotifyExcerptLen' => [
            'type' => 'string',
            'description' => 'Number of words to use for the excerpt',
            'show_in_rest' => false,
            'default' => '',
        ],
        // Preference center
        'maltystPcSlug' => [
            'type' => 'string',
            'description' => 'URL for the preference center',
            'show_in_rest' => false,
            'default' => '',
        ],
        'maltystOptinConfirmationUrl' => [
            'type' => 'string',
            'description' => 'URL for double opt-in confirmation',
            'show_in_rest' => false,
            'default' => '',
        ],
        'maltystPcSegments' => [
            'type' => 'string',
            'description' => 'JSON-encoded list of preference center segments',
            'show_in_rest' => false,
            'default' => '',
        ],
        'maltystBlogLogoUrl' => [
            'type' => 'string',
            'description' => 'URL for the blog logo',
            'show_in_rest' => false,
            'default' => '',
        ],
        // Post notification settings
        'maltystPostPublishNotify' => [
            'type' => 'number',
            'description' => 'Enable post-publish notifications',
            'show_in_rest' => false,
            'default' => 0,
        ],
        'maltystPostPublishNotifySegmentName' => [
            'type' => 'string',
            'description' => 'Mautic segment name for notifications',
            'show_in_rest' => false,
            'default' => '',
        ],
        'maltystPostPublishNotifyFromAddress' => [
            'type' => 'string',
            'description' => 'From email address for Mautic',
            'show_in_rest' => false,
            'default' => '',
        ],
        'maltystPostPublishNotifyFromName' => [
            'type' => 'string',
            'description' => 'From name for Mautic emails',
            'show_in_rest' => false,
            'default' => '',
        ],
        'maltystPostPublishNotifyReplyTo' => [
            'type' => 'string',
            'description' => 'Reply-to email address for Mautic',
            'show_in_rest' => false,
            'default' => '',
        ],
    ];

    public function __construct()
    {
        // Constructor is empty but allows for future initialization
    }

    /**
     * Returns the definitions for all settings fields
     */
    public function getSettingsFields(): array
    {
        return $this->settingsFields;
    }

    /**
     * Reads and returns all currently defined settings
     */
    public function getSettingsValues(): array
    {
        $allDefined = [];

        foreach ($this->settingsFields as $fieldName => $attributes) {
            // Retrieve and sanitize the option value
            $value = trim((string) get_option($fieldName));

            // Decode JSON for specific settings
            if ($fieldName === 'maltystPcSegments') {
                $value = empty($value) ? [] : json_decode($value, true);
            }

            $allDefined[$fieldName] = $value;
        }

        return $allDefined;
    }

    /**
     * Retrieves the value of a specific setting
     */
    public function getSettingsValue(string $fieldName): mixed
    {
        $allSettings = $this->getSettingsValues();
        return $allSettings[$fieldName] ?? null;
    }
}
