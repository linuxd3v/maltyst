<?php if ( ! defined( 'ABSPATH' ) ) exit;

class SettingsUtils
{

    //Let's define all supported fields
    private $settingsFields = [

        //Mautic api details
        'maltystMauticApiUrl' => [
            'type'                => 'string',
            'description'         => 'Full url of a mautic instance',
            //'sanitize_callback' => '(callable) A callback function that sanitizes the option's value.',
            'show_in_rest'        => false,
            'default'             => '',
        ],
        'maltystMauticBasicUsername' => [
            'type'                => 'string',
            'description'         => 'Username of a mautic basic auth user',
            //'sanitize_callback' => '(callable) A callback function that sanitizes the option's value.',
            'show_in_rest'        => false,
            'default'             => '',
        ],
        'maltystMauticBasicPassword' => [
            'type'                => 'string',
            'description'         => 'Password of a mautic basic auth user',
            //'sanitize_callback' => '(callable) A callback function that sanitizes the option's value.',
            'show_in_rest'        => false,
            'default'             => '',
        ],


        'maltystPostPublishNotifyMauticTemplateWelcome' => [
            'type'                => 'string',
            'description'         => 'Welcome mautic template',
            //'sanitize_callback' => '(callable) A callback function that sanitizes the option's value.',
            'show_in_rest'        => false,
            'default'             => '',
        ],

        'maltystPostPublishNotifyMauticTemplateDbl' => [
            'type'                => 'string',
            'description'         => 'Double optin mautic template',
            //'sanitize_callback' => '(callable) A callback function that sanitizes the option's value.',
            'show_in_rest'        => false,
            'default'             => '',
        ],



        //Double optin / Optin
        'maltystThrowawayDetection' => [
            'type'                => 'number',
            'description'         => 'Enable throwaway detection',
            //'sanitize_callback' => '(callable) A callback function that sanitizes the option's value.',
            'show_in_rest'        => false,
            'default'             => 0,
        ],


        'maltystWelcomeEmail' => [
            'type'                => 'number',
            'description'         => 'Enable welcome email',
            //'sanitize_callback' => '(callable) A callback function that sanitizes the option's value.',
            'show_in_rest'        => false,
            'default'             => 0,
        ],

        



        'maltystPostPublishNotifyExcerptLen' => [
            'type'                => 'string',
            'description'         => 'number of words to use for excerpt',
            //'sanitize_callback' => '(callable) A callback function that sanitizes the option's value.',
            'show_in_rest'        => false,
            'default'             => '',
        ],
        



        //Preference center
        'maltystPcUrl' => [
            'type'                => 'string',
            'description'         => 'Preference center url',
            //'sanitize_callback' => '(callable) A callback function that sanitizes the option's value.',
            'show_in_rest'        => false,
            'default'             => '',
        ],
        'maltystOptinConfirmationUrl' => [
            'type'                => 'string',
            'description'         => 'Double optin confirmation url',
            //'sanitize_callback' => '(callable) A callback function that sanitizes the option's value.',
            'show_in_rest'        => false,
            'default'             => '',
        ],
        'maltystPcSegments' => [
            'type'                => 'string',
            'description'         => 'Preference center segments',
            //'sanitize_callback' => '(callable) A callback function that sanitizes the option's value.',
            'show_in_rest'        => false,
            'default'             => '',
        ],
        'maltystBlogLogoUrl' => [
            'type'                => 'string',
            'description'         => 'URL path to the blog logo',
            //'sanitize_callback' => '(callable) A callback function that sanitizes the option's value.',
            'show_in_rest'        => false,
            'default'             => '',
        ],



        //New post notification
        'maltystPostPublishNotify' => [
            'type'                => 'number',
            'description'         => 'Enable post publish notifications',
            //'sanitize_callback' => '(callable) A callback function that sanitizes the option's value.',
            'show_in_rest'        => false,
            'default'             => 0,
        ],
        'maltystPostPublishNotifySegmentName' => [
            'type'                => 'string',
            'description'         => 'Mautic segment name to use for notifications',
            //'sanitize_callback' => '(callable) A callback function that sanitizes the option's value.',
            'show_in_rest'        => false,
            'default'             => '',
        ],
        'maltystPostPublishNotifyFromAddress' => [
            'type'                => 'string',
            'description'         => 'From field email address mautic should use',
            //'sanitize_callback' => '(callable) A callback function that sanitizes the option's value.',
            'show_in_rest'        => false,
            'default'             => '',
        ],
        'maltystPostPublishNotifyFromName' => [
            'type'                => 'string',
            'description'         => 'From field name mautic should use',
            //'sanitize_callback' => '(callable) A callback function that sanitizes the option's value.',
            'show_in_rest'        => false,
            'default'             => '',
        ],
        'maltystPostPublishNotifyReplyTo' => [
            'type'                => 'string',
            'description'         => 'Reply-to email address mautic should use',
            //'sanitize_callback' => '(callable) A callback function that sanitizes the option's value.',
            'show_in_rest'        => false,
            'default'             => '',
        ],
    ];


    public function __construct()
    {

    }

    public function getSettingsFields()
    {
        return $this->settingsFields;
    }

    /*
     * Let's read and return all currently defined settings
     */
    public function getSettingsValues()
    {
        $allDefined = [];

        foreach($this->settingsFields as $sName => $sAttrs) {

            $var = trim(get_option($sName));
            if ($sName === 'maltystPcSegments') {
                $var = empty($var) ? [] : json_decode($var, true);
            }
            
            $allDefined[$sName] = $var;
        }

        return $allDefined;
    }

    public function getSettingsValue($sName = '')
    {
        $allSettings = $this->getSettingsValues();
        
        return $allSettings[$sName];
    }

}