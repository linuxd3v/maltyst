<?php

class AdminMessage {
    private $message;

    public function __construct( $message ) {
        $this->message = $message;

        add_action( 'admin_notices', array( $this, 'render' ) );
    }

    public function render() 
    {
        printf('<div class="error notice notice notice-error is-dismissible"><p>%s</p></div>', $this->message );
    }
}