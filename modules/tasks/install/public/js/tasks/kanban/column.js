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

		this.sortButton = null;

		this.type = (options.type ? options.type : '');
	};

	BX.Tasks.Kanban.Column.prototype = {
		__proto__: BX.Kanban.Column.prototype,
		constructor: BX.Tasks.Kanban.Column,
		
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

		isRemovable: function()
		{
			if (this.getGrid().isScrumGrid())
			{
				return (!this.isFinishType() && !this.isNewType())
			}

			return BX.Kanban.Column.prototype.isRemovable.call(this);
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
				draggableItem.isSprintView
				&& this.isFinishType()
				&& (!draggableItem.getColumn().isFinishType())
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

		isFinishType: function()
		{
			return (this.getType() === 'FINISH');
		},

		isNewType: function()
		{
			return (this.getType() === 'NEW');
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

		setGrid: function(grid)
		{
			BX.Kanban.Column.prototype.setGrid.call(this, grid);

			BX.addCustomEvent(this.getGrid(), "Kanban.Grid:onItemDragStop", function() {
				if(this.getGrid().isRealtimeMode())
				{
					this.hideDragTarget();
				}
			}.bind(this));
		},

		handleConfirmButtonClick: function()
		{
			var confirmDialog = this.getConfirmDialog();
			var removeButton = confirmDialog.getButton("main-kanban-confirm-remove-button");
			if (removeButton.getContainer().classList.contains("popup-window-button-wait"))
			{
				//double click protection
				return;
			}

			removeButton.addClassName("popup-window-button-wait");

			var promise = this.getGrid().getEventPromise(
				"Kanban.Grid:onColumnRemovedAsync",
				null,
				function(result) {

					if (this.getGrid().isGroupingMode())
					{
						this.getGrid().removeColumnsByIdFromNeighborGrids(this.getId());
					}

					this.getGrid().removeColumn(this);
					removeButton.removeClassName("popup-window-button-wait");
					confirmDialog.close();

				}.bind(this),
				function(error) {
					confirmDialog.setContent(error);
					removeButton.getContainer().style.display = "none";
				}.bind(this)
			);

			promise.fulfill(this);
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
				var newColumn = this.getGrid().addColumn({
					id: 'kanban-new-column-' + BX.util.getRandomString(5),
					type: 'BX.Tasks.Kanban.DraftColumn',
					canSort: false,
					canAddItem: false,
					droppable: false,
					targetId: this.getGrid().getNextColumnSibling(this)
				});

				newColumn.switchToEditMode();
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

		getTitleContainer: function()
		{
			if (this.layout.title)
			{
				return this.layout.title;
			}

			this.layout.title = BX.create("div", {
				attrs: {
					className: (this.isChildScrumGrid() ? "" : "main-kanban-column-title")
				}
			});

			return this.layout.title;
		},

		renderTitle: function()
		{
			if (this.isChildScrumGrid())
			{
				return document.createElement('div');
			}
			else
			{
				return BX.Kanban.Column.prototype.renderTitle.call(this);
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
		},

		/**
		 * @returns {BX.Tasks.Kanban.Grid}
		 */
		getGrid: function()
		{
			return this.grid;
		},

		getAddColumnButton: function ()
		{
			if (this.isChildScrumGrid())
			{
				return document.createElement('div');
			}
			else
			{
				return BX.Kanban.Column.prototype.getAddColumnButton.call(this);
			}
		},

		isGridGroupingMode: function()
		{
			return this.getGrid().isGroupingMode();
		},

		isChildScrumGrid: function()
		{
			return this.getGrid().isChildScrumGrid();
		}
	};

	BX.Tasks.Kanban.DraftColumn = function(options)
	{
		BX.Kanban.DraftColumn.apply(this, arguments);
	};

	BX.Tasks.Kanban.DraftColumn.prototype = {
		__proto__: BX.Kanban.DraftColumn.prototype,
		constructor: BX.Tasks.Kanban.DraftColumn,
		applyEditMode: function()
		{
			if (this.asyncEventStarted)
			{
				return;
			}

			var title = BX.util.trim(this.getTitleTextBox().value);
			if (!title.length)
			{
				title = this.getGrid().getMessage("COLUMN_TITLE_PLACEHOLDER");
			}

			this.setName(title);
			this.getContainer().classList.add("main-kanban-column-disabled");
			this.getTitleTextBox().disabled = true;

			this.asyncEventStarted = true;
			var promise = this.getGrid().getEventPromise(
				"Kanban.Grid:onColumnAddedAsync",
				null,
				function(result) {

					if (!BX.Kanban.Utils.isValidId(result.targetId))
					{
						var targetColumn = this.getGrid().getNextColumnSibling(this);
						if (targetColumn)
						{
							result.targetId = targetColumn.getId();
						}
					}

					if (this.getGrid().isGroupingMode())
					{
						this.getGrid().getNeighborGrids().forEach(function(neighborGrid) {
							neighborGrid.addColumn(result);
						});
					}

					this.getGrid().removeColumn(this);
					this.getGrid().addColumn(result);

				}.bind(this),
				function(error) {

					this.getGrid().removeColumn(this);

				}.bind(this)
			);

			promise.fulfill(this);
		}
	};

})();