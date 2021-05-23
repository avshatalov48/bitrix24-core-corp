BX.namespace('BX.Tasks');

BX.Tasks.InterfaceToolbar = function(params)
{
    this.gridId = params.gridId || '';
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

        this.filterManager = BX.Main.filterManager.getById(this.gridId);

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
        var fields = {};

        switch(eventArgs.counterTypeId)
        {
            case "tasks_my_expired":
                var date = BX.date.format("d.m.Y", new Date(), null, true);

                fields = {DEADLINE_datesel: "RANGE", DEADLINE_to: date};
            break;
            case "tasks_my_expired_cand":
                var dFrom = new Date();
                var dTo = new Date();
                dFrom.setUTCHours(dFrom.getUTCHours() - 24);
                var dateFrom = BX.date.format("d.m.Y H:i:s", dFrom, null, true);

                dTo.setUTCHours(dTo.getUTCHours() + 24);
                var dateTo = BX.date.format("d.m.Y H:i:s", dTo, null, true);

                fields = {DEADLINE_datesel: "RANGE", DEADLINE_from: dateFrom, DEADLINE_to: dateTo};
            break;
        }

        this.filterApi.setFields(fields);
        this.filterApi.apply();
    }
};