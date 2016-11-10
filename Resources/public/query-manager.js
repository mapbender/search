/**
 * Created by ransomware on 27/09/16.
 * Released under the MIT license.
 */
$.widget("rw.queryManager", {
    options: {
        data:      {
            id: null
        },
        schemas:   [],
        styleMaps: [],
        asPopup:   true
    },

    /**
     * Current source (Feature type description)
     */
    currentSchema: null,

    /**
     * Constructor
     *
     * @private
     */
    _create: function() {
        var widget = this;
        var options = this.options;

        widget.render(options.data);

        if(options.asPopup) {
            widget.popup();
        }
    },

    changeSource: function(schemaId) {
        var widget = this;
        var schemas = widget.option('schemas');
        var currentSource = widget.currentSchema = schemas[schemaId];

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
        var schemas = options.schemas;
        var schemaId = _.keys(schemas)[0];
        var currentSource = widget.changeSource(schemaId);
        var schemaOptions = _.object(_.keys(schemas), _.pluck(schemas, 'title'));
        var formContainer = element.empty().generateElements({
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
                    name:    "schemaId",
                    title:   "Schema",
                    value:   schemaId,
                    options: schemaOptions,
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
                        options: _.object(_.pluck(options.styleMaps, 'id'), _.pluck(options.styleMaps, 'name')),
                        css:     {
                            width: "80%"
                        }
                    }, {
                        type:     "button",
                        cssClass: "bars",
                        title:    "Ändern",
                        click:    function(e) {
                            var styleMapId = element.find('[name=styleMap]').val();
                            var styleMap = _.findWhere(options.styleMaps, {id: styleMapId});
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
                    type:    "checkbox",
                    name:    "extentOnly",
                    title:   "Nur Kartenausschnitt",
                    checked: true

                }, {
                    type:  "checkbox",
                    name:  "exportOnly",
                    title: "Nur Export",
                    checked: false
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
                    data:         query && query.fields ? query.fields : [],
                    buttons:      [{
                        title:     "Löschen",
                        className: 'remove',
                        cssClass:  'critical',
                        onClick:   function(field, ui) {
                            var form = ui.closest('.popup-dialog');
                            var resultTable = form.find('[name="fields"]');
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

                            debugger;

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
                    data:         query && query.conditions ? query.conditions : [],
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
                                    title: "Value",
                                    type:  "input",
                                    name:  "value",
                                    css:   {width: "40%"}
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

        setTimeout(function() {
            formContainer.formData(query);
        }, 300);
        return formContainer;
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
                text:  "Prüfen",
                click: function() {
                    var form = $(this).closest('.popup-dialog');
                    var conditions = form.find('[name=conditions]').resultTable('getApi').rows().data().toArray();
                    var fields = form.find('[name=fields]').resultTable('getApi').rows().data().toArray();

                    widget._trigger('check', null, {
                        dialog: element,
                        widget: widget,
                        data:   $.extend({
                            id:         widget.options.data.id,
                            conditions: conditions,
                            fields:     fields
                        }, form.formData())
                    });

                    return false;
                }
            }, {
                text:  "Abbrechen",
                click: function() {
                    widget.close();
                    return false;
                }
            }, {
                text:  "Speichern",
                click: function() {
                    var form = $(this).closest('.popup-dialog');
                    var conditions = form.find('[name=conditions]').resultTable('getApi').rows().data().toArray();
                    var fields = form.find('[name=fields]').resultTable('getApi').rows().data().toArray();

                    widget._trigger('submit', null, {
                        dialog: element,
                        widget: widget,
                        data:   $.extend({
                            id:         widget.options.data.id,
                            conditions: conditions,
                            fields:     fields
                        }, form.formData())
                    });

                    return false;
                }
            }]
        });
    },

    updateStyleMapList: function(styleMaps) {
        var widget = this;
        var element = widget.element;
        // var formData = element.formData();

        element.find('select[name=styleMap]').updateSelect(styleMaps, 'id', 'name');

        widget._setOption("styleMaps", styleMaps);
        widget._trigger('stylesMapsUpdated');
        // widget.render(formData);
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

