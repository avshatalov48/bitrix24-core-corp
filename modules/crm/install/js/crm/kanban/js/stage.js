(function() {

	"use strict";

	BX.namespace("BX.CRM.Kanban");

	BX.CRM.Kanban.Stage = function(options)
	{
		this.renderTo = options.renderTo;
		this.items = null;
		this.layout = {
			container: null,
			items: null,
			more: null
		};

		this.bindEvents();
	};

	BX.CRM.Kanban.Stage.prototype =
	{
		bindevents: function()
		{
			bx.addcustomevent("kanban.grid:onitemdragstart", this.hideparentblock.bind(this));
			bx.addcustomevent("kanban.grid:onitemdragstop", this.unhideparentblock.bind(this));
		},

		hideparentblock: function()
		{
			this.renderto.style.opacity = "0";
			this.renderto.style.transition = ".2s";
			this.renderto.style.pointerevents = "none";
		},

		unhideparentblock: function()
		{
			this.renderto.style.opacity = "";
			this.renderto.style.transition = "";
			this.renderto.style.pointerevents = "";
		},

		render: function()
		{
		},

		getstagecontainer: function()
		{
			if(this.layout.container)
				return this.layout.container;

			return this.layout.container = bx.create("div", {
				props: {
					classname: "crm-kanban-stahe"
				}
			})
		}

	}
})();