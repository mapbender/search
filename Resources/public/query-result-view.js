$.widget("wheregroup.queryResultView", {
    options: {
        data: {
            id: null
        }

    },

    preparedData:{
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

        element
            .data('query', query)
            .attr('data-id', query.id)
            .empty();

        element.generateElements({
            type:     'fieldSet',
            children: [{
                type:     'checkbox',
                cssClass: 'onlyExtent',
                title:    "Nur Kartenabschnitt",
                checked:  query.extendOnly,
                change:   function(e) {
                    var input = $('input', this);
                    widget._trigger('changeExtend', null, {
                        query:   query,
                        widget:  widget,
                        element: element,
                        checked: input.is(":checked")
                    });
                    //schema.searchType = $(e.originalEvent.target).prop("checked") ? "currentExtent" : "all";
                    //widget._getData();
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

