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
				top.BX.loadExt('tasks.scrum.dod').then(function() {
					if (typeof top.BX.Tasks.Scrum === 'undefined' || typeof top.BX.Tasks.Scrum.Dod === 'undefined')
					{
						taskCompletePromise.fulfill();
					}
					this.scrumDod = new top.BX.Tasks.Scrum.Dod({
						groupId: draggableItem.getData()['groupId'],
						taskId: draggableItem.getId()
					});
					this.scrumDod.subscribe('resolve', function() { taskCompletePromise.fulfill() });
					this.scrumDod.subscribe('reject', function() { taskCompletePromise.reject() });
					this.scrumDod.isNecessary()
						.then(function(isNecessary) {
							if (isNecessary)
							{
								this.scrumDod.showList();
							}
							else
							{
								taskCompletePromise.fulfill();
							}
						}.bind(this))
					;
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
					targetId: this.getGrid().getNextColumnSibling(this),
					animate: 'slide-left'
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

			if (gridData.addItemInSlider === true && BX.SidePanel.Instance)
			{
				BX.SidePanel.Instance.open(gridData.pathToTaskCreate.replace('#task_id#', 0));
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
					className: ((this.isScrumGridHeader() || !this.isScrumGrid()) ? "main-kanban-column-title" : "")
				}
			});

			return this.layout.title;
		},

		getSubTitle: function()
		{
			if (this.isScrumGridHeader())
			{
				this.layout.subTitle = document.createElement('div');

				return this.layout.subTitle;
			}
			else
			{
				return BX.Kanban.Column.prototype.getSubTitle.call(this);
			}
		},

		renderTitle: function()
		{
			if (this.isScrumGridHeader())
			{
				this.getContainer().classList.add("main-kanban-column-scrum");
			}

			if ((this.isScrumGridHeader() || !this.isScrumGrid()))
			{
				return BX.Kanban.Column.prototype.renderTitle.call(this);
			}
			else
			{
				return document.createElement('div');
			}
		},

		getBody: function()
		{
			if (this.isScrumGridHeader())
			{
				this.layout.body = document.createElement('div');

				this.layout.items = this.getItemsContainer();

				return this.layout.body;
			}
			else
			{
				return BX.Kanban.Column.prototype.getBody.call(this);
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
			if (this.isScrumGrid() && !this.isScrumGridHeader())
			{
				return document.createElement('div');
			}
			else
			{
				return BX.Kanban.Column.prototype.getAddColumnButton.call(this);
			}
		},

		getItemsContainer: function()
		{
			if (this.isScrumGrid() && this.isScrumGridHeader())
			{
				return document.createElement('div');
			}
			else
			{
				return BX.Kanban.Column.prototype.getItemsContainer.call(this);
			}
		},

		isScrumGrid: function()
		{
			return this.getGrid().isScrumGrid();
		},

		isScrumGridHeader: function()
		{
			return this.getGrid().isScrumGridHeader();
		},

		addItem: function(item, beforeItem)
		{
			BX.Kanban.Column.prototype.addItem.call(this, item, beforeItem);

			if (item.isCountable())
			{
				this.updateHeaderColumn();
			}
		},

		removeItem: function(itemToRemove)
		{
			BX.Kanban.Column.prototype.removeItem.call(this, itemToRemove);

			if (itemToRemove.isCountable())
			{
				this.updateHeaderColumn();
			}
		},

		updateHeaderColumn: function()
		{
			if (this.isScrumGrid())
			{
				this.getGrid().getNeighborGrids()
					.forEach(function(neighborGrid) {
						if (neighborGrid.isScrumGridHeader())
						{
							neighborGrid.updateTotals();
						}
					}.bind(this))
				;
			}
		}
	};

	BX.Tasks.Kanban.DraftColumn = function(options)
	{
		BX.Kanban.DraftColumn.apply(this, arguments);
	};

	BX.Tasks.Kanban.DraftColumn.prototype = {
		__proto__: BX.Kanban.DraftColumn.prototype,
		constructor: BX.Tasks.Kanban.DraftColumn,
		isScrumGridHeader: function()
		{
			return this.getGrid().isScrumGridHeader();
		},
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
		},
		getSubTitle: function()
		{
			if (this.isScrumGridHeader())
			{
				this.layout.subTitle = document.createElement('div');

				return this.layout.subTitle;
			}
			else
			{
				return BX.Kanban.Column.prototype.getSubTitle.call(this);
			}
		},
		getBody: function()
		{
			if (this.isScrumGridHeader())
			{
				this.layout.body = document.createElement('div');

				this.layout.items = this.getItemsContainer();

				return this.layout.body;
			}
			else
			{
				return BX.Kanban.Column.prototype.getBody.call(this);
			}
		},
	};

})();