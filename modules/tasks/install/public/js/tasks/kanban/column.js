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

		this.type = (options.type ? options.type : '');

		this.finishStatus = 'FINISH';
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
		 *
		 * @param {Element} itemNode
		 * @param {number} x
		 * @param {number} y
		 */
		onDragDrop: function(itemNode, x, y)
		{
			if(this.getGrid().isMultiSelect() && this.getGrid().getSelectedItems().size > 1)
			{
				return this.onDragDropMultiple();
			}

			this.hideDragTarget();
			var draggableItem = this.getGrid().getItemByElement(itemNode);

			var event = new BX.Kanban.DragEvent();
			event.setItem(draggableItem);
			event.setTargetColumn(this);

			BX.onCustomEvent(this.getGrid(), "Kanban.Grid:onBeforeItemMoved", [event]);
			if (!event.isActionAllowed())
			{
				return;
			}

			var taskCompletePromise = new BX.Promise();

			if (
				draggableItem.isSprintView &&
				(this.type === this.finishStatus) &&
				(draggableItem.getColumn().type !== this.finishStatus)
			)
			{
				if (typeof BX.Tasks.Scrum === 'undefined' || typeof BX.Tasks.Scrum.ScrumDod === 'undefined')
				{
					taskCompletePromise.fulfill();
				}

				this.scrumDod = new BX.Tasks.Scrum.ScrumDod({
					groupId: draggableItem.getData()['groupId']
				});

				var choiceMadePromise = this.scrumDod.showList(draggableItem.getId());
				choiceMadePromise.then(function() {
					taskCompletePromise.fulfill();
				}.bind(this));
			}
			else
			{
				taskCompletePromise.fulfill();
			}

			taskCompletePromise.then(function() {
				var success = this.getGrid().moveItem(draggableItem, this);
				if (success)
				{
					BX.onCustomEvent(this.getGrid(), "Kanban.Grid:onItemMoved", [draggableItem, this, null]);
				}
			}.bind(this));
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
		},

		getType: function()
		{
			return this.type;
		}
	};

})();