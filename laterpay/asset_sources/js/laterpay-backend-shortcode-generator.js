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
     * On click event of color field.
     *
     * @return void
     */
    colorbox_on_action: function () {

        if ( 'object' !== typeof tinymce ) {
            return;
        }

        var editor = tinymce.activeEditor;

        var colorPickerCallback = editor.settings.color_picker_callback;

        if ( colorPickerCallback ) {
            return function () {
                var self = this;

                colorPickerCallback.call(
                    editor,
                    function ( value ) {
                        self.value( value ).fire( 'change' );
                    },
                    self.value()
                );
            };
        }
    },

    /**
     * Callback of on click media button.
     * To open WordPress media library and set URL as value when user select image.
     *
     * @return void
     */
    onclick_media_button: function () {
        var field = this;
        var frame = wp.media();

        frame.on( 'select', function () {

            var attachment = frame.state().get( 'selection' ).first().toJSON();

            if ( 'object' !== typeof attachment ) {
                return;
            }

            var image_element_id = 'image_' + field._name;
            var image_element = jQuery( '#' + image_element_id, this.$el );

            field.state.data.value = attachment.url;

            if ( !image_element || 0 === image_element.length ) {

                // Create element.
                image_element = document.createElement( 'IMG' );

                image_element.setAttribute( 'id', image_element_id );
                image_element.setAttribute( 'src', attachment.url );
                image_element.setAttribute( 'style', 'display: block; width: 150px; height: 150px; margin: 20px;' );

                field.$el.append( image_element );

            } else {
                jQuery( image_element ).attr( 'src', attachment.url );
            }

        } );

        frame.open();
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
                                height: 430,
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
                                        type: 'button',
                                        name: 'teaser_image_path',
                                        label: 'Teaser Image Path',
                                        text: 'Select Media',
                                        onclick: self.onclick_media_button,
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
                                height: 400,
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
                                        type: 'colorbox',
                                        name: 'button_background_color',
                                        label: 'Button background color',
                                        value: '#01a99d',
                                        onaction: self.colorbox_on_action,
                                    },
                                    {
                                        type: 'colorbox',
                                        name: 'button_text_color',
                                        label: 'Button text color',
                                        value: '#ffffff',
                                        onaction: self.colorbox_on_action,
                                    },
                                    {
                                        type: 'button',
                                        name: 'custom_image_path',
                                        label: 'Custom image path',
                                        text: 'Select Image',
                                        onclick: self.onclick_media_button,
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
                                height: 400,
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
                                        text: 'Select Image',
                                        onclick: self.onclick_media_button,
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
