$.widget("wheregroup.queryResultView", {
    tableDefaults: {
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
    },

    /**
     * Constructor
     */
    _create: function() {
        this.render(this.element.data('query'));
    },

    /**
     * Render
     */
    render: function(query) {
        this.element.attr('data-id', query.id);

        function escapeHtml(text) {
            'use strict';
            return text.replace(/["&'\/<>]/g, function (a) {
                return {
                    '"': '&quot;', '&': '&amp;', "'": '&#39;',
                    '/': '&#47;',  '<': '&lt;',  '>': '&gt;'
                }[a];
            });
        }

        var columns = [];

        _.each(query.fields, function(definition) {
            columns.push({
                title: definition.title,
                data:  function(row, type, val, meta) {
                    var data = row.data[definition.fieldName];
                    if(typeof (data) == 'string') {
                        data = escapeHtml(data);
                    }
                    return data;
                }
            });
        });

        var tableOptions = _.extend({
            columns: columns,
            createdRow: function(tr, feature) {
                $(tr).data({feature: feature});
                feature.tableRow = tr;
            }
        }, this.tableDefaults)

        // Add buttons column
        tableOptions.columnDefs = [{
            targets: -1,
            width: '1%',
            orderable: false,
            searchable: false,
            defaultContent: $('.-tpl-query-table-buttons', this.element).remove().html()
        }];
        tableOptions.columns.push({
            data: null,
            title: ''
        });


        $('table', this.element).dataTable(tableOptions);
    }
});

