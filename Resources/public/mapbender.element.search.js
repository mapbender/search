(function($) {
    /**
     * @external JQuery
     */
    'use strict';

    /**
     * Digitizing tool set
     *
     * @author Andriy Oblivantsev <eslider@gmail.com>
     * @author Stefan Winkelmann <stefan.winkelmann@wheregroup.com>
     *
     * @copyright 20.04.2015 by WhereGroup GmbH & Co. KG
     */
    $.widget("mapbender.mbSearch", {
        options: {
            // Default option values
            maxResults:        5001,
            pageLength:        10,
            searchType:        "currentExtent",
            inlineSearch:      true,
            clustering:        [{
                scale:    25000,
                distance: 30
            }, {
                scale:   24000,
                disable: true
            }]
        },
        mbMap: null,
        map:                    null,
        templates_: {},

        customStyles_: {
            'boris_ipe': {
                strokeColor:     "#e8c02f",
                strokeWidth:     5,
                strokeOpacity:   1,
                strokeDashstyle: "dashdot"
            },
            'segment': {
                strokeColor:     "#e50c24",
                strokeWidth:     5,
                strokeOpacity:   1,
                strokeDashstyle: "dashdot"
            },
            'flur': {
                fillColor:   "#0c7e00",
                pointRadius: 7
            }
        },


        /**
         * Dynamic loaded styles
         */
        _styles:          null,
        _schemas:         null,
        _styleMaps:       null,
        _queries:         {},
        /**
         * Constructor.
         *
         * At this moment not all elements (like a OpenLayers) are available.
         *
         * @private
         */
        _create: function() {
            var widget = this;
            var element = widget.element;
            this.templates_['query-manager'] = $('.-tpl-query-manager', this.element).remove().css({display: null}).html();
            this.templates_['style-map-manager'] = $('.-tpl-style-map-manager', this.element).remove().css({display: null}).html();
            this.templates_['query'] = $('.-tpl-query', this.element).remove().css({display: null}).html();
            this.templates_['style-editor'] = $('.-tpl-style-editor', this.element).remove().css({display: null}).html();
            this.templates_['table-buttons'] = $('.-tpl-query-table-buttons', this.element).remove().css({display: null}).html();
            this.tableRenderer = new Mapbender.Search.TableRenderer(this.templates_['table-buttons']);
            this.styleEditor = new Mapbender.Search.StyleEditor(this, this.templates_['style-editor'], this.templates_['style-map-manager']);

            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + element.attr('id') + '/';

            // Start loading data before wating on map
            this.initDataPromise_ = $.getJSON(this.elementUrl + 'init');

            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                widget.mbMap = mbMap;
                widget.map = mbMap.map.olMap;
                widget._setup();
            }, function() {
                Mapbender.checkTarget("mbSearch");
            });
        },
        _setup: function() {
            var widget = this;
            var element = widget.element;

                $('select[name="typeFilter"]', element).on('change', function() {
                    var schemaId = $(this).val();
                            _.each(widget._queries, function(query) {
                                var titleView = query.titleView;
                                var resultView = query.resultView;
                                var querySchemaId = query.schemaId;
                                var queryLayer =  query.layer;

                                if(schemaId == -1 || querySchemaId == schemaId) {
                                    titleView.show(0);
                                    if(query.isActive) {
                                        resultView.show(0);
                                        queryLayer.setVisibility(true);
                                    }
                                } else {
                                    titleView.hide(0);
                                    resultView.hide(0);
                                    queryLayer.setVisibility(false);
                                }
                            });
                });

                element.on('click', '.new-query', function() {
                    widget.openQueryManager();
                });
                element.on('click', '.new-stylemap', function() {
                    widget.openStyleMapManager();
                });
                element.on('click', '.new-style', function() {
                    widget.openStyleEditor();
                });

            var mapChangeHandler = function(e) {
                _.each(widget._queries, function(query) {
                    if (!query.isActive || !query.extendOnly) {
                        return
                    }
                    widget.fetchQuery(query);
                });
                return false;
            };
            this.map.events.register("moveend", null, mapChangeHandler);
            this.map.events.register("zoomend", null, function(e){
                mapChangeHandler(e);
                widget.updateClusterStrategies();
            });

            this.initDataPromise_.then(function(data) {
                widget._schemas = data.schemas;
                widget._styles = !Array.isArray(data.styles) && data.styles || {};
                widget._styleMaps = !Array.isArray(data.styleMaps) && data.styleMaps || {};
                widget._queries = !Array.isArray(data.queries) && data.queries || {};
                widget.renderSchemaFilterSelect();
                widget.renderQueries(widget._queries);
                widget.updateClusterStrategies();
                widget._trigger('ready');
            });
        },

        /**
         * Render object type filter
         */
        renderSchemaFilterSelect: function() {
            var widget = this;
            var queries = widget._queries;
            var schemas = widget._schemas;
            var element = widget.element;
            var filterSelect = element.find('[name="typeFilter"]');
            var oldValue = filterSelect.val() || '-1';

            element.find('option').remove();

            filterSelect.append('<option value="-1">Alle Objekttypen</option>');
            _.each(_.unique(_.pluck(queries, 'schemaId')), function(schemaId) {
                var schema = schemas[schemaId];
                filterSelect.append('<option value="' + schemaId + '">' + schema.title + '</option>');
            });

            filterSelect.val(oldValue);
            if (filterSelect.val() !== oldValue) {
                filterSelect.trigger('change');
            }
        },

        // Sidepane integration api
        hide: function() {
            this.disable();
        },
        reveal: function() {
            this.enable();
        },

        /**
         * Activate widget
         */
        enable: function() {
            var widget = this;
            _.each(widget._queries, function(query) {
                var isActive = query.hasOwnProperty("isActive") && query.isActive;
                query.layer.setVisibility(isActive);
            });
        },

        /**
         * Deactivate widget
         */
        disable: function() {
            var widget = this;
            _.each(widget._queries, function(query) {
                query.layer.setVisibility(false);
            });
        },

        /**
         * @param {Object} [style]
         */
        openStyleEditor: function(style) {
            var widget = this;
            return this.styleEditor.editStyle(style).then(function(style) {
                return widget.query('style/save', {
                    style: style
                }).then(function(response) {
                    widget._styles[response.style.id] = response.style;
                });
            });
        },


        /**
         * Open query manager
         */
        openQueryManager: function(query) {
            var widget = this;
            var schemas = widget._schemas;
            var queryManager = $("<div/>");
            var map = widget.map;

            queryManager.queryManager({
                owner: this,
                data:      query,
                schemas:   schemas,
                styleMaps: this._styleMaps,
                template: this.templates_['query-manager']
            });

            queryManager
                .bind('querymanagersubmit', function(event, context) {
                    var isNew = !context.data.id;
                    widget.query('query/save', {query: context.data}).done(function(r) {
                        var queryManagerWidget = context.widget;
                        query = $.extend(query || {}, r.entity);
                        queryManagerWidget.close();
                        $.notify("Erfolgreich gespeichert!", "info");
                        if (isNew) {
                            widget.addQuery(query);
                        } else {
                            widget.updateQuery(query);
                        }
                        widget.renderSchemaFilterSelect();
                    });
                })
                .bind('querymanagercheck', function(event, context) {
                    widget.query('query/check', {
                        query:             context.data,
                        srid:              map.getProjectionObject().proj.srsProjNumber,
                        intersect: map.getExtent().toGeometry().toString()
                    }).done(function(r) {
                        if(r.errorMessage) {
                            $.notify("Fehler beim Ausführen:\n" + r.errorMessage, 'error');
                            return;
                        }

                        $.notify("Anzahl der Ergebnisse : " + r.count + "\nAusführungsdauer: " + r.executionTime, 'info');
                    });
                });
        },

        /**
         * Open style map manager
         */
        openStyleMapManager: function(data) {
            var widget = this;
            return this.styleEditor.editStyleMap(data, this._styles).then(function(styleMap) {
                return widget.query('stylemap/save', {
                    styleMap: styleMap
                }).then(function(response) {
                    var styleMap = response.styleMap;
                    widget._styleMaps[styleMap.id] = styleMap;
                    return styleMap;
                });
            });
        },

        parseResponseFeatures_: function(data) {
            return data.map(function(featureData) {
                var feature = new OpenLayers.Feature.Vector(OpenLayers.Geometry.fromWKT(featureData.geometry));
                feature.attributes = feature.data = featureData.properties;
                feature.fid = featureData.id;
                return feature;
            });
        },
        /**
         * Execute and fetch query results
         *
         * @param query
         * @returns {*}
         */
        fetchQuery: function(query) {
            var widget = this;
            var map = widget.map;

            var request = {
                srid:              map.getProjectionObject().proj.srsProjNumber,
                queryId: query.id
            };

            if(query.extendOnly) {
                request.intersect = map.getExtent().toGeometry().toString()
            }

            if(query.fetchXhr) {
                query.fetchXhr.abort();
            }
            query.titleView.addClass('loading');

            if (!query.extendOnly && query._rowFeatures) {
                setTimeout(function() {
                    var featureCollection = widget.parseResponseFeatures_(query._rowFeatures);
                    widget.reloadFeatures(query, featureCollection);
                    query.titleView.removeClass('loading');
                }, 100);
                return null;
            } else {
                this.replaceTableRows(query, []);
            }

            return query.fetchXhr = widget.query('query/fetch', request, 'GET').done(function(r) {

                delete query.fetchXhr;

                if(r.errorMessage) {
                    $.notify(r.errorMessage);
                    return;
                }

                if(r.infoMessage) {
                    $(query.titleView).notify(r.infoMessage, {
                        autoHideDelay: 30000,
                        className:     'info'
                    });
                }

                if (!query.extendOnly) {
                    query._rowFeatures = r.features;
                }
                var featureCollection = widget.parseResponseFeatures_(r.features);
                widget.reloadFeatures(query, featureCollection);
            }).always(function() {
                query.titleView.removeClass('loading');
            });
        },

        /**
         * Render queries
         */
        renderQueries: function(queries) {
            var queriesArray = _.toArray(queries);
            this.initAccordion($('.queries-accordion', this.element));

            for (var i = 0; i < queriesArray.length; ++i) {
                this.addQuery(queriesArray[i]);
            }
        },
        addQuery: function(query) {
            this._queries[query.id] = query;
            var $accordion = $('.queries-accordion', this.element);
            $accordion.append(this.renderQuery(query));
            query.layer = this.createQueryLayer_(query);
            $accordion.accordion('refresh');
        },
        updateQuery: function(query) {
            this._queries[query.id] = query;
            this.updateQueryMarkup(query);
            query.titleView.addClass('updated');
            if (query.isActive) {
                widget.fetchQuery(query);
            }
        },
        updateQueryMarkup: function(query) {
            $('.-fn-zoomtolayer, .-fn-visibility', query.titleView).toggle(!query.exportOnly);
            $('.title-text', query.titleView).text(query.name);
            $('input[name="extent-only"]', query.resultView).prop('checked', !!query.extendOnly);
            $('input[type="search"]', query.resultView).attr('placeholder', _.pluck(query.fields, 'title').join(', '));
        },
        renderQuery: function(query) {
            var $query = $($.parseHTML(this.templates_['query']));
            var $titleView = $query.filter('.query-header');
            $titleView.data('query', query);
            query.titleView = $titleView;
            var $resultView = $query.filter('.query-content-panel');
            $resultView.data('query', query);
            query.resultView = $resultView;
            this.updateQueryMarkup(query);
            this.tableRenderer.initializeTable($('table:first', $resultView), query);
            this.initQueryViewEvents($resultView, query);
            this.initTitleEvents($titleView, query);
            return $query;
        },
        createQueryLayer_: function(query) {
            var widget = this;
            var map = this.map;
                var schema = widget._schemas[query.schemaId];

                var layerName = 'query-' + query.id;

                    var styleDefinitions = widget._styles;
                    var styleMapDefinition = _.extend({}, widget._styleMaps[query.styleMap] || _.first(_.toArray(widget._styleMaps)));
                    var styleMapConfig = {};
                    var strategies = [];

                    _.each(styleMapDefinition.styles, function(styleId, key) {
                        if(styleDefinitions[styleId]) {
                            var styleDefinition = _.extend({}, styleDefinitions[styleId]);
                            styleMapConfig[key] = new OpenLayers.Style(styleDefinition);
                        } else {
                            delete styleMapDefinition.styles[key];
                        }
                    });

                    var hasDefaultStyle = !styleMapConfig['default'];

                    if(hasDefaultStyle){
                        styleMapConfig['default'] = new OpenLayers.Style(OpenLayers.Feature.Vector.style["default"], {
                            extend: true
                        });
                    }
                    if (!styleMapConfig['select']) {
                        styleMapConfig['select'] = new OpenLayers.Style(OpenLayers.Feature.Vector.style["select"], {
                            extend: true
                        });
                    }
                    if (!styleMapConfig['invisible']) {
                        styleMapConfig['invisible'] = new OpenLayers.Style({
                            display: 'none'
                        });
                    }

                    if ((widget.options.clustering || []).length) {
                        var clusterStrategy = new OpenLayers.Strategy.Cluster({
                            threshold: 1,
                            distance: 30
                        });
                        strategies.push(clusterStrategy);
                    }

                    styleMapConfig.featureDefault = styleMapConfig['default'];
                    styleMapConfig.featureSelect = styleMapConfig['select'];

                    styleMapConfig.clusterDefault = new OpenLayers.Style(_.extend({}, styleMapConfig.featureDefault.defaultStyle, {
                        pointRadius:         '15',
                        fillOpacity:         1,
                        strokeColor:         "#d10a10",
                        strokeWidth:         2,
                        strokeOpacity:       1,
                        labelOutlineColor:   '#ffffff',
                        labelOutlineWidth:   3,
                        labelOutlineOpacity: 1,
                        fontSize:            '11',
                        fontColor:           '#707070',
                        label:               "${clusterLength}",
                        fontWeight:          'bold'
                    }), {
                        context: {
                            clusterLength: function(feature) {
                                return feature.cluster && feature.cluster.length > 1 ? feature.cluster.length : "";
                            }
                        }
                    });

                    styleMapConfig.clusterSelect = new OpenLayers.Style(_.extend({}, styleMapConfig.featureSelect.defaultStyle,{
                        pointRadius:         '15',
                        fillOpacity:         0.7,
                        strokeColor:         "#d10a10",
                        strokeWidth:         3,
                        strokeOpacity:       0.8,
                        labelOutlineColor:   '#ffffff',
                        labelOutlineWidth:   2,
                        labelOutlineOpacity: 1,
                        labelColor:          '#707070',
                        label:               "${clusterLength}"
                    }), {
                        context: {
                            clusterLength: function(feature) {
                                return feature.cluster && feature.cluster.length > 1 ? feature.cluster.length : "";
                            }
                        }
                    });

                    if (!hasDefaultStyle && schema.featureType && (typeof schema.featureType) === 'string' && widget.customStyles_[schema.featureType]) {
                        var customFtStyle = widget.customStyles_[schema.featureType];
                        _.each(['featureDefault', 'clusterDefault'], function(styleMapConfigName) {
                            _.extend(styleMapConfig[styleMapConfigName].defaultStyle, customFtStyle);
                        });
                    }
                    if (schema.featureType == "be") {
                        var fillMap = [
                            {value: 'DB Netz AG (BK09)', fillColor: '#2ca9a9'},
                            {value: 'DB Netz AG (BK16)', fillColor: '#adfcfc'},
                            {value: 'DB Station & Service AG', fillColor: '#ffb0be'},
                            {value: 'Usedomer Bäderbahn GmbH (UBB)', fillColor: '#ff80c0'},
                            {value: 'DB Energie GmbH', fillColor: '#f2f2f2'},
                            {value: 'DB Fernverkehr AG', fillColor: '#d5aaff'},
                            {value: 'DB Regio AG', fillColor: '#ffb76f'},
                            {value: 'DB Schenker Rail AG', fillColor: '#793f96'},
                            {value: 'DB Fahrzeuginstandhaltung GmbH', fillColor: '#46c426'},
                            {value: 'DB AG', fillColor: '#d8fcd8'},
                            {value: 'DB Systel GmbH', fillColor: '#ad7b10'},
                            {value: 'Stinnes Immobiliendienst (alt)', fillColor: '#c90070'},
                            {value: 'DB Mobility Logistics AG', fillColor: '#e83096'},
                            {value: 'Stinnes ID GmbH & Co. KG', fillColor: '#e73165'},
                            {value: '2. KG Stinnes Immobiliendienst', fillColor: '#e2007f'},
                            {value: 'Schenker AG', fillColor: '#793f96'}
                        ];
                        var styleOptions = _.extend({}, styleMapConfig['featureDefault'].defaultStyle, {
                            'fillColor': '${customBeFillColor}'
                        });
                        var fallbackColor = styleMapConfig['featureDefault'].defaultStyle.fillColor;
                        styleMapConfig['featureDefault'] = new OpenLayers.Style(styleOptions, {
                            context: {
                                customBeFillColor: function(feature) {
                                    var match = _.findWhere(fillMap, {value: feature.attributes.eigentuemer});
                                    return match && match.fillColor || fallbackColor;
                                }
                            }
                        });
                    }

                    var layer = new OpenLayers.Layer.Vector(layerName, {
                        styleMap:   new OpenLayers.StyleMap(styleMapConfig, {extendDefault: true}),
                        visibility: false,
                        strategies: strategies
                    }, {extendDefault: true});

                map.addLayer(layer);

                var selectControl = query.selectControl = new OpenLayers.Control.SelectFeature(layer, {
                    hover:        true,
                    overFeature:  function(feature) {
                        widget._highlightSchemaFeature(feature, true, true);
                    },
                    outFeature:   function(feature) {
                        widget._highlightSchemaFeature(feature, false, true);
                    }
                });

                // Workaround to move map by touch vector features
                if(typeof(selectControl.handlers) != "undefined") { // OL 2.7
                    selectControl.handlers.feature.stopDown = false;
                } else if(typeof(selectFeatureControl.handler) != "undefined") { // OL < 2.7
                    selectControl.handler.stopDown = false;
                    selectControl.handler.stopUp = false;
                }

                selectControl.deactivate();
                map.addControl(selectControl);
            layer.query = query;
            return layer;
        },
        initQueryViewEvents: function(queryView, query) {
            var widget = this;
            $('input[name="extent-only"]', queryView).on('change', function() {
                query.extendOnly = this.checked;
                widget.fetchQuery(query);
            });
            // noinspection JSVoidFunctionReturnValueUsed
            queryView
                .on('click', '.-fn-zoomto', function() {
                    var feature = $(this).closest('tr').data('feature');
                    widget.mbMap.getModel().zoomToFeature(feature);
                    return false;
                })
                .on('click', '.-fn-bookmark', function() {
                    var $row = $(this).closest('tr');
                    var feature = $row.data('feature');
                    $row.toggleClass('mark');
                    feature.mark = $row.hasClass('mark');
                    return false;
                })
                .on('click', '.-fn-toggle-visibility', function() {
                    var $btn = $(this);
                    var $icon = $('> i', this);
                    var $row = $btn.closest('tr');
                    var feature = $row.data('feature');
                    feature = widget._checkFeatureCluster(feature);
                    var hidden = !feature.__hidden__;
                    feature.__hidden__ = hidden;
                    $icon.toggleClass('fa-eye-slash', hidden);
                    $icon.toggleClass('fa-eye', !hidden);
                    feature.layer.drawFeature(feature, hidden && 'invisible' || 'default');
                    return false;
                })
                .on('mouseover', 'tbody > tr[role="row"]', function() {
                    var feature = $(this).data('feature');
                    widget._highlightSchemaFeature(feature, true);
                })
                .on('mouseout', 'tbody > tr[role="row"]', function() {
                    var feature = $(this).data('feature');
                    widget._highlightSchemaFeature(feature, false);
                })
                .on('click', 'tbody > tr[role="row"]', function() {
                        function format(feature) {
                            var table = $('<table>');

                            var query = feature.layer.query;
                            var schema = widget._schemas[query.schemaId];
                            var fieldNames = _.object(_.pluck(schema.fields, 'name'), _.pluck(schema.fields, 'title'));

                            _.each(fieldNames, function(title, key) {

                                if(!feature.data.hasOwnProperty(key) || feature.data[key] == "") {
                                    return;
                                }

                                table.append($("<tr/>")
                                    .append($('<th>').text(title + ": "))
                                    .append($('<td>').text(feature.data[key])));

                            });
                            return table;
                        }

                        var tr = $(this);
                        var tableApi = tr.closest('table').dataTable().api();
                        var row = tableApi.row(tr);
                        var feature = tr.data('feature');

                        if(row.child.isShown()) {
                            row.child.hide();
                        } else {
                            if (feature && feature.data) {
                                row.child(format(feature)).show();
                            }
                        }
                    });
        },
        /**
         *
         * @param {JQuery} queryTitleView
         * @param {Object} query
         */
        initTitleEvents: function(queryTitleView, query) {
            var widget = this;
            // noinspection JSVoidFunctionReturnValueUsed
            queryTitleView.on('click', '.-fn-export', function() {
                        var layer = query.layer;
                        var features = layer.features;
                        var exportFormat = 'xls';

                        if(features.length && features[0].cluster) {
                            features = _.flatten(_.pluck(layer.features, "cluster"));
                        }

                        var markedFeatures = _.where(features, {mark: true});

                        if( markedFeatures.length) {
                            widget.exportFeatures(query.id, exportFormat, _.pluck(markedFeatures, 'fid'));
                        } else {
                            widget.exportFeatures(query.id, exportFormat, _.pluck(features, 'fid'));
                        }

                        return false;
            }).on('click', '.-fn-edit', function() {
                        widget.openQueryManager(query);
                        return false;
            }).on('click', '.-fn-zoomtolayer', function() {
                        var layer = query.layer;
                        layer.map.zoomToExtent(layer.getDataExtent());
                        return false;
            }).on('click', '.-fn-visibility', function() {
                        /** @todo: implement this properly or remove the button! */
                        return false;
                        // var query = context.query;
                        // var layer = query.layer;
                        // layer.setVisibility()
                        // $.notify("Chnage visibility of layer");
                        // return false;
            }).on('click', '.-fn-delete', function() {
                        widget.confirmDelete(query);
                        return false;
            });
        },
        initAccordion: function(queriesAccordionView) {
            var widget = this;
            queriesAccordionView.accordion({
                // see https://api.jqueryui.com/accordion/
                header: ".query-header",
                collapsible: true,
                active: false,
                heightStyle: 'content',
                icons: false,
                beforeActivate: function(event, ui) {
                    var query = ui.newHeader && ui.newHeader.data('query');
                    var oldQuery = ui.oldHeader && ui.oldHeader.data('query');
                    if (oldQuery) {
                        oldQuery.isActive = false;
                        oldQuery.selectControl.deactivate();
                        oldQuery.layer.setVisibility(false);
                    }
                    if (query && query.exportOnly) {
                        return false;
                    }
                    if (query) {
                        ui.newHeader.addClass('loading');
                    }
                },
                activate: function(event, ui) {
                    var query = ui.newHeader && ui.newHeader.data('query');
                    if (query) {
                        query.isActive = true;
                        query.selectControl.activate();
                        query.layer.setVisibility(true);
                        widget.fetchQuery(query);
                    }
                }
            });
        },

        /**
         * Highlight schema feature on the map and table view
         *
         * @param {OpenLayers.Feature} feature
         * @param {boolean} highlight
         * @private
         */
        _highlightSchemaFeature: function(feature, highlight, highlightTableRow) {
            var feature_ = this._checkFeatureCluster(feature);
            feature_.layer.drawFeature(feature_, feature_.__hidden__ && 'invisible' || highlight && 'select' || 'default');

            if (highlightTableRow) {
                this._highlightTableRow(feature, highlight && !feature_.__hidden__);
            }
        },

        /**
         * Highlight table row
         *
         * @param {OpenLayers.Feature} feature
         * @param {boolean} highlight
         * @private
         */
        _highlightTableRow: function(feature, highlight) {
            var $table = $('table:first', feature.layer.query.resultView);
            var features = feature.cluster ? feature.cluster : [feature];

            for (var i = 0; i < features.length; ++i) {
                if (features[i].tableRow) {
                    var tr = features[i].tableRow;
                    $(tr).toggleClass('hover', !!highlight);
                    if (highlight) {
                        this.tableRenderer.pageToRow($table, tr);
                    }
                    break;
                }
            }
        },

        /**
         * Check feature cluster
         *
         * @param {OpenLayers.Feature} feature
         * @returns {OpenLayers.Feature} feature
         * @private
         */
        _checkFeatureCluster: function(feature) {
            var isOutsideFromCluster = !_.contains(feature.layer.features, feature);

            if(isOutsideFromCluster) {
                _.each(feature.layer.features, function(clusteredFeatures) {
                    if(!clusteredFeatures.cluster) {
                        return;
                    }

                    if(_.contains(clusteredFeatures.cluster, feature)) {
                        feature = clusteredFeatures;
                        return false;
                    }
                });
            }

            return feature;
        },

        /**
         * Get target OpenLayers map object
         *
         * @returns  {OpenLayers.Map}
         */
        getMap: function(){
            return this.map;
        },

        /**
         *
         * @param uri suffix
         * @param data object or null
         * @param method string (default 'POST'; @todo: 'GET' should be default)
         * @return xhr jQuery XHR object
         * @version 0.2
         */
        query: function(uri, data, method) {
            var widget = this;
            return $.ajax({
                url:         widget.elementUrl + uri,
                method:      method || 'POST',
                contentType: "application/json; charset=utf-8",
                dataType:    "json",
                data:        data && (method === 'GET' && data || JSON.stringify(data)) || null
            }).fail(function(xhr) {
                // this happens on logout: error callback with status code 200 'ok'
                if (xhr.status === 200 && xhr.getResponseHeader("Content-Type").toLowerCase().indexOf("text/html") >= 0) {
                    window.location.reload();
                }
            }).fail(function(xhr, message, e) {
                if (xhr.statusText === 'abort') {
                    return;
                }
                var errorMessage = Mapbender.trans('mb.search.api.query.error');
                var errorDom = $(xhr.responseText);
                // https://stackoverflow.com/a/298758
                var exceptionTextNodes = $('.sf-reset .text-exception h1', errorDom).contents().filter(function() {
                    return this.nodeType === (Node && Node.TEXT_NODE || 3) && ((this.nodeValue || '').trim());
                });
                if (exceptionTextNodes && exceptionTextNodes.length) {
                    errorMessage = [errorMessage, exceptionTextNodes[0].nodeValue.trim()].join("\n");
                }
                $.notify(errorMessage, {
                    autoHide: false
                });
            });
        },

        /**
         *
         * @param queryId
         * @param exportType
         * @param idList
         */
        exportFeatures: function(queryId, exportType, idList) {
            var widget = this;
            var form = $('<form enctype="multipart/form-data" method="POST" style="display: none"/>');

            if(idList) {
                for (var key in idList) {
                    form.append($('<input name="ids[]" value="' + idList[key] + '" />'));
                }
            }
            form.append($('<input name="queryId" value="' + queryId + '" />'));
            form.append($('<input name="type" value="' + exportType + '" />'));
            form.attr('action', widget.elementUrl + 'export');
            form.attr('target', '_blank');

            $("body").append(form);

            form.submit();

            setTimeout(function() {
                form.remove();
            }, 200);
        },

        /**
         * Reload or replace features from the layer and feature table
         * - Fix OpenLayer bug by clustered features.
         *
         * @param layer
         * @version 0.2
         */
        reloadFeatures: function(query, _features) {
            var widget = this;
            var schema = widget._schemas[query.schemaId];
            var layer = query.layer;
            var features = _features ? _features : layer.features;

            if(features.length && features[0].cluster) {
                features = _.flatten(_.pluck(layer.features, "cluster"));
            }

            layer.removeAllFeatures();
            layer.addFeatures(features);

            // Add layer to feature
            _.each(features, function(feature) {
                feature.layer = layer;
                feature.query = query;
                feature.schema = schema;
            });

            layer.redraw();
            this.replaceTableRows(query, features);
        },
        replaceTableRows: function(query, features) {
            this.tableRenderer.replaceRows($('table:first', query.resultView), features);
        },
        /**
         * Update cluster strategies
         */
        updateClusterStrategies: function() {
            var scale = Mapbender.Model.getCurrentScale(false);
            var enabled = false;
            for (var i = 0; i < this.options.clustering.length; ++i) {
                var clusterSetting = this.options.clustering[i];
                if (scale >= clusterSetting.scale) {
                    enabled = !clusterSetting.disable;
                    break;
                }
            }

            var widget = this;
            _.each(this._queries, function(query) {
                widget.toggleClustering(query, enabled);
            });
        },
        toggleClustering: function(query, state) {
            var layer = query.layer;
            var styles = layer.options.styleMap.styles;
            var clusterStrategy = layer.strategies[0];
            if (state && clusterStrategy) {
                styles['default'] = styles.clusterDefault;
                styles['select'] = styles.clusterSelect;
                clusterStrategy.activate();
            } else {
                styles['default'] = styles.featureDefault;
                styles['select'] = styles.featureSelect;
                if (clusterStrategy) {
                    clusterStrategy.deactivate();
                }
            }
        },
        removeQuery: function(query) {
            if (query.resultView) {
                query.resultView.remove();
            }
            if (query.titleView) {
                query.titleView.remove();
            }
            if (query.layer && query.layer.map) {
                query.layer.map.removeControl(query.selectControl);
                query.layer.map.removeLayer(query.layer);
            }
            delete this._queries[query.id];
            $('.queries-accordion', this.element).accordion('refresh');
        },
        confirmDelete: function(query) {
            var self = this;
            var $content = $(document.createElement('div'))
                .text('Die Suche "' + query.name + '" löschen?')
            ;
            $content.dialog({
                title: 'Suche löschen?',
                classes: {
                    'ui-dialog': 'ui-dialog mb-search-dialog'
                },
                modal: true,
                buttons: [{
                    text: 'Ok',
                    click: function() {
                        self.query('query/remove', {id: query.id}).then(function(r) {
                            $.notify("Die Suche wurde gelöscht!", 'notice');
                            self.removeQuery(query);
                            $content.dialog('destroy');
                        });
                    }
                }, {
                    text: Mapbender.trans('mb.actions.cancel'),
                    click: function() {
                        $content.dialog('destroy');
                    }
                }]
            });
        }
    });

})(jQuery);
