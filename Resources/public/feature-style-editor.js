(function($) {

    /**
     * Style manager widget
     */
    $.widget('mapbender.featureStyleEditor', {
        options: {
            asPopup: true,
            data:    {
                'id':         null,
                'borderSize': 1
            }
        },

        /**
         * Generate StyleManagerForm
         */
        _create: function() {
            var widget = this;
            var element = $(widget.element);
            var options = widget.options;

            element.generateElements({
                type:     "tabs",
                children: [{
                    title:    "Allgemein",
                    type:     "form",
                    children: [{
                        type:      'input',
                        name:      'name',
                        title:     'Name',
                        infoText:  'Die Name erscheint in der Auswahlliste.',
                        mandatory: true
                    }]
                }, {
                    title:    "Füllung",
                    type:     "form",
                    children: [{
                        type:     'fieldSet',
                        children: [{
                            title:         "Farbe",
                            type:          "colorPicker",
                            name:          "fillColor",
                            value:         "#ff0000",
                            mandatory:     "/^#{1,1}[abcdefABCDEF0-9]{6,6}$/",
                            mandatoryText: "Bitte Farbwähler nutzen",
                            css:           {width: "50%"}

                        }, {
                            title: "Deckkraft",
                            name:  "fillOpacity",
                            type:  "slider",
                            range: "max",
                            min:   0.1,
                            max:   1,
                            value: 1,
                            step:  0.1,
                            css:   {width: "50%"}

                        }, {
                            title:   "Aktivieren",
                            type:    "checkbox",
                            checked: true,
                            name:    "fill",
                            value:   '1'
                        }]
                    }]
                }, {
                    title:    "Hintergrund",
                    type:     "form",
                    children: [{
                        type:     'fieldSet',
                        children: [{
                            title:         "Breite",
                            infoText:      "The width of the background width.  If not provided, the graphicWidth will be used.",
                            type:          "input",
                            name:          "backgroundWidth",
                            mandatoryText: "Bitte nur Zahlen verwenden",
                            css:           {width: '50%'}

                        }, {
                            title:         "Höhe",
                            infoText:      "The height of the background graphic.  If not provided, the graphicHeight will be used.",
                            type:          "input",
                            name:          "backgroundHeight",
                            mandatoryText: "Bitte nur Zahlen verwenden",
                            css:           {width: '50%'}
                        }]
                    }, {
                        type:     'fieldSet',
                        children: [{
                            title: "X-Offset", // infoText:  "The x offset (in pixels) for the background graphic.",
                            name:  "backgroundXOffset",
                            type:  "slider",
                            range: "max",
                            min:   0,
                            max:   100,
                            value: 0,
                            step:  1,
                            css:   {
                                width: '33%'
                            }
                        }, {
                            title: "Y-Offset",
                            type:  "slider",
                            name:  "backgroundYOffset",
                            range: "max",
                            min:   0,
                            max:   100,
                            value: 0,
                            step:  1,
                            css:   {
                                width: '33%'
                            }
                        }, {
                            title: "Z-Index",
                            type:  "slider",
                            name:  "backgroundGraphicZIndex", // infoText:  "The integer z-index value to use in rendering the background graphic.",
                            range: "max",
                            min:   0,
                            max:   100,
                            value: 0,
                            step:  1,
                            css:   {
                                width: '34%'
                            }
                        }]
                    }, {
                        title:       "Bild URL",
                        infoText:    "Url to a graphic to be used as the background under an externalGraphic.",
                        type:        "input",
                        name:        "backgroundGraphic",
                        value:       "",
                        placeholder: "URL"
                    }]
                }, {
                    title:    "Rand",
                    type:     "form",
                    children: [{
                        type:     'fieldSet',
                        children: [{
                            title:         "Farbe",
                            type:          "colorPicker",
                            name:          "strokeColor",
                            value:         "#ffffff",
                            horizontal:    true,
                            mandatory:     "/^\#[A-F0-9]{6}$/i",
                            mandatoryText: "Bitte Farbwähler nutzen",
                            css:           {width: "30%"}

                        }, {
                            title: "Deckkraft",
                            name:  "strokeOpacity",
                            type:  "slider",
                            range: "max",
                            min:   0.1,
                            max:   1,
                            value: 1,
                            step:  0.1,
                            css:   {width: "35%"}

                        }, {
                            title: "Breite",
                            type:  "slider",
                            name:  "strokeSize",
                            min:   0,
                            max:   10,
                            step:  0.1,
                            value: 1,
                            css:   {width: "35%"}
                        }]
                    }, {
                        type:     'fieldSet',
                        children: [{
                            title:   "Glättung",
                            name:    "strokeLineCap",
                            type:    "select",
                            options: {
                                round:  "round",
                                square: "square",
                                butt:   "butt"
                            },
                            value:   "round",
                            css:     {width: "50%"}
                        }, {
                            title:   "Style",
                            name:    "strokeDashStyle",
                            type:    "select",
                            options: {
                                Solid:           'Solid',
                                ShortDash:       'ShortDash',
                                ShortDot:        'ShortDot',
                                ShortDashDot:    'ShortDashDot',
                                ShortDashDotDot: 'ShortDashDotDot',
                                Dot:             'Dot',
                                Dash:            'Dash',
                                LongDash:        'LongDash',
                                DashDot:         'DashDot',
                                LongDashDot:     'LongDashDot',
                                LongDashDotDot:  'LongDashDotDot'
                            },
                            value:   "Solid",
                            css:     {width: "50%"}

                        }]
                    }, {
                        title:   "Aktivieren",
                        type:    "checkbox",
                        checked: true,
                        name:    "stroke",
                        value:   '1'
                    }]
                }, {
                    title:    "Bild",
                    type:     "form",
                    children: [{
                        title:    "Name",
                        type:     "input",
                        name:     "graphicName",
                        infoText: "Named graphic to use when rendering points.  Supported values include “circle” (default), “square”, “star”, “x”, “cross”, “triangle”."
                    }, {
                        type:     'fieldSet',
                        children: [{
                            title:         "Breite",
                            type:          "input",
                            name:          "graphicWidth",
                            mandatoryText: "Bitte nur Zahlen verwenden",
                            css:           {width: '50%'}
                        }, {
                            title:         "Höhe",
                            type:          "input",
                            name:          "graphicHeight",
                            mandatoryText: "Bitte nur Zahlen verwenden",
                            css:           {width: '50%'}
                        }]
                    }, {
                        type:     'fieldSet',
                        children: [{
                            title:         "X-Offset",
                            name:          "graphicXOffset",
                            type:          "slider",
                            mandatoryText: "Bitte nur Zahlen verwenden",
                            range:         "max",
                            min:           0,
                            max:           100,
                            value:         0,
                            step:          1,
                            css:           {
                                width: '33%'
                            }
                        }, {
                            title:         "Y-Offset",
                            type:          "slider",
                            name:          "graphicYOffset",
                            mandatoryText: "Bitte nur Zahlen verwenden",
                            range:         "max",
                            min:           0,
                            max:           100,
                            value:         0,
                            step:          1,
                            css:           {
                                width: '33%'
                            }
                        }, {
                            title: "Deckkraft",
                            name:  "graphicOpacity",
                            type:  "slider",
                            range: "max",
                            min:   0,
                            max:   1,
                            value: 1,
                            step:  0.01,
                            css:   {
                                width: '34%'
                            }
                        }]
                    }, {
                        title:       "URL",
                        type:        "input",
                        name:        "graphicUrl",
                        value:       "",
                        placeholder: "URL"
                    }]
                }, {

                    title:    'Beschriftung',
                    type:     'form',
                    children: [// labelAlign	{String} Label alignment.  This specifies the insertion point relative to the text.  It is a string composed of two characters.  The first character is for the horizontal alignment, the second for the vertical alignment.  Valid values for horizontal alignment: “l”=left, “c”=center, “r”=right.  Valid values for vertical alignment: “t”=top, “m”=middle, “b”=bottom.  Example values: “lt”, “cm”, “rb”.  Default is “cm”.
                        // labelXOffset	{Number} Pixel offset along the positive x axis for displacing the label.  Not supported by the canvas renderer.
                        // labelYOffset	{Number} Pixel offset along the positive y axis for displacing the label.  Not supported by the canvas renderer.
                        // labelOutlineColor	{String} The color of the label outline.  Default is ‘white’.  Only supported by the canvas & SVG renderers.
                        // labelOutlineWidth	{Number} The width of the label outline.  Default is 3, set to 0 or null to disable.  Only supported by the SVG renderers.
                        // labelOutlineOpacity	{Number} The opacity (0-1) of the label outline.  Default is fontOpacity.  Only supported by the canvas & SVG renderers.
                        {
                            type:     'textArea',
                            css:      {width: "100 %"},
                            name:     'label',
                            infoText: 'The text for an optional label.  For browsers that use the canvas renderer, this requires either fillText or mozDrawText to be available.'
                        }, {
                            type:     'fieldSet',
                            children: [{
                                title:    'Fontname',
                                type:     'select',
                                options:  {
                                    "Open Sans":    "Open Sans",
                                    "Calluna Sans": "Calluna Sans",
                                    "Gill Sans MT": "Gill Sans MT",
                                    'Calibri':      'Calibri',
                                    "Trebuchet MS": "Trebuchet MS",
                                    'sans-serif':   'sans-serif'
                                },
                                name:     'fontFamily',
                                infoText: 'The font family for the label, to be provided like in CSS.',
                                css:      {width: "50%"}

                            }, {
                                title:    'Grösse',
                                name:     'fontSize',
                                type:     'select',
                                options:  {
                                    "9":  9,
                                    "10": 10,
                                    "11": 11,
                                    "12": 12,
                                    "13": 13,
                                    "14": 14
                                },
                                css:      {width: "20%"},
                                infoText: 'The font size for the label, to be provided like in CSS'
                            }, //     {
                                //     title:    'Style',
                                //     type:     'input',
                                //     name:     'fontStyle',
                                //     infoText: 'The font style for the label, to be provided like in CSS'
                                // },

                                {
                                    title:    'Art',
                                    name:     'fontWeight',
                                    type:     'select',
                                    options:  {
                                        'regular': 'Normal',
                                        'bold':    'Fett',
                                        'italic':  'Kursive',
                                    },
                                    css:      {width: "30%"},
                                    infoText: 'The font weight for the label, to be provided like in CSS.'
                                }, {
                                    title:    'Farbe',
                                    type:     'colorPicker',
                                    name:     'fontColor',
                                    infoText: 'The font color for the label, to be provided like CSS.',
                                    css:      {width: "50%"}
                                }, {
                                    title: "Deckkraft",
                                    name:  "fontOpacity",
                                    type:  "slider",
                                    range: "max",
                                    min:   0,
                                    max:   1,
                                    value: 1,
                                    step:  0.01,
                                    css:   {
                                        width: '50%'
                                    }
                                }, {
                                    title:    "Selektierbar?",
                                    type:     "checkbox",
                                    checked:  false,
                                    name:     "labelEnabled",
                                    infoText: 'If set to true, labels will be selectable using SelectFeature or similar controls.  Default is false.',
                                    value:    '1',
                                    css:      {
                                        width: '30%'
                                    }
                                }]
                        }]

                }, {
                    title:    "Misc",
                    type:     "form",
                    children: [{
                        title:         "Punkt Radius",
                        name:          "pointRadius",
                        type:          "slider",
                        mandatory:     "/^\\d+$/",
                        mandatoryText: "Bitte nur Zahlen verwenden",
                        range:         "max",
                        min:           0,
                        max:           10,
                        value:         0
                    }, {
                        title:   "Zeiger",
                        name:    "cursor",
                        type:    "select",
                        options: {
                            auto:        'auto',
                            'default':   'default',
                            crosshair:   'crosshair',
                            pointer:     'pointer',
                            move:        'move',
                            'n-resize':  'n-resize',
                            'ne-resize': 'ne-resize',
                            'e-resize':  'e-resize',
                            'se-resize': 'se-resize',
                            's-resize':  's-resize',
                            'sw-resize': 'sw-resize',
                            'w-resize':  'w-resize',
                            'nw-resize': 'nw-resize',
                            text:        'text',
                            wait:        'wait',
                            help:        'help'
                        },
                        value:   "pointer"
                    }, {
                        title:     "Rotation (°)",
                        name:      "rotation",
                        type:      "slider",
                        mandatory: "/^\\d+$/",
                        range:     "max",
                        min:       0,
                        max:       360,
                        value:     0,
                        step:      1
                    }, {
                        title:   "Anzeige",
                        name:    "display",
                        type:    "select",
                        options: {
                            inline:         "inline",
                            "inline-block": "inline-block",
                            block:          "block",
                            none:           "none"
                        },
                        value:   "block"
                    }]
                }]
            });

            window.setTimeout(function() {
                element.formData(options.data);
            }, 1000);

            if(options.asPopup) {
                widget.popup();
            }

            return widget;
        },

        popup: function() {
            var widget = this;
            var element = $(widget.element);
            element.popupDialog({
                title:   "Stylemanager",
                modal:   true,
                width:   '500px',
                buttons: [{
                    text:  "Abbrechen",
                    click: function(e) {
                        widget.close();
                        return false;
                    }
                }, {
                    text:  "Speichern",
                    click: function(e) {
                        var form = $(e.currentTarget).closest(".ui-dialog");
                        widget._trigger('submit', null, {
                            form:   form,
                            widget: widget
                        });
                    }
                }]
            });
        },

        /**
         *
         * @private
         */
        close: function() {
            var widget = this;
            var element = $(widget.element);
            var options = widget.options;

            if(options.asPopup) {
                element.popupDialog("close");
            } else {
                widget.element.remove();
            }
        }
    });

})(jQuery);