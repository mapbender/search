;!(function() {
    "use strict";

    var customFtColors = {
        be: {
            prop: 'eigentuemer',
            match: [
                ['DB Netz AG (BK09)', [44, 169, 169]],
                ['DB Netz AG (BK16)', [173, 252, 252]],
                ['DB Station & Service AG', [255, 176, 190]],
                ['Usedomer Bäderbahn GmbH (UBB)', [255, 128, 192]],
                ['DB Energie GmbH', [242, 242, 242]],
                ['DB Fernverkehr AG', [213, 170, 255]],
                ['DB Regio AG', [255, 183, 111]],
                ['DB Schenker Rail AG', [121, 63, 150]],
                ['DB Fahrzeuginstandhaltung GmbH', [70, 196, 38]],
                ['DB AG', [216, 252, 216]],
                ['DB Systel GmbH', [173, 123, 16]],
                ['Stinnes Immobiliendienst (alt)', [201, 0, 112]],
                ['DB Mobility Logistics AG', [232, 48, 150]],
                ['Stinnes ID GmbH & Co. KG', [231, 49, 101]],
                ['2. KG Stinnes Immobiliendienst', [226, 0, 127]],
                ['Schenker AG', [121, 63, 150]]
            ],
            fallback: [173, 252, 252]
        }
    };

    function resolveCustomColor(featureType, props) {
        var ftData = customFtColors[featureType];
        var propName = ftData.prop;
        var propValue = props[propName];
        for (var i = 0; i < ftData.match.length; ++i) {
            if (ftData.match[i][0] === propValue) {
                return ftData.match[i][1].slice();
            }
        }
        return ftData.fallback.slice();
    }

    window.Mapbender = Mapbender || {};
    window.Mapbender.Search = window.Mapbender.Search || {}
    window.Mapbender.Search.FeatureRenderer = function FeatureRenderer(olMap, owner, clusterResolution) {
        this.olMap = olMap;
        this.owner = owner;
        this.clusterResolution = clusterResolution;
        this.hoverInteractions = {};
        this.queryLayers = {};
    };
    Object.assign(window.Mapbender.Search.FeatureRenderer.prototype, {
        constructor: window.Mapbender.Search.FeatureRenderer,
        addQuery: function(query) {
            var source = new ol.source.Vector();
            var clusterSource = new ol.source.Cluster({
                source: source,
                distance: 30,
                minDistance: 30,    // Prevent random reclustering
                geometryFunction: function(feature) {
                    var geom = feature.getGeometry();
                    if (geom instanceof ol.geom.Point) {
                        return geom;
                    }
                    if (typeof geom.getInteriorPoint === 'function') {
                        return geom.getInteriorPoint();
                    } else {
                        return new ol.geom.Point(ol.extent.getCenter(geom.getExtent()));
                    }
                }
            });
            this.queryLayers[query.id] = new ol.layer.Group({
                visible: false,
                layers: [
                    new ol.layer.Vector({
                        source: source,
                        visible: true,
                        maxResolution: this.clusterResolution || 0
                    }),
                    new ol.layer.Vector({
                        source: clusterSource,
                        visible: !!this.clusterResolution,
                        minResolution: this.clusterResolution || 0
                    })
                ]
            });
            this.owner.updateStyles_(query);
            this.olMap.addLayer(this.queryLayers[query.id]);
            this.initHighlights(query, this.queryLayers[query.id]);
        },
        setFeatures: function(query, features) {
            var unclustered = this.queryLayers[query.id].getLayers().item(0);
            var source = unclustered.getSource();
            source.clear(true);
            source.addFeatures(features);
        },
        removeQuery: function(query) {
            this.olMap.removeInteraction(this.hoverInteractions[query.id]);
            this.hoverInteractions[query.id].dispose();
            delete this.hoverInteractions[query.id];
            this.olMap.removeLayer(this.queryLayers[query.id]);
            this.queryLayers[query.id].dispose();
        },
        getDataExtent: function(query) {
            var unclustered = this.queryLayers[query.id].getLayers().item(0);
            return unclustered.getSource().getExtent();
        },
        toggleQueryLayer: function(query, state) {
            this.hoverInteractions[query.id].setActive(state);
            this.queryLayers[query.id].setVisible(state);
        },
        getClusterSiblings: function(query, feature) {
            var clusters = this.getActiveClusters_(query);
            for (var i = 0; i < clusters.length; ++i) {
                var cluster = clusters[i];
                var siblings = cluster.get('features') || [];
                if (-1 !== siblings.indexOf(feature)) {
                    return siblings;
                }
            }
            return [feature];
        },
        getCluster: function(query, feature) {
            var clusters = this.getActiveClusters_(query);
            for (var i = 0; i < clusters.length; ++i) {
                if (-1 !== clusters[i].get('features').indexOf(feature)) {
                    return clusters[i];
                }
            }
            return null;
        },
        updateStyles: function(query, rules, featureType) {
            var layerGroup = this.queryLayers[query.id];
            var mainLayer = layerGroup.getLayers().item(0);
            var clusterLayer = layerGroup.getLayers().item(1);

            var styleFunction = this.getMainStyleFunction_(rules, featureType);
            mainLayer.setStyle(styleFunction);
            clusterLayer.setStyle(this.getClusterStyleFunction(styleFunction));
        },
        getMainStyleFunction_: function(styleRules, featureType) {
            var baseStyles = {};
            var labelStyleFunctions = {};
            var self = this;
            ['default', 'select'].forEach(function(renderIntent) {
                var intentRules = styleRules[renderIntent];
                baseStyles[renderIntent] = new ol.style.Style({
                    fill: new ol.style.Fill({
                        color: Mapbender.StyleUtil.parseSvgColor(intentRules, 'fillColor', 'fillOpacity')
                    }),
                    stroke: new ol.style.Stroke({
                        color: Mapbender.StyleUtil.parseSvgColor(intentRules, 'strokeColor', 'strokeOpacity'),
                        width: parseInt(intentRules.strokeWidth || '2')
                    })
                });
                var labelFn = intentRules.label && self.getPlaceholderResolver_(intentRules.label);
                var labelScale = (intentRules.fontSize && parseFloat(intentRules.fontSize) || 11.0) / 9;
                var labelBaseStyle = new ol.style.Text({
                    overflow: true,
                    scale: [labelScale, labelScale],
                    fill: new ol.style.Fill({
                        color: Mapbender.StyleUtil.parseSvgColor(intentRules, 'fontColor', 'fontOpacity')
                    }),
                    stroke: new ol.style.Stroke({
                        color: Mapbender.StyleUtil.parseSvgColor(intentRules, 'labelOutlineColor', 'labelOutlineOpacity'),
                        width: intentRules.labelOutlineWidth || 2
                    }),
                    text: ''
                });
                labelStyleFunctions[renderIntent] = (function(labelBaseStyle, labelFn) {
                    return function(feature) {
                        var textStyle = labelBaseStyle;
                        if (labelFn) {
                            textStyle = textStyle.clone();
                            textStyle.setText(labelFn(feature.getProperties()));
                        }
                        return textStyle;
                    };
                }(labelBaseStyle, labelFn));
            });
            return (function(baseStyles, labelStyleFunctions, featureType) {
                var invisible = new ol.style.Style({
                    fill: new ol.style.Fill({
                        color: [0,0,0,0]
                    })
                });
                return function(feature) {
                    if (feature.__hidden__) {
                        return [invisible];
                    } else {
                        var intent = feature.__highlight__ && 'select' || 'default';
                        var style = baseStyles[intent].clone();
                        var labelStyleFn = labelStyleFunctions[intent];
                        if (labelStyleFn) {
                            style.setText(labelStyleFn(feature));
                        }
                        var customColor = customFtColors[featureType] && resolveCustomColor(featureType, feature.getProperties());
                        if (customColor) {
                            var opacity = style.getFill().getColor()[3];
                            customColor[3] = (opacity);
                            style.getFill().setColor(customColor);
                        }
                        return [style];
                    }
                };
            }(baseStyles, labelStyleFunctions, featureType));
        },
        getClusterStyleFunction: function(baseStyleFunction) {
            return (function(baseFn) {
                var defaultText = new ol.style.Text({
                    overflow: true,
                    fill: new ol.style.Fill({
                        color: [0, 0, 0, 1]
                    }),
                    stroke: new ol.style.Stroke({
                        color: [255, 255, 255, 1],
                        width: 2
                    })
                });
                return function(cluster, resolution) {
                    var members = cluster.get('features');
                    var baseStyle = baseFn(cluster, resolution)[0];
                    if (cluster.__hidden__) {
                        return [];
                    }
                    var text = (baseStyle.getText() || defaultText).clone();
                    text.setText('' + members.length);
                    var style = new ol.style.Style({
                        image: new ol.style.Circle({
                            radius: 15,
                            fill: baseStyle.getFill(),
                            stroke: baseStyle.getStroke()
                        }),
                        text: text
                    });
                    return [style];
                };
            }(baseStyleFunction));
        },
        initHighlights: function(query, layerGroup) {
            var self = this;
            var interaction = new ol.interaction.Select({
                condition: ol.events.condition.pointerMove,
                style: null,
                layers: layerGroup.getLayers().getArray()
            });
            interaction.on('select', function(e) {
                e.deselected.forEach(function(feature) {
                    self.toggleHighlights_(query, feature, false);
                });
                e.selected.forEach(function(feature) {
                    self.toggleHighlights_(query, feature, true);
                });
            });
            this.hoverInteractions[query.id] = interaction;
            this.olMap.addInteraction(interaction);
        },
        toggleHighlights_: function(query, feature, state) {
            feature.__highlight__ = state;
            feature.changed();
            // Unpack cluster
            var features = feature.get('features') || [feature];
            this.owner.highlightTableRows(query, features, state);
        },
        getActiveClusters_: function(query) {
            if (this.clusterResolution && this.olMap.getView().getResolution() >= this.clusterResolution) {
                var layerGroup = this.queryLayers[query.id];
                return layerGroup.getLayers().item(1).getSource().getFeatures();
            } else {
                return [];
            }
        },
        getPlaceholderResolver_: (function() {
            var placeholderRx = /\${([^}]*)}/g;
            var placeholderResolverCache = {};

            function scanPlaceholders(literal) {
                // Run ~String.protype.matchAll, but without the iterator
                /** @see https://gist.github.com/TheBrenny/039add509c87a3143b9c077f76aa550b */
                // Clone RegExp to always reset index to zero
                var rxClone = new RegExp(placeholderRx);
                var match;
                var matchesInfo = [];
                while (match = rxClone.exec(literal)) {
                    matchesInfo.push([match.index, match[1], match[0].length]);
                }
                // Based on matches, build a list of constants and placholders
                var parts = [];
                var beforeNext = 0;
                for (var i = 0; i < matchesInfo.length; ++i) {
                    var matchInfo = matchesInfo[i];
                    if (beforeNext < matchInfo[0]) {
                        parts.push({
                            constant: true,
                            value: literal.slice(beforeNext, matchInfo[0])
                        });
                    }
                    parts.push({
                        constant: false,
                        value: matchInfo[1]
                    });
                    beforeNext = matchInfo[0] + matchInfo[2];
                }
                if (beforeNext < literal.length) {
                    parts.push({
                        constant: true,
                        value: literal.slice(beforeNext)
                    });
                }
                return parts;
            }

            return function getPlaceholderResolver_(literal) {
                if (typeof (placeholderResolverCache[literal]) === 'undefined') {
                    placeholderResolverCache[literal] = (function(specs) {
                        return function(props) {
                            var parts = specs.map(function(spec) {
                                return spec.constant && spec.value || props[spec.value] || '';
                            });
                            return parts.join('');
                        };
                    }(scanPlaceholders(literal)));
                }
                return placeholderResolverCache[literal];
            }
        }()),
        __dummy__: null
    })
}());
