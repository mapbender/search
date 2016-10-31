/**
 * Created by ransomware on 27/09/16.
 * Released under the MIT license.
 */
$.widget("rw.queryManager", {

    version: "1.0.1",

    options: {
        query:   null,
        sources: []
    },

    onFormError: null,
    onOpen:      null,
    onClose:     null,

    callBackMapping: {
        "open":      "onOpen",
        "close":     "onClose",
        "formError": "onFormError",
        "ready":     "onReady"
    },

    eventMap: {},

    /**
     * Current source (Feature type description)
     */
    currentSource: null,

    /**
     * Contstrants operators
     */
    constraintsOperators: [">", "<", ">=", "<=", "==", "!=", "LIKE", "NOT LIKE"],

    /**
     * Constructor
     *
     * @private
     */
    _create: function() {
        var widget = this;
        var options = this.options;

        _.extend(this, EventDispatcher);

        widget.el = widget._getDiv();
        widget.showPopup();
    },

    /**
     * Capitalize
     *
     * @param string
     * @returns {string}
     * @private
     */
    _capitalizeFirstCharacter: function(string) {
        return string && string.length > 0 ? string.charAt(0).toUpperCase() + string.slice(1) : string;
    },

    _extractEvents: function(key, value) {
        var defaultMapping = this.callBackMapping;
        for (var prop in defaultMapping) {
            if(defaultMapping.hasOwnProperty(prop)) {
                var eventKey = "on" + this._capitalizeFirstCharacter(prop);
                if(key === prop || key === eventKey) {
                    this[eventKey] = value;
                }
            }
        }
    },

    _setOption: function(key, value) {
        this._extractEvents(key, value);
        this._super(key, value);
    },

    _has: function(obj, prop) {
        return obj && obj[prop] !== undefined;
    },

    changeSource: function(featureTypeId) {
        var widget = this;
        var featureTypeDescriptions = widget.option('featureTypeDescriptions');
        var currentSource = widget.currentSource = featureTypeDescriptions[featureTypeId];

        widget._trigger('changeSource', null, {
            widget:                 widget,
            featureTypeDeclaration: currentSource
        });

        return currentSource;
    },

    getForm: function() {
        var widget = this;
        var featureTypeDescriptions = widget.option('featureTypeDescriptions');
        var featureTypeId = _.keys(featureTypeDescriptions)[0];
        var currentSource = widget.changeSource(featureTypeId);
        var constraintsOperators = widget.constraintsOperators;

        var fieldsTableDataGen = function(options) {
            return {
                title:     "<input type='text' placeholder='" + options.placeholder + "'>",
                fieldName: options.fieldName
            };
        };

        function constraintsTableDataGen(options) {
            var selectOptions = "";

            if(options.selectOptions) {
                options.selectOptions.forEach(function(val, i) {
                    selectOptions += '<option value="' + i + '">' + val + '</option>';
                })
            }

            return {
                fieldName: options.fieldName,
                operator:  "<select>" + selectOptions + "</select>",
                value:     "<input type='text' placeholder='" + options.placeholder + "'>",
                action:    "<button class='button' title='" + options.fieldName + "'><i class='fa fa-bars'></i></button>"
            };
        }

        return widget.el.generateElements({
            type:     "tabs",
            children: [widget._getForm({
                title:    "Allgemein",
                children: [{
                    type:        "input",
                    name:        "name",
                    placeholder: "Eindeutige Name",
                    title:       "Name",
                    mandatory:   true
                }, {
                    type:    "select",
                    name:    "featureType",
                    title:   "Feature type",
                    value:   featureTypeId,
                    options: _.object(_.keys(featureTypeDescriptions), _.pluck(featureTypeDescriptions, 'title')),
                    change:  function(e) {
                        var featureTypeId = $('select', e.currentTarget).val();
                        currentSource = widget.changeSource(featureTypeId)
                    }
                }, {
                    type:     "fieldSet",
                    children: [{
                        type:    "select",
                        name:    "styleMap",
                        title:   "Style",
                        value:   0,
                        options: ['StyleMap #1', 'StyleMap #2', 'StyleMap #3', 'StyleMap #4'],
                        css:     {
                            width: "90%"
                        }
                    }, {
                        type:     "button",
                        name:     "buttonExtendInputStyle",
                        cssClass: "bars",
                        title:    "Edit",
                        click:    function(e) {
                            return false;
                        },
                        css:      {
                            width: "10%"
                        }
                    }]
                }, {
                    type:        "checkbox",
                    name:        "extentOnly",
                    placeholder: "Extent only",
                    title:       "Extent only",
                    checked:     true

                }]
            }, true), widget._getForm({
                title:    "Felder",
                children: [{

                    html: $('<div/>').resultTable({
                        lengthChange: false,
                        searching:    false,
                        info:         false,
                        paging:       false,
                        columns:      [{
                            data:  'fieldName',
                            title: 'Field Name'
                        }, {
                            data:  'title',
                            title: 'Title'
                        }],
                        data:         [fieldsTableDataGen({
                            placeholder: "Name",
                            fieldName:   "name"
                        }), fieldsTableDataGen({
                            placeholder: "Beschreibung",
                            fieldName:   "description"
                        }), fieldsTableDataGen({
                            placeholder: "Entfernung",
                            fieldName:   "km"
                        })],
                        buttons:      [{
                            title:     "",
                            className: "fa fa-bars"
                        }]

                    })
                }, {
                    type:     "button",
                    cssClass: "plus",
                    title:    "Feld hinzufügen", // tran
                    click:    function(e) {
                        var addFieldDialog = $("<div/>");
                        addFieldDialog.generateElements({
                            type:     'fieldSet',
                            children: [{
                                type:      "select",
                                title:     "Field name",
                                name:      "fieldName",
                                options:   currentSource.fieldNames,
                                mandatory: true,
                                change:    function(e) {
                                    var fieldName = addFieldDialog.formData().fieldName;
                                    addFieldDialog.formData({title: fieldName})
                                },
                                css:       {width: "40%"}
                            }, {
                                type:  "input",
                                title: "Title (alias)",
                                name:  "title",
                                css:   {width: "60%"}
                            }]
                        });

                        addFieldDialog.popupDialog({
                            title:   "Feldbenennung",
                            width:   500,
                            buttons: [{
                                text:  "Add",
                                click: function(e) {
                                    console.log(addFieldDialog.formData());
                                }
                            }]
                        });
                        return false;
                    }
                }]
            }), widget._getForm({
                title:    "Bedingungen",
                children: [{
                    html: $('<div/>').resultTable({
                        lengthChange: false,
                        searching:    false,
                        info:         false,
                        paging:       false,
                        columns:      [{
                            data:  'fieldName',
                            title: 'Feldname'
                        }, {
                            data:  'operator',
                            title: 'Operator'
                        }, {
                            data:  'value',
                            title: 'Wert'
                        }, {
                            data:  'action',
                            title: 'Aktion'
                        }],
                        data:         [constraintsTableDataGen({
                            placeholder:   "mustermann",
                            fieldName:     "name",
                            selectOptions: constraintsOperators
                        }), constraintsTableDataGen({
                            placeholder:   "about",
                            fieldName:     "description",
                            selectOptions: constraintsOperators
                        }), constraintsTableDataGen({
                            placeholder:   20,
                            fieldName:     "km",
                            selectOptions: constraintsOperators
                        })]
                    })
                }, {
                    type:     "button",
                    name:     "buttonAddCondition",
                    cssClass: "plus",
                    title:    "Neue Bedingung",
                    click:    function(e) {
                        var el = $(e.currentTarget);
                        var form = el.closest('.popup-dialog');
                        var conditionForm = $("<div/>");
                        conditionForm.generateElements({
                            type:     'fieldSet',
                            children: [{
                                title:     "Field",
                                type:      "select",
                                name:      "fieldName",
                                options:   currentSource.fieldNames,
                                mandatory: true,
                                css:       {width: "40%"}
                            }, {
                                title:     "Operator",
                                type:      "select",
                                name:      "operator",
                                options:   currentSource.operators,
                                mandatory: true,
                                css:       {width: "20%"}
                            }, {
                                title:     "Value",
                                type:      "input",
                                name:      "value",
                                mandatory: true,
                                css:       {width: "40%"}
                            }]
                        });
                        conditionForm.popupDialog({
                            title:   'Bedingung',
                            width:   500,
                            buttons: [{
                                text:  "Speichern",
                                click: function() {
                                    console.log(conditionForm.formData());
                                    return false;
                                }
                            }]
                        });

                        e.preventDefault();

                        return false;
                    }
                }]
            })]
        });
    },

    showPopup:      function() {
        var widget = this;
        var dialog = widget.getForm().popupDialog({
            title:       "Abfrage",
            maximizable: true,
            width:       "500px",
            buttons:     [{
                name:  "cancelSave",
                text:  "Abbrechen",
                click: function() {
                    dialog.popupDialog('close');
                }
            }, {
                name:  "buttonSave",
                text:  "Speichern",
                click: function() {
                    var data = dialog.formData();
                    var hasError = dialog.find(".has-error").size() > 0;
                    widget._trigger(hasError ? 'dataInvalid' : 'dataValid', null, {
                        data:   data,
                        dialog: dialog,
                        widget: widget
                    });
                }
            }]
        });

        return dialog;

    },
    _getDiv:        function() {
        return this.el || $("<div/>");
    },
    _getIconButton: function(options) {
        return {
            type:     "button",
            name:     options.name,
            title:    options.title,
            text:     options.title,
            cssClass: options.icon ? this._getIcon(options.icon) : undefined,
            click:    options.click
        };
    },

    fill: function(data) {
        this.el.formData(data);
    },

    _getForm: function(obj, active) {
        return {
            type:     "form",
            title:    obj.title,
            children: obj.children,
            active:   !!active
        }
    },

    check: function(data) {
        this.el.formData();
    }
});

