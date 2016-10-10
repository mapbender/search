(function() {
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

    var constraintsTableData = [constraintsTableDataGen({
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
    })];

    var defaults = {
        general:     {
            title: "General",
            children: [{
                type:        "input",
                name:        "inputQueryName",
                placeholder: "Query name",
                title:       "Query-name"
            }, {
                type:        "checkbox",
                name:        "inputExtentOnly",
                placeholder: "Extent only",
                title:       "Extent only",
                checked:     true

            }, {
                type:        "input",
                name:        "inputStyle",
                placeholder: "Style",
                title:       "Style"
            }, getIconButton({
                name:  "buttonExtendInputStyle",
                icon:  "bars",
                title: ""
            })

            ]
        },
        source:      {
            title:    "Source",
            children: [{
                type:        "select",
                name:        "selectFeatureTyp",
                placeholder: "Feature type",
                title:       "Featuretype",
                options:     []

            }, getIconButton({
                name:  "buttonExtendFeatureType",
                icon:  "bars",
                title: ""
            })]
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
            }, getIconButton({
                name:  "buttonAddField",
                icon:  "plus",
                title: " Add fields"
            }), {
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
            }, {
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
            }, getIconButton({
                name:  "buttonAddCondition",
                icon:  "plus",
                title: " Add Condition",
                click: function(e) {
                    var el = $(e.currentTarget);
                    var form = el.closest('.popup-dialog');
                    e.preventDefault();

                }
            }), {
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
                    data:         constraintsTableData
                })
            }

            ]
        }
    };

    return defaults;
})();