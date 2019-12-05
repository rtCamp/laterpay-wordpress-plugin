<?php

/**
 * Laterpay TinyMCE controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Controller_Admin_TinyMCE extends LaterPay_Controller_Admin_Base {

    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_subscribed_events()
     */
    public static function get_subscribed_events() {

        return [
            'laterpay_mce_buttons'          => [
                [ 'laterpay_on_admin_view', 200 ],
                [ 'laterpay_on_plugin_is_active', 200 ],
                [ 'register_tinymce_button' ],
            ],
            'laterpay_mce_external_plugins' => [
                [ 'laterpay_on_admin_view', 200 ],
                [ 'laterpay_on_plugin_is_active', 200 ],
                [ 'add_tinymce_button' ],
            ],
        ];
    }

    /**
     * To add laterpay short code generator dropdown in TinyMCE.
     *
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function register_tinymce_button( LaterPay_Core_Event $event ) {

        $result = $event->get_result();

        $result = array_merge(
            $result,
            [
                'laterpay_shortcode_generator',
            ]
        );

        $event->set_result( $result );

    }

    /**
     * To register script for custom button for shortcode generator dropdown.
     *
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function add_tinymce_button( LaterPay_Core_Event $event ) {

        $result = $event->get_result();

        $result['laterpay_shortcode_generator'] = sprintf( '%slaterpay-backend-shortcode-generator.js', $this->config->js_url );

        $event->set_result( $result );
    }

}
