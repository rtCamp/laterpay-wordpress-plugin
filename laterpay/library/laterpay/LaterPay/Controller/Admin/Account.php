<?php

class LaterPay_Controller_Admin_Account extends LaterPay_Controller_Abstract
{

	/**
	 * @see LaterPay_Controller_Abstract::load_assets
	 */
	public function load_assets() {
        parent::load_assets();
        global $laterpay_version;

        // load page-specific JS
        wp_register_script(
            'laterpay-backend-account',
            LATERPAY_ASSETS_PATH . '/js/laterpay-backend-account.js',
            array( 'jquery' ),
            $laterpay_version,
            true
        );
        wp_enqueue_script( 'laterpay-backend-account' );

        // pass localized strings and variables to script
        wp_localize_script(
            'laterpay-backend-account',
            'lpVars',
            array(
                'i18nApiKeyInvalid'         => __( 'The API key you entered is not a valid LaterPay API key! ', 'laterpay' ),
                'i18nMerchantIdInvalid'     => __( 'The Merchant ID you entered is not a valid LaterPay Merchant ID! ', 'laterpay' ),
                'i18nLiveApiDataRequired'   => __( 'Switching into Live mode requires a valid Live Merchant ID and Live API Key.', 'laterpay' ),
                'i18nPreventUnload'         => __( 'LaterPay does not work properly with invalid API credentials.', 'laterpay' ),
            )
        );
    }

	/**
	 * @see LaterPay_Controller_Abstract::render_page
	 */
    public function render_page() {
        $this->load_assets();

        $this->assign( 'sandbox_merchant_id',    get_option( 'laterpay_sandbox_merchant_id' ) );
        $this->assign( 'sandbox_api_key',        get_option( 'laterpay_sandbox_api_key' ) );
        $this->assign( 'live_merchant_id',       get_option( 'laterpay_live_merchant_id' ) );
        $this->assign( 'live_api_key',           get_option( 'laterpay_live_api_key' ) );
        $this->assign( 'plugin_is_in_live_mode', get_option( 'laterpay_plugin_is_in_live_mode' ) == 1 );
        $this->assign( 'top_nav',                $this->get_menu() );

        $this->render( 'backend/tabs/account' );
    }

    /**
     * Process Ajax requests from account tab
     *
     * @return void
     */
    public static function process_ajax_requests() {
        if ( isset( $_POST['form'] ) ) {
            // check for required privileges to perform action
            if ( ! LaterPay_Helper_User::can( 'laterpay_edit_plugin_settings' ) ) {
                echo Zend_Json::encode(
                    array(
                        'success' => false,
                        'message' => __( 'You don´t have sufficient user privileges to do this.', 'laterpay' )
                    )
                );
                die;
            }

            if ( function_exists( 'check_admin_referer' ) ) {
                check_admin_referer( 'laterpay_form' );
            }

            switch ( $_POST['form'] ) {
                case 'laterpay_sandbox_merchant_id':
                    self::_update_sandbox_merchant_id();
                    break;

                case 'laterpay_sandbox_api_key':
                    self::_update_sandbox_api_key();
                    break;

                case 'laterpay_live_merchant_id':
                    self::_update_live_merchant_id();
                    break;

                case 'laterpay_live_api_key':
                    self::_update_live_api_key();
                    break;

                case 'plugin_is_in_live_mode':
                    self::_update_plugin_mode();
                    break;

                default:
                    echo Zend_Json::encode(
                        array(
                            'success' => false,
                            'message' => __( 'An error occurred when trying to save your settings. Please try again.', 'laterpay' )
                        )
                    );
                    die;
            }
        }
    }

    /**
     * Update LaterPay Sandbox Merchant ID, required for making test transactions against Sandbox environment
     *
     * @return void
     */
    protected static function _update_sandbox_merchant_id() {
        $sandbox_merchant_id = $_POST['laterpay_sandbox_merchant_id'];

        if ( self::is_valid_merchant_id( $sandbox_merchant_id ) ) {
            update_option( 'laterpay_sandbox_merchant_id', $sandbox_merchant_id );
            echo Zend_Json::encode(
                array(
                    'success' => true,
                    'message' => __( 'Sandbox Merchant ID verified and saved.', 'laterpay' )
                )
            );
        } elseif ( strlen( $sandbox_merchant_id ) == 0 ) {
            update_option( 'laterpay_sandbox_merchant_id', '' );
            echo Zend_Json::encode(
                array(
                    'success' => true,
                    'message' => __( 'The Sandbox Merchant ID has been removed.', 'laterpay' )
                )
            );
        } else {
            echo Zend_Json::encode(
                array(
                    'success' => false,
                    'message' => __( 'The Merchant ID you entered is not a valid LaterPay Sandbox Merchant ID! ', 'laterpay' )
                )
            );
        }
        die;
    }

