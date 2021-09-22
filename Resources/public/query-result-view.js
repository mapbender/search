$.widget("wheregroup.queryResultView", {
    options: {
        query: null
    },
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
        this.render(this.options.query);
    },

    /**
     * Render
     */
    render: function(query) {
        var widget = this;
        var element = $(widget.element);
        var options = widget.options;
        var table, tableApi;

        element
            .attr('data-id', query.id)
            .empty();

        element.generateElements(Mapbender.Util.beautifyGenerateElements({
            type:     'fieldSet',
            children: [{
                type:     'checkbox',
                cssClass: 'onlyExtent',
                title:    Mapbender.trans("mb.search.onlyMapSection"),
                checked:  query.extendOnly,
                change:   function() {
                    var input = $('input', this);
                    widget._trigger('changeExtend', null, {
                        query:   query,
                        widget:  widget,
                        element: element,
                        checked: input.is(":checked")
                    });
                }
            }]
        }));

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
        var buttons = [];

        buttons.push({
            title:     Mapbender.trans("mb.search.feature.zoomTo"),
            className: 'zoomTo',
            onClick:   function(olFeature, ui) {
                widget._trigger('zoomTo', null, {
                    feature: olFeature,
                    ui:      ui,
                    query:   query,
                    widget:  widget
                })
            }
        });

        buttons.push({
            title:     Mapbender.trans("mb.search.feature.bookmark"),
            className: 'bookmark',
            onClick:   function(olFeature, ui) {
                widget._trigger('mark', null, {
                    feature: olFeature,
                    ui:      ui,
                    query:   query,
                    widget:  widget
                })
            }
        });

        buttons.push({
            title:     Mapbender.trans("mb.search.feature.toggleVisibility"),
            className: 'visibility',
            onClick:   function(olFeature, ui) {
                widget._trigger('toggleVisibility', null, {
                    feature: olFeature,
                    ui:      ui,
                    query:   query,
                    widget:  widget
                })
            }
        });

       /* buttons.push({
            title:     "Druck",
            className: 'print',
            onClick:   function(olFeature, ui) {
                widget._trigger('print', null, {
                    feature: olFeature,
                    ui:      ui,
                    query:   query,
                    widget:  widget
                })
            }
        }); */

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
            buttons: buttons,
            createdRow: function(tr, feature) {
                $(tr).data({feature: feature});
                feature.tableRow = tr;
            }
        }, this.tableDefaults)
        table = this.table = $("<div class='resultQueries'/>").resultTable(tableOptions);

        table.find("tbody")
            .off('click', '> tr')
            .on('click', '> tr[role="row"]', function(e) {
                if ($(e.target).parentsUntil(this).is('.buttons')) {
                    return;
                }
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
            .off('mouseover', '> tr')
            .on('mouseover', '> tr[role="row"]', function(e) {
                if ($(e.target).parentsUntil(this).is('.buttons')) {
                    return;
                }
                var olFeature = $(this).data('feature');
                if(!olFeature) {
                    return false;
                }

                widget._trigger('featureOver', null, {
                    feature:  olFeature
                });
                return false;
            })
            .off('mouseout', '> tr')
            .on('mouseout', '> tr[role="row"]', function(e) {
                if ($(e.target).parentsUntil(this).is('.buttons')) {
                    return;
                }
                var olFeature = $(this).data('feature');
                if(!olFeature) {
                    return false;
                }

                widget._trigger('featureOut', null, {
                    feature:  olFeature
                });
                return false;
            });

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

