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
        styleMaps: []
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
        this.fieldFormContent_ = this.content_.filter('.-tpl-field-form')
        this.conditionFormContent_ = this.content_.filter('.-tpl-condition-form')
        this.content_ = this.content_
            .not(this.fieldFormContent_)
            .not(this.conditionFormContent_)
        ;

        widget.render(options.data);
        widget.popup();
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
            var currentSchema = widget.getCurrentSchema();
            var fieldForm = widget.renderFieldForm_(currentSchema);
            widget.dialog_(fieldForm, {
                                title:   'Feldbenennung',
                                modal:   true,

                                buttons: [{
                                    text:  "Speichern",
                                    click: function() {
                                        var fieldName = $('select', fieldForm).val();
                                        if (!fieldName) {
                                            $('.form-group', fieldForm).addClass('has-error');
                                            return false;
                                        }
                                        var fieldLabel = $('option:selected', fieldForm).text();
                                        fieldsTableApi.rows.add([{
                                            fieldName: fieldName,
                                            title: fieldLabel
                                        }]);
                                        fieldsTableApi.draw();
                                        fieldForm.dialog('destroy');

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
            widget.dialog_(conditionForm, {
                title: 'Bedingung',
                modal: true,
                                buttons: [{
                                    text:  "Speichern",
                                    click: function() {
                                        if (!Mapbender.Search.FormUtil.checkValidity(conditionForm)) {
                                            return false;
                                        }
                                        var data = Mapbender.Search.FormUtil.getData(conditionForm);
                                        var fieldDefinition = _.findWhere(currentSchema.fields, {name: data.fieldName});

                                        conditionsTableApi.rows.add([{
                                            fieldName:  data.fieldName,
                                            fieldTitle: fieldDefinition.title,
                                            operator:   data.operator,
                                            value:      data.value
                                        }]);

                                        conditionsTableApi.draw();

                                        conditionForm.dialog('destroy');

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

        $('.ui-tabs', element).tabs({
            active: 0
        });
        Mapbender.Search.FormUtil.setData(this.element, query);
        return element;
    },

    popup: function() {
        var widget = this;
        return this.dialog_(this.element, {
            title: "Abfrage",
            modal:       true,
            buttons:     [{
                text:  "Prüfen",
                click: function() {
                    return widget.submitData_($(this), true);
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
                    return widget.submitData_($(this), false);
                }
            }]
        });
    },
    submitData_: function(form, isCheck) {
        var formData = {};
        $(':input[name]', form).get().forEach(function(input) {
            formData[input.name] = input.type === 'checkbox' ? input.checked : (input.value || '');
        });
        if (!isCheck && !formData['name']) {
            $('input[name="name"]', form).closest('.form-group').addClass('has-error');
            return false;
        }
                    var eventName = isCheck && 'check' || 'submit';
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
        this.element.dialog("close");
    },
    initCollection_: function($target, options) {
        var options_ = $.extend({}, options, {
            lengthChange: false,
            searching:    false,
            info:         false,
            paging:       false,
            ordering:     false,
            autoWidth: false,
            oLanguage: {
                sEmptyTable: 'Nichts ausgewählt'
            }
        });
        options_.columns.push({
            targets: -1,
            width: '1%',
            defaultContent: '<span class="-fn-collection-remove button" title="Löschen"><i class="fa fas fa-times"></i><span class="sr-only">Löschen</span></span>'
        });
        $target.dataTable(options_);
        return $target.dataTable().api();
    },
    initFieldNameChoices_: function($select, schema) {
        $select.empty();
        $select.append(_.map(schema.fields, function(field) {
            return $(document.createElement('option'))
                .attr('value', field.name)
                .text(field.title)
                .data('field', field)
            ;
        }));
        $select.val('');
    },
    renderFieldForm_: function(schema) {
        var content = this.fieldFormContent_.clone();
        var $fieldSelect = $('[name="fieldName"]', content);
        this.initFieldNameChoices_($fieldSelect, schema);
        return content;
    },
    renderConditionForm_: function(schema) {
        var conditionForm = this.conditionFormContent_.clone();
        var $fieldSelect = $('[name="fieldName"]', conditionForm);
        this.initFieldNameChoices_($fieldSelect, schema);
        var $operatorField = $('.-js-operator-field', conditionForm);
        var $operatorSelect = $('[name="operator"]', conditionForm);
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
    dialog_: function(element, options) {
        var options_ = {
            classes: {
                'ui-dialog': 'ui-dialog mb-search-dialog'
            },
            closeText: '',
            width: 500,
            resizable: false,
            close: function() {
                $(this).dialog('destroy');
            }
        };
        if ($('.ui-tabs-panel', element).length) {
            options_.classes['ui-dialog'] = [options_.classes['ui-dialog'], 'tabbed-dialog'].join(' ');
        }

        Object.assign(options_, options || {});
        return $(element).dialog(options_);
    },
    __dummy__: null
});
