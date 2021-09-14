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
    /**
     * Get current schema
     * @param schemaId
     * @returns {null}
     */
    getCurrentSchema: function() {
        var schemaId = $('select[name="schemaId"]', this.element).val();
        return this.options.schemas[schemaId];
    },

    /**
     * Render
     */
    render: function(query) {
        var widget = this;
        var element = $(widget.element);
        var options = widget.options;
        var initialFields = query && query.fields ? query.fields : [];

        this.element.append($(this.options.template).html());
        $('select[name="schemaId"]', this.element).empty().append(_.map(this.options.schemas, function(schema, key) {
            var option = document.createElement('option');
            $(option).text(schema.title).attr('value', key);
            return option;
        }));
        $('select[name="styleMap"]', this.element).empty().append(_.map(this.options.styleMaps, function(styleMap) {
            var option = document.createElement('option');
            $(option).text(styleMap.name).attr('value', styleMap.id);
            return option;
        }));
        this.element.on('click', '.-fn-edit-stylemap', function() {
                            var styleMapId = element.find('[name=styleMap]').val();
                            var styleMap = _.findWhere(options.styleMaps, {id: styleMapId});
                            widget._trigger('styleMapChange', null, {
                                widget:   widget,
                                form:     element,
                                styleMap: styleMap
                            });
                            return false;
        });
        this.element.on('change', 'select[name="schemaId"]', function() {
            var taFields = $('.fields table', element).dataTable().api();
            taFields.clear();
            taFields.draw();
            var taConditions = $('.conditions table', element).dataTable().api();
            taConditions();
            taConditions.draw();
        });
        var fieldsTableApi = this.initCollection_($('.fields', this.element), {
            columns: [{
                data:  'title',
                title: 'Feldname'
            }],
            data: initialFields
        });

        this.element.on('click', '.-fn-add-field', function() {
                            var fieldForm = $("<div>");
                            var currentSchema = widget.getCurrentSchema();
                            var fieldNames = _.object(_.pluck(currentSchema.fields, 'name'), _.pluck(currentSchema.fields, 'title'));

                            fieldForm.generateElements( Mapbender.Util.beautifyGenerateElements({
                                type:     'fieldSet',
                                children: [{
                                    title:     "Feld",
                                    type:      "select",
                                    name:      "fieldName",
                                    options:   Mapbender.Util.beautifyOptions(fieldNames),
                                    mandatory: true,
                                    css:       {width: "100%"}
                                }]
                            }));
                            fieldForm.popupDialog({
                                title:   'Feldbenennung',
                                width:   500,
                                modal:   true,
                                buttons: [{
                                    text:  "Speichern",
                                    click: function() {
                                        var data = fieldForm.formData();

                                        var errorInputs = $(".has-error", fieldForm);
                                        var hasErrors = errorInputs.size() > 0;

                                        if(hasErrors) {
                                            return false;
                                        }

                                        var title = _.object(_.pluck(currentSchema.fields, 'name'), _.pluck(currentSchema.fields, 'title'))[data.fieldName];
                                        fieldsTableApi.rows.add([{
                                            fieldName: data.fieldName,
                                            title:     title
                                        }]);
                                        fieldsTableApi.draw();
                                        fieldForm.popupDialog('close');

                                        return false;
                                    }
                                }]
                            });

                            return false;
        });


        var conditionsTableApi = this.initCollection_($('.conditions', this.element), {
            columns: [
                {
                    data:  'fieldName',
                    title: 'Feldname'
                }, {
                    data:  'operator',
                    title: 'Operator'
                }, {
                    data:  'value',
                    title: 'Wert'
                }
            ],
            data: query && query.conditions ? query.conditions : []
        });

        this.element.on('click', '.-fn-add-condition', function() {
                            var conditionForm = $("<div>");
                            var currentSchema = widget.getCurrentSchema();
                            var fieldNames = _.object(_.pluck(currentSchema.fields, 'name'), _.pluck(currentSchema.fields, 'title'));

                            conditionForm.generateElements(Mapbender.Util.beautifyGenerateElements({
                                type:     'fieldSet',
                                children: [{
                                    title:     "Field",
                                    type:      "select",
                                    name:      "fieldName",
                                    options:   Mapbender.Util.beautifyOptions(fieldNames),
                                    mandatory: true,
                                    css:       {width: "40%"},
                                    change:    function() {
                                        var el = $(this);
                                        var select = $("select", el);
                                        var fieldDefinition = _.findWhere(currentSchema.fields, {name: select.val()});
                                        var container = el.next();
                                        var operators = _.object(fieldDefinition.operators, fieldDefinition.operators);

                                        container.empty();
                                        container.generateElements( Mapbender.Util.beautifyGenerateElements({
                                            title:     "Operator",
                                            type:      "select",
                                            name:      "operator",
                                            options:   Mapbender.Util.beautifyOptions(operators),
                                            mandatory: true,
                                            css:       {width: "30%"}
                                        }));


                                        if(fieldDefinition.hasOwnProperty('options')) {
                                            container
                                                .generateElements( Mapbender.Util.beautifyGenerateElements({
                                                    title:   "Optionen",
                                                    type:    'select',
                                                    name:    'value',
                                                    options: Mapbender.Util.beautifyOptions(fieldDefinition.options),
                                                    css:     {width: "70%"}
                                                }));
                                        } else if(fieldDefinition.hasOwnProperty("operators")) {
                                            container
                                                .generateElements(Mapbender.Util.beautifyGenerateElements({
                                                    title: "Value",
                                                    type:  "input",
                                                    name:  "value",
                                                    css:   {width: "70%"}
                                                }));
                                        }
                                    }
                                }, {
                                    type:     'fieldSet',
                                    cssClass: 'condition',
                                    css:      {width: "60%"}
                                }]
                            }));

                            conditionForm.popupDialog({
                                title:   'Bedingung',
                                width:   500,
                                modal:   true,
                                buttons: [{
                                    text:  "Speichern",
                                    click: function() {
                                        var data = conditionForm.formData();
                                        var errorInputs = $(".has-error", conditionForm);
                                        var hasErrors = errorInputs.size() > 0;
                                        var fieldDefinition = _.findWhere(currentSchema.fields, {name: data.fieldName});

                                        if(hasErrors) {
                                            return false;
                                        }

                                        conditionsTableApi.rows.add([{
                                            fieldName:  data.fieldName,
                                            fieldTitle: fieldDefinition.title,
                                            operator:   data.operator,
                                            value:      data.value
                                        }]);

                                        conditionsTableApi.draw();

                                        conditionForm.popupDialog('close');

                                        return false;
                                    }
                                }]
                            });

                            return false;
        });

        $('.mapbender-element-tab-navigator', element).tabs({
            active: 0,
            classes: {
                "ui-tabs": "ui-tabs mapbender-element-tab-navigator",
                "ui-tabs-nav": "ui-tabs-nav nav nav-tabs",
                "ui-tabs-panel": "ui-tabs-panel tab-content"
            }
        });
        element.formData(query);
        return element;
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
                    return widget.submitData_($(this).closest('.popup-dialog'), true);
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
                    return widget.submitData_($(this).closest('.popup-dialog'), false);
                }
            }]
        });
    },
    submitData_: function(form, isCheck) {
                    var eventName = isCheck && 'check' || 'submit';
                    var formData = form.formData();
                    if (!formData || (!isCheck && $('.has-error', form).length)) {
                        return false;
                    }
                    formData.conditions = $('.conditions table', form).dataTable().api().rows().data().toArray();
                    formData.fields = $('.fields table', form).dataTable().api().rows().data().toArray();
                    formData.id = this.options.data.id;

                    this._trigger(eventName, null, {
                        dialog: this.element,
                        widget: this,
                        data: formData
                    });

                    return false;
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
    },
    initCollection_: function($target, options) {
        var options_ = $.extend({}, options, {
            lengthChange: false,
            searching:    false,
            info:         false,
            paging:       false,
            ordering:     false,
            autoWidth: false
        });
        options_.buttons = options_.buttons || [];
        options_.buttons.push({
            type: "html",
            html: '<button type="button" class="button icon-remove remove" title="Löschen">Löschen</button>',
            title:     "Löschen",
            className: 'remove',
            cssClass:  'critical',
            onClick:   function(field, ui) {
                var tableApi = ui.closest('table').dataTable().api();
                tableApi.row(ui.closest('tr').get(0)).remove();
                tableApi.draw();
                return false;
            }
        });
        $target.resultTable(options_);
        return $('table', $target).dataTable().api();
    }
});

