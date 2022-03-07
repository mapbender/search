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
            cluster_threshold: 15000
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
            },
            be: {
                fillColor: '#adfcfc'
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
                return widget.query('style/save', style).then(function(response) {
                    widget._styles[response.id] = response;
                    return response;
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

            queryManager.queryManager({
                owner: this,
                data:      query,
                schemas:   schemas,
                styleMaps: this._styleMaps,
                template: this.templates_['query-manager']
            });
        },
        saveQuery: function(queryData) {
            var self = this;
            var isNew = !queryData.id;
            var query = isNew && {} || this._queries[queryData.id] || {};
            return this.query('query/save', queryData).then(function(response) {
                Object.assign(query, response);
                $.notify("Erfolgreich gespeichert!", "info");
                if (isNew) {
                    self.addQuery(query);
                } else {
                    self.updateQuery(query);
                    self.updateStyles_(query.layer, query);
                }
                self.renderSchemaFilterSelect();
                return query;
            });
        },
        checkQuery: function(query) {
            this.query('query/check', {
                query: query,
                srid: Mapbender.Model.getCurrentProjectionCode().replace(/^\w+:/, ''),
                intersect: this.getIntersectWkt_()
            }).done(function(r) {
                if(r.errorMessage) {
                    $.notify("Fehler beim Ausführen:\n" + r.errorMessage, 'error');
                    return;
                }
                $.notify("Anzahl der Ergebnisse : " + r.count + "\nAusführungsdauer: " + r.executionTime, 'info');
            });
        },
        getIntersectWkt_: function() {
            var extent = this.mbMap.getModel().getCurrentExtentArray();
            var coordinates = [
                [extent[0], extent[1]],
                [extent[2], extent[1]],
                [extent[2], extent[3]],
                [extent[0], extent[3]]
            ];
            coordinates.push(coordinates[0]);
            return [
                'POLYGON((',
                coordinates.map(function(coord) {
                    return coord.join(' ');
                }).join(','),
                '))'
            ].join('');
        },
        /**
         * Open style map manager
         */
        openStyleMapManager: function(data) {
            var widget = this;
            return this.styleEditor.editStyleMap(data, this._styles).then(function(styleMap) {
                return widget.query('stylemap/save', styleMap).then(function(response) {
                    widget._styleMaps[response.id] = response;
                    return response;
                });
            });
        },

        parseResponseFeatures_: function(data) {
            return data.map(function(featureData) {
                return new OpenLayers.Feature.Vector(OpenLayers.Geometry.fromWKT(featureData.geometry), featureData.properties);
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

            var request = {
                srid: this.mbMap.getModel().getCurrentProjectionCode().replace(/^\w+:/, ''),
                intersect: query.extendOnly && this.getIntersectWkt_() || null,
                queryId: query.id
            };

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
                this.fetchQuery(query);
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
            var strategies = [];
            if (this.options.cluster_threshold) {
                var clusterStrategy = new OpenLayers.Strategy.Cluster({
                    threshold: 1,
                    distance: 30
                });
                strategies.push(clusterStrategy);
            }

            var layer = new OpenLayers.Layer.Vector(null, {
                visibility: false,
                strategies: strategies
            });
            this.updateStyles_(layer, query);
            this.map.addLayer(layer);
            query.layer = layer;
            this.initSelectControl_(query, layer);

            layer.query = query;
            return layer;
        },
        updateStyles_: function(layer, query) {
            var self = this;
            var schema = this._schemas[query.schemaId];

            var customStyleIds = (this._styleMaps[query.styleMap] || {}).styles || {};
            var customStyleMapStyles = {
                default: customStyleIds.default && this._styles[customStyleIds.default] || {},
                select: customStyleIds.select && this._styles[customStyleIds.select] || {}
            };

            var styleRules = {
                invisible: {
                    display: 'none'
                },
                default: Object.assign({}, OpenLayers.Feature.Vector.style["default"], customStyleMapStyles.default || {}, this.customStyles_[schema.featureType] || {}),
                select: Object.assign({}, OpenLayers.Feature.Vector.style["select"], customStyleMapStyles.select || {}, this.customStyles_[schema.featureType] || {})
            };
            function getStyleContext(renderIntent) {
                return {
                    clusterLength: function(feature) {
                        return feature.cluster && feature.cluster.length > 1 ? feature.cluster.length : "";
                    },
                    customBeColor: function(feature) {
                        return self.getCustomBeColor_(feature) || styleRules[renderIntent].fillColor;
                    }
                };
            }

                    var clusterRules = {
                        pointRadius:         '15',
                        strokeColor:         "#d10a10",
                        strokeWidth:         2,
                        labelOutlineColor:   '#ffffff',
                        labelOutlineWidth:   2,
                        labelOutlineOpacity: 1,
                        fontSize:            '11',
                        fontColor:           '#707070',
                        label:               "${clusterLength}"
                    };

            if (schema.featureType === "be") {
                styleRules['default'].fillColor = '${customBeColor}';
                styleRules['default'].strokeColor = '${customBeColor}';
                clusterRules.strokeColor = '${customBeColor}';
            }
            styleRules['clusterDefault'] = Object.assign({}, styleRules['default'], clusterRules);

            var styles = {};
            var rules = [];
            if (this.options.cluster_threshold) {
                rules.push(new OpenLayers.Rule()); // Pre-apply standard styling
                rules.push(new OpenLayers.Rule({
                    symbolizer: clusterRules,
                    minScaleDenominator: this.options.cluster_threshold
                }));
            }
            Object.keys(styleRules).forEach(function(renderIntent) {
                styles[renderIntent] = new OpenLayers.Style(styleRules[renderIntent], {
                    context: getStyleContext(renderIntent),
                    rules: rules
                });
            });
            layer.styleMap = new OpenLayers.StyleMap(styles);
        },
        getCustomBeColor_: function(feature) {
            var colors = [
                            // NOTE: unicode in Object keys are problematic
                            // => use a list instead
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
            var feature_ = feature.cluster && feature.cluster[0] || feature;
            var match = _.findWhere(colors, {value: feature_.attributes.eigentuemer});
            return match && match.fillColor;
        },
        initSelectControl_: function(query, layer) {
            var self = this;
            var selectControl = new OpenLayers.Control.SelectFeature(layer, {
                    hover:        true,
                    highlightOnly: true,
                    active: false,
                    overFeature:  function(feature) {
                        self._highlightSchemaFeature(feature, true, true);
                    },
                    outFeature:   function(feature) {
                        self._highlightSchemaFeature(feature, false, true);
                    },
                    mousedown: function() {
                        return true;    // Not handled / allow propagation
                    }
            });


            query.selectControl = selectControl;
            this.map.addControl(selectControl);
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
                    widget.tableRenderer.toggleDetails(this, widget._schemas[query.schemaId]);
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
                } else {
                    if (xhr.statusText !== 'abort') {
                        $.notify(Mapbender.trans('mb.search.api.query.error'), {
                            autoHide: false
                        });
                    }
                }
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
            var enabled = scale >= this.options.cluster_threshold;
            var layers = Object.values(this._queries).map(function(query) {
                return query.layer;
            });
            for (var i = 0; i < layers.length; ++i) {
                var layer = layers[i];
                var clusterStrategy = layer.strategies[0];
                if (enabled && clusterStrategy) {
                    clusterStrategy.activate();
                } else {
                    if (clusterStrategy) {
                        clusterStrategy.deactivate();
                    }
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
        },
        __dummy__: null
    });

})(jQuery);
