(function(window) {

if (BX.TaskPriorityPopup)
	return;

/*==================================================Generic Task List Popup========================================================*/
var TaskListPopup = function(popupId, popupClassName, title, items, params)
{
	this.items = [];
	this.popupId = popupId;
	this.popupClassName = popupClassName;
	if (BX.type.isArray(items))
	{
		for (var i = 0; i < items.length; i++)
		{
			if (typeof items[i]["id"] !== "undefined")
				this.items.push(items[i]);
		}
	}

	this.params = params || {};
	this.title = title;

	this.popupWindow = null;
	this.currentTask = null;
	this.tasksData = {};
	this.itemList = [];
};

TaskListPopup.prototype.show = function(taskId, bindElement, currentValues, params)
{
	if (this.popupWindow !== null)
		this.popupWindow.close();

	this.currentTask = this.__getTask(taskId, bindElement, currentValues, params);
	if (this.currentTask === null)
		return false;

	if (this.popupWindow == null)
		this.__createLayout();
	else
		this.popupWindow.setBindElement(this.currentTask.bindElement);

	this.__redrawList();

	this.popupWindow.show();
};

TaskListPopup.prototype.__createLayout = function()
{
	var items = [];
	for (var i = 0; i < this.items.length; i++)
	{
		var item = this.items[i];
		var domElement = BX.create(
			"a",
			{
				props : { className: "task-popup-list-item" },
				events : {
					click : BX.proxy(this.__onItemClick, {obj : this, listItem : item })
				},
				children : [
						BX.create("span", { props : { className : "task-popup-list-item-left" }}),
						BX.create("span", { props : { className : "task-popup-list-item-icon task-popup-"+ this.popupClassName + "-icon-" + item.className }}),
						BX.create("span", { props : { className : "task-popup-list-item-text" }, text : item.name }),
						BX.create("span", { props : { className : "task-popup-list-item-right" }})
				]
			}
		);

		BX.data(domElement, 'item', item);

		this.itemList[item.id] = domElement;
		items.push(domElement);
	}

	this.popupWindow = BX.PopupWindowManager.create("bx-task-" + this.popupId + "-popup", this.currentTask.bindElement, {
			autoHide : true,
			offsetTop : 1,
			lightShadow : true,
			closeByEsc: true,
			events : {
				onPopupClose : BX.proxy(this.__onPopupClose, this)
			},
			content : (this.popupWindowContent = BX.create(
				"div",
				{
					props : { className: "task-" + this.popupClassName + "-popup" },
					children : [
							BX.create("div", { props: { className: "task-" + this.popupClassName +"-popup-title" }, text : this.title } ),
							BX.create("div", { props : { className : "popup-window-hr" }, children : [ BX.create("i", {}) ]}),
							BX.create("div", { props: { className: "task-popup-list-list" }, children :  items } )
					]
				}
			))
	});
};

TaskListPopup.prototype.__redrawList = function()
{
	this.__selectItem(this.currentTask.listValue);
};

TaskListPopup.prototype.__selectItem = function(itemId)
{
	BX.Tasks.each(this.itemList, function(item, id){
		var obj = BX.data(item, 'item');

		if(obj.id == itemId || (typeof obj.values != 'undefined' && obj.values[itemId]))
		{
			BX.addClass(item, "task-popup-list-item-selected");
		}
		else
		{
			BX.removeClass(item, "task-popup-list-item-selected");
		}
	}.bind(this));
};

TaskListPopup.prototype.__getTask = function(taskId, bindElement, currentValues, params)
{
	if (!BX.type.isNumber(taskId))
		return null;

	this.tasksData[taskId] = {
		id : taskId,
		bindElement : bindElement,
		listItem : {},
		onPopupChange : params.events && params.events.onPopupChange && BX.type.isFunction(params.events.onPopupChange) ? params.events.onPopupChange : null,
		onPopupClose : params.events && params.events.onPopupClose && BX.type.isFunction(params.events.onPopupClose) ? params.events.onPopupClose : null
	};

	if (typeof(currentValues) === "object")
	{
		for (var prop in currentValues)
		{
			this.tasksData[taskId][prop] = currentValues[prop];
		}
	}
	else
	{
		this.tasksData[taskId].listValue = currentValues;
	}

	if (typeof(this.tasksData[taskId]["listValue"]) !== "undefined")
	{
		for (var i = 0; i < this.items.length; i++)
		{
			if (this.items[i].id === this.tasksData[taskId].listValue)
			{
				this.tasksData[taskId].listItem = this.items[i];
				break;
			}
		}
	}

	return this.tasksData[taskId];
};

TaskListPopup.prototype.__onItemClick = function(event)
{
	this.obj.popupWindow.close();

	if (this.obj.currentTask.listValue != this.listItem.id)
	{
		this.obj.currentTask.listValue = this.listItem.id;
		this.obj.currentTask.listItem = this.listItem;
		if (this.obj.currentTask.onPopupChange)
			this.obj.currentTask.onPopupChange();
	}

	BX.PreventDefault(event);
};

TaskListPopup.prototype.__onPopupClose = function(popupWindow)
{
	if (this.currentTask.onPopupClose)
		this.currentTask.onPopupClose();
};

/* ===================================================Priority Popup ===============================================================*/
var TaskPriorityPopup = function()
{
	TaskPriorityPopup.superclass.constructor.apply(this, [
		"priority",
		"priority",
		BX.message("TASKS_PRIORITY_V2"),
		[
			{ id : 0, name : BX.message("TASKS_COMMON_NO"), className : "low", values: {'0': true, '1': true} },
			{ id : 2, name : BX.message("TASKS_COMMON_YES"), className : "high" }
		],
		{}
	]);
};
BX.extend(TaskPriorityPopup, TaskListPopup);

/* ===================================================Public Priority Popup Method====================================================*/
BX.TaskPriorityPopup = {

	popup : null,
	show : function(taskId, bindElement, currentValue, params)
	{
		if (this.popup === null)
		{
			this.popup = new TaskPriorityPopup();
		}

		this.popup.show(taskId, bindElement, currentValue, params);
	}
};


/*=======================================================Simple Grade Popup===========================================================*/
var TaskGradeSimplePopup = function(popupId)
{
	TaskGradeSimplePopup.superclass.constructor.apply(this, [
		popupId,
		"grade",
		BX.message("TASKS_MARK"),
		[
			{ id : "NULL", name : BX.message("TASKS_MARK_NONE"), className : "none" },
			{ id : "P", name : BX.message("TASKS_MARK_P"), className : "plus" },
			{ id : "N", name : BX.message("TASKS_MARK_N"), className : "minus" }
		],
		{}
	]);
};

BX.extend(TaskGradeSimplePopup, TaskListPopup);

/* ================================================== Public Popup Method ======================================================================*/
BX.TaskGradePopup = {

	simplePopup : null,

	show : function(taskId, bindElement, currentValues, params)
	{
		if (this.simplePopup == null)
		{
			this.simplePopup = new TaskGradeSimplePopup("grade-simple");
		}
		this.simplePopup.show(taskId, bindElement, currentValues, params);
	}
};

})(window);