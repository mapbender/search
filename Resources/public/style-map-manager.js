/**
 * Style map manager widget
 *
 * @author Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 */
$.widget("wheregroup.styleMapManager", {

    options: {
        styles:     ['Style #1', 'Style #2'],
        styleNames: ["Default", 'Hover', 'Select'],
        onSave:     null,
        messages:   {
            save: {
                error:       'Speichern nicht möglich',
                successfull: 'Speichern erfolgreich'
            }
        },
        onCancel:   null
    },

    /**
     * Open style manager editor
     */
    openStyleManager: function() {
        var styleManagerContainer = $("<div/>");
        styleManagerContainer.featureStyleManager();
        styleManagerContainer.bind('featurestylemanagersubmit', function(e, fsm) {
            widget._trigger('styleChange', null, fsm);
        });
    },

    _create: function() {
        var widget = this;
        var options = widget.options;
        var styleMapPopup = $(widget.element);
        var styles = options.styles;
        var styleSelectors = [];
        var styleNames = options.styleNames;

        var saveMessage = "StyleMap gespeichert";

        styleSelectors.push({
            type:        'input',
            title:       'Name',
            placeholder: 'Stylemap name',
            name:        'name'
        });

        _.each(styleNames, function(name) {
            styleSelectors.push({
                type:     'fieldSet',
                children: [{
                    type:    'select',
                    title:   name,
                    options: styles,
                    name:    name,
                    change:  function(e) {
                        var selectElement = $(this).find('select');
                        var currentValue = selectElement.val();
                        var selectElements = selectElement.closest("form").find("select");
                        _.each(selectElements, function(element) {
                            element = $(element);
                            if(element.val() == null) {
                                element.val(currentValue);
                            }
                        });
                    },
                    css:     {width: '80%'}
                }, {
                    type:  'button',
                    title: 'Edit',
                    css:   {width: '20%'},
                    click: function() {
                        var button = $(this);
                        var fieldSet = button.parent();
                        var styleId = fieldSet.find("[name]").val();
                        console.log('Edit style #' + styles[styleId]);
                        widget.styleManager(styleId);
                        return false;
                    }
                }]
            })
        });

        styleMapPopup.generateElements({
            type:     'popup',
            title:    'Style map',
            children: [{
                type:     'form',
                children: styleSelectors
            }],
            buttons:  [{
                text:  'Style erstellen',
                click: function() {
                    $("<div/>").featureStyleEditor();
                    return false;
                }
            }, {
                text:  'Abbrechen',
                click: function() {
                    styleMapPopup.popupDialog('close');
                    return false;
                }
            }, {
                text:  'Speichern',
                click: function() {
                    widget.save();

                    $.notify(saveMessage, 'notice');
                    return false;
                }
            }]
        });
    },

    save: function() {
        var widget = this;
        var options = widget.options;
        var element = $(widget.element);

        if(options.onSave && typeof options.onSave == "function") {
            var result = options.onSave(element.formData());
            if(result) {
                widget.close();
            }
        }
    },

    close: function() {
        var widget = this;
        var element = $(widget.element);
        element.popupDialog('close');
    }
});