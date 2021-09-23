(function($) {
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
        map:                    null,
        templates_: {},

        /**
         * Dynamic loaded styles
         */
        _styles:          null,
        _schemas:         null,
        _styleMaps:       null,
        _queries:         {},
        _originalQueries: {},
        _typeFilter:      null,
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
            var rendered = jQuery.Deferred();
            this.templates_['query-manager'] = $('.-tpl-query-manager', this.element).remove().css({display: null}).html();
            this.templates_['style-map-manager'] = $('.-tpl-style-map-manager', this.element).remove().css({display: null}).html();
            this.templates_['query'] = $('.-tpl-query', this.element).remove().css({display: null}).html();

            widget.elementUrl = Mapbender.configuration.application.urls.element + '/' + element.attr('id') + '/';
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                widget.map = mbMap.map.olMap;
                widget._setup();
                rendered.resolveWith(true);
            }, function() {
                Mapbender.checkTarget("mbSearch");
            });
            this.postSetup_(rendered);
        },
        _setup: function() {
            var widget = this;
            var element = widget.element;

                $('select[name="typeFilter"]', element).on('change', function() {
                    var schemaId = $(this).val();

                            widget._typeFilter = schemaId;

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
                    widget.openCreateDialog();
                });
                element.on('click', '.new-stylemap', function() {
                    widget.openStyleMapManager({id: null}, widget._styles);
                });
                element.on('click', '.new-style', function() {
                    widget.openStyleEditor();
                });
            },
        postSetup_: function(rendered) {
            var widget = this;

            jQuery.when(
                widget.refreshSchemas(),
                widget.refreshStyles(),
                widget.refreshStyleMaps(),
                rendered
            ).done(function() {
                widget.refreshQueries().done(function(r) {
                    widget.renderSchemaFilterSelect();
                    widget._trigger('ready');
                });
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

            element.find('option').remove();

            filterSelect.append('<option value="-1">Alle Objekttypen</option>');
            _.each(_.unique(_.pluck(queries, 'schemaId')), function(schemaId) {
                var schema = schemas[schemaId];
                filterSelect.append('<option value="' + schemaId + '">' + schema.title + '</option>');
            });

            filterSelect.val(-1);

            // Restore previous value
            if(widget._typeFilter != null){
                console.log("Rerender filter",widget._typeFilter);
                window.setTimeout(function() {
                    filterSelect.val(widget._typeFilter);
                    filterSelect.trigger("change")
                },10);
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
         * Open style editor dialog
         *
         * @param options
         * @todo: Check inputs
         */
        openStyleEditor: function(options) {
            var widget = this;
            var styleEditor = $("<div/>").featureStyleEditor(options);

            styleEditor.bind('featurestyleeditorsubmit', function(e, context) {
                var formData = styleEditor.formData();
                var incompleteFields = styleEditor.has(".has-error");
                var styleId = styleEditor.featureStyleEditor('option', 'data').id;
                var isNew = !!styleId;

                if(incompleteFields.size()) {
                    $.notify("Bitte vervollständigen sie die Daten.");
                } else {
                    widget.query('style/save', {
                        style: _.extend(formData, {id: styleId})
                    }).done(function(r) {
                        var style = r.style;
                        styleEditor.formData(style);
                        $.notify("Style erfolgreich gespeichert!", "info");
                        styleEditor.featureStyleEditor('close');
                        widget.refreshStyles();
                    });
                }
            });

            return styleEditor;
        },


        /**
         * Open query manager
         */
        openQueryManager: function(query) {
            var widget = this;
            var element = widget.element;
            var styleMaps = widget._styleMaps;
            var schemas = widget._schemas;
            var queryManager = $("<div/>");
            var map = widget.map;

            queryManager.queryManager({
                data:      query,
                schemas:   schemas,
                styleMaps: styleMaps,
                template: this.templates_['query-manager']
            });

            queryManager
                .bind('querymanagerstylemapchange', function(event, context) {
                    widget.openStyleMapManager(context.styleMap, widget._styles);
                })
                .bind('querymanagersubmit', function(event, context) {
                    widget.query('query/save', {query: context.data}).done(function(r) {
                        var queryManagerWidget = context.widget;
                        $.extend(query, r.entity);
                        queryManagerWidget.close();
                        $.notify("Erfolgreich gespeichert!", "info");

                        widget.refreshQueries().done(function() {
                            var updatedQuery = _.findWhere( widget._queries, {id: query.id});
                            updatedQuery.titleView.addClass('updated');
                            widget.renderSchemaFilterSelect();
                        })
                    });
                })
                .bind('querymanagercheck', function(event, context) {
                    var queryDialog = context.dialog;
                    queryDialog.disableForm();
                    widget.query('query/check', {
                        query:             context.data,
                        srid:              map.getProjectionObject().proj.srsProjNumber,
                        intersect: map.getExtent().toGeometry().toString()
                    }).done(function(r) {
                        queryDialog.enableForm();

                        if(r.errorMessage) {
                            $.notify("Fehler beim Ausführen:\n" + r.errorMessage, 'error');
                            return;
                        }

                        $.notify("Anzahl der Ergebnisse : " + r.count + "\nAusführungsdauer: " + r.executionTime, 'info');
                        queryDialog.enableForm();
                    });
                });

            element.bind('mbsearchstylesmapsupdated', function(e, styleMaps) {
                queryManager.queryManager('updateStyleMapList', styleMaps);
            });

        },

        /**
         * Create query
         */
        openCreateDialog: function(query) {
            var widget = this;
            widget.openQueryManager();
        },

        /**
         * Open style map manager
         */
        openStyleMapManager: function(data, styles) {
            var widget = this;
            var element = widget.element;
            var styleMapManager = $("<div/>");

            styleMapManager.bind('stylemapmanagereditstyle', function(event, context) {
                var style = context.style;
                if(style) {
                    widget.openStyleEditor({data: style});
                } else {
                    $.notify("Bitte Style wählen!");
                }
            });

            styleMapManager.bind('stylemapmanagersubmit', function(event, context) {
                var formData = styleMapManager.formData();
                var incompleteFields = styleMapManager.has(".has-error");
                var styleMapId = styleMapManager.styleMapManager('option', 'data').id;
                var isNew = !!styleMapId;

                if(incompleteFields.size()) {
                    $.notify("Bitte vervollständigen sie die Daten.");
                } else {
                    widget.query('stylemap/save', {
                        styleMap: _.extend(formData, {id: styleMapId})
                    }).done(function(r) {
                        var styleMap = r.styleMap;
                        styleMapManager.formData(styleMap);
                        $.notify("Stylemap erfolgreich gespeichert!", "info");
                        styleMapManager.styleMapManager('close');
                        widget.refreshStyleMaps();
                    });
                }
            });

            element.bind('mbsearchstylesupdated', function(e, styles) {
                styleMapManager.styleMapManager('updateStyleList', styles);
            });

            return styleMapManager.styleMapManager({
                styles: styles,
                data: data,
                template: this.templates_['style-map-manager']
            })
        },

        /**
         * Refresh styles
         */
        refreshStyles: function() {
            var widget = this;
            return widget.query('style/list').done(function(r) {
                widget._styles = r.list;
                widget._trigger('stylesUpdated', null, r.list);
            });
        },

        /**
         * Refresh style maps
         */
        refreshStyleMaps: function() {
            var widget = this;
            return widget.query('stylemap/list').done(function(r) {
                widget._styleMaps = r.list;
                widget._trigger('stylesMapsUpdated', null, r.list);
            });
        },

        /**
         * Refresh feature types
         */
        refreshSchemas: function() {
            var widget = this;
            return widget.query('schemas/list').done(function(r) {
                var schemas = r.list;
                widget._schemas = schemas;
                widget._trigger('schemasUpdate', null, schemas);
            });
        },

        /**
         * Refresh feature types
         */
        refreshQueries: function() {
            var widget = this;
            return widget.query('queries/list').done(function(r) {
                var queries = $.isArray(r.list) ? {} : r.list;
                // clean up previous queries
                _.each(widget._queries, function(query) {
                    if(query.layer && query.layer.map) {
                        var layer = query.layer;
                        var map = layer.map;
                        map.removeControl(query.selectControl);
                        map.removeLayer(layer);
                    }
                });

                widget._queries = queries;
                widget._originalQueries = {};
                _.each(queries, function(query, id) {
                    widget._originalQueries[id] = _.clone(query);
                });
                widget._trigger('queriesUpdate', null, queries);
                widget.renderQueries(queries);
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

            query.titleView.queryResultTitleBarView('showPreloader');

            if (!query.extendOnly && query._rowFeatures) {
                setTimeout(function() {
                    var featureCollection = widget.parseResponseFeatures_(query._rowFeatures);
                    query.resultView.queryResultView('updateList', featureCollection);
                    widget.reloadFeatures(query, featureCollection);
                    query.titleView.queryResultTitleBarView('hidePreloader');
                }, 100);
                return null;
            }else {
                query.resultView.queryResultView('updateList', []);
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

                query.resultView.queryResultView('updateList', featureCollection);
                widget.reloadFeatures(query, featureCollection);
            }).always(function() {
                query.titleView.queryResultTitleBarView('hidePreloader');
            });
        },

        /**
         * Render queries
         */
        renderQueries: function(queries) {
            var widget = this;
            var element = widget.element;
            var queriesContainer = element.find('> .html-element-container');
            var queriesAccordionView = $('<div class="queries-accordion"/>');

            var queriesArray = _.toArray(queries);

            // TODO: clean up, remove/refresh map layers, events, etc...
            queriesContainer.empty();
            for (var i = 0; i < queriesArray.length; ++i) {
                queriesContainer.append(this.renderQuery(queriesAccordionView, queriesArray[i]));
            }
            this.initAccordion(queriesAccordionView, queries);
            queriesContainer.append(queriesAccordionView);
        },
        renderQuery: function(queriesAccordionView, query) {
            var widget = this;
            var map = this.map;
            var options = this.options;
                var schema = widget._schemas[query.schemaId];
                schema.clustering =  options.clustering; // schema.clustering ? schema.clustering : options.clustering;
            var $query = $(widget.templates_['query']);
            var queryTitleView = query.titleView = $query.filter('.query-header');
            queryTitleView.data('query', query);
                queryTitleView.queryResultTitleBarView();
            var queryView = query.resultView = $query.filter('.query-content-panel');
            queryView.data('query', query);
            queryView.queryResultView();
                var layerName = 'query-' + query.id;
                var isClustered = schema.isClustered = schema.hasOwnProperty('clustering');

                /**
                 * Create query layer and style maps
                 */
                query.layer = function createQueryLayer(query) {
                    var styleMaps = widget._styleMaps;
                    var styleDefinitions = widget._styles;
                    var styleMapDefinition = _.extend({}, styleMaps[query.styleMap] ? styleMaps[query.styleMap] : _.first(_.toArray(styleMaps)));
                    var styleMapConfig = {};
                    var strategies = [];

                    _.each(styleMapDefinition.styles, function(styleId, key) {
                        if(styleDefinitions[styleId]) {
                            var styleDefinition = _.extend({}, styleDefinitions[styleId]);

                            styleMapConfig[key] = new OpenLayers.Style(styleDefinition, {
                                context: {
                                    'customFillColor': function() {
                                    }
                                }
                            })
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

                    if(isClustered) {
                        var clusterStrategy = new OpenLayers.Strategy.Cluster({
                            threshold: 1,
                            distance:  -1
                        });
                        strategies.push(clusterStrategy);
                        query.clusterStrategy = clusterStrategy;
                    }

                    styleMapConfig.featureDefault = styleMapConfig['default'];
                    styleMapConfig.featureSelect = styleMapConfig['select'];
                    styleMapConfig.featureInvisible = styleMapConfig['invisible'];

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

                    styleMapConfig.clusterInvisible = styleMapConfig.featureInvisible.defaultStyle;

                    if(schema.featureType == "boris_ipe" && !hasDefaultStyle) {
                        /**
                         * Darstellung mit orange-farbigem Umring aus einer Punkt-Strich-Linie
                         * Vorgabe (durch Nutzer änderbar): Rand/Umring-Farbe: #e8c02f, Breite 5, Style: Strichpunkt
                         */
                        var restrictedStyle = {
                            strokeColor:     "#e8c02f",
                            strokeWidth:     5,
                            strokeOpacity:   1,
                            strokeDashstyle: "dashdot"
                        };

                        _.each(['featureDefault', 'clusterDefault'], function(styleMapConfigName) {
                            _.extend(styleMapConfig[styleMapConfigName].defaultStyle, restrictedStyle);
                        });

                    }
                    if(schema.featureType == "segment" && !hasDefaultStyle) {
                        /**
                         * Darstellung mit rotem Umring aus einer Punkt-Strich-Linie
                         Vorgabe (durch Nutzer änderbar): Rand/Umring-Farbe: #e50c24, Breite 5, Style: Strichpunkt
                         */
                        var restrictedStyle = {
                            strokeColor:     "#e50c24",
                            strokeWidth:     5,
                            strokeOpacity:   1,
                            strokeDashstyle: "dashdot"
                        };
                        _.each(['featureDefault', 'clusterDefault'], function(styleMapConfigName) {
                            _.extend(styleMapConfig[styleMapConfigName].defaultStyle, restrictedStyle);
                        });
                    }

                    if(schema.featureType == "flur" && !hasDefaultStyle) {
                        /** Vorgabe (durch Nutzer änderbar): Punkt-Objekt, Durchmesser 7, Farbe #0c7e00 */
                        var restrictedStyle = {
                            fillColor:   "#0c7e00",
                            pointRadius: 7
                        };
                        _.each(['featureDefault', 'clusterDefault'], function(styleMapConfigName) {
                            _.extend(styleMapConfig[styleMapConfigName].defaultStyle, restrictedStyle);
                        });
                    }

                    if(schema.featureType == "be") {
                        var restrictedStyle = {
                        };
                        _.extend(styleMapConfig['featureDefault'].defaultStyle, restrictedStyle);
                        _.extend(styleMapConfig['featureSelect'].defaultStyle, restrictedStyle);
                        _.extend(styleMapConfig['featureInvisible'].defaultStyle, restrictedStyle);
                        _.extend(styleMapConfig['clusterDefault'].defaultStyle, restrictedStyle);
                        _.extend(styleMapConfig['clusterSelect'].defaultStyle, restrictedStyle);
                        _.extend(styleMapConfig['clusterInvisible'].defaultStyle, restrictedStyle);

                        var rawRules = [
                            {value: "DB Netz AG (BK09)", fillColor: "#2ca9a9"},
                            {value: "DB Netz AG (BK16)", fillColor: "#adfcfc"},
                            {value: "DB Station & Service AG", fillColor: "#ffb0be"},
                            {value: "Usedomer Bäderbahn GmbH (UBB)", fillColor: "#ff80c0"},
                            {value: "DB Energie GmbH", fillColor: "#f2f2f2"},
                            {value: "DB Fernverkehr AG", fillColor: "#d5aaff"},
                            {value: "DB Regio AG", fillColor: "#ffb76f"},
                            {value: "DB Schenker Rail AG", fillColor: "#793f96"},
                            {value: "DB Fahrzeuginstandhaltung GmbH", fillColor: "#46c426"},
                            {value: "DB AG", fillColor: "#d8fcd8"},
                            {value: "DB Systel GmbH", fillColor: "#ad7b10"},
                            {value: "Stinnes Immobiliendienst (alt)", fillColor: "#c90070"},
                            {value: "DB Mobility Logistics AG", fillColor: "#e83096"},
                            {value: "Stinnes ID GmbH & Co. KG", fillColor: "#e73165"},
                            {value: "2. KG Stinnes Immobiliendienst", fillColor: "#e2007f"},
                            {value: "Schenker AG", fillColor: "#793f96"}
                        ];

                        _.each(rawRules,function(rule) {
                            styleMapConfig['featureDefault'].rules.push(new OpenLayers.Rule({
                                filter:     new OpenLayers.Filter.Comparison({
                                    type:     OpenLayers.Filter.Comparison.EQUAL_TO,
                                    property: "eigentuemer",
                                    value:    rule.value
                                }),
                                symbolizer: {
                                    fillColor: rule.fillColor
                                }
                            }));
                        });

                        styleMapConfig['featureDefault'].rules.push( new OpenLayers.Rule({
                            elseFilter: true,
                            symbolizer: styleMapConfig.featureDefault.defaultStyle
                        }));
                    }

                    var layer = new OpenLayers.Layer.Vector(layerName, {
                        styleMap:   new OpenLayers.StyleMap(styleMapConfig, {extendDefault: true}),
                        visibility: false,
                        strategies: strategies
                    }, {extendDefault: true});

                    layer.query = query;

                    return layer;
                }(query);

                map.addLayer(query.layer);

                var selectControl = query.selectControl = new OpenLayers.Control.SelectFeature(query.layer, {
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


                // Shows     how long queries runs and queries results can be fetched.
                // widget.query('query/check', {query: query}).done(function(r) {
                //     queryTitleView.find(".titleText").html(query.name + " (" + r.count + ", "+r.executionTime+")")
                // });
            this.initQueryViewEvents(queryView);
            this.initTitleEvents(queryTitleView);
            queriesAccordionView.append(queryTitleView);
            queriesAccordionView.append(queryView);
        },
        initQueryViewEvents: function(queryView) {
            var widget = this;
                queryView
                    .bind('queryresultviewchangeextend', function(e, context) {
                        var query = context.query;
                        query.extendOnly = context.checked;
                        widget.fetchQuery(query);
                        return false;
                    })
                    .bind('queryresultviewzoomto', function(e, context) {
                        Mapbender.Util.OpenLayers2.zoomToJsonFeature(context.feature);
                        return false;
                    })
                    .bind('queryresultviewprint', function(e, context) {
                        var feature = context.feature;
                        var printWidget = null;
                        _.each($('.mb-element-printclient'), function(el) {
                            printWidget = $(el).data("mapbenderMbPrintClient");
                            if(printWidget) {
                                return false;
                            }
                        });

                        if(!printWidget){
                            $.notify("Druckelement ist nicht verfügbar");
                        }

                        var query = context.query;
                        var featureTypeName = widget._schemas[query.schemaId].featureType;
                        printWidget.printDigitizerFeature(featureTypeName, feature.fid);
                        return false;
                    })
                    .bind('queryresultviewmark', function(e, context) {
                        var tr = $(context.ui).closest("tr");
                        var tableApi = tr.closest('table').dataTable().api();
                        var row = tableApi.row(tr);
                        var feature = row.data();

                        if(tr.is(".mark")) {
                            tr.removeClass('mark');
                        } else {
                            tr.addClass('mark');
                        }

                        feature.mark = tr.is(".mark");

                        return false;
                    })
                    .bind('queryresultviewtogglevisibility', function(e, context) {

                        var feature = widget._checkFeatureCluster(context.feature);
                        var layer = feature.layer;
                        var featureStyle = (context.feature.styleId) ? context.feature.styleId : 'default';

                        if(!feature.renderIntent || feature.renderIntent != 'invisible') {
                            featureStyle = 'invisible';
                        }

                        layer.drawFeature(feature, featureStyle);

                        context.ui.closest('tr').toggleClass('invisible-feature');

                        return false;
                    })
                    .bind('queryresultviewfeatureover', function(e, context) {
                        widget._highlightSchemaFeature(context.feature, true);
                        return false;
                    })
                    .bind('queryresultviewfeatureout ', function(e, context) {
                        widget._highlightSchemaFeature(context.feature, false);
                        return false;
                    })
                    .bind('queryresultviewfeatureclick', function(e, context) {
                        function format(feature) {
                            var table = $("<table class='feature-details'/>");

                            var query = feature.layer.query;
                            var schema = widget._schemas[query.schemaId];
                            var fieldNames = _.object(_.pluck(schema.fields, 'name'), _.pluck(schema.fields, 'title'));

                            _.each(fieldNames, function(title, key) {

                                if(!feature.data.hasOwnProperty(key) || feature.data[key] == "") {
                                    return;
                                }

                                table.append($("<tr/>")
                                    .append($('<td class="key"/>').html(title + ": "))
                                    .append($('<td class="value"/>').text(feature.data[key])));

                            });
                            return table;
                        }

                        var tr = $(context.ui);
                        var tableApi = tr.closest('table').dataTable().api();
                        var row = tableApi.row(tr);
                        var feature = tr.data('feature');

                        if(row.child.isShown()) {
                            row.child.hide();
                            tr.removeClass('shown');
                        } else {
                            if (feature && feature.data) {
                                row.child(format(feature)).show();
                                tr.addClass('shown');
                            }
                        }
                    });
        },
        initTitleEvents: function(queryTitleView) {
            var widget = this;
                queryTitleView
                    .bind('queryresulttitlebarviewexport', function(e, context) {
                        var query = context.query;
                        var layer = query.layer;
                        var features = layer.features;
                        var exportFormat = 'xls';
                        var markedFeatures = null;

                        if(features.length && features[0].cluster) {
                            features = _.flatten(_.pluck(layer.features, "cluster"));
                        }

                        markedFeatures = _.where(features, {mark: true});

                        if( markedFeatures.length) {
                            widget.exportFeatures(query.id, exportFormat, _.pluck(markedFeatures, 'fid'));
                        } else {
                            widget.exportFeatures(query.id, exportFormat, _.pluck(features, 'fid'));
                        }

                        return false;
                    })
                    .bind('queryresulttitlebarviewedit', function(e, context) {
                        var originalQuery = widget._originalQueries[context.query.id];
                        widget.openQueryManager(originalQuery);
                        return false;
                    })
                    .bind('queryresulttitlebarviewzoomtolayer', function(e, context) {
                        var query = context.query;
                        var layer = query.layer;
                        layer.map.zoomToExtent(layer.getDataExtent());
                        return false;
                    })
                    .bind('queryresulttitlebarviewvisibility', function(e, context) {
                        console.log(context);
                        // var query = context.query;
                        // var layer = query.layer;
                        // layer.setVisibility()
                        // $.notify("Chnage visibility of layer");
                        // return false;
                    })
                    .bind('queryresulttitlebarviewremove', function(e, context) {
                        var query = context.query;
                        Mapbender.confirmDialog({
                            title:     'Suche löschen?',
                            html:      'Die Suche "' + query.name + '" löschen?',
                            onSuccess: function() {
                                widget.query('query/remove', {id: query.id}).done(function(r) {
                                    $.notify("Die Suche wurde gelöscht!", 'notice');
                                    widget.refreshQueries();
                                })
                            }
                        });
                    });

        },
        initAccordion: function(queriesAccordionView, queries) {
            var widget = this;
            var map = this.map;
            queriesAccordionView.accordion({
                // see https://api.jqueryui.com/accordion/
                header: ".query-header",
                collapsible: true,
                active: false,
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
                        $('.preloader', ui.newHeader).show();
                    }
                },
                activate: function(event, ui) {
                    var query = ui.newHeader && ui.newHeader.data('query');
                    if (query) {
                        query.isActive = true;
                        query.selectControl.activate();
                        query.layer.setVisibility(true);
                        widget.fetchQuery(query).always(function() {
                            $('.preloader', ui.newHeader).hide();
                        });
                    }
                }
            });

            var mapChangeHandler = function(e) {
                _.each(queries, function(query) {
                    if (!query.isActive || !query.extendOnly) {
                        return
                    }
                    widget.fetchQuery(query);
                });
                return false;
            };
            map.events.register("moveend", null, mapChangeHandler);
            map.events.register("zoomend", null, function(e){
                mapChangeHandler(e);
                widget.updateClusterStrategies();
            });

            widget.updateClusterStrategies();
        },

        /**
         * Highlight schema feature on the map and table view
         *
         * @param {OpenLayers.Feature} feature
         * @param {boolean} highlight
         * @private
         */
        _highlightSchemaFeature: function(feature, highlight, highlightTableRow) {
            var feature = this._checkFeatureCluster(feature);
            var isSketchFeature = !feature.cluster && feature._sketch && _.size(feature.data) == 0;
            var layer = feature.layer;

            if (this._isFeatureInvisible(feature) || isSketchFeature) {
                return;
            }

            layer.drawFeature(feature, highlight ? 'select' : 'default');

            if(highlightTableRow) {
                this._highlightTableRow(feature, highlight);
            }
        },

        /**
         * Highlight feature on the map
         *
         * @param {OpenLayers.Feature} feature
         * @param {boolean} highlight
         * @private
         */
        _highlightFeature: function(feature, highlight) {
            return this._highlightSchemaFeature(feature, highlight);
        },

        /**
         * Highlight table row
         *
         * @param {OpenLayers.Feature} feature
         * @param {boolean} highlight
         * @private
         */
        _highlightTableRow: function(feature, highlight) {
            var table = feature.layer.query.resultView.find('.mapbender-element-result-table');
            var features = feature.cluster ? feature.cluster : [feature];

            for (var i = 0; i < features.length; ++i) {
                if (features[i].tableRow) {
                    var tr = features[i].tableRow;
                    $(tr).toggleClass('hover', !!highlight);
                    if (highlight) {
                        var tableApi = $('table:first', table).DataTable();
                        var rowsPerPage = tableApi.page.len();
                        var rowIndex = tableApi.rows({order: 'current'}).nodes().indexOf(tr);
                        var pageWithRow = Math.floor(rowIndex / rowsPerPage);
                        tableApi.page(pageWithRow).draw(false);
                    }
                    break;
                }
            }
        },

        /**
         * Is a feature invisible?
         *
         * @param {OpenLayers.Feature} feature
         * @returns {boolean}
         * @private
         */
        _isFeatureInvisible: function(feature) {
            return (feature.renderIntent === 'invisible') ? true : false;
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
            var tableApi = $('table', query.resultView).dataTable().api();
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

            tableApi.clear();
            tableApi.rows.add(features);
            tableApi.draw();
        },

        /**
         * Update cluster strategies
         */
        updateClusterStrategies: function() {

            var widget = this;
            var options = widget.options;
            var scale = Math.round(widget.map.getScale());
            var map = widget.map;
            var clusterSettings;
            var closestClusterSettings;

            _.each(widget._queries, function(query) {
                var schema = widget._schemas[query.schemaId];
                var layer = query.layer;
                var styleMap = layer.options.styleMap;
                var styles = styleMap.styles;
                var features = layer.features;

                clusterSettings = null;

                if(!schema.clustering) {
                    return
                }

                _.each(schema.clustering, function(_clusterSettings) {
                    if(_clusterSettings.scale == scale) {
                        clusterSettings = _clusterSettings;
                        return false;
                    }

                    if(_clusterSettings.scale < scale) {
                        if(closestClusterSettings && _clusterSettings.scale > closestClusterSettings.scale) {
                            closestClusterSettings = _clusterSettings;
                        } else {
                            if(!closestClusterSettings) {
                                closestClusterSettings = _clusterSettings;
                            }
                        }
                    }
                });

                if(!clusterSettings && closestClusterSettings) {
                    clusterSettings = closestClusterSettings
                }

                if(!clusterSettings) {
                    clusterSettings = {'disable': true};
                }

                if(clusterSettings) {
                    if(clusterSettings.hasOwnProperty('disable') && clusterSettings.disable) {
                        styles['default'] = styles.featureDefault;
                        styles['select'] = styles.featureSelect;
                        styles['invisible'] = styles.featureInvisible;

                        // query.layer.options.
                        query.clusterStrategy.distance = -1;

                        widget.reloadFeatures(query, []);
                        query.clusterStrategy.deactivate();
                        //schema.layer.redraw();
                        schema.isClustered = false;
                        widget.reloadFeatures(query, features);
                        // layer.redraw();

                    } else {
                        styles['default'] = styles.clusterDefault;
                        styles['select'] = styles.clusterSelect;
                        styles['invisible'] = styles.clusterInvisible;

                        query.clusterStrategy.activate();
                        schema.isClustered = true;
                        // layer.redraw();
                    }

                    if(clusterSettings.hasOwnProperty('distance')) {
                        query.clusterStrategy.distance = clusterSettings.distance;
                    }
                } else {
                    //schema.clusterStrategy.deactivate();
                }
            });
        }

    });

})(jQuery);
