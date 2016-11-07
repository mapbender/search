$.widget("wheregroup.queryResultView", {
    options: {

        // Result table settings
        table: {
            lengthChange: false,
            pageLength:   15,
            searching:    true,
            info:         true,
            processing:   false,
            ordering:     true,
            paging:       true,
            autoWidth:    false
        }
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
        var widget = this;
        var options = this.options;
        var element = $(widget.element);
        var query = element.data('query');

        widget.render(query);
    },

    /**
     * Render
     */
    render: function(query) {
        var widget = this;
        var element = $(widget.element);
        var options = widget.options;
        var progressBar = $("<div class='progressBar'/>");

        element
            .attr('data-id', query.id)
            .empty();

        element.generateElements({
            type:     'fieldSet',
            children: [{
                type:     'checkbox',
                cssClass: 'onlyExtent',
                title:    "Nur Kartenabschnitt",
                checked:  query.extendOnly,
                change:   function(e) {
                    var input = $('input', this);
                    widget._trigger('changeExtend', null, {
                        query:   query,
                        widget:  widget,
                        element: element,
                        checked: input.is(":checked")
                    });
                }
            }]
        });

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
            title:     "Zoom to",
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
            title:     "Bookmark",
            className: 'bookmark',
            onClick:   function(olFeature, ui) {
                widget._trigger('bookmark', null, {
                    feature: olFeature,
                    ui:      ui,
                    query:   query,
                    widget:  widget
                })
            }
        });

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

        var table = widget.table = $("<div class='resultQueries'/>").resultTable(tableOptions = _.extend({
            columns: columns,
            buttons: buttons
        }, options.table));

        var tableApi = table.resultTable('getApi');

        table.contextMenu({
            selector: 'table > tbody > tr',
            events:   {
                // show: function(options) {
                //     // var tr = $(options.$trigger);
                //     // var resultTable = tr.closest('.mapbender-element-result-table');
                //     // var api = resultTable.resultTable('getApi');
                //     // var olFeature = api.row(tr).data();
                //     return true;
                // }
            },
            build:    function($trigger, e) {
                var tr = $($trigger);
                var resultTable = tr.closest('.mapbender-element-result-table');
                var api = resultTable.resultTable('getApi');
                var olFeature = api.row(tr).data();
                var items = {};

                items['zoomTo'] = {name: "Zoom to!"};


                return {
                    callback: function(key, options) {
                        widget._trigger(key, null, {
                            feature: olFeature,
                            table: resultTable,
                            tableApi: api,
                            tr: tr,
                            options: options
                        });
                    },
                    items:    items
                };
            }
        });

        table.off('mouseenter', 'mouseleave', 'click');

        table
            .delegate("tbody > tr", 'mouseenter', function() {
                var tr = this;
                var row = tableApi.row(tr);
                var olFeature = row.data();
                widget._trigger('featureOver', null, {
                    feature: olFeature,
                    ui:      tr,
                    query:   query,
                    widget:  widget
                });
                // widget._highlightFeature(row.data(), true);
                return false;

            })
            .delegate("tbody > tr", 'mouseleave', function() {
                var tr = this;
                var row = tableApi.row(tr);
                var olFeature = row.data();
                widget._trigger('featureOut', null, {
                    feature: olFeature,
                    ui:      tr,
                    query:   query,
                    widget:  widget
                });
                // widget._highlightFeature(row.data(), false);
                return false;
            })
            .delegate("tbody > tr", 'click', function() {
                var tr = this;
                var row = tableApi.row(tr);
                var olFeature = row.data();
                widget._trigger('featureClick', null, {
                    feature: olFeature,
                    ui:      tr,
                    query:   query,
                    widget:  widget
                });
                // widget.zoomToJsonFeature(row.data());
                return false;
            });

        element.append(table);
        element.append(progressBar);

        // Add placeholder to result table filter search input
        $('input[type="search"]', table).attr('placeholder', _.pluck(query.fields, 'title').join(', '));

        return element;
    },

    updateList: function(list) {
        var widget = this;
        var element = $(widget.element);
        var query = element.data('query')
        var table = widget.table;
        // var tableWidget = table.data('visUiJsResultTable');
        var tableApi = table.resultTable('getApi');
        tableApi.clear();
        tableApi.rows.add(list);
        tableApi.draw();

        // Redraw page
        // tableApi.draw({"paging": "page"});

        // update data item
        // tableApi.row(item).invalidate().draw();

        // get item by DOM
        // tableWidget.getDomRowByData(feature)
    },

    /**
     * Close and remove widget
     */
    close: function() {
        var widget = this;
        var element = $(widget.element);
        var options = widget.optionás;

        widget.element.remove();
    }
});

