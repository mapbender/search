$.widget("wheregroup.queryResultView", {
    tableDefaults: {
        lengthChange: false,
        pageLength:   15,
        searching:    true,
        info:         true,
        processing:   false,
        ordering:     true,
        paging:       true,
        autoWidth:    false
    },

    /**
     * Settings for saving
     */
    preparedData:{
    },

    table: null,

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
        var widget = this;
        var element = $(widget.element);
        var table;

        this.element.attr('data-id', query.id);
        $('input[name="extent-only"]', this.element).on('change', function() {
            widget._trigger('changeExtend', null, {
                query:   query,
                widget:  widget,
                element: element,
                checked: $(this).prop('checked')
            });
        }).prop('checked', !!query.extendOnly);

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

        table = this.table = $('.resultQueries', this.element).resultTable(tableOptions);

        table.find("tbody")
            .on('click', '> tr[role="row"]', function(e) {
                var olFeature = $(this).data('feature');
                if(!olFeature) {
                    return;
                }

                widget._trigger('featureClick', null, {
                    feature:  olFeature,
                    ui: this,
                    query: query
                });
                return false;
            })
            .on('mouseover', '> tr[role="row"]', function(e) {
                var olFeature = $(this).data('feature');
                if(!olFeature) {
                    return false;
                }

                widget._trigger('featureOver', null, {
                    feature:  olFeature
                });
                return false;
            })
            .on('mouseout', '> tr[role="row"]', function(e) {
                var olFeature = $(this).data('feature');
                if(!olFeature) {
                    return false;
                }

                widget._trigger('featureOut', null, {
                    feature:  olFeature
                });
                return false;
            })
            .on('click', '.-fn-zoomto', function() {
                widget._trigger('zoomTo', null, {
                    feature: $(this).closest('tr').data('feature'),
                });
            })
            .on('click', '.-fn-toggle-visibility', function() {
                widget._trigger('toggleVisibility', null, {
                    feature: $(this).closest('tr').data('feature'),
                    ui:      $(this)
                });
            })
            .on('click', '.-fn-bookmark', function() {
                widget._trigger('mark', null, {
                    ui: this
                });
            })
        ;

        element.append(table);

        // Add placeholder to result table filter search input
        $('input[type="search"]', table).attr('placeholder', _.pluck(query.fields, 'title').join(', '));

        return element;
    },

    updateList: function(list) {
        var $table = $('table:first', this.table);
        var tableApi = $table.dataTable().api();
        tableApi.clear();
        tableApi.rows.add(list);
        tableApi.draw();
    }
});

