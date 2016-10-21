/**
 * Created by ransomware on 27/09/16.
 * Released under the MIT license.
 */
var getIcon = function(name) {
    return 'fa fa-' + name;
};

var fieldsTableDataGen = function(options) {
    return {
        title:     "<input type='text' placeholder='" + options.placeholder + "'>",
        fieldName: options.fieldName
    };
};

var getIconButton = function(options) {
    return {
        type:     "button",
        name:     options.name,
        title:    options.title,
        cssClass: options.icon ? getIcon(options.icon) : undefined,
        click:    options.click
    };
};

var constraintsTableDataGen = function(options) {

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
};

var constraintsOperators = [">", "<", ">=", "<=", "==", "!=", "LIKE", "NOT LIKE"];

$.widget("rw.querymanager", {

    version: "1.0.1",

    options: {
        query: null
    },

    source: {
        title:    'Source',
        children: [{
            type:     "fieldSet",
            children: [{
                type:    "select",
                name:    "selectFeatureTyp",
                title:   "Feature type",
                options: ['IPE', 'Grundstücke', 'EO-Projekt', 'Bauliche Anlage aus SAP'],
                css:     {
                    width: "90%"
                }
            }, {
                type:     "button",
                name:     "buttonExtendFeatureType",
                cssClass: "bars button btn",
                title:    "Add",
                css:      {
                    width: "10%"
                }
            }]
        }]
    },

    fields:      {
        title:    "Fields",
        children: [{
            type: "label",
            text: "Add display field:",
            name: "labelAddDisplayField"
        }, {
            type:  "label",
            title: "Field name",
            name:  "labelFieldName"
        }, {
            type: "select",

            name: "selectFieldName"
        }, {
            type:  "label",
            title: "Title (alias)",
            name:  "labelTitleAlias"
        }, {
            type: "input",
            name: "inputTitleAlias"
        }, {
            type:     "button",
            name:     "buttonAddField",
            cssClass: "plus",
            title:    " Add fields"
        }, {
            html: $('<div/>').resultTable({
                lengthChange: false,
                searching:    false,
                info:         false,
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
        }]
    },
    constraints: {
        title:    "Constraints",
        children: [{
            type:  "label",
            title: "Add condition:",
            name:  "labelAddCondition"
        }, {type:'breakLine'},{
            type:  "label",
            title: "Field",
            name:  "labelField"
        }, {
            type: "select",
            name: "selectField"
        }, {
            type:  "label",
            title: "Operator",
            name:  "labelOperator"
        }, {
            type: "select",
            name: "selectOperator"
        }, {
            type:  "label",
            title: "Value",
            name:  "labelValue"
        }, {
            type: "input",
            name: "inputConditionValue"
        }, {
            type:     "button",
            name:     "buttonAddCondition",
            cssClass: "plus",
            title:    " Add Condition",
            click:    function(e) {
                var el = $(e.currentTarget);
                var form = el.closest('.popup-dialog');
                e.preventDefault();

            }
        }, {
            html: $('<div/>').resultTable({
                lengthChange: false,
                searching:    false,
                info:         false,
                columns:      [{
                    data:  'fieldName',
                    title: 'Field Name'
                }, {
                    data:  'operator',
                    title: 'Operator'
                }, {
                    data:  'value',
                    title: 'Value'
                }, {
                    data:  'action',
                    title: 'Action'
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
        }]
    },

    general: {
        title:    "General",
        children: [{
            type:        "input",
            name:        "inputQueryName",
            placeholder: "Eindeutige Name",
            title:       "Name",
            mandatory:   true
        }, {
            type:        "checkbox",
            name:        "inputExtentOnly",
            placeholder: "Extent only",
            title:       "Extent only",
            checked:     true

        }, {type: "breakLine"}, {
            type:     "fieldSet",
            children: [{
                type:    "select",
                name:    "style",
                title:   "Style",
                value:   0,
                options: ['Style #1', 'Style #2', 'Style #3', 'Style #4'],
                css:     {
                    width: "90%"
                }
            }, {
                type:     "button",
                name:     "buttonExtendInputStyle",
                cssClass: "bars",
                title: "Edit",
                click: function(e) {
                    debugger;
                    var styleManagerContainer = $("<div/>");
                    styleManagerContainer.featureStyleManager();
                    styleManagerContainer.bind('featurestylemanagersubmit', function(e, fsm) {
                        var featureStyleData = fsm.form.formData();
                        widget.query('style/update', {
                            data: featureStyleData
                        }).done(function(r) {
                            console.log(e, data);
                        });
                    });
                },
                css:   {
                    width: "10%"
                }
            }]
        }]
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

    // Private Methods
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

    _setOptions: function(options) {
        this._super(options);
        this.refresh();
    },

    _initEventHandler: function() {

    },

    _has: function(obj, prop) {
        return obj && obj[prop] !== undefined;
    },

    getForm: function() {
        var widget = this;
        return widget.el.generateElements({
            type:     "tabs",
            children: [
                widget._getForm(widget.general, true),
                widget._getForm(widget.source),
                widget._getForm(widget.fields),
                widget._getForm(widget.constraints)
            ]
        });
    },

    showPopup: function() {
        var widget = this;
        var dialog = widget.getForm().popupDialog({
            title:       "Edit query",
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
                    data = dialog.formData();
                    if(dialog.find(".has-error").size()) {
                        $.notify(JSON.stringify(data));
                    } else {
                        $.notify("Erfolgreich gespeichert", "info");
                        dialog.dispatch("dataValid", {
                            data:   data,
                            dialog: dialog,
                            widget: widget
                        });
                        dialog.popupDialog("close");
                    }
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

    _getForm: function(obj, active) {
        return {
            type:     "form",
            title:    obj.title,
            children: obj.children,
            active:   !!active
        }
    },

    fill: function(data) {
        this.el.formData(data);
    },

    check: function(data) {
        this.el.formData();
    }
});

