/**
 * Style map manager widget
 *
 * @author Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 */
$.widget("wheregroup.styleMapManager", {

    options: {
        styles:   [],
        messages: {
            save: {
                error:       'Speichern nicht möglich',
                successfull: 'Speichern erfolgreich'
            }
        },
        data:     {
            name: null,
            id:   null
        },
        template: ''
    },

    _create: function() {
        var widget = this;
        var options = widget.options;

        widget.render(options.data);
        widget.popup();
    },

    updateStyleSelects_: function(styles) {
        var $selects = $('select[name^="style_"]', this.element);

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

    render: function(data) {
        var widget = this;
        var element = widget.element;
        var options = widget.options;
        this.element.empty().append(this.options.template);
        this.updateStyleSelects_(this.options.styles);

        this.element.on('click', '.-fn-edit-style[data-style-name]', function() {
            var styleId = $('select[name="' + $(this).attr('data-style-name') + '"]', element).val();
            var style = options.styles[styleId];
            widget._trigger('editstyle', null, {
                widget: widget,
                style: style
            });
        });
        $('[name="name"]', this.element).val(data.name || 'Style map #' + Math.round(Math.random() * 10000));
        $('[name="style_default"]', this.element).val(data.styles && data.styles.default || '');
        $('[name="style_select"]', this.element).val(data.styles && data.styles.select || '');
    },

    updateStyleList: function(styles) {
        this.options.styles = styles;
        this.updateStyleSelects_(styles);
    },

    popup: function() {
        var widget = this;
        var element = widget.element;

        return element.dialog({
            title:   'Kartenstil',
            modal:   true,
            classes: {
                'ui-dialog': 'ui-dialog mb-search-dialog'
            },
            closeText: '',
            resizable: false,

            buttons: [{
                text:  'Abbrechen',
                click: function(e) {
                    widget.close();
                    return false;
                }
            }, {
                text:  'Speichern',
                click: function(e) {
                    if (Mapbender.Search.FormUtil.checkValidity(this)) {
                        var formDataRaw = Mapbender.Search.FormUtil.getData(this)
                        widget._trigger('submit', null, {
                            data: {
                                name: formDataRaw.name,
                                styles: {
                                    default: formDataRaw['style_default'],
                                    select: formDataRaw['style_select']
                                }
                            }
                        });
                    }
                    return false;
                }
            }]
        });
    },

    close: function() {
        this.element.dialog('destroy');
        this.element.remove();
    }
});
