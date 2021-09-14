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
        template: '',
        asPopup:  true
    },

    _create: function() {
        var widget = this;
        var options = widget.options;

        widget.render(options.data);

        if(options.asPopup) {
            widget.popup();
        }
    },

    updateStyleSelects_: function(styles) {
        var $selects = $('select[name^="styles"]', this.element);

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
            var styleId = $('select[name="styles[' + $(this).attr('data-style-name') + ']"]', element).val();
            var style = options.styles[styleId];
            widget._trigger('editstyle', null, {
                widget: widget,
                style: style
            });
        });

        window.setTimeout(function() {
            if(!data.name) {
                data.name = 'Style map #' + Math.round(Math.random() * 10000);
            }
            for (var k in data.styles) {
                data['styles[' + k + ']'] = data.styles[k];
            }
            element.formData(data)
        }, 200);
    },

    updateStyleList: function(styles) {
        this.options.styles = styles;
        this.updateStyleSelects_(styles);
    },

    popup: function() {
        var widget = this;
        var element = widget.element;

        return element.popupDialog({
            title:   'Kartenstil',
            modal:   true,
            buttons: [{
                text:  'Abbrechen',
                click: function(e) {
                    widget.close();
                    return false;
                }
            }, {
                text:  'Speichern',
                click: function(e) {
                    widget._trigger('submit', null, {
                        form:   element,
                        widget: widget
                    });
                    return false;
                }
            }]
        });
    },

    close: function() {
        var widget = this;
        var element = $(widget.element);
        var options = widget.options;

        if(options.asPopup) {
            element.popupDialog("close");
        } else {
            widget.element.remove();
        }
    }
});
