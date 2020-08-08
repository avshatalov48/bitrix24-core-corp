(function() {

	"use strict";

	BX.namespace("BX.Tasks.Kanban");

	/**
	 *
	 * @param options
	 * @extends {BX.Kanban.Column}
	 * @constructor
	 */
	BX.Tasks.Kanban.Column = function(options)
	{
		BX.Kanban.Column.apply(this, arguments);
		this.bindEvents();

		this.sortButton = null;
	};

	BX.Tasks.Kanban.Column.prototype = {
		__proto__: BX.Kanban.Column.prototype,
		constructor: BX.Tasks.Kanban.Column,

		bindEvents: function()
		{
			BX.addCustomEvent("Kanban.Grid:onItemDragStop", function() {
				if(this.getGrid().isRealtimeMode())
				{
					this.hideDragTarget();
				}
			}.bind(this));
		},
		
		/**
		 * Customize title buttons.
		 * @returns {DOMNode}
		 */
		getCustomTitleButtons: function ()
		{
			if (this.getGridData().showSortButton)
			{
				this.sortButton = BX.create("div", {
					props: {
						className: "tasks-kanban-column-sort"
					},
					events: {
						click: this.handleSortButtonClick.bind(this)
					}
				});

				return this.sortButton;
			}
		},

		/**
		 * Hook on sort column button.
		 * @param {MouseEvent} event
		 * @returns {void}
		 */
		handleSortButtonClick: function(event)
		{
			var menuItems = this.getGridData().sortMenuItems;
			BX.PopupMenu.show("tasks-kanban-column-sort-" + this.getId(), this.sortButton, menuItems, {});
		},
		
		/**
		 * Hook on add column button.
		 * @param {MouseEvent} event
		 * @returns {void}
		 */
		handleAddColumnButtonClick: function(event)
		{
			var gridData = this.getGridData();
			// if no access, show access-query popup
			if (
				gridData.rights &&
				gridData.rights.canAddColumn
			)
			{
				BX.Kanban.Column.prototype.handleAddColumnButtonClick.apply(this, arguments);
			}
			else if (typeof BX.Intranet !== "undefined")
			{
				this.getGrid().accessNotify();
			}
		},
		
		/**
		 * Switch from view to edit mode (column).
		 * @returns {void}
		 */
		switchToEditMode: function()
		{
			var gridData = this.getGridData();
			// if no access, show access-query popup
			if (
				gridData.rights &&
				gridData.rights.canAddColumn
			)
			{
				BX.Kanban.Column.prototype.switchToEditMode.apply(this, arguments);
			}
			else if (typeof BX.Intranet !== "undefined")
			{
				this.getGrid().accessNotify();
			}
		},

		/**
		 * Handler on new item add button.
		 * @param {event} event
		 * @returns {void}
		 */
		handleAddItemButtonClick: function(event)
		{
			var gridData = this.getGridData();

			if (
				gridData.addItemInSlider === true &&
				typeof BX.Bitrix24 !== "undefined" &&
				typeof BX.Bitrix24.PageSlider !== "undefined"
			)
			{
				BX.Bitrix24.PageSlider.open(
					gridData.pathToTaskCreate.replace("#task_id#", 0)
				);
			}
			else
			{
				BX.Kanban.Column.prototype.handleAddItemButtonClick.apply(this, arguments);
			}
		},

		/**
		 *
		 * @returns {Element}
		 */
		getDefaultTitleLayout: function()
		{
			if (this.layout.titleBody)
			{
				return this.layout.titleBody;
			}

			var customButtons = this.getCustomTitleButtons();
			var gridData = this.getGrid().getData();

			if (BX.type.isDomNode(customButtons))
			{
				customButtons = [customButtons];
			}
			else if (!BX.type.isArray(customButtons))
			{
				customButtons = [];
			}

			this.layout.titleBody = BX.create("div", {
				attrs: {
					className: "main-kanban-column-title-wrapper"
				},
				children: [
					this.layout.color = BX.create("div", {
						attrs: {
							className: "main-kanban-column-title-bg",
							style: "background: #" + this.getColor()
						}
					}),
					this.layout.info = BX.create("div", {
						attrs: {
							className: "main-kanban-column-title-info"
						},
						children: [

							this.layout.name = BX.create("div", {
								attrs: {
									className: "main-kanban-column-title-text"
								},
								children: [
									this.getColumnTitle(),
									this.getTotalItem()
								]
							}),

							this.isEditable() ? this.getEditButton() : null
						].concat(customButtons)
					}),

					this.isEditable() ? this.getEditForm() : null,

					this.layout.titleArrow = BX.create("span", {
						attrs: {
							className: "main-kanban-column-title-right"

						}
					})
				]});

			if(gridData.kanbanType === "TL")
			{
				this.layout.titleBody.classList.add("task-kanban-column-revert")
			}

			return this.layout.titleBody;
		}
	};

})();