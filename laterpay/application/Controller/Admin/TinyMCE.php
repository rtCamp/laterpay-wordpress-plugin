<?php

/**
 * LaterPay TinyMCE controller.
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

        return array(
            'laterpay_mce_buttons'                    => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'laterpay_on_plugin_is_active', 200 ),
                array( 'register_tinymce_button' ),
            ),
            'laterpay_mce_external_plugins'           => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'laterpay_on_plugin_is_active', 200 ),
                array( 'add_tinymce_button' ),
            ),
            'laterpay_admin_enqueue_styles_post_edit' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'localize_script' ),
            ),
            'laterpay_admin_enqueue_styles_post_new'  => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'localize_script' ),
            ),
        );

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

    /**
     * To localize text for shortcode generator.
     *
     * @return void
     */
    public function localize_script() {

        // Time passes data.
        $time_passes_model = LaterPay_Model_TimePassWP::get_instance();
        $time_passes_list  = $time_passes_model->get_active_time_passes();
        $time_passes_ids   = array();

        foreach ( $time_passes_list as $item ) {

            if ( empty( $item['title'] ) || empty( $item['pass_id'] ) ) {
                continue;
            }

            $time_passes_ids[] = array(
                'text'  => $item['title'],
                'value' => $item['pass_id'],
            );
        }

        // Subscriptions data.
        $subscriptions_model = LaterPay_Model_SubscriptionWP::get_instance();
        $subscriptions_list  = $subscriptions_model->get_active_subscriptions();
        $subscriptions_ids   = [];

        foreach ( $subscriptions_list as $item ) {

            if ( empty( $item['title'] ) || empty( $item['id'] ) ) {
                continue;
            }

            $subscriptions_ids[] = array(
                'text'  => $item['title'],
                'value' => $item['id'],
            );
        }

        wp_localize_script(
            'wp-tinymce',
            'laterpay_shortcode_generator_labels',
            array(
                'button'                       => array(
                    'text' => esc_html__( 'LaterPay ShortCodes', 'laterpay' ),
                ),
                'premium_download'             => array(
                    'title'             => esc_html__( 'LaterPay Premium Download Box', 'laterpay' ),
                    'target_post_id'    => array(
                        'label' => esc_html__( 'Post ID', 'laterpay' ),
                    ),
                    'target_post_title' => array(
                        'label' => esc_html__( 'Post Title', 'laterpay' ),
                    ),
                    'heading_text'      => array(
                        'label' => esc_html__( 'Heading Title', 'laterpay' ),
                        'value' => esc_html__( 'Additional Premium Content', 'laterpay' ),
                    ),
                    'description_text'  => array(
                        'label' => esc_html__( 'Description text', 'laterpay' ),
                    ),
                    'content_type'      => array(
                        'label'  => esc_html__( 'Content Type', 'laterpay' ),
                        'values' => array(
                            array(
                                'text'  => esc_html__( 'Auto Identify', 'laterpay' ),
                                'value' => '',
                            ),
                            array(
                                'text'  => esc_html__( 'Link', 'laterpay' ),
                                'value' => 'link',
                            ),
                            array(
                                'text'  => esc_html__( 'File', 'laterpay' ),
                                'value' => 'file',
                            ),
                            array(
                                'text'  => esc_html__( 'Gallery', 'laterpay' ),
                                'value' => 'gallery',
                            ),
                            array(
                                'text'  => esc_html__( 'Audio', 'laterpay' ),
                                'value' => 'audio',
                            ),
                            array(
                                'text'  => esc_html__( 'Video', 'laterpay' ),
                                'value' => 'video',
                            ),
                            array(
                                'text'  => esc_html__( 'Text', 'laterpay' ),
                                'value' => 'text',
                            ),
                        ),
                    ),
                    'teaser_image_path' => array(
                        'label' => esc_html__( 'Teaser Image Path', 'laterpay' ),
                        'text'  => esc_html__( 'Select Media', 'laterpay' ),
                    ),
                ),
                'time_pass_purchase_button'    => array(
                    'title'                   => esc_html__( 'Time Pass purchase button', 'laterpay' ),
                    'no_item_text'            => esc_html__( 'No Time-pass Available.', 'laterpay' ),
                    'id'                      => array(
                        'label'  => esc_html__( 'ID', 'laterpay' ),
                        'values' => $time_passes_ids,
                    ),
                    'button_text'             => array(
                        'label' => esc_html__( 'Button Text', 'laterpay' ),
                    ),
                    'button_background_color' => array(
                        'label' => esc_html__( 'Button background color', 'laterpay' ),
                        'value' => get_option( 'laterpay_main_color', '#01a99d' ),
                    ),
                    'button_text_color'       => array(
                        'label' => esc_html__( 'Button text color', 'laterpay' ),
                    ),
                    'custom_image_path'       => array(
                        'label' => esc_html__( 'Custom image path', 'laterpay' ),
                        'text'  => esc_html__( 'Select Image', 'laterpay' ),
                    ),
                ),
                'subscription_purchase_button' => array(
                    'title'                   => esc_html__( 'Subscription purchase button', 'laterpay' ),
                    'no_item_text'            => esc_html__( 'No Subscription Available.', 'laterpay' ),
                    'id'                      => array(
                        'label'  => esc_html__( 'ID', 'laterpay' ),
                        'values' => $subscriptions_ids,
                    ),
                    'button_text'             => array(
                        'label' => esc_html__( 'Button Text', 'laterpay' ),
                    ),
                    'button_background_color' => array(
                        'label' => esc_html__( 'Button background color', 'laterpay' ),
                        'value' => get_option( 'laterpay_main_color', '#01a99d' ),
                    ),
                    'button_text_color'       => array(
                        'label' => esc_html__( 'Button text color', 'laterpay' ),
                    ),
                    'custom_image_path'       => array(
                        'label' => esc_html__( 'Custom image path', 'laterpay' ),
                        'text'  => esc_html__( 'Select Image', 'laterpay' ),
                    ),
                ),
            )
        );

    }

}
