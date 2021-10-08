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
        this.content_ = $(this.options.template);
        this.conditionFormContent_ = this.content_.filter('.-tpl-condition-form')
        this.content_ = this.content_.not(this.conditionFormContent_);

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

    renderFieldSelect_: function(schema) {
        var $select = $(document.createElement('select'))
            .prop('required', true)
        ;
        var options = _.map(schema.fields, function(field) {
        });
    },

    /**
     * Render
     */
    render: function(query) {
        var widget = this;
        var element = $(widget.element);
        var initialFields = query && query.fields ? query.fields : [];

        this.element.append(this.content_.clone());
        $('select[name="schemaId"]', this.element).empty().append(_.map(this.options.schemas, function(schema, key) {
            var option = document.createElement('option');
            $(option).text(schema.title).attr('value', key);
            return option;
        }));
        this.updateStyleMapList(this.options.styleMaps);
        this.element.on('click', '.-fn-edit-stylemap', function() {
            var styleMap = $('[name="styleMap"] option:selected', element).data('stylemap');
            widget._trigger('styleMapChange', null, {
                styleMap: styleMap
            });
            return false;
        });
        this.element.on('change', 'select[name="schemaId"]', function() {
            var taFields = $('.table.-js-fields-collection', element).dataTable().api();
            taFields.clear();
            taFields.draw();
            var taConditions = $('table.-js-conditions-collection table', element).dataTable().api();
            taConditions();
            taConditions.draw();
        });
        var fieldsTableApi = this.initCollection_($('table.-js-fields-collection', this.element), {
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


        var conditionsTableApi = this.initCollection_($('.-js-conditions-collection', this.element), {
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
            var currentSchema = widget.getCurrentSchema();
            var conditionForm = widget.renderConditionForm_(currentSchema);

            conditionForm.dialog({
                title: 'Bedingung',
                width: 500,
                classes: {
                    'ui-dialog': 'ui-dialog mb-search-dialog'
                },
                closeText: '',
                resizable: false,
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

                                        conditionForm.dialog('close').dialog('destroy');

                                        return false;
                                    }
                                }]
                            });

                            return false;
        });

        this.element.on('click', 'table .-fn-collection-remove', function() {
            var $tr = $(this).closest('tr');
            var tableApi = $tr.closest('table').dataTable().api();
            tableApi.row($tr.get(0)).remove();
            tableApi.draw();
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
        var element = widget.element;
        return element.popupDialog({
            title:       "Abfrage",
            maximizable: true,
            modal:       true,
            width:       "500px",
            close: function() {
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
                    formData.conditions = $('table.-js-conditions-collection', form).dataTable().api().rows().data().toArray();
                    formData.fields = $('table.-js-fields-collection', form).dataTable().api().rows().data().toArray();
                    formData.id = this.options.data.id;

                    this._trigger(eventName, null, {
                        dialog: this.element,
                        widget: this,
                        data: formData
                    });

                    return false;
    },

    updateStyleMapList: function(styleMaps) {
        var $select = $('select[name="styleMap"]', this.element);
        var currentValue = $select.val();
        $select.empty();
        $select.append('<option value="">');
        $select.append(_.map(styleMaps, function(styleMap) {
            var option = document.createElement('option');
            $(option).text(styleMap.name).attr('value', styleMap.id).data('stylemap', styleMap);
            return option;
        }));
        $select.val(currentValue || '');
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
        options_.columns.push({
            targets: -1,
            width: '1%',
            defaultContent: '<button type="button" class="-fn-collection-remove button" title="Löschen"><i class="fa fas fa-times"></i><span class="sr-only">Löschen</span></button>'
        });
        $target.dataTable(options_);
        return $target.dataTable().api();
    },
    renderConditionForm_: function(schema) {
        var conditionForm = this.conditionFormContent_.clone();
        var $fieldSelect = $('[name="fieldName"]', conditionForm);
        var $operatorField = $('.-js-operator-field', conditionForm);
        var $operatorSelect = $('[name="operator"]', conditionForm);
        $fieldSelect.empty();
        $fieldSelect.append(_.map(schema.fields, function(field) {
            return $(document.createElement('option'))
                .attr('value', field.name)
                .text(field.title)
                .data('field', field)
            ;
        }));
        $fieldSelect.val('');
        $operatorField.hide();
        $fieldSelect.on('change', function() {
            var field = $(':selected', this).data('field');
            $operatorSelect.empty().append(_.map(field.operators, function(operator) {
                return $(document.createElement('option'))
                    .text(operator)
                    .val(operator)
                ;
            }));
            $operatorField.show();
            if (field.options) {
                $('label[for="value"]', conditionForm).text('Optionen');
                $('.-js-value-text', conditionForm).attr('name', null).hide();
                $('.-js-value-choice', conditionForm)
                    .attr('name', 'value')
                    .append(_.map(field.options, function(text, value) {
                        return $(document.createElement('option'))
                            .attr('value', value)
                            .text(text)
                        ;
                    }))
                    .val('')
                    .show()
                ;
            } else {
                $('label[for="value"]', conditionForm).text('Wert');
                $('.-js-value-choice', conditionForm).attr('name', null).hide();
                $('.-js-value-text', conditionForm).attr('name', 'value').show();
            }
        });
        return conditionForm;
    },
    __dummy__: null
});
