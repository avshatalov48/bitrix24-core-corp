BX.namespace('BX.Tasks');

BX.Tasks.InterfaceToolbar = function(params)
{
    this.filterId = params.filterId;
    this.grid = {};
    this.containerId = params.containerId;
    this.container = {};
    this.itemSelector = 'a.tasks-counter-container';
    this.params = params || {};
    this.filterManager = null;
    this.filterApi = null;
    this.itemClickHandler = null;

    return this.init();
};

BX.Tasks.InterfaceToolbar.prototype = {
    init: function()
    {
        this.itemClickHandler = BX.delegate(this.onItemClick, this);

        this.container = BX(this.containerId);

        if(!BX.type.isElementNode(this.container))
        {
            throw "BX.Tasks.InterfaceToolbar: Could not find container: " + this.containerId;
        }

        var itemNodes = this.container.querySelectorAll(this.itemSelector);
        for(var i = 0, l = itemNodes.length; i < l; i++)
        {
            BX.bind(itemNodes[i], "click", this.itemClickHandler);
        }

        this.filterManager = BX.Main.filterManager.getById(this.filterId);

        if(!this.filterManager)
        {
            alert('BX.Main.filterManager not initialised');
            return;
        }
        this.filterApi = this.filterManager.getApi();
    },
    onItemClick: function(e)
    {
        var itemNode = BX.findParent(BX.getEventTarget(e), { tagName: "A", className: "tasks-counter-container" });
        if(itemNode)
        {
            var typeId = itemNode.getAttribute("data-type-id");
            if(BX.type.isNotEmptyString(typeId))
            {
                var eventArgs = { counterTypeId: typeId, cancel: false };

                this.applyFilter(eventArgs);

                if(eventArgs.cancel)
                {
                    return BX.PreventDefault(e);
                }
            }
        }
        return true;
    },
    applyFilter: function(eventArgs)
    {
        var fields = {
            PROBLEM: eventArgs.counterTypeId
        };

        this.filterApi.setFields(fields);
        this.filterApi.apply();
    }
};