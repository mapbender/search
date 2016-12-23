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
     * Get current schema
     * @param schemaId
     * @returns {null}
     */
    getCurrentSchema: function() {
        var widget = this;
        return  widget.currentSchema;
    },

    /**
     * Render
     */
    render: function(query) {
        var widget = this;
        var element = $(widget.element);
        var options = widget.options;
        var schemas = options.schemas;
        var schemaId = query.hasOwnProperty("schemaId") ? query.schemaId : _.keys(schemas)[0];
        var currentSchema = widget.changeSource(schemaId);
        var schemaOptions = _.object(_.keys(schemas), _.pluck(schemas, 'title'));
        var initialFields = query && query.fields ? query.fields : [];
        var fieldNames = _.object(_.pluck(currentSchema.fields, 'name'), _.pluck(currentSchema.fields, 'title'));

        // Add field titles
        // _.each(query.conditions, function(condition) {
        //     condition.fieldTitle = fieldNames[condition.fieldName];
        // });

        var formContainer = element.empty().generateElements({
            type:     "tabs",
            children: [{
                title:    "Allgemein",
                type:     'form',
                children: [{
                    type:        "input",
                    name:        "name",
                    placeholder: "Eindeutiger Name",
                    title:       "Name",
                    mandatory:   true
                }, {
                    type:    "select",
                    name:    "schemaId",
                    title:   "Schema (Objekttyp)",
                    value:   schemaId,
                    options: schemaOptions,
                    change:  function(e) {
                        var featureTypeId = $('select', e.currentTarget).val();
                        currentSchema = widget.changeSource(featureTypeId)
                    }
                }, {
                    type:     "fieldSet",
                    children: [{
                        type:    "select",
                        name:    "styleMap",
                        title:   "Kartenstil",
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
                    name:    "extendOnly",
                    title:   "Nur Kartenausschnitt",
                    value:   true,
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
                        data:  'title',
                        title: 'Feldname'
                    }],
                    data:         initialFields,
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
                        click: function(e) {
                            var el = $(e.currentTarget);
                            var form = el.closest('.popup-dialog');
                            var fieldForm = $("<div style='overflow: initial'/>");
                            var currentSchema = widget.getCurrentSchema();
                            var fieldNames = _.object(_.pluck(currentSchema.fields, 'name'), _.pluck(currentSchema.fields, 'title'));

                            fieldForm.generateElements({
                                type:     'fieldSet',
                                children: [{
                                    title:     "Feld",
                                    type:      "select",
                                    name:      "fieldName",
                                    options:   fieldNames,
                                    mandatory: true,
                                    css:       {width: "100%"}
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

                                        var title = _.object(_.pluck(currentSchema.fields, 'name'), _.pluck(currentSchema.fields, 'title'))[data.fieldName];
                                        tableApi.rows.add([{
                                            fieldName: data.fieldName,
                                            title:     title
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
                    searching: false,
                    info:      false,
                    paging:    false,
                    ordering:  false,
                    columns:   [{
                        data:  'fieldName',
                        title: 'Feldname'
                    }, {
                        data:  'operator',
                        title: 'Operator'
                    }, {
                        data:  'value',
                        title: 'Wert'
                    }],
                    data:      query && query.conditions ? query.conditions : [],
                    buttons:   [{
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
                            var currentSchema = widget.getCurrentSchema();
                            var fieldNames = _.object(_.pluck(currentSchema.fields, 'name'), _.pluck(currentSchema.fields, 'title'));

                            conditionForm.generateElements({
                                type:     'fieldSet',
                                children: [{
                                    title:     "Field",
                                    type:      "select",
                                    name:      "fieldName",
                                    options:   fieldNames,
                                    mandatory: true,
                                    css:       {width: "40%"},
                                    change:    function() {
                                        var el = $(this);
                                        var select = $("select", el);
                                        var fieldDefinition = _.findWhere(currentSchema.fields, {name: select.val()});
                                        var container = el.next();
                                        var operators = _.object(fieldDefinition.operators, fieldDefinition.operators);

                                        container.empty();

                                        if(fieldDefinition.hasOwnProperty('options')) {
                                            container
                                                .generateElements({
                                                    title:     "Operator",
                                                    type:      "select",
                                                    name:      "operator",
                                                    options:   operators,
                                                    mandatory: true,
                                                    css:       {width: "30%"}
                                                })
                                                .generateElements({
                                                    title:   "Optionen",
                                                    type:    'select',
                                                    name:    'value',
                                                    options: fieldDefinition.options,
                                                    css:     {width: "70%"}
                                                });
                                        } else if(fieldDefinition.hasOwnProperty("operators")) {
                                            container
                                                .generateElements({
                                                    title:     "Operator",
                                                    type:      "select",
                                                    name:      "operator",
                                                    options:   operators,
                                                    mandatory: true,
                                                    css:       {width: "30%"}
                                                })
                                                .generateElements({
                                                    title: "Value",
                                                    type:  "input",
                                                    name:  "value",
                                                    css:   {width: "70%"}
                                                });
                                        }
                                    }
                                }, {
                                    type:     'fieldSet',
                                    cssClass: 'condition',
                                    css:      {width: "60%"}
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
                                        var fieldDefinition = _.findWhere(currentSchema.fields, {name: data.fieldName});

                                        if(hasErrors) {
                                            return false;
                                        }

                                        tableApi.rows.add([{
                                            fieldName:  data.fieldName,
                                            fieldTitle: fieldDefinition.title,
                                            operator:   data.operator,
                                            value:      data.value
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
        var options = widget.options;
        var element = widget.element;
        return element.popupDialog({
            title:       "Abfrage",
            maximizable: true,
            modal:       true,
            width:       "500px",
            close: function(e,a,u) {
                widget._trigger('close');
                return false;
            },
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

        widget._trigger('close');
    }
});

