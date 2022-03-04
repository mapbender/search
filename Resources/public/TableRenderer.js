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
        var columns = query.fields.map(function(definition) {
            return {
                title: definition.title,
                data: function(row) {
                    var data = row.data[definition.fieldName];
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
}());

