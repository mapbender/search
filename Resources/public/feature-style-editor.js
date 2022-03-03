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
            if (this.options.data && this.options.data.fontColor === null) {
                delete(this.options.data.fontColor);
            }

            this.element.empty().append(this.options.template);
            Mapbender.Search.FormUtil.setData(this.element, this.options.data);
            $('.-js-colorpicker', this.element).colorpicker({
                format: 'hex',
                colorSelectors: this.options.customColors
            });
            this.popup();
        },

        popup: function() {
            var widget = this;
            this.element.dialog({
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
                }],
                classes: {
                    'ui-dialog': 'ui-dialog mb-search-dialog mb-search-style-dialog'
                },
                closeText: '',
                resizable: false,
                close: function() {
                    $(this).dialog('destroy');
                }
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