    /**
     * Update LaterPay Sandbox API Key, required for making test transactions against Sandbox environment
     *
     * @return void
     */
    protected static function _update_sandbox_api_key() {
        $sandbox_api_key = $_POST['laterpay_sandbox_api_key'];

        if ( self::is_valid_api_key( $sandbox_api_key ) ) {
            update_option( 'laterpay_sandbox_api_key', $sandbox_api_key );
            echo Zend_Json::encode(
                array(
                    'success' => true,
                    'message' => __( 'Your Sandbox API key is valid. You can now make TEST transactions.', 'laterpay' )
                )
            );
        } elseif ( strlen( $sandbox_api_key ) == 0 ) {
            update_option( 'laterpay_sandbox_api_key', '' );
            echo Zend_Json::encode(
                array(
                    'success' => true,
                    'message' => __( 'The Sandbox API key has been removed.', 'laterpay' )
                )
            );
        } else {
            echo Zend_Json::encode(
                array(
                    'success' => false,
                    'message' => __( 'The API key you entered is not a valid LaterPay Sandbox API key! ', 'laterpay' )
                )
            );
        }
        die;
    }

    /**
     * Update LaterPay Live Merchant ID, required for making real transactions against production environment
     *
     * @return void
     */
    protected static function _update_live_merchant_id() {
        $live_merchant_id = $_POST['laterpay_live_merchant_id'];

        if ( self::is_valid_merchant_id( $live_merchant_id ) ) {
            update_option( 'laterpay_live_merchant_id', $live_merchant_id );
            echo Zend_Json::encode(
                array(
                    'success' => true,
                    'message' => __( 'Live Merchant ID verified and saved.', 'laterpay' )
                )
            );
        } elseif ( strlen( $live_merchant_id ) == 0 ) {
            update_option( 'laterpay_live_merchant_id', '' );
            echo Zend_Json::encode(
                array(
                    'success' => true,
                    'message' => __( 'The Live Merchant ID has been removed.', 'laterpay' )
                )
            );
        } else {
            echo Zend_Json::encode(
                array(
                    'success' => false,
                    'message' => __( 'The Merchant ID you entered is not a valid LaterPay Live Merchant ID! ', 'laterpay' )
                )
            );
        }
        die;
    }

    /**
     * Update LaterPay Live API Key, required for making real transactions against production environment
     *
     * @return void
     */
    protected static function _update_live_api_key() {
        $live_api_key = $_POST['laterpay_live_api_key'];

        if ( self::is_valid_api_key( $live_api_key ) ) {
            update_option( 'laterpay_live_api_key', $live_api_key );
            echo Zend_Json::encode(
                array(
                    'success' => true,
                    'message' => __( 'Live API key verified and saved. You can now make REAL transactions.', 'laterpay' )
                )
            );
        } elseif ( strlen( $live_api_key ) == 0 ) {
            update_option( 'laterpay_live_api_key', '' );
            echo Zend_Json::encode(
                array(
                    'success' => true,
                    'message' => __( 'The Live API key has been removed.', 'laterpay' )
                )
            );
        } else {
            echo Zend_Json::encode(
                array(
                    'success' => false,
                    'message' => __( 'The API key you entered is not a valid LaterPay Live API key! ', 'laterpay' )
                )
            );
        }
        die;
    }

    /**
     * Update LaterPay Plugin Mode
     *
     * @return void
     */
    protected static function _update_plugin_mode() {
        $result = update_option( 'laterpay_plugin_is_in_live_mode', $_POST['plugin_is_in_live_mode'] );
        if ( $result ) {
            if ( get_option( 'laterpay_plugin_is_in_live_mode' ) ) {
                echo Zend_Json::encode(
                    array(
                        'success' => true,
                        'message' => __( 'The LaterPay plugin is in LIVE mode now. All payments are actually booked and credited to your account.', 'laterpay' ),
                    )
                );
            } else {
                echo Zend_Json::encode(
                    array(
                        'success' => true,
                        'message' => __( 'The LaterPay plugin is in TEST mode now. Payments are only simulated and not actually booked.', 'laterpay' ),
                    )
                );
            }
        } else {
            echo Zend_Json::encode(
                array(
                    'success' => false,
                    'message' => __( 'An error occurred when trying to save your settings. Please try again.', 'laterpay' ),
                )
            );
        }
        die;
    }


	/**
	 * Validate format of LaterPay Merchant ID
	 *
	 * @param   string|int $merchant_id
     *
	 * @return  int
	 */
    public static function is_valid_merchant_id( $merchant_id ) {
        return preg_match( '/[a-zA-Z0-9]{22}/', $merchant_id );
    }

    /**
     * Validate format of LaterPay API key
     *
     * @param   string|int $api_key
     *
     * @return  int
     */
    public static function is_valid_api_key( $api_key ) {
        return preg_match( '/[a-z0-9]{32}/', $api_key );
    }

}
