/**
 * Created by ransomware on 27/09/16.
 * Released under the MIT license.
 */
$.widget("rw.queryManager", {

    version: "1.0.1",

    options: {
        query:                   null,
        featureTypeDescriptions: [],
        styleMaps:               [],
        asPopup:                 true
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

        widget.render(options.query);

        if(options.asPopup) {
            widget.popup();
        }
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

    /**
     * Render
     */
    render: function(query) {
        var widget = this;
        var element = $(widget.element);
        var options = widget.options;
        var styleMaps = options.styleMaps;
        var featureTypeDescriptions = options.featureTypeDescriptions;
        var featureTypeId = _.keys(featureTypeDescriptions)[0];
        var currentSource = widget.changeSource(featureTypeId);
        var constraintsOperators = widget.constraintsOperators;
        var featureTypeNames = _.object(_.keys(featureTypeDescriptions), _.pluck(featureTypeDescriptions, 'title'));
        var styleMapNames = _.object(_.pluck(styleMaps, 'id'), _.pluck(styleMaps, 'name'));

        return element.generateElements({
            type:     "tabs",
            children: [{
                title:    "Allgemein",
                type:     'form',
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
                    options: featureTypeNames,
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
                        options: styleMapNames,
                        css:     {
                            width: "80%"
                        }
                    }, {
                        type:     "button",
                        cssClass: "bars",
                        title:    "Ändern",
                        click:    function(e) {
                            var styleMapId = element.formData().styleMap;
                            var styleMap = _.findWhere(styleMaps, {id: styleMapId});
                            widget._trigger('styleMapChange', null, {
                                widget:   widget,
                                form:     element,
                                styleMap: styleMap
                            });
                            return false;
                        },
                        css:      {
                            width: "20%"
                        }
                    }]
                }, {
                    type:        "checkbox",
                    name:        "extentOnly",
                    placeholder: "Extent only",
                    title:       "Extent only",
                    checked:     true

                }]
            }, {
                type:     "form",
                title:    "Felder",
                children: [{
                    type:         'resultTable',
                    name:         'fields',
                    cssClass:     'fields',
                    lengthChange: false,
                    searching:    false,
                    info:         false,
                    paging:       false,
                    ordering:     false,
                    columns:      [{
                        data:  'fieldName',
                        title: 'Feldname'
                    }, {
                        data:  'title',
                        title: 'Operator'
                    }],
                    data:         [],
                    buttons:      [{
                        title:     "Löschen",
                        className: 'remove',
                        cssClass:  'critical',
                        onClick:   function(field, ui) {
                            var form = ui.closest('.popup-dialog');
                            var resultTable = form.find('[name="conditions"]');
                            var tableWidget = resultTable.data('visUiJsResultTable');
                            var tableApi = resultTable.resultTable('getApi');

                            tableApi.row(tableWidget.getDomRowByData(field)).remove();
                            tableApi.draw();
                            return false;
                        }
                    }]
                }, {
                    type:     'fieldSet',
                    children: [{
                        type:     "button",
                        cssClass: "plus",
                        title:    "Neues Feld",
                        css:      {'margin-top': '10px'},
                        click:    function(e) {
                            var el = $(e.currentTarget);
                            var form = el.closest('.popup-dialog');
                            var fieldForm = $("<div style='overflow: initial'/>");
                            var fieldNames = currentSource.fieldNames;

                            fieldForm.generateElements({
                                type:     'fieldSet',
                                children: [{
                                    title:     "Field",
                                    type:      "select",
                                    name:      "fieldName",
                                    options:   fieldNames,
                                    mandatory: true,
                                    css:       {width: "40%"},
                                    change:    function(e) {
                                        var fieldName = fieldForm.formData().fieldName;
                                        fieldForm.formData({title: fieldName})
                                    }
                                }, {
                                    type:  "input",
                                    title: "Title (alias)",
                                    name:  "title",
                                    css:   {width: "60%"}
                                }]
                            });
                            fieldForm.popupDialog({
                                title:   'Feldbenennung',
                                width:   500,
                                modal:   true,
                                buttons: [{
                                    text:  "Speichern",
                                    click: function() {
                                        var resultTable = form.find('[name="fields"]');
                                        var tableApi = resultTable.resultTable('getApi');
                                        var data = fieldForm.formData();

                                        var errorInputs = $(".has-error", fieldForm);
                                        var hasErrors = errorInputs.size() > 0;

                                        if(hasErrors) {
                                            return false;
                                        }

                                        tableApi.rows.add([{
                                            fieldName: data.fieldName,
                                            title:     data.title,
                                        }]);

                                        tableApi.draw();

                                        fieldForm.popupDialog('close');

                                        return false;
                                    }
                                }]
                            });

                            return false;
                        }
                    }]
                }]
            }, {
                type:     "form",
                title:    "Bedingungen",
                children: [{
                    type:         'resultTable',
                    name:         'conditions',
                    cssClass:     'conditions',
                    lengthChange: false,
                    searching:    false,
                    info:         false,
                    paging:       false,
                    ordering:     false,
                    columns:      [{
                        data:  'fieldName',
                        title: 'Feldname'
                    }, {
                        data:  'operator',
                        title: 'Operator'
                    }, {
                        data:  'value',
                        title: 'Wert'
                    }],
                    data:         [],
                    buttons:      [{
                        title:     "Löschen",
                        className: 'remove',
                        cssClass:  'critical',
                        onClick:   function(condition, ui) {
                            var form = ui.closest('.popup-dialog');
                            var resultTable = form.find('[name="conditions"]');
                            var tableWidget = resultTable.data('visUiJsResultTable');
                            var tableApi = resultTable.resultTable('getApi');

                            tableApi.row(tableWidget.getDomRowByData(condition)).remove();
                            tableApi.draw();
                            return false;
                        }
                    }]
                }, {
                    type:     'fieldSet',
                    children: [{
                        type:     "button",
                        cssClass: "plus",
                        title:    "Neue Bedingung",
                        css:      {'margin-top': '10px'},
                        click:    function(e) {
                            var el = $(e.currentTarget);
                            var form = el.closest('.popup-dialog');
                            var conditionForm = $("<div style='overflow: initial'/>");
                            var operators = currentSource.operators;
                            var fieldNames = currentSource.fieldNames;

                            conditionForm.generateElements({
                                type:     'fieldSet',
                                children: [{
                                    title:     "Field",
                                    type:      "select",
                                    name:      "fieldName",
                                    options:   fieldNames,
                                    mandatory: true,
                                    css:       {width: "40%"}
                                }, {
                                    title:     "Operator",
                                    type:      "select",
                                    name:      "operator",
                                    options:   operators,
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
                                modal:   true,
                                buttons: [{
                                    text:  "Speichern",
                                    click: function() {
                                        var resultTable = form.find('[name="conditions"]');
                                        var tableApi = resultTable.resultTable('getApi');
                                        var data = conditionForm.formData();

                                        var errorInputs = $(".has-error", conditionForm);
                                        var hasErrors = errorInputs.size() > 0;

                                        if(hasErrors) {
                                            return false;
                                        }

                                        tableApi.rows.add([{
                                            fieldName: data.fieldName,
                                            operator:  data.operator,
                                            value:     data.value
                                        }]);

                                        tableApi.draw();

                                        conditionForm.popupDialog('close');

                                        return false;
                                    }
                                }]
                            });

                            return false;
                        }
                    }]
                }]
            }]
        });
    },

    popup: function() {
        var widget = this;
        var element = widget.element;
        return element.popupDialog({
            title:       "Abfrage",
            maximizable: true,
            modal:       true,
            width:       "500px",
            buttons:     [{
                name:  "cancelSave",
                text:  "Abbrechen",
                click: function() {
                    widget.close();
                    return false;
                }
            }, {
                name:  "buttonSave",
                text:  "Speichern",
                click: function() {
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

