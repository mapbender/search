/**
 * Created by ransomware on 27/09/16.
 * Released under the MIT license.
 */
( function($) {
    debugger;
    return $.widget("rw.querymanager", {
        version: "1.0.0",

        source:      null,
        fields:      null,
        constraints: null,
        general:     null,

        onFormError: null,
        onOpen:      null,
        onClose:     null,

        callBackMapping: {
            "open":      "onOpen",
            "close":     "onClose",
            "formError": "onFormError",
            "ready":     "onReady"

        },

        eventMap:    {

        },

        options: {

        },
        /**
         * Constructor
         *
         * @private
         */
        _create: function() {
            var self = this;
            var options = this.options;
            var eventManager = EventDispatcher;
            this.eventManager = _.extend({}, eventManager);
            if(options.onReady)
                eventManager.on("onReady", options.onReady);
            self.el = self._getDiv();

            $.getScript("querymanager-defaults.js", function(data, statusCode, xhr) {
                debugger;
                var options = eval(data);
                self.general = options.general;
                self.source = options.source;
                self.fields = options.fields;
                self.constraints = options.constraints;
                eventManager.dispatch("onReady", self);
            });
        },


        // Private Methods
        _capitalizeFirstCharacter: function(string) {
            return string && string.length > 0 ? string.charAt(0).toUpperCase() + string.slice(1) : string;
        },

        _extractEvents: function(key, value) {
            var defaultMapping = this.callBackMapping;
            for (var prop in defaultMapping) {
                if(defaultMapping.hasOwnProperty(prop)) {
                    var eventKey = "on" + this._capitalizeFirstCharacter(prop);
                    if(key === prop || key === eventKey) {
                        this[eventKey] = value;
                    }
                }
            }
        },

        _setOption: function(key, value) {
            this._extractEvents(key, value);
            this._super(key, value);
        },

        _setOptions: function(options) {
            this._super(options);
            this.refresh();
        },


        _initEventHandler: function() {

        },

        _has: function(obj, prop) {
            return obj && obj[prop] !== undefined;
        },

        getForm: function() {
            var self = this;

            return self.el.generateElements({
                type:     "tabs",
                children: [self._getForm(self.general, true), self._getForm(self.source), self._getForm(self.fields), self._getForm(self.constraints)]
            });

        },

        showPopup:      function() {

            var self = this;
            var buttons = [self._cancelButton(function(e) {
                var popup = $(e.currentTarget).closest(".ui-").find(".popup-");
                console.log(popup.formData());
                popup.popup('close');
            }), self._saveButton()];

            return this.getForm().popupDialog({
                title:       "Querymanager",
                maximizable: true,
                width:       "500px",
                buttons:     buttons
            });

        },
        _getDiv:        function() {
            return this.el || $("<div/>");
        },
        _getIconButton: function(options) {
            return {
                type:     "button",
                name:     options.name,
                title:    options.title,
                text:     options.title,
                cssClass: options.icon ? this._getIcon(options.icon) : undefined,
                click:    options.click
            };
        },

        _getForm: function(obj, active) {
            return {
                type:     "form",
                title:    obj.title,
                children: obj.children,
                active:   !!active
            }
        },

        _cancelButton: function(clickHandler) {
            return this._getIconButton({
                name:  "cancelSave",
                title: "Cancel",
                click: clickHandler
            });
        },

        _saveButton: function(clickHandler) {
            return this._getIconButton({
                name:  "buttonSave",
                title: "Save",
                click: clickHandler
            });
        },

        _getIcon: function(name) {
            return 'fa fa-' + name;
        },
        fill:     function(data) {
            this.el.formData(data);
        },
        check:    function(data) {

        }
    });

} );
