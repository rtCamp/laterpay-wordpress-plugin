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

        var self = this;

        tinymce.PluginManager.add( 'laterpay_shortcode_generator', function ( editor, url ) {
            self.register_dropdown( editor, url );
        } );

    },

    /**
     * To convert object into string.
     *
     * @param {object} object Object that need to convert.
     *
     * @return {string} Converted string.
     */
    object_to_string: function ( object ) {

        if ( 'object' !== typeof object ) {
            return '';
        }

        var string = '',
            index = 0;

        for ( index in object ) {
            string += index + '="' + object[ index ] + '" ';
        }

        return string;
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

        var self = this;

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
                            // Open window
                            editor.windowManager.open( {
                                title: 'LaterPay Premium Download',
                                width: 512,
                                height: 272,
                                body: [
                                    {
                                        type: 'textbox',
                                        name: 'target_post_id',
                                        label: 'Post ID'
                                    },
                                    {
                                        type: 'textbox',
                                        name: 'target_post_title',
                                        label: 'Post Title'
                                    },
                                    {
                                        type: 'textbox',
                                        name: 'heading_text',
                                        label: 'Heading Title',
                                        value: 'Additional Premium Content'
                                    },
                                    {
                                        type: 'textbox',
                                        name: 'description_text',
                                        label: 'Description text'
                                    },
                                    {
                                        type: 'listbox',
                                        name: 'content_type',
                                        label: 'Content Type',
                                        values: [
                                            { text: 'application/zip', value: 'application/zip' },
                                            {
                                                text: 'application/x-rar-compressed',
                                                value: 'application/x-rar-compressed'
                                            },
                                            { text: 'application/pdf', value: 'application/pdf' },
                                            { text: 'image/jpeg', value: 'image/jpeg' },
                                            { text: 'image/png', value: 'image/png' },
                                            { text: 'image/gif', value: 'image/gif' },
                                            { text: 'audio/vnd.wav', value: 'audio/vnd.wav' },
                                            { text: 'audio/mpeg', value: 'audio/mpeg' },
                                            { text: 'audio/mp4', value: 'audio/mp4' },
                                            { text: 'audio/ogg', value: 'audio/ogg' },
                                            { text: 'audio/aac', value: 'audio/aac' },
                                            { text: 'audio/aacp', value: 'audio/aacp' },
                                            { text: 'video/mpeg', value: 'video/mpeg' },
                                            { text: 'video/mp4', value: 'video/mp4' },
                                            { text: 'video/quicktime', value: 'video/quicktime' },
                                        ]
                                    },
                                    {
                                        type: 'textbox',
                                        name: 'teaser_image_path',
                                        label: 'Teaser Image Path'
                                    },
                                ],
                                onsubmit: function ( e ) {
                                    var values = self.object_to_string( e.data );
                                    var shortcode = '[laterpay_premium_download ' + values + ' ]';
                                    editor.insertContent( shortcode );
                                }
                            } );

                        }
                    },
                    {
                        text: 'Time-pass purchase button',
                        onclick: function () {

                            editor.windowManager.open( {
                                title: 'Time-pass purchase button',
                                width: 512,
                                height: 230,
                                body: [
                                    {
                                        type: 'textbox',
                                        name: 'id',
                                        label: 'ID'
                                    },
                                    {
                                        type: 'textbox',
                                        name: 'button_text',
                                        label: 'Button Text'
                                    },
                                    {
                                        type: 'textbox',
                                        name: 'button_background_color',
                                        label: 'Button background color',
                                        value: '#01a99d',
                                    },
                                    {
                                        type: 'textbox',
                                        name: 'button_text_color',
                                        label: 'Button text color',
                                        value: '#ffffff',
                                    },
                                    {
                                        type: 'textbox',
                                        name: 'custom_image_path',
                                        label: 'Custom image path',
                                    },
                                ],
                                onsubmit: function ( e ) {
                                    var values = self.object_to_string( e.data );
                                    var shortcode = '[laterpay_time_pass_purchase ' + values + ' ]';
                                    editor.insertContent( shortcode );
                                }
                            } );

                        }
                    },
                    {
                        text: 'Subscription purchase button',
                        onclick: function () {

                            editor.windowManager.open( {
                                title: 'Subscription purchase button',
                                width: 512,
                                height: 230,
                                body: [
                                    {
                                        type: 'textbox',
                                        name: 'id',
                                        label: 'ID'
                                    },
                                    {
                                        type: 'textbox',
                                        name: 'button_text',
                                        label: 'Button Text'
                                    },
                                    {
                                        type: 'textbox',
                                        name: 'button_background_color',
                                        label: 'Button background color',
                                        value: '#01a99d',
                                    },
                                    {
                                        type: 'textbox',
                                        name: 'button_text_color',
                                        label: 'Button text color',
                                        value: '#ffffff',
                                    },
                                    {
                                        type: 'textbox',
                                        name: 'custom_image_path',
                                        label: 'Custom image path',
                                    },
                                ],
                                onsubmit: function ( e ) {
                                    var values = self.object_to_string( e.data );
                                    var shortcode = '[laterpay_subscription_purchase ' + values + ' ]';
                                    editor.insertContent( shortcode );
                                }
                            } );

                        }
                    },
                ]
            }
        );
    },

};

laterpay_shortcode_genrator.init();
