$.widget("wheregroup.queryResultTitleBarView", {
    options: {
        query: null
    },

    /**
     * Constructor
     */
    _create: function() {
        this.render(this.options.query);
    },

    /**
     * Render
     */
    render: function(query) {
        var widget = this;
        var element = $(widget.element);
        $('.title-text', this.element).text(query.name);
        var context = {
            widget: widget,
            query:  query
        };

        element.attr('data-id', query.id);
        var $buttons = $('>.buttons', this.element);    // Match only within header
        $('.-fn-zoomtolayer, .-fn-visibility', $buttons).toggle(!query.exportOnly);
        $buttons.on('click', '.-fn-edit', function() {
            widget._trigger('edit', null, context);
            return false;
        });
        $buttons.on('click', '.-fn-delete', function() {
            widget._trigger('remove', null, context);
            return false;
        });
        $buttons.on('click', '.-fn-export', function() {
            widget._trigger('export', null, context);
            return false;
        });
        $buttons.on('click', '.-fn-zoomtolayer', function() {
            widget._trigger('zoomToLayer', null, context);
            return false;
        });
        $buttons.on('click', '.-fn-visibility', function() {
            widget._trigger('visibility', null, context);
            return false;
        });
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

