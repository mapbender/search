$.widget("wheregroup.queryResultTitleBarView", {
    options: {},

    /**
     * Constructor
     */
    _create: function() {
        var widget = this;
        var element = $(widget.element);
        var query = element.data('query');

        widget.render(query);
    },

    /**
     * Render
     */
    render: function(query) {
        var widget = this;
        var element = $(widget.element);
        var title = $("<span class='titleText' />").text(query.name);
        var context = {
            widget: widget,
            query:  query
        };
        var buttons = [];

        element
            .empty()
            .attr('data-id', query.id)
            .append(title);

        var editButton = {
            type:     'button',
            cssClass: 'fa fa-edit',
            title:    'Abfrage bearbeiten',
            click:    function(e) {
                widget._trigger("edit", null, context);
                return false;
            }
        };
        var removeButton = {
            cssClass: 'fa fa-remove',
            type:     'button',
            title:    'Abfrage löschen',
            click:    function(e) {
                widget._trigger("remove", null, context);
                return false;
            }
        };
        var exportButton = {
            type:     'button',
            cssClass: 'fa fa-download',
            title:    'Exportieren',
            click:    function(e) {
                widget._trigger("export", null, context);
                return false;
            }
        };
        var zoomToButton = {
            type:     'button',
            cssClass: 'fa fa-map-o',
            title:    'Heranzoomen (Nur wenn Treffer vorhanden sind.)',
            click:    function(e) {
                widget._trigger("zoomToLayer", null, context);
                return false;
            }
        };
        var showButton = {
            type:     'button',
            cssClass: 'fa fa-eye',
            title:    'Abfrageergebnisse Anzeigen',
            click:    function(e) {
                widget._trigger("visibility", null, context);
                // return false;
            }
        };

        buttons.push('<i class="fa-li fa fa-spinner fa-spin preloader" style="margin-top: 2px; display: none"></i>');

        buttons.push(editButton);
        buttons.push(exportButton);

        if(!query.exportOnly) {

            buttons.push(zoomToButton);
            buttons.push(showButton);
        }

        buttons.push(removeButton);

        element.generateElements(Mapbender.Util.beautifyGenerateElements({
            cssClass: 'buttons',
            type:     'fieldSet',
            children: buttons
        }));

        return element;
    },

    showPreloader: function() {
        var widget = this;
        var element = $(widget.element);
        $('.preloader', element).show();
    },

    hidePreloader: function() {
        var widget = this;
        var element = $(widget.element);
        $('.preloader', element).hide();
    },

    /**
     * Close and remove widget
     */
    close: function() {
        var widget = this;
        var element = $(widget.element);
        var options = widget.optionás;

        widget.element.remove();
    }
});

