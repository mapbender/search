(function($) {



    /**
     * Style manager widget
     */
    $.widget('mapbender.featureStyleEditor', {
        options: {
            data: {},
            customColors: {
                '#777777': '#777777',
                '#337ab7': '#337ab7',
                '#5cb85c': '#5cb85c',
                '#5bc0de': '#5bc0de',
                '#f0ad4e': '#f0ad4e',
                '#d9534f': '#d9534f'
            }
        },

        /**
         * Generate StyleManagerForm
         */
        _create: function() {
            var widget = this;
            var element = $(widget.element);
            var options = widget.options;
            var commonTab = {
                title:    "Allgemein",
                type:     "form",
                children: [{
                    type:      'input',
                    name:      'name',
                    title:     'Name',
                    mandatoryText: "Bitte einen Namen eintrag                                                                                                                                                                                                                                                                       en.",
                    infoText:  'Die Name erscheint in der Auswahlliste.',
                    mandatory: true
                }]
            };
            var customColors = options.customColors;
            var fillTab = {
                title:    "Füllung",
                type:     "form",
                children: [{
                    type:     'fieldSet',
                    children: [{
                        title:          "Farbe",
                        type:           "colorPicker",
                        name:           "fillColor",
                        value:          "#ff0000",
                        mandatory:      "/^#{1,1}[abcdefABCDEF0-9]{6,6}$/",
                        mandatoryText:  "Bitte Farbwähler nutzen",
                        colorSelectors: customColors,
                        css:            {width: "30%"}
                    }, {
                        title: "Deckkraft",
                        name:  "fillOpacity",
                        type:  "slider",
                        range: "max",
                        min:   0.1,
                        max:   1,
                        value: 1,
                        step:  0.1,
                        css:   {width: "35%"}

                    }, {
                        title:         "Punkt Radius",
                        name:          "pointRadius",
                        type:          "slider",
                        mandatory:     "/^\\d+$/",
                        mandatoryText: "Bitte nur Zahlen verwenden",
                        range:         "max",
                        min:           0,
                        max:           20,
                        value:         0,
                        css:   {width: "35%"}

                    },{
                        title:   "Aktivieren",
                        type:    "checkbox",
                        checked: true,
                        name:    "fill",
                        value:   '1'
                    }]
                }]
            };
            var strokeTab = {
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
                        name:  "strokeWidth",
                        min:   0,
                        max:   10,
                        step:  1,
                        value: 1,
                        css:   {width: "35%"}
                    }]
                }, {
                    type:     'fieldSet',
                    children: [{
                        title:   "Glättung",
                        name:    "strokeLinecap",
                        type:    "select",
                        options: Mapbender.Util.beautifyOptions({
                            round:  "abgerundet",
                            square: "eckig",
                            butt:   "bündig"
                        }),
                        value:   "round",
                        css:     {width: "50%"}
                    }, {
                        title:   "Style",
                        name:    "strokeDashstyle",
                        type:    "select",
                        //  strokeDashstyle	{String} Stroke dash style.
                        // Default is “solid”.  [dot | dash | dashdot | longdash | longdashdot | solid]
                        options: Mapbender.Util.beautifyOptions({
                            solid:           'Durchgezogen',
                            // shortdash:       'Kurze Striche',
                            // shortdot:        'Kleine Punkte',
                            // shortdashdot:    'Strichpunkt, kurz',
                            // shortdashdotdot: 'Strichpunktpunkt, kurz',
                            dot:             'Punktiert',
                            dash:            'Gestrichelt',
                            longdash:        'Gestrichelt, lang',
                            dashdot:         'Strichpunkt',
                            longdashdot:     'Strichpunktpunkt'
                            // longdashdotdot:  'Strichpunktpunkt, lang'
                        }),
                        value:   "solid",
                        css:     {width: "50%"}

                    }]
                }, {
                    title:   "Aktivieren",
                    type:    "checkbox",
                    checked: true,
                    name:    "stroke",
                    value:   '1'
                }]
            };
            var labelTab = {

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
                            title:   'Fontname',
                            type:    'select',
                            value:   'Arial, Helvetica, sans-serif',
                            options: Mapbender.Util.beautifyOptions({
                                'Arial, Helvetica, sans-serif':                         'Arial, Helvetica, sans-serif',
                                '"Arial Black", Gadget, sans-serif':                    'Arial Black, Gadget, sans-serif',
                                '"Comic Sans MS", cursive, sans-serif':                 'Comic Sans MS, cursive, sans-serif',
                                'Impact, Charcoal, sans-serif':                         'Impact, Charcoal, sans-serif',
                                '"Lucida Sans Unicode", "Lucida Grande", sans-serif':   'Lucida Sans Unicode, Lucida Grande, sans-serif',
                                'Tahoma, Geneva, sans-serif':                           'Tahoma, Geneva, sans-serif',
                                '"Trebuchet MS", Helvetica, sans-serif':                'Trebuchet MS, Helvetica, sans-serif',
                                'Verdana, Geneva, sans-serif':                          'Verdana, Geneva, sans-serif',
                                'Georgia, serif':                                       'Georgia, serif (nichtproportionale Schrift)',
                                '"Palatino Linotype", "Book Antiqua", Palatino, serif': 'Palatino Linotype, "Book Antiqua", Palatino, serif (nichtproportionale Schrift)',
                                '"Times New Roman", Times, serif':                      'Times New Roman, Times, serif (nichtproportionale Schrift)'
                            }),
                            name:     'fontFamily',
                            infoText: 'The font family for the label, to be provided like in CSS.',
                            css:      {width: "50%"}

                        }, {
                            title:   'Grösse',
                            name:    'fontSize',
                            type:    'select',
                            value:   11,
                            options: Mapbender.Util.beautifyOptions({
                                "9":  9,
                                "10": 10,
                                "11": 11,
                                "12": 12,
                                "13": 13,
                                "14": 14
                            }),
                            css:      {width: "20%"},
                            infoText: 'The font size for the label, to be provided like in CSS'
                        },
                            {
                                title:    'Art',
                                name:     'fontWeight',
                                type:     'select',
                                value:    'regular',
                                options:  Mapbender.Util.beautifyOptions({
                                    'regular': 'Normal',
                                    'bold':    'Fett',
                                    'italic':  'Kursive'
                                }),
                                css:      {width: "30%"},
                                infoText: 'The font weight for the label, to be provided like in CSS.'
                            }, {
                                title:    'Farbe',
                                type:     'colorPicker',
                                name:     'fontColor',
                                // infoText: 'The font color for the label, to be provided like CSS.',
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
                            }
                            ]
                    }]

            };

            element.generateElements(Mapbender.Util.beautifyGenerateElements({
                type:     "tabs",
                children: [commonTab, fillTab, strokeTab, labelTab]
            }));

            window.setTimeout(function() {
                element.formData(options.data);
            }, 100);

            this.popup();
        },

        popup: function() {
            var widget = this;
            this.element.popupDialog({
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
            this.element.dialog("close");
        }
    });

})(jQuery);
