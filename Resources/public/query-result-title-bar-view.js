$.widget("wheregroup.queryResultTitleBarView", {
    options: {
        data: {
            id: null
        }
    },

    /**
     * Constructor
     */
    _create: function() {
        var widget = this;
        var options = this.options;

        widget.render(options.data);
    },

    /**
     * Render
     */
    render: function(query) {
        var widget = this;
        var element = $(widget.element);
        var options = widget.options;
        var title = $("<span class='titleText' />").html(query.name);
        var context = {
            widget: widget,
            query:  options.data
        };

        element.data('query', query).attr('data-id', query.id);

        element.append(title);

        element.generateElements({
            cssClass: 'buttons',
            type:     'fieldSet',
            children: [{
                type:     'button',
                cssClass: 'fa fa-edit',
                title:    'Edit',
                click:    function(e) {
                    widget._trigger("edit", null, context);
                    return false;
                }
            }, {
                type:     'button',
                cssClass: 'fa fa-download',
                title:    'Export',
                click:    function(e) {
                    widget._trigger("export", null, context);
                    return false;
                }
            }, {
                type:     'button',
                cssClass: 'fa fa-eye',
                title:    'Anzeigen',
                click:    function(e) {
                    widget._trigger("visibility", null, context);
                    return false;
                }
            }, {
                cssClass: 'fa fa-remove',
                type:     'button',
                title:    'Löschen',
                click:    function(e) {
                    widget._trigger("remove", null, context);
                    return false;
                }
            }]
        });

        return element;
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

