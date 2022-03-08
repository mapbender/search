;!(function() {
    window.Mapbender = Mapbender || {};
    window.Mapbender.Search = window.Mapbender.Search || {}
    var tableDefaults = {
        lengthChange: false,
        pageLength:   15,
        searching:    true,
        info:         true,
        processing:   false,
        ordering:     true,
        paging:       true,
        autoWidth:    false,
        oLanguage: {
            sInfo: '_START_ / _END_ (_TOTAL_)',
            oPaginate: {
                sNext: 'Weiter',
                sPrevious: 'Zurück'
            }
        }
    };

    function escapeHtml(text) {
        'use strict';
        return text.replace(/["&'\/<>]/g, function (a) {
            return {
                '"': '&quot;', '&': '&amp;', "'": '&#39;',
                '/': '&#47;',  '<': '&lt;',  '>': '&gt;'
            }[a];
        });
    }

    Mapbender.Search.TableRenderer = function(buttonTemplate) {
        this.buttonTemplate = buttonTemplate;
    };

    Mapbender.Search.TableRenderer.prototype.initializeTable = function(table, query) {
        var self = this;
        var columns = query.fields.map(function(definition) {
            return {
                title: definition.title,
                data: function(feature) {
                    var data = self.dataFromFeature_(feature)[definition.fieldName];
                    if (typeof(data) == 'string') {
                        data = escapeHtml(data);
                    }
                    return data;
                }
            };
        });

        var tableOptions = Object.assign({}, tableDefaults, {
            columns: columns,
            createdRow: function(tr, feature) {
                $(tr).data({feature: feature});
                feature.tableRow = tr;
            }
        });

        // Add buttons column
        tableOptions.columnDefs = [{
            targets: -1,
            width: '1%',
            orderable: false,
            searchable: false,
            defaultContent: this.buttonTemplate
        }];
        tableOptions.columns.push({
            data: null,
            title: ''
        });
        $(table).dataTable(tableOptions);
    };
    Mapbender.Search.TableRenderer.prototype.replaceRows = function(table, data) {
        var tableApi = $(table).dataTable().api();
        tableApi.clear();
        tableApi.rows.add(data);
        tableApi.draw();
    };
    Mapbender.Search.TableRenderer.prototype.pageToRow = function(table, tr) {
        var tableApi = $(table).dataTable().api();
        var rowsPerPage = tableApi.page.len();
        var rowIndex = tableApi.rows({order: 'current'}).nodes().indexOf(tr);
        var pageWithRow = Math.floor(rowIndex / rowsPerPage);
        tableApi.page(pageWithRow).draw(false);
    };
    Mapbender.Search.TableRenderer.prototype.toggleDetails = function(tr, schema) {
        var $tr = $(tr);
        var tableApi = $tr.closest('table').dataTable().api();
        var row = tableApi.row(tr);

        if (row.child.isShown()) {
            row.child.hide();
        } else {
            var markup = this.renderDetails_(schema, $(tr).data('feature'));
            if (markup) {
                row.child(markup);
                row.child.show();
            }
        }
    };
    Mapbender.Search.TableRenderer.prototype.renderDetails_ = function(schema, feature) {
        var rows = [];
        var fieldDefs = schema.fields || [];
        var data = this.dataFromFeature_(feature);
        for (var i = 0; i < fieldDefs.length; ++i) {
            var field = fieldDefs[i].name;
            if (data[field]) {
                rows.push($(document.createElement('tr'))
                    .append($('<th>').text(fieldDefs[i].title + ": "))
                    .append($('<td>').text(data[field])));
            }
        }
        if (rows.length) {
            return $(document.createElement('table')).append(rows);
        } else {
            return null;
        }
    };
    Mapbender.Search.TableRenderer.prototype.dataFromFeature_ = function(feature) {
        return feature.getProperties && feature.getProperties() || feature.attributes || {};
    };
}());

