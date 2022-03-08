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
                strokeColor:     "#e8c02f"
            },
            'segment': {
                strokeColor:     "#e50c24"
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
        fetchXhr: null,
        queryFeatures: {},
        queryFeaturesUnbounded: {},

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
                widget.olMap = mbMap.getModel().olMap;
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
                    Object.values(widget._queries).forEach(function(query) {
                        var show = schemaId == -1 || schemaId == query.schemaId;
                        widget.getQueryTab_(query).toggle(show);
                        widget.getQueryPanel_(query).toggle(show && query.isActive);
                        widget.featureRenderer.toggleQueryLayer(show && query.isActive);
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

            this.mbMap.element.on('mbmapviewchanged', function(e, data) {
                var affectedQueries = Object.values(widget._queries).filter(function(query) {
                    return query.isActive && query.extendOnly;
                });
                for (var i = 0; i < affectedQueries.length; ++i) {
                    widget.fetchQuery(affectedQueries[i]);
                }
            });
            var clusterRes = this.options.cluster_threshold && this.mbMap.getModel().scaleToResolution(this.options.cluster_threshold) || null;
            this.featureRenderer = new Mapbender.Search.FeatureRenderer(this.olMap, this, clusterRes);

            this.initDataPromise_.then(function(data) {
                widget._schemas = data.schemas;
                widget._styles = !Array.isArray(data.styles) && data.styles || {};
                widget._styleMaps = !Array.isArray(data.styleMaps) && data.styleMaps || {};
                widget._queries = !Array.isArray(data.queries) && data.queries || {};
                widget.renderSchemaFilterSelect();
                widget.initAccordion($('.queries-accordion', element));
                widget.addQueries(Object.values(widget._queries));
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
            var featureRenderer = this.featureRenderer;
            _.each(this._queries, function(query) {
                featureRenderer.toggleQueryLayer(query, false);
            });
        },
        reveal: function() {
            var featureRenderer = this.featureRenderer;
            _.each(this._queries, function(query) {
                featureRenderer.toggleQueryLayer(query, query.isActive);
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
                    self.addQueries([query]);
                } else {
                    self.updateQuery(query);
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
        /**
         * Execute and fetch query results
         *
         * @param query
         * @returns {*}
         */
        fetchQuery: function(query) {
            var widget = this;
            var srsName = this.mbMap.getModel().getCurrentProjectionCode();

            var request = {
                srid: srsName.replace(/^\w+:/, ''),
                intersect: query.extendOnly && this.getIntersectWkt_() || null,
                queryId: query.id
            };

            if (!query.extendOnly && this.queryFeaturesUnbounded[query.id]) {
                this.reloadFeatures(query, this.queryFeaturesUnbounded[query.id]);
                return;
            }
            if (this.fetchXhr) {
                this.fetchXhr.abort();
            }
            var $accordionHeader = this.getQueryTab_(query);
            $accordionHeader.addClass('loading');
            var model = this.mbMap.getModel();

            return this.query('query/fetch', request, 'GET').done(function(r) {
                widget.fetchXhr = null;

                if(r.infoMessage) {
                    $.notify(r.infoMessage, {
                        autoHideDelay: 30000,
                        className:     'info'
                    });
                }
                var features = r.features.map(function(featureData) {
                    var feature = model.parseWktFeature(featureData.geometry, srsName);
                    feature.setProperties(featureData.properties || {});
                    return feature;
                });

                if (!query.extendOnly) {
                    widget.queryFeaturesUnbounded[query.id] = features;
                }
                widget.queryFeatures[query.id] = features;
                widget.reloadFeatures(query, features);
            }).always(function() {
                $accordionHeader.removeClass('loading');
            });
        },
        /**
         * @param {Array<Object>} queries
         */
        addQueries: function(queries) {
            var $accordion = $('.queries-accordion', this.element);
            for (var i = 0; i < queries.length; ++i) {
                var query = queries[i];
                this._queries[query.id] = query;
                $accordion.append(this.renderQuery(query));
                this.featureRenderer.addQuery(query);
            }
            $accordion.accordion('refresh');
        },
        updateQuery: function(query) {
            this._queries[query.id] = query;
            this.queryFeaturesUnbounded[query.id] = null;
            var $tab = this.getQueryTab_(query);
            var $panel = this.getQueryPanel_(query);
            this.updateQueryMarkup($tab, $panel, query);
            $tab.addClass('updated');
            this.updateStyles_(query);
            if (query.isActive) {
                this.fetchQuery(query);
            }
        },
        updateQueryMarkup: function($tab, $panel, query) {
            $('.-fn-zoomtolayer, .-fn-visibility', $tab).toggle(!query.exportOnly);
            $('.title-text', $tab).text(query.name);
            $('input[name="extent-only"]', $panel).prop('checked', !!query.extendOnly);
            $('input[type="search"]', $panel).attr('placeholder', _.pluck(query.fields, 'title').join(', '));
        },
        renderQuery: function(query) {
            var $query = $($.parseHTML(this.templates_['query']));
            var $titleView = $query.filter('.query-header');
            $titleView.attr('data-query-id', query.id);
            $titleView.data('query', query);
            var $resultView = $query.filter('.query-content-panel');
            $resultView.attr('data-query-id', query.id);
            $resultView.data('query', query);
            this.updateQueryMarkup($titleView, $resultView, query);
            this.tableRenderer.initializeTable($('table:first', $resultView), query);
            this.initQueryViewEvents($resultView, query);
            this.initTitleEvents($titleView, query);
            return $query;
        },
        updateStyles_: function(query) {
            var featureType = this._schemas[query.schemaId].featureType;
            var rules = this.getStyleRules_(query, featureType);
            this.featureRenderer.updateStyles(query, rules, featureType);
        },
        getStyleRules_: function(query, featureType) {
            var customStyleIds = (this._styleMaps[query.styleMap] || {}).styles || {};
            var defaultRules = Object.assign({},
                customStyleIds.default && this._styles[customStyleIds.default] || {},
                this.customStyles_[featureType] || {}
            );
            return {
                default: defaultRules,
                select: Object.assign({}, defaultRules,
                    customStyleIds.select && this._styles[customStyleIds.select] || {},
                    this.customStyles_[featureType] || {}
                )
            };
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
                    var $row = $btn.closest('tr');
                    var rowFeature = $row.data('feature');
                    var hidden = !rowFeature.__hidden__;
                    var features = widget.featureRenderer.getClusterSiblings(query, rowFeature);
                    for (var i = 0; i < features.length; ++i) {
                        var feature = features[i];
                        feature.__hidden__ = hidden;
                        if (feature.tableRow) {
                            var $icon = $('.-fn-toggle-visibility > i', feature.tableRow);
                            $icon.toggleClass('fa-eye-slash', hidden);
                            $icon.toggleClass('fa-eye', !hidden);
                        }
                        widget.redrawFeature(query, feature, false);
                    }

                    return false;
                })
                .on('mouseover', 'tbody > tr[role="row"]', function() {
                    var feature = $(this).data('feature');
                    var cluster = widget.featureRenderer.getCluster(query, feature);
                    widget.redrawFeature(query, cluster || feature, true);
                })
                .on('mouseout', 'tbody > tr[role="row"]', function() {
                    var feature = $(this).data('feature');
                    var cluster = widget.featureRenderer.getCluster(query, feature);
                    widget.redrawFeature(query, cluster || feature, false);
                })
                .on('click', 'tbody > tr[role="row"]', function() {
                    widget.tableRenderer.toggleDetails(this, widget._schemas[query.schemaId]);
                });
        },
        dataFromFeature_: function(feature) {
            return feature.getProperties && feature.getProperties() || feature.attributes || {};
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
                var features = widget.queryFeatures[query.id];
                        var exportFormat = 'xls';
                        var markedFeatures = _.where(features, {mark: true});
                        var exportFeatures = markedFeatures.length && markedFeatures || features;
                        var featureIds = exportFeatures.map(function(feature) {
                            return widget.dataFromFeature_(feature)['id'];
                        });
                        widget.exportFeatures(query.id, exportFormat, featureIds);
                        return false;
            }).on('click', '.-fn-edit', function() {
                        widget.openQueryManager(query);
                        return false;
            }).on('click', '.-fn-zoomtolayer', function() {
                widget.zoomToQueryDataExtent(query);
                return false;
            }).on('click', '.-fn-visibility', function() {
                        /** @todo: implement this properly or remove the button! */
                        return false;
                        // layer.setVisibility()
                        // $.notify("Chnage visibility of layer");
                        // return false;
            }).on('click', '.-fn-delete', function() {
                        widget.confirmDelete(query);
                        return false;
            });
        },
        zoomToQueryDataExtent: function(query) {
            var extent = this.featureRenderer.getDataExtent(query);
            this.olMap.getView().fit(extent, {
                padding: this.mbMap.getModel().getMapPadding_()
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
                        widget.featureRenderer.toggleQueryLayer(oldQuery, false);
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
                        widget.featureRenderer.toggleQueryLayer(query, true);
                        widget.fetchQuery(query);
                    }
                }
            });
        },
        redrawFeature: function(query, feature, highlight) {
            feature.__highlight__ = !!highlight;
            feature.changed();
        },
        highlightTableRows: function(query, features, highlight) {
            var $table = this.getQueryTable_(query);
            // var features = feature.cluster ? feature.cluster : [feature];

            var firstHighlighted = null;
            for (var i = 0; i < features.length; ++i) {
                if (features[i].tableRow) {
                    var tr = features[i].tableRow;
                    var doHighlight = highlight && !features[i].__hidden__;
                    $(tr).toggleClass('hover', doHighlight);
                    if (highlight && !firstHighlighted) {
                        firstHighlighted = tr;
                    }
                }
            }
            if (firstHighlighted) {
                this.tableRenderer.pageToRow($table, firstHighlighted);
            }
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
                        var message = Mapbender.trans('mb.search.api.query.error');
                        var messageDetail = xhr.getResponseHeader('X-Error-Message');
                        if (messageDetail) {
                            message = [message, messageDetail].join("\n");
                        }
                        $.notify(message, {
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
        reloadFeatures: function(query, features) {
            this.featureRenderer.setFeatures(query, features);
            this.tableRenderer.replaceRows(this.getQueryTable_(query), features);
        },
        removeQuery: function(query) {
            $('[data-query-id="' + query.id + '"]', this.element).remove();
            this.featureRenderer.removeQuery(query);
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
        getQueryTab_: function(query) {
            return $('.query-header[data-query-id="' + query.id + '"]', this.element);
        },
        getQueryPanel_: function(query) {
            return $('.query-content-panel[data-query-id="' + query.id + '"]', this.element);
        },
        getQueryTable_: function(query) {
            return $('table:first', this.getQueryPanel_(query));
        },
        __dummy__: null
    });

})(jQuery);
