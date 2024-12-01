<?php if ( ! defined( 'ABSPATH' ) ) exit;

class SettingsController
{
    private $htmlDir;
    private $platesEngine;

    private $db;
    private $utils;
    private $mauticAccess;
    private $settingsUtils;





    public function __construct($db, $utils, $mauticAccess, $settingsUtils)
    {   
        //HTML PHP rendering for views
        $this->htmlDir  = __DIR__ . '/../html-views';
        $this->platesEngine = new \League\Plates\Engine($this->htmlDir, 'phtml');


        $this->db = $db;
        $this->utils = $utils;
        $this->mauticAccess = $mauticAccess;
        $this->settingsUtils = $settingsUtils;
    }

    private function render($tpl, $data=[]) 
    {
        ob_start();
        $html = $this->platesEngine->render($tpl, $data);
        echo $html;
        return ob_get_clean();
    }



    public function maltystRegisterSettings($post)
    {
        $page_title = 'Maltyst Settings';
        $menu_title = 'Maltyst';
        $capability = 'manage_options';
        $menu_slug  = 'maltyst';
        $callable   = [$this, 'maltystShowSettings'];

        add_options_page( $page_title, $menu_title, $capability, $menu_slug, $callable, $position = null );


        //Registering Settings fields:
        $settingsFields = $this->settingsUtils->getSettingsFields();
        foreach($settingsFields as $settingsFieldName => $settingsFieldArgs) {
            register_setting( 'maltyst-settings', $settingsFieldName,  $settingsFieldArgs);
        }
    }

    public function maltystShowSettings($post)
    {
        $tpl = 'settings';

        $data = [
            'prefix'   => PREFIX,
            //'formType' => $tpl,
        ];

        echo $this->render($tpl, $data);
    }
}