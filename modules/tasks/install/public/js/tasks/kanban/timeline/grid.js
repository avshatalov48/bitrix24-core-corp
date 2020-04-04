(function() {

"use strict";

BX.namespace("BX.Tasks.Timeline");

/**
 *
 * @param options
 * @extends {BX.Tasks.Kanban.Grid}
 * @constructor
 */
BX.Tasks.Timeline.Grid = function(options)
{
	BX.Tasks.Kanban.Grid.apply(this, arguments);
};

BX.Tasks.Timeline.Grid.prototype = {
	__proto__: BX.Tasks.Kanban.Grid.prototype,
	constructor: BX.Tasks.Timeline.Grid,

	onItemDragStart: function(item)
	{
		this.setDragMode(BX.Kanban.DragMode.ITEM);

		var errorMsg = "";

		var items = this.getItems();
		var taskData = item.getData();
		var taskColumnId = item.getColumnId();

		var isComplete = taskData.completed || taskData.completed_supposedly;
		var isAllowChange = taskData.allow_change_deadline;
		var overdueUntil = taskData.overdue_until ? taskData.overdue_until : 0;

		var columnIdComplete = this.getData().columnIdComplete;
		var columnIdOverdue = this.getData().columnIdOverdue;

		if (errorMsg === "")
		{
			for (var itemId in items)
			{
				var columnId = items[itemId].getColumnId();
				var columnData = items[itemId].getColumn().getData();
				var enable = false;

				// in same column, or make complete
				if (taskColumnId === columnId)
				{
					enable = true;
				}
				else if (columnId === columnIdComplete)
				{
					enable = true;
				}
				// generate errors
				if (!enable && isComplete)
				{
					errorMsg = BX.message("TASKS_KANBAN_ME_DISABLE_COMPLETE");
				}
				else if (!enable && (taskColumnId === columnIdOverdue))
				{
					errorMsg = BX.message("TASKS_KANBAN_ME_DISABLE_FROM_OVERDUE");
				}
				else if (!enable && !isAllowChange)
				{
					errorMsg = BX.message("TASKS_KANBAN_ME_DISABLE_DEADLINE");
				}
				else if (
					!enable && columnData.overdue_until &&
					overdueUntil && overdueUntil <= columnData.overdue_until
				)
				{
					errorMsg = BX.message("TASKS_KANBAN_ME_DISABLE_DEADLINE_PART");
				}
				// or enable drag
				else if (columnId !== columnIdOverdue)
				{
					enable = true;
				}

				if (enable)
				{
					items[itemId].enableDropping();
				}
			}

			this.getColumns().forEach(function(/*BX.Kanban.Column*/column) {
				var columnId = column.getId();
				var columnData = column.getData();
				var enable = false;

				// in same column, or make complete
				if (taskColumnId === columnId)
				{
					enable = true;
				}
				else if (columnId === columnIdComplete)
				{
					enable = true;
				}
				// generate errors
				if (!enable && isComplete)
				{
					errorMsg = BX.message("TASKS_KANBAN_ME_DISABLE_COMPLETE");
				}
				else if (!enable && (taskColumnId === columnIdOverdue))
				{
					errorMsg = BX.message("TASKS_KANBAN_ME_DISABLE_FROM_OVERDUE");
				}
				else if (!enable && !isAllowChange)
				{
					errorMsg = BX.message("TASKS_KANBAN_ME_DISABLE_DEADLINE");
				}
				else if (
					!enable && columnData.overdue_until &&
					overdueUntil && overdueUntil <= columnData.overdue_until
				)
				{
					errorMsg = BX.message("TASKS_KANBAN_ME_DISABLE_DEADLINE_PART");
				}
				// or enable drag
				else if (columnId !== columnIdOverdue)
				{
					enable = true;
				}

				if (enable)
				{
					column.enableDropping();
				}
			});
		}

		if (errorMsg !== "")
		{
			item.getDragElement().appendChild(this.createAlertBlock(errorMsg))
		}

	},

	createAlertBlock: function (message)
	{

		return BX.create("div", {
			props: {
				className: "tasks-kanban-item-alert"
			},
			text: message
		});

	}

};


})();