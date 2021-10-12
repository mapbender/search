(function($) {



    /**
     * Style manager widget
     */
    $.widget('mapbender.featureStyleEditor', {
        options: {
            template: null,
            data: {},
            customColors: {
                '#777777': '#777777',
                '#337ab7': '#337ab7',
                '#5cb85c': '#5cb85c',
                '#5bc0de': '#5bc0de',
                '#f0ad4e': '#f0ad4e',
                '#d9534f': '#d9534f'
            }
        },

        /**
         * Generate StyleManagerForm
         */
        _create: function() {
            var widget = this;
            var element = $(widget.element);
            var options = widget.options;

            this.element.empty().append(this.options.template);
            window.setTimeout(function() {
                element.formData(options.data);
                $('.-js-colorpicker', element).colorpicker({
                    format: 'hex',
                    colorSelectors: options.customColors
                });
            }, 100);

            this.popup();
        },

        popup: function() {
            var widget = this;
            this.element.popupDialog({
                title:   "Stylemanager",
                modal:   true,
                width:   '500px',
                buttons: [{
                    text:  "Abbrechen",
                    click: function(e) {
                        widget.close();
                        return false;
                    }
                }, {
                    text:  "Speichern",
                    click: function(e) {
                        var form = $(e.currentTarget).closest(".ui-dialog");
                        widget._trigger('submit', null, {
                            form:   form,
                            widget: widget
                        });
                    }
                }]
            });
        },

        /**
         *
         * @private
         */
        close: function() {
            this.element.dialog("close");
        }
    });

})(jQuery);
