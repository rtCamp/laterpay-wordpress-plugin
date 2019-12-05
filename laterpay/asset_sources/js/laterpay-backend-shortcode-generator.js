/**
 * To add TinyMCE drop down for shortcode generator.
 *
 * @package laterpay
 */

'use strict';

var laterpay_shortcode_genrator = {

    /**
     * Init function,
     * To register function to add dropdown for shortcode generator.
     *
     * @return void
     */
    init: function () {

        if ( 'object' !== typeof tinymce ) {
            return;
        }

        tinymce.PluginManager.add( 'laterpay_shortcode_generator', this.register_dropdown );
    },

    /**
     * To add dropdown for shortcode generator.
     *
     * @param {object} editor Object of TinyMCE editor.
     * @param {string} url Directory URL of current file.
     *
     * @return void
     */
    register_dropdown: function ( editor, url ) {
        editor.addButton(
            'laterpay_shortcode_generator',
            {
                text: 'LaterPay ShortCodes',
                icon: false,
                type: 'menubutton',
                menu: [
                    {
                        text: 'Download Box',
                        onclick: function () {
                            editor.insertContent( '[laterpay_premium_download target_post_id="" heading_text="" description_text="" teaser_image_path=""]]' );
                        }
                    },
                    {
                        text: 'Time-pass purchase button',
                        onclick: function () {
                            editor.insertContent( '[laterpay_time_passes call_to_action_text="Get yours now!"]' );
                        }
                    },
                    {
                        text: 'Subscription purchase button',
                        onclick: function () {
                            editor.insertContent( '[laterpay_subscription_purchase id="5" button_background_color="blue" button_text_color="black" button_text="Purchase Now!"]' );
                        }
                    },
                ]
            }
        );
    },

};

laterpay_shortcode_genrator.init();
