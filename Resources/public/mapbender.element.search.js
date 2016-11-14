(function($) {

    $.notify.defaults( { globalPosition: 'top left', style: 'bootstrap'} );
    /**
     * Regular Expression to get checked if string should be translated
     *
     * @type {RegExp}
     */
    var translationReg = /^trans:\w+\.(\w|-|\.\w+)+\w+$/;

    /**
     * Translate digitizer keywords
     * @param title
     * @param withoutSuffix
     * @returns {*}
     */
    function translate(title, withoutSuffix) {
        return Mapbender.trans(withoutSuffix ? title : "mb.digitizer." + title);
    }

    /**
     * Translate object
     *
     * @param items
     * @returns object
     */
    function translateObject(items) {
        for (var k in items) {
            if(!items.hasOwnProperty(k)){
                continue;
            }
            var item = items[k];
            if(typeof item === "string" && item.match(translationReg)) {
                items[k] = translate(item.split(':')[1], true);
            } else if(typeof item === "object") {
                translateObject(item);
            }
        }
        return item;
    }

    /**
     * Check and replace values recursive if they should be translated.
     * For checking used "translationReg" variable
     *
     *
     * @param items
     */
    function translateStructure(items) {
        var isArray = items instanceof Array;
        for (var k in items) {
            if(isArray || k == "children") {
                translateStructure(items[k]);
            } else {
                if(typeof items[k] == "string" && items[k].match(translationReg)) {
                    items[k] = translate(items[k].split(':')[1], true);
                }
            }
        }
    }

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
            allowDigitize:     false,
            allowDelete:       false,
            allowEditData:     true,
            openFormAfterEdit: true,
            maxResults:        5001,
            pageLength:        10,
            oneInstanceEdit:   true,
            searchType:        "currentExtent",
            inlineSearch:      true,
            useContextMenu:    true,
            clustering:        [{
                scale:    5000000,
                distance: 30
            }]
        },
        map:                    null,
        currentSettings:        null,
        featureEditDialogWidth: "423px",

        /**
         * Dynamic loaded styles
         */
        _styles:    null,
        _schemas:   null,
        _styleMaps: null,
        _queries:   {},

        /**
         * Constructor.
         *
         * At this moment not all elements (like a OpenLayers) are avaible.
         *
         * @private
         */
        _create: function() {
            var widget = this;
            var element = widget.element;
            var options = widget.options;
            var target = $(".olMap").attr("id");

            if(!Mapbender.checkTarget("mbSearch", target)) {
                return;
            }

            widget.elementUrl = Mapbender.configuration.application.urls.element + '/' + element.attr('id') + '/';
            Mapbender.elementRegistry.onElementReady(target, function() {

                widget.map = $('#' + options.target).data('mapbenderMbMap').map.olMap;

                element.generateElements({
                    type:     'fieldSet',
                    children: [{
                        type:     'button',
                        title:    'Neue Abfrage',
                        cssClass: 'btn new-query',
                        css:      {width: '33%'},
                        click:    function() {
                            widget.openCreateDialog();
                        }
                    }, {
                        type:     'button',
                        title:    'Neue Theme',
                        cssClass: 'btn new-query',
                        css:      {width: '33%'},
                        click:    function() {
                            widget.openStyleMapManager({id: null}, widget._styles);
                        }
                    }, {
                        type:     'button',
                        title:    'Neuer Style',
                        cssClass: 'btn new-query',
                        css:      {width: '30%'},
                        click:    function() {
                            widget.openStyleEditor();
                        }
                    }]
                });

                element.generateElements({
                    type: 'html',
                    html: '<div class="queries"></div>'
                });

                // widget.activateContextMenu();

                //widget.map.resetLayersZIndex();
                widget._trigger('ready');
            });

            widget.refreshSchemas().done(function(){
                widget.refreshStyles().done(function(){
                    widget.refreshStyleMaps().done(function(){
                        widget.refreshQueries().done(function(){
                        });
                    });
                });
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
         * Load external source
         *
         * @param uri
         * @param onComplete
         */
        load: function(uri, onComplete) {
            var assetUrl = Mapbender.configuration.application.urls.asset + "bundles/mapbendersearch/";
            var asset = assetUrl + uri;
            console.log("Load: "+asset);
            $.getScript(asset, function(data, statusCode, xhr) {
                console.log("Loaded");
                onComplete && typeof onComplete == "function" && onComplete(eval(data), data, statusCode, xhr, uri);
            });
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

            queryManager.queryManager({
                data:      query,
                schemas:   schemas,
                styleMaps: styleMaps
            });

            queryManager
                .bind('querymanagerstylemapchange', function(event, context) {
                    widget.openStyleMapManager(context.styleMap, widget._styles);
                })
                .bind('querymanagersubmit', function(event, context) {
                    var errorInputs = $(".has-error", context.form);
                    var hasErrors = errorInputs.size() > 0;

                    if(hasErrors) {
                        return false;
                    }

                    widget.query('query/save', {query: context.data}).done(function(r) {
                        var queryManagerWidget = context.widget;
                        $.extend(queryManagerWidget.options.data, r.entity);
                        queryManagerWidget.close();
                        $.notify("Erfolgreich gespeichert!", "info");
                        widget.refreshQueries()
                    });
                })
                .bind('querymanagercheck', function(event, context) {
                    var queryDialog = context.dialog;
                    queryDialog.disableForm();
                    widget.query('query/check', {query: context.data}).done(function(r) {
                        queryDialog.enableForm();

                        if(r.errorMessage) {
                            $.notify("Fehler beim Ausführen:\n" + r.errorMessage, 'error');
                            return;
                        }

                        $.notify("Anzahl der Ergebnisse : " + r.count, 'info');
                        $.notify("Ausführungsdauer: " + r.executionTime, 'info');
                        $.notify(_.toArray(r.explainInfo).join("\n"), 'info');
                        queryDialog.enableForm();
                    });
                })
                .bind('querymanagerclose', function(e, styleMaps) {
                    // WORKS
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
                    widget.query('styleMap/save', {
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
                data:   data
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
            return widget.query('styleMap/list').done(function(r) {
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
                var queryList = r.list;

                // _.each(queryList, function(query, queryId) {
                //     if(!widget._queries.hasOwnProperty(queryId)) {
                //         widget._queries[queryId] = _.extend(query, {
                //             clean: function() {
                //                 var query = this;
                //                 var layer = query.layer;
                //                 if(layer.map) {
                //                     var map = layer.map;
                //                     map.removeControl(query.selectControl);
                //                     map.removeLayer(layer);
                //                     query.dispatch('update');
                //                 }
                //             }
                //         }, EventDispatcher);
                //     } else {
                //         var existQuery = widget._queries[queryId];
                //         existQuery.clean();
                //         _.extend(existQuery, query);
                //     }
                // });
                // widget._queries = r.list;
                widget._trigger('queriesUpdate', null, r.list);
                widget.renderQueries(r.list);
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
            var layer = query.layer;
            var map = layer.map;

            // if(!layer.map){
            //     $.notify("Layer ist nicht vorhanden "+ query.name );
            //     return;
            // }

            var request = {
                intersectGeometry: map.getExtent().toGeometry().toString(),
                srid:              map.getProjectionObject().proj.srsProjNumber,
                query:             {
                    id: query.id
                }
            };

            if(map.getScale() > 150000) {
                $.notify("Datensuche '"+query.name+"' ist nicht möglich",'info');
                return false;
            }


            if(query.fetchXhr) {
                query.fetchXhr.abort();
                $.notify("Datensuche '"+query.name+"' Abbruch",'info');

            }

            $.notify("Datensuche '"+query.name+"' lädt Daten",'info');

            return query.fetchXhr = widget.query('query/fetch', request).done(function(r) {

                delete query.fetchXhr;

                if(r.errorMessage) {
                    $.notify(r.errorMessage);
                    return;
                }
                $.notify("Datensuche '"+query.name+"' geladen.",'info');

                var geoJsonReader = new OpenLayers.Format.GeoJSON();
                var featureCollection = geoJsonReader.read({
                    type:     "FeatureCollection",
                    features: r.features
                });

                query.resultView.queryResultView('updateList', featureCollection);

                layer.removeAllFeatures();
                layer.addFeatures(featureCollection);
                layer.redraw();

                // Add layer to feature
                // _.each(features, function(feature) {
                //     feature.layer = layer;
                //     feature.schema = schema;
                // });
            });
        },

        /**
         * Render queries
         */
        renderQueries: function(queries) {
            var widget = this;
            var options = widget.options;
            var element = widget.element;
            var queriesContainer = element.find('> .html-element-container');
            var queriesAccordionView = $('<div class="queries-accordion"/>');

            // ---
            var map = widget.map;

            // TODO: clean up, remove/refresh map layers, events, etc...
            queriesContainer.empty();

            _.each(queries, function(query) {
                var queryTitleView = query.titleView = $('<h3/>').data('query', query).queryResultTitleBarView();
                var queryView = query.resultView = $('<div/>').data('query', query).queryResultView();
                var layerName = 'query-' + query.id;

                /**
                 * Create query layer and style maps
                 */
                query.layer = function createQueryLayer(query) {
                    var styleMaps = widget._styleMaps;
                    var styleDefinitions = widget._styles;
                    var styleMapDefinition = _.extend({}, styleMaps[query.styleMap] ? styleMaps[query.styleMap] : _.first(_.toArray(styleMaps)));
                    var styleMapConfig = {};
                    _.each(styleMapDefinition.styles, function(styleId, key) {
                        if(styleDefinitions[styleId]) {
                            var styleDefinition = _.extend({}, styleDefinitions[styleId]);
                            // styleDefinition.fillOpacity = 0.5;
                            styleMapConfig[key] = new OpenLayers.Style(styleDefinition, {
                                context: {
                                    label: function(feature) {
                                        return feature.cluster && feature.cluster.length > 1 ? feature.cluster.length : "";
                                    }
                                }
                            })
                        } else {
                            delete styleMapDefinition.styles[key];
                        }
                    });
                    var layer = new OpenLayers.Layer.Vector(layerName, {
                        styleMap:   new OpenLayers.StyleMap(styleMapConfig, {extendDefault: true}), // strategies: []
                        visibility: false
                    }, {extendDefault: true});
                    // layer.name = layerName;
                    layer.query = query;
                    return layer;
                }(query);

                map.addLayer(query.layer);

                var selectControl = query.selectControl = new OpenLayers.Control.SelectFeature(query.layer, {
                    hover:        true,
                    clickFeature: function(feature) {
                        var features = feature.cluster ? feature.cluster : [feature];

                        if(_.find(map.getControlsByClass('OpenLayers.Control.ModifyFeature'), {active: true})) {
                            return;
                        }
                        // widget._openFeatureEditDialog(features[0]);
                    },
                    overFeature:  function(feature) {
                        widget._highlightSchemaFeature(feature, true);
                    },
                    outFeature:   function(feature) {
                        widget._highlightSchemaFeature(feature, false);
                    }
                });

                selectControl.deactivate();
                map.addControl(selectControl);


                // Shows     how long queries runs and queries results can be fetched.
                // widget.query('query/check', {query: query}).done(function(r) {
                //     queryTitleView.find(".titleText").html(query.name + " (" + r.count + ", "+r.executionTime+")")
                // });

                queryView
                    .bind('queryresultviewchangeextend', function(e, context) {
                        return false;
                    })
                    .bind('queryresultviewzoomto', function(e, context) {
                        widget.zoomToJsonFeature(context.feature);
                        return false;
                    })
                    .bind('queryresultviewprint', function(e, context) {
                        var feature = context.feature;
                        var printWidget = $('.mb-element-printclient').data('mapbenderMbPrintClient');
                        var query = context.query;
                        var featureTypeName = widget._schemas[query.schemaId].featureType;
                        printWidget.printDigitizerFeature(featureTypeName, feature.fid);
                        return false;
                    })
                    .bind('queryresultviewmark', function(e, context) {

                        var tr = $(context.ui).closest("tr");
                        var row = context.tableApi.row(tr);
                        var feature = row.data();

                        if(tr.is(".mark")) {
                            tr.removeClass('mark');
                        } else {
                            tr.addClass('mark');
                        }

                        feature.mark = tr.is(".mark");

                        return false;
                    })
                    .bind('queryresultviewfeatureover', function(e, context) {
                        widget._highlightSchemaFeature(context.feature, true);
                    })
                    .bind('queryresultviewfeatureout ', function(e, context) {
                        widget._highlightSchemaFeature(context.feature, false);
                    })
                    .bind('queryresultviewfeatureclick ', function(e, context) {
                        function format(feature) {
                            if(!feature || !feature.data) {
                                return;
                            }
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

                        var table = context.tableApi;
                        var tr = $(context.ui);
                        var row = table.row(tr);
                        var feature = row.data();

                        if(row.child.isShown()) {
                            row.child.hide();
                            tr.removeClass('shown');
                        } else {
                            if(feature){
                                row.child(format(feature)).show();
                                tr.addClass('shown');
                            }
                        }
                        // widget._openFeatureEditDialog(context.feature);
                    });

                queryTitleView
                    .bind('queryresulttitlebarviewexport', function(e, context) {
                        var query = context.query;
                        var layer = query.layer;
                        var features = layer.features;
                        var markedFeatures = _.where(features, {mark: true});
                        var exportFormat = 'xls';

                        if(markedFeatures) {
                            widget.exportFeatures(query.id, exportFormat, _.pluck(markedFeatures, 'fid'));
                        } else {
                            widget.exportFeatures(query.id, exportFormat, _.pluck(features, 'fid'));
                        }

                        return false;
                    })
                    .bind('queryresulttitlebarviewedit', function(e, context) {
                        widget.openQueryManager(context.query);
                        return false;
                    })
                    .bind('queryresulttitlebarviewzoomtolayer', function(e, context) {
                        var query = context.query;
                        var layer = query.layer;
                        layer.map.zoomToExtent(layer.getDataExtent());
                        return false;
                    })
                    .bind('queryresulttitlebarviewvisibility', function(e, context) {
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

                queriesAccordionView
                    .append(queryTitleView)
                    .append(queryView);
            });

            queriesAccordionView.togglepanels({
                onChange: function(e, context) {
                    var isActive = $(e.currentTarget).is('.ui-state-active');
                    var title = context.title;
                    var content = context.content;
                    var query = title.data('query');
                    var layer = query.layer;


                    if(query.exportOnly) {
                        content.hide(0);
                        return false;
                    }

                    query.isActive = isActive;
                    layer.setVisibility(isActive);

                    if(isActive){
                        query.selectControl.activate();
                    }else{
                        query.selectControl.deactivate();
                    }


                    if(!isActive) {
                        return;
                    }

                    widget.fetchQuery(query);
                }
            });

            var mapChangeHandler = function(e) {
                _.each(queries, function(query) {
                    if(!query.layer.getVisibility()) {
                        return
                    }


                    widget.fetchQuery(query);
                });
                return false;
            };
            map.events.register("moveend", null, mapChangeHandler);
            map.events.register("zoomend", null, mapChangeHandler);


            queriesContainer.append(queriesAccordionView);
        },

        /**
         * Highlight schema feature on the map and table view
         *
         * @param {OpenLayers.Feature} feature
         * @param {boolean} highlight
         * @private
         */
        _highlightSchemaFeature: function(feature, highlight) {
            var widget = this;
            var table = feature.layer.query.resultView.find('.mapbender-element-result-table');
            var tableWidget = table.data('visUiJsResultTable');
            var isSketchFeature = !feature.cluster && feature._sketch && _.size(feature.data) == 0;
            var features = feature.cluster ? feature.cluster : [feature];
            var layer = feature.layer;
            var domRow;

            if(isSketchFeature) {
                return;
            }

            //widget._highlightFeature(feature, highlight);
            layer.drawFeature(feature, highlight ? 'select' : 'default');

            for (var k in features) {
                domRow = tableWidget.getDomRowByData(features[k]);
                if(domRow && domRow.size()) {
                    tableWidget.showByRow(domRow);
                    if(highlight) {
                        domRow.addClass('hover');
                    } else {
                        domRow.removeClass('hover');
                    }
                    break;
                }
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
            if(!feature || (feature && !feature.layer)) {
                return;
            }

            var layer = feature.layer;
            var isFeatureVisible = _.contains(feature.layer.features, feature);
            var features = [];

            if(isFeatureVisible) {
                features.push(feature);
            } else {
                _.each(feature.layer.features, function(_feature) {
                    if(_feature.cluster && _.contains(_feature.cluster, feature)) {
                        features.push(_feature);
                        return false;
                    }
                });
            }

            _.each(features, function(feature) {
                layer.drawFeature(feature, highlight ? 'hover' : 'default');
            })
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
         * Zoom to JSON feature
         *
         * @param {OpenLayers.Feature} feature
         */
        zoomToJsonFeature: function(feature) {
            var widget = this;
            var olMap = widget.getMap();
            var bounds = feature.geometry.getBounds();
            olMap.zoomToExtent(bounds);
            var mapBounds = olMap.getExtent();

            if(!widget.isContainedInBounds(bounds, mapBounds)) {
                var niceBounds = widget.getNiceBounds(bounds, mapBounds, 10);
                olMap.zoomToExtent(niceBounds);
            }
        },

        /**
         *
         * @param bounds
         * @param mapBounds
         * @returns {boolean}
         */
        isContainedInBounds: function(bounds, mapBounds) {
            return bounds.left >= mapBounds.left && bounds.bottom >= mapBounds.bottom && bounds.right <= mapBounds.right &&bounds.top <= mapBounds.top;
        },

        /**
         * Get Bounds with padding
         *
         * @param {Object} bounds
         * @param {Object} mapBounds
         * @param {int} padding
         */
        getNiceBounds: function(bounds, mapBounds, padding) {
            var widget = this;

            var getBiggerBounds = function(bounds) {
                bounds.left -= padding;
                bounds.right += padding;
                bounds.top += padding;
                bounds.bottom -= padding;
                return bounds;
            };

            var cloneBounds = function(bounds) {
                return {
                    left:   bounds.left,
                    right:  bounds.right,
                    top:    bounds.top,
                    bottom: bounds.bottom
                };
            };

            var scaledMapBounds = cloneBounds(mapBounds);

            while (!widget.isContainedInBounds(bounds, scaledMapBounds)) {
                scaledMapBounds = getBiggerBounds(bounds, scaledMapBounds);
            }

            return scaledMapBounds;
        },

        /**
         * Get OL feature by X:Y coordinates.
         *
         * Dirty but works.
         *
         * @param x
         * @param y
         * @returns {Array}
         * @private
         */
        _getFeaturesFromEvent: function(x, y) {
            var features = [], targets = [], layers = [];
            var layer, target, feature, i, len;
            var map = this.map;

            //map.resetLayersZIndex();

            // go through all layers looking for targets
            for (i = map.layers.length - 1; i >= 0; --i) {
                layer = map.layers[i];
                if(layer.div.style.display !== "none") {
                    if(layer === this.activeLayer) {
                        target = document.elementFromPoint(x, y);
                        while (target && target._featureId) {
                            feature = layer.getFeatureById(target._featureId);
                            if(feature) {
                                features.push(feature);
                                target.style.visibility = 'hidden';
                                targets.push(target);
                                target = document.elementFromPoint(x, y);
                            } else {
                                target = false;
                            }
                        }
                    }
                    layers.push(layer);
                    layer.div.style.display = "none";
                }
            }

            // restore feature visibility
            for (i = 0, len = targets.length; i < len; ++i) {
                targets[i].style.display = "";
                targets[i].style.visibility = 'visible';
            }

            // restore layer visibility
            for (i = layers.length - 1; i >= 0; --i) {
                layers[i].div.style.display = "block";
            }

            //map.resetLayersZIndex();
            return features;
        },

        save: function(dataItem) {
            debugger;
        },

        /**
         * Digitizer API connection query
         *
         * @param uri suffix
         * @param request query
         * @return xhr jQuery XHR object
         * @version 0.2
         */
        query: function(uri, request) {
            var widget = this;
            return $.ajax({
                url:         widget.elementUrl + uri,
                type:        'POST',
                contentType: "application/json; charset=utf-8",
                dataType:    "json",
                data:        JSON.stringify(request)
            }).error(function(xhr) {

                if(xhr.statusText == "abort"){
                    return;
                }

                var errorMessage = translate('api.query.error-message');
                var errorDom = $(xhr.responseText);

                if(errorDom.size() && errorDom.is(".sf-reset")) {
                    errorMessage += "\n" + errorDom.find(".block_exception h2").text() + "\n";
                    errorMessage += "Trace:\n";
                    _.each(errorDom.find(".traces li"), function(li) {
                        errorMessage += $(li).text() + "\n";
                    });

                } else {
                    errorMessage += JSON.stringify(xhr.responseText);
                }

                $.notify(errorMessage,{
                    autoHide: false
                });
                console.log(errorMessage, xhr);
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

            $("body").append(form);

            form.submit();

            setTimeout(function() {
                form.remove();
            }, 200);
        }
    });

})(jQuery);
