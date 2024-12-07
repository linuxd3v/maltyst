<?php

namespace Maltyst;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AdminMessage
{
    private string $message;

     //message The message to display in the admin notice.
    public function __construct(string $message)
    {
        $this->message = esc_html($message); // Ensure the message is escaped for HTML output
        add_action('admin_notices', [$this, 'render']);
    }

    //Renders the admin notice in the WordPress admin panel.
    public function render(): void
    {
        printf(
            '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
            $this->message
        );
    }
}
