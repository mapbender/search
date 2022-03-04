;!(function() {
    window.Mapbender = Mapbender || {};
    window.Mapbender.Search = window.Mapbender.Search || {};

    var customColors = {
                '#777777': '#777777',
                '#337ab7': '#337ab7',
                '#5cb85c': '#5cb85c',
                '#5bc0de': '#5bc0de',
                '#f0ad4e': '#f0ad4e',
                '#d9534f': '#d9534f'
    };
    var dialogDefaults = {
        classes: {
            'ui-dialog': 'ui-dialog mb-search-dialog'
        },
        closeText: '',
        resizable: false,
        modal: true,
        buttons: [{
            text:  'Abbrechen',
            click: function() {
                $(this).dialog('destroy');
            }
        }],
        close: function() {
            $(this).dialog('destroy');
        }
    };

    window.Mapbender.Search.StyleEditor = function(owner, styleTemplate, styleMapTemplate) {
        this.owner = owner;
        this.styleTemplate = styleTemplate;
        this.styleMapTemplate = styleMapTemplate;
    };

    /**
     * @param {Object} data
     * @return {Promise}
     */
    window.Mapbender.Search.StyleEditor.prototype.editStyle = function(data) {
        var $content = $(document.createElement('div'))
            .append(this.styleTemplate)
        ;
        if (data && data.fontColor === null) {
            delete(data.fontColor);
        }
        Mapbender.Search.FormUtil.setData($content, data);
        $('.-js-colorpicker', $content).colorpicker({
            format: 'hex',
            colorSelectors: customColors
        });
        var deferred = $.Deferred();
        var dialogOptions = this.asyncDialogOptions_(deferred, {
            title: "Stylemanager",
            width: '500px'
        });
        dialogOptions.classes['ui-dialog'] = 'ui-dialog mb-search-dialog mb-search-style-dialog';
        dialogOptions.buttons.push({
                    text:  "Speichern",
                    click: function() {
                        if (Mapbender.Search.FormUtil.checkValidity($content)) {
                            var newData = Object.assign({}, Mapbender.Search.FormUtil.getData($content), {
                                id: (data || {}).id || null
                            });
                            deferred.resolve(newData);
                            $(this).dialog('destroy');
                        }
                    }
        });
        $content.dialog(dialogOptions);
        return deferred.promise();
    };
    Object.assign(window.Mapbender.Search.StyleEditor.prototype, {
        editStyleMap: function(styleMap, styles) {
            var self = this;
            var $content = $(document.createElement('div'))
                .append(this.styleMapTemplate)
            ;
            this.updateStyleSelects_($content, styles);
            $('[name="name"]', $content).val((styleMap || {}).name || 'Style map #' + Math.round(Math.random() * 10000));
            $('[name="style_default"]', $content).val(((styleMap || {}).styles || {}).default || '');
            $('[name="style_select"]', $content).val(((styleMap || {}).styles || {}).select || '');
            $content.on('click', '.-fn-edit-style[data-style-name]', function() {
                var styleId = $('select[name="' + $(this).attr('data-style-name') + '"]', $content).val();
                var style = styles[styleId];
                if (style) {
                    self.owner.openStyleEditor(style).then(function(styles) {
                        self.updateStyleSelects_($content, styles);
                    });
                } else {
                    $.notify("Bitte Style wählen!");
                }
            });
            var deferred = $.Deferred();
            var dialogOptions = this.asyncDialogOptions_(deferred, {
                title: 'Kartenstil'
            });
            dialogOptions.buttons.push({
                text: 'Speichern',
                click: function() {
                    if (Mapbender.Search.FormUtil.checkValidity(this)) {
                        var formDataRaw = Mapbender.Search.FormUtil.getData(this);
                        deferred.resolve({
                            id: (styleMap || {}).id || null,
                            name: formDataRaw.name,
                            styles: {
                                default: formDataRaw['style_default'],
                                select: formDataRaw['style_select']
                            }
                        });
                        $(this).dialog('destroy');
                    }
                }
            });
            $content.dialog(dialogOptions);
            return deferred.promise();
        },
        updateStyleSelects_: function(scope, styles) {
            var $selects = $('select[name^="style_"]', scope);

            for (var i = 0; i < $selects.length; ++i) {
                var $select = $selects.eq(i);
                var currentValue = $select.val();
                $select.empty().append('<option value=""></option>');
                $select.append(_.map(styles, function(style) {
                    var option = document.createElement('option');
                    $(option).text(style.name).attr('value', style.id);
                    return option;
                }));
                $select.val(currentValue || '');
            }
        },
        asyncDialogOptions_: function(deferred, options) {
            var extra = {
                buttons: [{
                    text: dialogDefaults.buttons[0].text,
                    click: function() {
                        deferred.reject();
                        return dialogDefaults.buttons[0].click.apply(this, arguments);
                    }
                }],
                close: function() {
                    deferred.reject();
                    return dialogDefaults.close.apply(this, arguments);
                }
            };
            return Object.assign({}, dialogDefaults, extra, options);
        }
    });
}());
