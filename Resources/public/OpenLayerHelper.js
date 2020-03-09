/**
 *
 * @author Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 26.12.16 by WhereGroup GmbH & Co. KG
 */
var Mapbender = Mapbender || {};
Mapbender.Util = Mapbender.Util || {};
Mapbender.Util.beautifyOptions = function(options) {

    if (Array.isArray(options) || typeof options != "object") {
        return options;
    }

    var newOptions = [];
    Object.keys(options).forEach(function(key){
        newOptions.push({
           value: key,
           label: options[key]
        });
    });

    return newOptions;

};

Mapbender.Util.beautifyGenerateElements = function(element) {

    return {
        children: [element]
    }
};
Mapbender.Util.OpenLayers2 = {
    /**
     * Zoom to JSON feature
     *
     * @param {OpenLayers.Feature} feature
     */
    zoomToJsonFeature: function(feature) {
        var olMap = feature.layer.map;
        var bounds = feature.geometry.getBounds();
        olMap.zoomToExtent(bounds);
        var mapBounds = olMap.getExtent();

        if(!this.isContainedInBounds(bounds, mapBounds)) {
            var niceBounds = this.getNiceBounds(bounds, mapBounds, 10);
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
        return bounds.left >= mapBounds.left && bounds.bottom >= mapBounds.bottom && bounds.right <= mapBounds.right && bounds.top <= mapBounds.top;
    },

    /**
     * Get Bounds with padding
     *
     * @param {Object} bounds
     * @param {Object} mapBounds
     * @param {int} padding
     */
    getNiceBounds: function(bounds, mapBounds, padding) {

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

        while (!this.isContainedInBounds(bounds, scaledMapBounds)) {
            scaledMapBounds = getBiggerBounds(bounds, scaledMapBounds);
        }

        return scaledMapBounds;
    }
};
