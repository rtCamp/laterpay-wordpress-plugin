<?php
/**
 * LaterPay post metabox controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */

class LaterPay_Controller_Admin_Post_Metabox extends LaterPay_Controller_Base
{
    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_subscribed_events()
     */
    public static function get_subscribed_events() {
        return array(
            'laterpay_meta_boxes' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'add_teaser_meta_box' ),
                array( 'add_pricing_meta_box' ),
            ),
            'laterpay_post_save' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'save_laterpay_post_data' ),
            ),
            'laterpay_attachment_edit' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'save_laterpay_post_data' ),
            ),
            'laterpay_transition_post_status' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'update_post_publication_date' ),
            ),
            'laterpay_admin_enqueue_styles_post_edit' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'load_assets' ),
            ),
            'laterpay_admin_enqueue_styles_post_new' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'load_assets' ),
            ),
            'wp_ajax_laterpay_reset_post_publication_date' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'laterpay_on_ajax_send_json', 0 ),
                array( 'reset_post_publication_date' ),
            ),
            'wp_ajax_laterpay_get_dynamic_pricing_data' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'laterpay_on_ajax_send_json', 0 ),
                array( 'get_dynamic_pricing_data' ),
            ),
            'wp_ajax_laterpay_remove_post_dynamic_pricing' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'laterpay_on_ajax_send_json', 0 ),
                array( 'remove_dynamic_pricing_data' ),
            ),
        );
    }

    /**
     * @see LaterPay_Core_View::load_assets()
     */
    public function load_assets() {
        parent::load_assets();
        $this->load_stylesheets();
        $this->load_scripts();
    }

    /**
     * Load page-specific CSS.
     *
     * @return void
     */
    public function load_stylesheets() {
        wp_register_style(
            'laterpay-post-edit',
            $this->config->get( 'css_url' ) . 'laterpay-post-edit.css',
            array(),
            $this->config->get( 'version' )
        );
        wp_enqueue_style( 'laterpay-post-edit' );
    }

    /**
     * Load page-specific JS.
     *
     * @return void
     */
    public function load_scripts() {
        wp_register_script(
            'laterpay-d3',
            $this->config->get( 'js_url' ) . '/vendor/d3.min.js',
            array(),
            $this->config->get( 'version' ),
            true
        );
        wp_register_script(
            'laterpay-d3-dynamic-pricing-widget',
            $this->config->get( 'js_url' ) . '/laterpay-dynamic-pricing-widget.js',
            array( 'laterpay-d3' ),
            $this->config->get( 'version' ),
            true
        );
        wp_register_script(
            'laterpay-velocity',
            $this->config->get( 'js_url' ) . 'vendor/velocity.min.js',
            array(),
            $this->config->get( 'version' ),
            true
        );
        wp_register_script(
            'laterpay-post-edit',
            $this->config->get( 'js_url' ) . '/laterpay-post-edit.js',
            array( 'laterpay-d3', 'laterpay-d3-dynamic-pricing-widget', 'laterpay-velocity', 'jquery' ),
            $this->config->get( 'version' ),
            true
        );
        wp_enqueue_script( 'laterpay-d3' );
        wp_enqueue_script( 'laterpay-d3-dynamic-pricing-widget' );
        wp_enqueue_script( 'laterpay-velocity' );
        wp_enqueue_script( 'laterpay-post-edit' );

        // pass localized strings and variables to scripts
        wp_localize_script(
            'laterpay-post-edit',
            'laterpay_post_edit',
            array(
                'ajaxUrl'                   => admin_url( 'admin-ajax.php' ),
                'globalDefaultPrice'        => (float) get_option( 'laterpay_global_price' ),
                'locale'                    => get_locale(),
                'i18nTeaserError'           => __( 'Paid posts require some teaser content. Please fill in the Teaser Content field.', 'laterpay' ),
                'i18nAddDynamicPricing'     => __( 'Add dynamic pricing', 'laterpay' ),
                'i18nRemoveDynamicPricing'  => __( 'Remove dynamic pricing', 'laterpay' ),
                'l10n_print_after'          => 'jQuery.extend(lpVars, laterpay_post_edit)',
            )
        );
        wp_localize_script(
            'laterpay-d3-dynamic-pricing-widget',
            'laterpay_d3_dynamic_pricing_widget',
            array(
                'currency'          => get_option( 'laterpay_currency' ),
                'i18nDefaultPrice'  => __( 'default price', 'laterpay' ),
                'i18nDays'          => __( 'days', 'laterpay' ),
                'i18nToday'         => __( 'Today', 'laterpay' ),
                'l10n_print_after'  => 'jQuery.extend(lpVars, laterpay_d3_dynamic_pricing_widget)',
            )
        );
    }

    /**
     * Add teaser content editor to add / edit post page.
     *
     * @wp-hook add_meta_boxes
     *
     * @return void
     */
    public function add_teaser_meta_box() {
        $post_types = $this->config->get( 'content.enabled_post_types' );

        foreach ( $post_types as $post_type ) {
            // add teaser content metabox below content editor
            add_meta_box(
                'lp_post-teaser',
                __( 'Teaser Content', 'laterpay' ),
                array( $this, 'render_teaser_content_box' ),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Add pricing edit box to add / edit post page.
     *
     * @wp-hook add_meta_boxes
     *
     * @return void
     */
    public function add_pricing_meta_box() {
        $post_types = $this->config->get( 'content.enabled_post_types' );

        foreach ( $post_types as $post_type ) {
            // add post price metabox in sidebar
            add_meta_box(
                'lp_post-pricing',
                __( 'Pricing for this Post', 'laterpay' ),
                array( $this, 'render_post_pricing_form' ),
                $post_type,
                'side',
                'high'
            );
        }
    }

    /**
     * Callback function of add_meta_box to render the editor for teaser content.
     *
     * @param WP_Post $post
     *
     * @return void
     */
    public function render_teaser_content_box( $post ) {
        if ( ! LaterPay_Helper_User::can( 'laterpay_edit_teaser_content', $post ) ) {
            $this->logger->warning(
                __METHOD__ . ' - current user can not edit teaser content',
                array(
                    'post'          => $post,
                    'current_user'  => wp_get_current_user(),
                )
            );
            return;
        }

        $settings = array(
            'wpautop'         => 1,
            'media_buttons'   => 1,
            'textarea_name'   => 'laterpay_post_teaser',
            'textarea_rows'   => 8,
            'tabindex'        => null,
            'editor_css'      => '',
            'editor_class'    => '',
            'teeny'           => 1,
            'dfw'             => 1,
            'tinymce'         => 1,
            'quicktags'       => 1,
        );
        $content = get_post_meta( $post->ID, 'laterpay_post_teaser', true );

        // prefill teaser content of existing posts on edit with automatically generated excerpt, if it's empty
        if ( ! $content ) {
            $content = LaterPay_Helper_Post::add_teaser_to_the_post( $post, null, false );
        }

        $editor_id = 'postcueeditor';

        echo laterpay_sanitize_output( '<dfn>' .
            __( 'Visitors will see the teaser content <strong>instead of the full content</strong> before purchase.', 'laterpay' ) . '<br>' .
            __( 'If you do not enter any teaser content, the plugin will use an excerpt of the full content as teaser content.', 'laterpay' ) . '<br>' .
            __( 'We do recommend to write dedicated teaser content to increase your sales though.', 'laterpay' ) .
        '</dfn>' );
        wp_editor( $content, $editor_id, $settings );
        echo laterpay_sanitized( '<input type="hidden" name="laterpay_teaser_content_box_nonce" value="' . wp_create_nonce( $this->config->get( 'plugin_base_name' ) ) . '" />' );
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
     * Callback for add_meta_box to render form for pricing of post.
     *
     * @param WP_Post $post
     *
     * @return void
     */
    public function render_post_pricing_form( $post ) {
        if ( ! LaterPay_Helper_User::can( 'laterpay_edit_individual_price', $post ) ) {
            $this->logger->warning(
                __METHOD__ . ' - current user can not edit individual price',
                array(
                    'post'          => $post,
                    'current_user'  => wp_get_current_user(),
                )
            );
            return;
        }

        $post_prices = get_post_meta( $post->ID, 'laterpay_post_prices', true );
        if ( ! is_array( $post_prices ) ) {
            $post_prices = array();
        }

        $post_default_category = array_key_exists( 'category_id',   $post_prices ) ? (int) $post_prices['category_id'] : 0;
        $post_revenue_model    = array_key_exists( 'revenue_model', $post_prices ) ? $post_prices['revenue_model'] : 'ppu';
        $post_status           = $post->post_status;

        // category default price data
        $category_price_data                    = null;
        $category_default_price_revenue_model   = null;
        $categories_of_post                     = wp_get_post_categories( $post->ID );
        if ( ! empty( $categories_of_post ) ) {
            $category_price_data = LaterPay_Helper_Pricing::get_category_price_data_by_category_ids( $categories_of_post );
            // if the post has a category defined, from which to use the category default price, then let's get that price
            if ( $post_default_category > 0 ) {
                $laterpay_category_model              = new LaterPay_Model_CategoryPrice();
                $category_default_price_revenue_model = (string) $laterpay_category_model->get_revenue_model_by_category_id( $post_default_category );
            }
        }

        // get price data
        $global_default_price               = get_option( 'laterpay_global_price' );
        $global_default_price_revenue_model = get_option( 'laterpay_global_price_revenue_model' );

        $price           = LaterPay_Helper_Pricing::get_post_price( $post->ID );
        $post_price_type = LaterPay_Helper_Pricing::get_post_price_type( $post->ID );

        // set post revenue model according to the selected price type
        if ( $post_price_type == LaterPay_Helper_Pricing::TYPE_CATEGORY_DEFAULT_PRICE ) {
            $post_revenue_model = $category_default_price_revenue_model;
        } elseif ( $post_price_type == LaterPay_Helper_Pricing::TYPE_GLOBAL_DEFAULT_PRICE ) {
            $post_revenue_model = $global_default_price_revenue_model;
        }

        // get valid price ranges
        $price_ranges = LaterPay_Helper_Pricing::get_price_ranges_by_revenue_model();

        echo laterpay_sanitized( '<input type="hidden" name="laterpay_pricing_post_content_box_nonce" value="' . wp_create_nonce( $this->config->plugin_base_name ) . '" />' );

        $view_args = array(
            'post_id'                              => $post->ID,
            'post_price_type'                      => $post_price_type,
            'post_status'                          => $post_status,
            'post_revenue_model'                   => $post_revenue_model,
            'price'                                => $price,
            'currency'                             => get_option( 'laterpay_currency' ),
            'category_prices'                      => $category_price_data,
            'post_default_category'                => (int) $post_default_category,
            'global_default_price'                 => $global_default_price,
            'global_default_price_revenue_model'   => $global_default_price_revenue_model,
            'category_default_price_revenue_model' => $category_default_price_revenue_model,
            'price_ranges'                         => json_encode( $price_ranges ),
        );

        $this->assign( 'laterpay', $view_args );

        $this->render( 'backend/partials/post-pricing-form' );
    }

    /**
     * Save LaterPay post data.
     *
     * @wp-hook save_post, edit_attachments
     *
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function save_laterpay_post_data( LaterPay_Core_Event $event ) {
        list( $post_id ) = $event->get_arguments() + array( '' );
        if ( ! $this->has_permission( $post_id ) ) {
            return;
        }

        // no post found -> do nothing
        $post = get_post( $post_id );
        if ( $post === null ) {
            throw new LaterPay_Core_Exception_PostNotFound( $post_id );
        }

        $post_form = new LaterPay_Form_Post( $_POST );
        $condition = array(
            'verify_nonce' => array(
                'action' => $this->config->get( 'plugin_base_name' ),
            )
        );
        $post_form->add_validation( 'laterpay_teaser_content_box_nonce', $condition );

        try {
            $post_form->validate();
        } catch ( LaterPay_Core_Exception_FormValidation $e ) {
            $context = array(
                'trace'  => $e->getTrace(),
                'form'   => 'LaterPay_Form_Post',
                'errors' => $post_form->get_errors(),
            );
            laterpay_get_logger()->error( $e->getMessage(), $context );
            return;
        }

        // no rights to edit laterpay_edit_teaser_content -> do nothing
        if ( LaterPay_Helper_User::can( 'laterpay_edit_teaser_content', $post_id ) ) {
            $teaser = $post_form->get_field_value( 'laterpay_post_teaser' );
            LaterPay_Helper_Post::add_teaser_to_the_post( $post, $teaser );
        }

        // no rights to edit laterpay_edit_individual_price -> do nothing
        if ( LaterPay_Helper_User::can( 'laterpay_edit_individual_price', $post_id ) ) {
            // postmeta values array
            $meta_values = array();

            // apply global default price, if pricing type is not defined
            $post_price_type = $post_form->get_field_value( 'post_price_type' );
            $type = $post_price_type ? $post_price_type : LaterPay_Helper_Pricing::TYPE_GLOBAL_DEFAULT_PRICE;
            $meta_values['type'] = $type;

            // apply (static) individual price
            if ( $type === LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_PRICE ) {
                $meta_values['price'] = $post_form->get_field_value( 'post-price' );
            }

            // apply revenue model
            if ( in_array( $type, array( LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_PRICE, LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE ) ) ) {
                $meta_values['revenue_model'] = $post_form->get_field_value( 'post_revenue_model' );
            }

            // apply dynamic individual price
            if ( $type === LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE ) {
                $start_price = $post_form->get_field_value( 'start_price' );
                $end_price   = $post_form->get_field_value( 'end_price' );

                if ( $start_price !== null && $end_price !== null ) {
                    list(
                         $meta_values['start_price'],
                         $meta_values['end_price'],
                         $meta_values['price_range_type']
                     ) = LaterPay_Helper_Pricing::adjust_dynamic_price_points( $start_price, $end_price );
                }

                if ( $post_form->get_field_value( 'change_start_price_after_days' ) ) {
                    $meta_values['change_start_price_after_days'] = $post_form->get_field_value( 'change_start_price_after_days' );
                }

                if ( $post_form->get_field_value( 'transitional_period_end_after_days' ) ) {
                    $meta_values['transitional_period_end_after_days'] = $post_form->get_field_value( 'transitional_period_end_after_days' );
                }

                if ( $post_form->get_field_value( 'reach_end_price_after_days' ) ) {
                    $meta_values['reach_end_price_after_days'] = $post_form->get_field_value( 'reach_end_price_after_days' );
                }
            }

            // apply category default price of given category
            if ( $type === LaterPay_Helper_Pricing::TYPE_CATEGORY_DEFAULT_PRICE ) {
                if ( $post_form->get_field_value( 'post_default_category' ) ) {
                    $category_id = $post_form->get_field_value( 'post_default_category' );
                    $meta_values['category_id'] = $category_id;
                }
            }

            $this->set_post_meta(
                'laterpay_post_prices',
                $meta_values,
                $post_id
            );
        }
    }

    /**
     * Set post meta data.
     *
     * @param string  $name meta name
     * @param string  $meta_value new meta value
     * @param integer $post_id post id
     *
     * @return bool|int false failure, post_meta_id on insert / update, or true on success
     */
    public function set_post_meta( $name, $meta_value, $post_id ) {
        if ( empty( $meta_value ) ) {
            return delete_post_meta( $post_id, $name );
        } else {
            return update_post_meta( $post_id, $name, $meta_value );
        }
    }

    /**
     * Update publication date of post during saving.
     *
     * @wp-hook publish_post
     *
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function update_post_publication_date( LaterPay_Core_Event $event ) {
        list( $status_after_update, $status_before_update, $post ) = $event->get_arguments() + array( '', '', '' );

        // skip on insufficient permission
        if ( ! $this->has_permission( $post->ID ) ) {
            return;
        }

        // only update publication date of posts with dynamic pricing
        if ( LaterPay_Helper_Pricing::get_post_price_type( $post->ID ) !== LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE ) {
            return;
        }

        // don't update publication date of already published posts
        if ( $status_before_update === LaterPay_Helper_Pricing::STATUS_POST_PUBLISHED ) {
            return;
        }

        // don't update publication date of unpublished posts
        if ( $status_after_update !== LaterPay_Helper_Pricing::STATUS_POST_PUBLISHED ) {
            return;
        }

        LaterPay_Helper_Pricing::reset_post_publication_date( $post );
    }

    /**
     * Reset post publication date.
     *
     * @wp-hook wp_ajax_laterpay_reset_post_publication_date
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function reset_post_publication_date( LaterPay_Core_Event $event ) {
        $event->set_result(
            array(
                'success' => false,
            )
        );

        if ( ! isset( $_POST['post_id'] ) ) {
            throw new LaterPay_Core_Exception_InvalidIncomingData( 'post_id' );
        }

        $post = get_post( sanitize_text_field( $_POST['post_id'] ) );
        if ( $post !== null ) {
            LaterPay_Helper_Pricing::reset_post_publication_date( $post );
            $event->set_result(
                array(
                    'success' => true,
                )
            );
            return;
        }
    }

    /**
     * Get dynamic pricing data.
     *
     * @wp-hook wp_ajax_laterpay_get_dynamic_pricing_data
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function get_dynamic_pricing_data( LaterPay_Core_Event $event ) {
        $dynamic_pricing_data_form = new LaterPay_Form_DynamicPricingData();
        $event->set_result(
            array(
                'success' => false,
            )
        );

        try {
            $dynamic_pricing_data_form->validate( $_POST );
        } catch ( LaterPay_Core_Exception_FormValidation $e ) {
            $context = array(
                'trace'  => $e->getTrace(),
                'form'   => 'LaterPay_Form_DynamicPricingData',
                'errors' => $dynamic_pricing_data_form->get_errors(),
            );
            laterpay_get_logger()->error( $e->getMessage(), $context );
            return;
        }


        $post         = get_post( $dynamic_pricing_data_form->get_field_value( 'post_id' ) );
        $post_price   = $dynamic_pricing_data_form->get_field_value( 'post_price' );

        $event->set_result(
            LaterPay_Helper_Pricing::get_dynamic_prices( $post, $post_price ) + array( 'success' => true, )
        );

        return;
    }

    /**
     * Remove dynamic pricing data.
     *
     * @wp-hook wp_ajax_laterpay_remove_post_dynamic_pricing
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function remove_dynamic_pricing_data( LaterPay_Core_Event $event ) {
        $event->set_result(
            array(
                'success' => false,
            )
        );

        if ( ! isset( $_POST['post_id'] ) || empty( $_POST['post_id'] ) ) {
            throw new LaterPay_Core_Exception_InvalidIncomingData( 'post_id' );
        }

        $post_id = sanitize_text_field( $_POST['post_id'] );
        $post_price = get_post_meta( $post_id, LaterPay_Helper_Pricing::META_KEY, true );
        unset( $post_price['price_range_type'] );
        unset( $post_price['start_price'] );
        unset( $post_price['end_price'] );
        unset( $post_price['reach_end_price_after_days'] );
        unset( $post_price['change_start_price_after_days'] );
        unset( $post_price['transitional_period_end_after_days'] );

        $this->set_post_meta(
            'laterpay_post_prices',
            $post_price,
            $post_id
        );

        $event->set_result(
            array(
                'success' => true,
            )
        );
        return;
    }
}
