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
        asPopup:  true,
        onSave:   null,
        onCancel: null
    },

    _create: function() {
        var widget = this;
        var options = widget.options;

        widget.render(options.data);

        if(options.asPopup) {
            widget.popup();
        }
    },

    render: function(data) {
        var widget = this;
        var element = widget.element;
        var options = widget.options;
        var styles = options.styles;
        var styleNames = _.object(_.pluck(styles,'id'),_.pluck(styles,'name'));
        var editButton = {
            type:  'button',
            title: 'Ändern',
            css:   {width: '20%'},
            click: function() {
                var button = $(this);
                var fieldSet = button.parent();
                var styleId = fieldSet.find("[name]").val();
                var style = styles[styleId]

                widget._trigger('editStyle', null, {
                    widget: widget,
                    style:  style
                });

                return false;
            }
        };

        element.html('');

        function onStyleChange(e) {
            var selectElement = $(this).find('select');
            var currentValue = selectElement.val();
            var selectElements = selectElement.closest("form").find("select");
            _.each(selectElements, function(element) {
                element = $(element);
                if(element.val() == null) {
                    element.val(currentValue);
                }
            });
        }

        element.generateElements({
            type:     'form',
            children: [{
                type:        'input',
                title:       'Name',
                placeholder: 'Name',
                name:        'name',
            }, {
                type:     'fieldSet',
                children: [{
                    type:    'select',
                    title:   'Standard',
                    options: styleNames,
                    name:    'styles[default]',
                    change:  onStyleChange,
                    css:     {width: '80%'}
                }, editButton]
            }, {
                type:     'fieldSet',
                children: [{
                    type:    'select',
                    title:   'Drüber',
                    options: styleNames,
                    name:    'styles[hover]',
                    change:  onStyleChange,
                    css:     {width: '80%'}
                }, editButton]
            }, {
                type:     'fieldSet',
                children: [{
                    type:    'select',
                    title:   'Selektiert',
                    options: styleNames,
                    name:    'styles[select]',
                    change:  onStyleChange,
                    css:     {width: '80%'}
                }, editButton]
            }]
        });

        window.setTimeout(function() {

            if(!data.name) {
                data.name = 'Style map #' + Math.round(Math.random() * 10000)
            }
            element.formData(data)
        }, 200);
    },

    updateStyleList: function(styles) {
        var widget = this;
        var element = widget.element;
        var formData = element.formData();
        widget._setOption("styles", styles);
        widget._trigger('stylesUpdated');
        widget.render(formData);
    },

    popup: function() {
        var widget = this;
        var element = widget.element;

        return element.popupDialog({
            title:   'Style map',
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