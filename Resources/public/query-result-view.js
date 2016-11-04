$.widget("wheregroup.queryResultView", {
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

        element.data('query', query).attr('data-id', query.id).html(JSON.stringify(query));

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

