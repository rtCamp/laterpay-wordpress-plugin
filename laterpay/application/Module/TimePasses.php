<?php

/**
 * LaterPay TimePasses class
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Module_TimePasses extends LaterPay_Core_View implements LaterPay_Core_Event_SubscriberInterface {

    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_shared_events()
     */
    public static function get_shared_events() {
        return array();
    }

    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_subscribed_events()
     */
    public static function get_subscribed_events() {
        return array(
            'laterpay_post_content' => array(
                array( 'modify_post_content', 5 ),
            ),
            'laterpay_time_passes' => array(
                array( 'the_time_passes_widget' ),
            ),
            'laterpay_time_pass_render' => array(
                array( 'render_time_pass' ),
            ),
            'laterpay_loaded' => array(
                array( 'buy_time_pass' ),
            ),
            'laterpay_shortcode_time_passes' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'render_time_passes_widget' ),
            ),
            'laterpay_admin_notices' => array(
                array( 'maybe_add_only_time_pass_purchase_option' ),
            ),
            'laterpay_purchase_overlay_content' => array(
                array( 'on_purchase_overlay_content', 5 ),
            ),
            'wp_ajax_laterpay_post_statistic_render' => array(
                array( 'remove_laterpay_post_statistic', 200 ),
            ),
            'laterpay_purchase_button' => array(
                array( 'check_only_time_pass_purchases_allowed', 200 ),
            ),
            'laterpay_purchase_link' => array(
                array( 'check_only_time_pass_purchases_allowed', 200 ),
            ),
            'laterpay_show_sis_notification' => array(
                array( 'display_sis_notification', 0 ),
            )
        );
    }

    /**
     * Post statistics are irrelevant, if only time pass purchases are allowed, but we still need to have the
     * option to switch the preview mode for the given post, so we only render that switch in this case
     *
     * @param LaterPay_Core_Event $event
     */
    public function remove_laterpay_post_statistic( LaterPay_Core_Event $event ) {
        if ( get_option( 'laterpay_only_time_pass_purchases_allowed' ) ) {
            $listener = LaterPay_Core_Bootstrap::get_controller( 'Frontend_Statistic' );
            $result = laterpay_event_dispatcher()->remove_listener( $event->get_name(), array( $listener, 'ajax_render_tab' ) );
            if ( $result ) {
                laterpay_event_dispatcher()->add_listener( $event->get_name(), array( $this, 'ajax_render_tab_without_statistics' ) );
            }
        }
    }

    /**
     * Check the permissions on saving the metaboxes.
     *
     * @wp-hook save_post
     *
     * @param int $post_id
     *
     * @return bool true|false
     */
    protected function has_permission( $post_id ) {
        // autosave -> do nothing
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return false;
        }

        // Ajax -> do nothing
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return false;
        }

        // no post found -> do nothing
        $post = get_post( $post_id );
        if ( $post === null ) {
            return false;
        }

        // current post type is not enabled for LaterPay -> do nothing
        if ( ! in_array( $post->post_type, $this->config->get( 'content.enabled_post_types' ) ) ) {
            return false;
        }

        return true;
    }

    /**
     * Callback to render a widget with the available LaterPay time passes within the theme
     * that can be freely positioned.
     *
     * @wp-hook laterpay_time_passes
     *
     * @var string $introductory_text     additional text rendered at the top of the widget
     * @var string $call_to_action_text   additional text rendered after the time passes and before the voucher code input
     * @var int    $time_pass_id          id of one time pass to be rendered instead of all time passes
     *
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function the_time_passes_widget( LaterPay_Core_Event $event ) {
        if ( $event->has_argument( 'post' ) ) {
            $post = $event->get_argument( 'post' );
        } else {
            $post = get_post();
        }

        list( $introductory_text, $call_to_action_text, $time_pass_id ) = $event->get_arguments() + array( '', '', null );
        if ( empty( $introductory_text ) ) {
            $introductory_text = '';
        }
        if ( empty( $call_to_action_text ) ) {
            $call_to_action_text = '';
        }

        $is_homepage                     = is_front_page() && is_home();
        $show_widget_on_free_posts       = get_option( 'laterpay_show_time_passes_widget_on_free_posts' );
        $time_passes_positioned_manually = get_option( 'laterpay_time_passes_positioned_manually' );

        // prevent execution, if the current post is not the given post and we are not on the homepage,
        // or the action was called a second time,
        // or the post is free and we can't show the time pass widget on free posts
        if ( LaterPay_Helper_Pricing::is_purchasable() === false && ! $is_homepage ||
             did_action( 'laterpay_time_passes' ) > 1 ||
             LaterPay_Helper_Pricing::is_purchasable() === null && ! $show_widget_on_free_posts
        ) {
            return;
        }

        // don't display widget on a search or multiposts page, if it is positioned automatically
        if ( ! is_singular() && ! $time_passes_positioned_manually ) {
            return;
        }

        // get time passes list
        $time_passes_with_access = $this->get_time_passes_with_access();

        if ( isset( $time_pass_id ) ) {
            if ( in_array( $time_pass_id, $time_passes_with_access ) ) {
                return;
            }
            $time_passes_list = array( LaterPay_Helper_TimePass::get_time_pass_by_id( $time_pass_id, true ) );
        } else {
            // check, if we are on the homepage or on a post / page page
            if ( $is_homepage ) {
                $time_passes_list = LaterPay_Helper_TimePass::get_time_passes_list_by_post_id(
                    null,
                    $time_passes_with_access,
                    true
                );
            } else {
                $time_passes_list = LaterPay_Helper_TimePass::get_time_passes_list_by_post_id(
                    ! empty( $post )? $post->ID: null,
                    $time_passes_with_access,
                    true
                );
            }
        }

        // don't render the widget, if there are no time passes
        if ( count( $time_passes_list ) === 0 ) {
            return;
        }

        // check, if the time passes to be rendered have vouchers
        $has_vouchers = LaterPay_Helper_Voucher::passes_have_vouchers( $time_passes_list );

        $view_args = array(
            'passes_list'                    => $time_passes_list,
            'has_vouchers'                   => $has_vouchers,
            'time_pass_introductory_text'    => $introductory_text,
            'time_pass_call_to_action_text'  => $call_to_action_text,
        );

        $this->assign( 'laterpay_widget', $view_args );
        $html = $event->get_result();
        $html .= $this->get_text_view( 'frontend/partials/widget/time-passes' );

        $event->set_result( $html );
    }

    /**
     * Display notification for sis time passes
     *
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function display_sis_notification($event) {
        if ( $event->has_argument( 'post' ) ) {
            $post = $event->get_argument( 'post' );
        } else {
            $post = get_post();
        }

        if ( $post === null ) {
            return;
        }

        // get time passes list
        $time_passes_with_access = $this->get_time_passes_with_access();
        $time_passes_list = LaterPay_Helper_TimePass::get_time_passes_list_by_post_id(
            ! empty( $post )? $post->ID: null,
            $time_passes_with_access,
            true
        );

        if ( count( $time_passes_list ) === 0 ) {
            return;
        }

        $has_sis_revenue = false;
        foreach ( $time_passes_list as $time_pass ) {
            if ( $time_pass['revenue_model'] === 'sis' ) {
                $has_sis_revenue = true;
                break;
            }
        }

        if ( ! $has_sis_revenue ) {
            return;
        }

        $content = $event->get_result();
        $link = 'https://web.laterpay.net/auth/user/login?_on_complete=' . urlencode( get_permalink() );
        if ( $time_passes_list ) {
            $content .= '<div><a class="lp_sis_notification" href="' . $link . '">' . __( 'I have a time pass', 'laterpay' ) . '</a></div>';
        }
        $event->set_result( $content );
    }

    /**
     * Render time pass HTML.
     *
     * @param array $pass
     *
     * @return string
     */
    public function render_time_pass( $pass = array() ) {
        $defaults = array(
            'pass_id'     => 0,
            'title'       => LaterPay_Helper_TimePass::get_default_options( 'title' ),
            'description' => LaterPay_Helper_TimePass::get_description(),
            'price'       => LaterPay_Helper_TimePass::get_default_options( 'price' ),
            'url'         => '',
        );

        $laterpay_pass = array_merge( $defaults, $pass );
        if ( ! empty( $laterpay_pass['pass_id'] ) ) {
            $laterpay_pass['url'] = LaterPay_Helper_TimePass::get_laterpay_purchase_link( $laterpay_pass['pass_id'] );
        }

        $laterpay_pass['preview_post_as_visitor'] = LaterPay_Helper_User::preview_post_as_visitor( get_post() );

        $args = array(
            'standard_currency' => get_option( 'laterpay_currency' ),
        );
        $this->assign( 'laterpay',      $args );
        $this->assign( 'laterpay_pass', $laterpay_pass );

        $string = $this->get_text_view( 'backend/partials/time-pass' );

        return $string;
    }

    /**
     * Get time passes that have access to the current posts.
     *
     * @return array of time pass ids with access
     */
    protected function get_time_passes_with_access() {
        $access                     = LaterPay_Helper_Post::get_access_state();
        $time_passes_with_access    = array();

        // get time passes with access
        foreach ( $access as $access_key => $access_value ) {
            // if access was purchased
            if ( $access_value === true ) {
                $access_key_exploded = explode( '_', $access_key );
                // if this is time pass key - store time pass id
                if ( $access_key_exploded[0] === LaterPay_Helper_TimePass::PASS_TOKEN ) {
                    $time_passes_with_access[] = $access_key_exploded[1];
                }
            }
        }

        return $time_passes_with_access;
    }

    /**
     * Save time pass info after purchase.
     *
     * @wp-hook template_reditect
     *
     * @return  void
     */
    public function buy_time_pass() {
        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( $_SERVER['REQUEST_METHOD'] ) : '';
        $request        = new LaterPay_Core_Request();
        $pass_id        = $request->get_param( 'pass_id' );
        $link           = $request->get_param( 'link' );
        if ( ! isset( $pass_id ) || ! isset( $link ) ) {
            return;
        }
        $id_currency    = $request->get_param( 'id_currency' );
        $price          = $request->get_param( 'price' );
        $date           = $request->get_param( 'date' );
        $ip             = $request->get_param( 'ip' );
        $revenue_model  = $request->get_param( 'revenue_model' );
        $voucher        = $request->get_param( 'voucher' );
        $hmac           = $request->get_param( 'hmac' );
        $lptoken        = $request->get_param( 'lptoken' );
        $pass_id = LaterPay_Helper_TimePass::get_untokenized_time_pass_id( $pass_id );
        $post_id = 0;
        $post    = get_post();
        if ( $post !== null ) {
            $post_id = $post->ID;
        }
        $client_options  = LaterPay_Helper_Config::get_php_client_options();
        $laterpay_client = new LaterPay_Client(
            $client_options['cp_key'],
            $client_options['api_key'],
            $client_options['api_root'],
            $client_options['web_root'],
            $client_options['token_name']
        );
        if ( LaterPay_Client_Signing::verify( $hmac, $laterpay_client->get_api_key(), $request->get_data( 'get' ), get_permalink(), $request_method ) ) {
            // check token
            if ( ! empty( $lptoken ) ) {
                $laterpay_client->set_token( $lptoken );
            } elseif ( ! $laterpay_client->has_token() ) {
                $laterpay_client->acquire_token();
            }

            $code = null;
            // process vouchers
            if ( ! LaterPay_Helper_Voucher::check_voucher_code( $voucher ) ) {
                if ( ! LaterPay_Helper_Voucher::check_voucher_code( $voucher, true ) ) {
                    // save the pre-generated gift code as valid voucher code now that the purchase is complete
                    $gift_cards = LaterPay_Helper_Voucher::get_time_pass_vouchers( $pass_id, true );
                    $gift_cards[ $voucher ] = array(
                        'price' => 0,
                        'title' => null,
                    );
                    LaterPay_Helper_Voucher::save_pass_vouchers( $pass_id, $gift_cards, true );
                    // set param for purchase history
                    $code = $voucher;
                    // set cookie to store information that gift card was purchased
                    setcookie(
                        'laterpay_purchased_gift_card',
                        $voucher . '|' . $pass_id,
                        time() + 30,
                        '/'
                    );
                } else {
                    // update gift code statistics
                    LaterPay_Helper_Voucher::update_voucher_statistic( $pass_id, $voucher, true );
                }
            } else {
                // update voucher statistics
                LaterPay_Helper_Voucher::update_voucher_statistic( $pass_id, $voucher );
            }
            // save payment history
            $data = array(
                'id_currency'   => $id_currency,
                'post_id'       => $post_id,
                'price'         => $price,
                'date'          => $date,
                'ip'            => $ip,
                'hash'          => $hmac,
                'revenue_model' => $revenue_model,
                'pass_id'       => $pass_id,
                'code'          => $code,
            );

            $payment_history_model = new LaterPay_Model_Payment_History();
            $payment_history_model->set_payment_history( $data );
        }
        wp_redirect( $link );
        // exit script after redirect was set
        exit;
    }

    /**
     * Modify the post content of paid posts.
     *
     * @wp-hook the_content
     *
     * @param LaterPay_Core_Event $event
     *
     * @return string $content
     */
    public function modify_post_content( LaterPay_Core_Event $event ) {
        if ( $event->has_argument( 'post' ) ) {
            $post = $event->get_argument( 'post' );
        } else {
            $post = get_post();
        }

        if ( $post === null ) {
            return;
        }

        $timepasses_positioned_manually = get_option( 'laterpay_time_passes_positioned_manually' );
        if ( $timepasses_positioned_manually ) {
            return;
        }
        $content = $event->get_result();

        $only_time_passes_allowed = get_option( 'laterpay_only_time_pass_purchases_allowed' );

        if ( $only_time_passes_allowed ) {
            $content .= laterpay_sanitize_output( __( 'Buy a time pass to read the full content.', 'laterpay' ) );
        }
        $time_pass_event = new LaterPay_Core_Event();
        $time_pass_event->set_echo( false );
        laterpay_event_dispatcher()->dispatch( 'laterpay_time_passes', $time_pass_event );
        $content .= $time_pass_event->get_result();

        $event->set_result( $content );
    }

    /**
     * Render time passes widget from shortcode [laterpay_time_passes].
     *
     * The shortcode [laterpay_time_passes] accepts two optional parameters:
     * introductory_text     additional text rendered at the top of the widget
     * call_to_action_text   additional text rendered after the time passes and before the voucher code input
     *
     * You can find the ID of a time pass on the pricing page on the left side of the time pass (e.g. "Pass 3").
     * If no parameters are provided, the shortcode renders the time pass widget w/o parameters.
     *
     * Example:
     * [laterpay_time_passes]
     * or:
     * [laterpay_time_passes call_to_action_text="Get yours now!"]
     *
     * @var array $atts
     * @param LaterPay_Core_Event $event
     *
     * @return string
     */
    public function render_time_passes_widget( LaterPay_Core_Event $event ) {
        list( $atts ) = $event->get_arguments();

        $data = shortcode_atts( array(
            'id'                  => null,
            'introductory_text'   => '',
            'call_to_action_text' => '',
        ), $atts );

        if ( isset( $data['id'] ) && ! LaterPay_Helper_TimePass::get_time_pass_by_id( $data['id'], true ) ) {
            $error_message = LaterPay_Helper_View::get_error_message( __( 'Wrong time pass id or no time passes specified.', 'laterpay' ), $atts );
            $event->set_result( $error_message );
            $event->stop_propagation();
            return;
        }

        // $introductory_text, $call_to_action_text, $time_pass_id
        $timepass_event = new LaterPay_Core_Event( array( $data['introductory_text'], $data['call_to_action_text'], $data['id'] ) );
        $timepass_event->set_echo( false );
        laterpay_event_dispatcher()->dispatch( 'laterpay_time_passes', $timepass_event );

        $html = $timepass_event->get_result();
        $event->set_result( $html );
    }

    /**
     * Ajax callback to render the statistics pane.
     *
     * @wp-hook wp_ajax_laterpay_post_statistic_render
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function ajax_render_tab_without_statistics( LaterPay_Core_Event $event ) {
        $statistic_form = new LaterPay_Form_Statistic( $_GET );

        $condition = array(
            'verify_nonce' => array(
                'action' => $statistic_form->get_field_value( 'action' ),
            )
        );
        $statistic_form->add_validation( 'nonce', $condition );

        if ( ! $statistic_form->is_valid() ) {
            $event->stop_propagation();
            return;
        }

        $post_id = $statistic_form->get_field_value( 'post_id' );
        if ( ! LaterPay_Helper_User::can( 'laterpay_read_post_statistics', $post_id ) ) {
            $event->stop_propagation();
            return;
        }

        $post = get_post( $post_id );
        // assign variables
        $view_args = array(
            'preview_post_as_visitor'   => LaterPay_Helper_User::preview_post_as_visitor( $post ),
            'hide_statistics_pane'      => LaterPay_Helper_User::statistics_pane_is_hidden(),
        );
        $this->assign( 'laterpay', $view_args );

        $event->set_result( $this->get_text_view( 'frontend/partials/post/select-preview-mode-tab' ) );
    }

    /**
     * Adding option to allow only time pass purchases.
     *
     * @since 0.9.11
     * @wp-hook admin_notices
     *
     * @return void
     */
    public function maybe_add_only_time_pass_purchase_option() {
        $current_version = get_option( 'laterpay_version' );
        if ( version_compare( $current_version, '0.9.11', '<' ) ) {
            return;
        }

        if ( get_option( 'laterpay_only_time_pass_purchases_allowed' ) === null ) {
            add_option( 'laterpay_only_time_pass_purchases_allowed', 0 );
        }
    }

    /**
     * Collect content of benefits overlay.
     *
     * @param LaterPay_Core_Event $event
     * @var string                $revenue_model       LaterPay revenue model applied to content
     *
     * @return array $overlay_content
     */
    public function on_purchase_overlay_content( LaterPay_Core_Event $event ) {
        $only_time_passes_allowed = get_option( 'laterpay_only_time_pass_purchases_allowed' );

        // determine overlay title to show
        if ( $only_time_passes_allowed ) {
            $overlay_title = __( 'Read Now', 'laterpay' );
            $overlay_benefits = array(
                array(
                    'title' => __( 'Buy Time Pass', 'laterpay' ),
                    'text'  => __( 'Buy a LaterPay time pass and pay with a payment method you trust.', 'laterpay' ),
                    'class' => 'lp_benefit--buy-now',
                ),
                array(
                    'title' => __( 'Read Immediately', 'laterpay' ),
                    'text'  => __( 'Immediately access your content. <br>A time pass is not a subscription, it expires automatically.', 'laterpay' ),
                    'class' => 'lp_benefit--use-immediately',
                ),
            );
            $overlay_content = array(
                'title'      => $overlay_title,
                'benefits'   => $overlay_benefits,
                'action'     => $this->get_text_view( 'frontend/partials/widget/time-passes-link' ),
            );
            $event->set_result( $overlay_content );
        }
    }

    /**
     * Hide purchase information if only time-passes are allowed
     *
     * @param LaterPay_Core_Event $event
     */
    public function check_only_time_pass_purchases_allowed( LaterPay_Core_Event $event ) {
        $only_time_passes_allowed = get_option( 'laterpay_only_time_pass_purchases_allowed' );
        if ( $only_time_passes_allowed ) {
            $event->stop_propagation();
        }
    }
}
