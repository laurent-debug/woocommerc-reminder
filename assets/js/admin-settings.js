(function($) {
    'use strict';

    function getPreviewMarkup(url) {
        return '<img src="' + url + '" alt="" style="max-width:150px;height:auto;" />';
    }

    $(function() {
        if ( typeof $.fn.wpColorPicker === 'function' ) {
            $('.wr-color-field').wpColorPicker();
        }

        $(document).on('click', '.wr-brand-logo-upload', function(event) {
            event.preventDefault();

            var $button    = $(this);
            var $container = $button.closest('.wr-brand-logo-field');
            var frame      = $container.data('wrMediaFrame');

            if ( frame ) {
                frame.open();
                return;
            }

            frame = wp.media({
                title: wrAdminSettings.mediaTitle,
                button: {
                    text: wrAdminSettings.mediaButton
                },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                var url        = attachment.url;

                if ( attachment.sizes ) {
                    if ( attachment.sizes.thumbnail ) {
                        url = attachment.sizes.thumbnail.url;
                    } else if ( attachment.sizes.medium ) {
                        url = attachment.sizes.medium.url;
                    }
                }

                $container.find('.wr-brand-logo-id').val(attachment.id);
                $container.find('.wr-brand-logo-preview').html(getPreviewMarkup(url));
                $container.find('.wr-brand-logo-remove').prop('disabled', false);
            });

            frame.on('open', function() {
                var selection   = frame.state().get('selection');
                var attachmentId = parseInt($container.find('.wr-brand-logo-id').val(), 10);

                if ( attachmentId ) {
                    var attachment = wp.media.attachment(attachmentId);

                    if ( attachment ) {
                        attachment.fetch();
                        selection.add(attachment);
                    }
                }
            });

            frame.open();
            $container.data('wrMediaFrame', frame);
        });

        $(document).on('click', '.wr-brand-logo-remove', function(event) {
            event.preventDefault();

            var $button    = $(this);
            var $container = $button.closest('.wr-brand-logo-field');

            $container.find('.wr-brand-logo-id').val('');
            $container.find('.wr-brand-logo-preview').html('<span class="description">' + wrAdminSettings.noLogo + '</span>');
            $button.prop('disabled', true);
        });
    });
})(jQuery);
