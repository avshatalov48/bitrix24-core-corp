var loadedTasks = {};
var newTaskParent = 0;
var newTaskDepth = 0;
var newTaskGroup = 0;
var newTaskGroupObj;
var tasksMenuPopup = {};
var quickInfoData = {};
var preOrder = {};

var TaskListFilterPopup = {
	popup : null,

	init : function(bindElement)
	{
		if (this.popup != null)
			return;

		this.popup = new BX.PopupWindow("task-list-filter", bindElement, {
			content : BX("task-list-filter"),
			offsetLeft : -263 + bindElement.offsetWidth - 10,
			offsetTop : 3,
			className : "task-filter-popup-window",
			zIndex: -120,
			closeByEsc : true,
			events: {
				onPopupClose: function(popupWindow) {
					if (tasksTagsPopUp != null)
					{
						tasksTagsPopUp.popupWindow.close();
					}
				}
			}
		});

		BX.bind(BX("task-list-filter"), "click", BX.delegate(this.onFilterSwitch, this));
	},

	show : function(bindElement)
	{
		if (!this.popup)
			this.init(bindElement);

		if (BX.hasClass(bindElement, "task-title-button-filter-pressed"))
		{
			this.popup.close();
			BX.removeClass(bindElement, "task-title-button-filter-pressed");
			this.adjustListHeight();
		}
		else
		{
			this.popup.show();
			BX.addClass(bindElement, "task-title-button-filter-pressed");
			this.adjustListHeight();

			BX.bind(BX('task-list-filter'), 'click', BX.proxy(this.onFilterClick, this));

			window.setTimeout(
				(function(bindElement, objSelf){
					return function()
					{
						objSelf.bindElement = bindElement;
						BX.bind(document, 'click', BX.proxy(objSelf.onDocumentClick, objSelf));
					};
				})(bindElement, this),
				100
			);
		}
	},


	onFilterClick : function(e)
	{
		if (!e)
			e = event;

		if (e.stopPropagation)
			e.stopPropagation();
		else
			e.cancelBubble = true;
	},


	onDocumentClick : function()
	{
		this.popup.close();
		BX.removeClass(this.bindElement, "task-title-button-filter-pressed");
		BX.removeClass(this.bindElement, "webform-small-button-active");
		this.adjustListHeight();
		BX.unbind(document, 'click', BX.proxy(this.onDocumentClick, this));
		BX.unbind(BX('task-list-filter'), 'click', BX.proxy(this.onFilterClick, this));
	},

	adjustListHeight : function()
	{
		var ganttContainer = BX("task-list-container", true);

		if ( ! ganttContainer )
			return;

		var ganttHeight = ganttContainer.offsetHeight - (parseInt(ganttContainer.style.paddingBottom) || 0);
		var filterHeight = this.popup ? this.popup.popupContainer.offsetHeight : 0;

		if (filterHeight > ganttHeight)
			BX("task-list-container", true).style.paddingBottom = filterHeight - ganttHeight + "px";
		else
			BX("task-list-container", true).style.paddingBottom = "0px";

	},

	onFilterSwitch : function(event)
	{
		event = event || window.event;
		var target = event.target || event.srcElement;
		if (BX.hasClass(target, "task-filter-mode-selected"))
			this.adjustListHeight();
	}
};

var tasksListTemplateDefaultInit = function() {

	if (BX("task-title-button-search-input", true))
	{
		BX.bind(BX("task-title-button-search-input", true), "click", function(e) {
			if(!e) e = window.event;

			BX.addClass(this.parentNode.parentNode.parentNode, "task-title-button-search-full");
		});
		BX.bind(BX("task-title-button-search-input", true), "blur", function(e) {
			if(!e) e = window.event;

			if (this.value == "")
			{
				BX.removeClass(this.parentNode.parentNode.parentNode, "task-title-button-search-full");
			}
		});
		BX.bind(BX("task-title-button-search-input", true), "keyup", function(e) {
			if(!e) e = window.event;

			if (e.keyCode == 13) {
				BX.submit(document.forms["task-filter-title-form"]);
			}
		});
		BX.bind(BX("task-title-button-search-icon"), "click", function(e) {
			if(!e) e = window.event;

			BX.submit(document.forms["task-filter-title-form"]);
		})
	}
};

function showAjaxErrorPopup()
{
	var popup = new BX.PopupWindow("gantt-ajax-error-popup", null, {
		lightShadow: true,
		overlay: true,
		buttons: [new BX.PopupWindowButton({
			text: BX.message("JS_CORE_WINDOW_CLOSE"),
			className: "",
			events: {
				click: function() {
					if (
						BX.SidePanel
						&& BX.SidePanel.Instance.getTopSlider() === BX.SidePanel.Instance.getSliderByWindow(window)
					)
					{
						window.location.reload();
					}
					else
					{
						BX.reload();
					}
					this.popupWindow.close();
				}
			}
		})]
	});

	var errors = [];
	for (var i = 0; i < arguments.length; i++)
	{
		var argument = arguments[i];
		if (BX.type.isArray(argument))
		{
			errors = BX.util.array_merge(errors, argument);
		}
		else if (BX.type.isString(argument))
		{
			errors.push(argument);
		}
	}

	var popupContent = "";
	for (i = 0; i < errors.length; i++)
	{
		popupContent += (typeof(errors[i].MESSAGE) !== "undefined" ? errors[i].MESSAGE : errors[i]) + "<br>";
	}

	popup.setContent("<div class='task-new-item-error-popup'>" + popupContent + "</div>");
	popup.show();
}

function isLeftClick(event)
{
	if (!event.which && event.button !== undefined)
	{
		if (event.button & 1)
			event.which = 1;
		else if (event.button & 4)
			event.which = 2;
		else if (event.button & 2)
			event.which = 3;
		else
			event.which = 0;
	}

	return event.which == 1 || (event.which == 0 && BX.browser.IsIE());
}

function AddQuickPopupTask(e, newTaskData)
{
	newTaskData = newTaskData || {};

	if (!e)
		e = window.event;

	if ( ! isLeftClick(e) )
		return;

	BX.PreventDefault(e);

	if(typeof taskIFramePopup != 'undefined')
	{
		taskIFramePopup.edit(0, newTaskData);
	}
}

function SetCSSStatus(element, status, prefix)
{
	BX.removeClass(element, prefix + "overdue");
	BX.removeClass(element, prefix + "new");
	BX.removeClass(element, prefix + "accepted");
	BX.removeClass(element, prefix + "in-progress");
	BX.removeClass(element, prefix + "delayed");
	BX.removeClass(element, prefix + "waiting");
	BX.removeClass(element, prefix + "completed");

	var cells = element.getElementsByTagName("TD");
	var title = BX.findChild(cells[0], {tagName : "a"}, true);
	BX.style(title, "text-decoration", "none");

	BX.addClass(element, prefix + status);
}

function SetServerStatus(taskId, status, params)
{
	var columnsIds = null;
	var data = {
		mode : status,
		sessid : BX.message("bitrix_sessid"),
		path_to_task: BX.message("TASKS_PATH_TO_TASK"),
		id : taskId
	};

	if ((typeof tasksListNS !== 'undefined') && tasksListNS.getColumnsOrder)
	{
		columnsIds = tasksListNS.getColumnsOrder();
		data['columnsOrder'] = columnsIds;
	}

	if (params)
	{
		for(var i in params)
		{
			data[i] = params[i];
		}
	}

	BX.ajax({
		method      : 'POST',
		dataType    : 'json',
		url         :  tasksListAjaxUrl + '&_CODE=' + status + '&viewType=VIEW_MODE_GANTT',
		data        :  data,
		processData :  true,
		onsuccess   : (function(taskId){
			return function(reply)
			{
				if (
					reply.status === 'failure'
					&& reply.message
				)
				{
					BX.UI.Notification.Center.notify({content: reply.message});
					return;
				}

				if (reply.status != 'success')
					return;

				var taskInfo = BX.parseJSON(reply.tasksRenderJSON);

				// replace menu items here
				quickInfoData[taskId].menuItems = taskInfo.menuItems;
				quickInfoData[taskId].realStatus = taskInfo.realStatus;
				tasksMenuPopup[taskId] = taskInfo.menuItems;

				if (typeof(ganttChart) != "undefined")
				{
					ganttChart.getTaskById(taskId).setMenuItems(__FilterMenuByStatus(quickInfoData[taskId]));

					var ganttTask = ganttChart.getTaskById(taskId);
					if (ganttTask)
					{
						ganttChart.updateTask(ganttTask.id, taskInfo);
					}
				}

				if (BX.TasksTimerManager)
					BX.TasksTimerManager.reLoadInitTimerDataFromServer();

				if (window.BX.TasksIFrameInst)
					window.BX.TasksIFrameInst.onTaskChanged(taskInfo, null, null, null, taskInfo.html);
			};
		})(taskId),
	});

	__InvalidateMenus([taskId, "c" + taskId]);
}

/*=====================Menu Popup===============================*/

function ShowMenuPopup(taskId, bindElement)
{
	if (tasksMenuPopup[taskId])
		BX.PopupMenu.show(taskId, bindElement, __FilterMenuByStatus(quickInfoData[taskId]), {events : {onPopupClose: __onMenuPopupClose}});

	BX.addClass(bindElement, "task-menu-button-selected");

	return false;
}

function __onMenuPopupClose()
{
	BX.removeClass(this.bindElement, "task-menu-button-selected");
}

function ShowMenuPopupContext(taskId, event)
{
	var target = event.target || event.srcElement;
	if (target && target.tagName.toUpperCase() == "A")
		return true;
	if (tasksMenuPopup[taskId])
	{
		BX.PopupMenu.show("c" + taskId, event, __FilterMenuByStatus(quickInfoData[taskId]), {});
		BX.PopupMenu.getCurrentMenu().popupWindow.setBindElement(event);
		BX.PopupMenu.getCurrentMenu().popupWindow.adjustPosition();
	}

	BX.PreventDefault(event);
}

function __InvalidateMenus(menus)
{
	for(var i = 0, count = menus.length; i < count; i++)
	{
		BX.PopupMenu.destroy(menus[i]);
	}
}

function __FilterMenuByStatus(task)
{
	var filteredMenu = [];
	for(var i = 0, count = task.menuItems.length; i < count; i++)
	{
		if (typeof(task.menuItems[i].status) == "undefined" || BX.util.in_array(task.realStatus, task.menuItems[i].status))
		{
			filteredMenu.push(task.menuItems[i]);
		}
	}

	return filteredMenu;
}

/*=====================Quick Info Popup==========================*/
var titleBuffer = "";
function ShowTaskQuickInfo(taskId, event)
{
	if (quickInfoData[taskId])
	{
		titleBuffer = BX("task-" + taskId).title;
		BX("task-" + taskId).title = "";
		BX.fixEventPageXY(event);
		var bindElement = { left : event.pageX, top : event.pageY, bottom : event.pageY};
		BX.TaskQuickInfo.show(
			bindElement,
			quickInfoData[taskId],
			{
				offsetTop :  10,
				dateFormat : "DD.MM.YYYY",
				onDetailClick : TaskQuickInfoDetail,
				userProfileUrl : "/company/personal/user/#user_id#/"
			}
		);
	}
}

function HideTaskQuickInfo(taskId, event)
{
	BX("task-" + taskId).title = titleBuffer;
	BX.TaskQuickInfo.hide();
}

function TaskQuickInfoDetail(event, popupWindow, quickInfo)
{
	popupWindow.close();
}

/*=====================Templates Popup==========================*/

function ShowTemplatesPopup(bindElement)
{
	var popup = BX("task-popup-templates-popup-content", true);

	BX.PopupWindowManager.create("task-templates-popup" , bindElement, {
		autoHide : true,
		offsetTop : 1,
		//lightShadow : true,
		events : {
			onPopupClose : __onTemplatesPopupClose
		},
		content : popup
	}).show();

	BX.addClass(bindElement, "webform-button-active");

	return false;
}

function __onTemplatesPopupClose()
{
	BX.removeClass(this.bindElement, "webform-button-active");
}

function SwitchTaskFilter(link)
{
	if (BX.hasClass(link, "task-filter-mode-selected"))
		return false;

	BX.toggleClass(link.parentNode.parentNode.parentNode, "task-filter-advanced-mode");

	var links = link.parentNode.getElementsByTagName("a");
	for (var i = 0; i < links.length; i++)
		BX.toggleClass(links[i], "task-filter-mode-selected");

	return false;
}

function Add2Timeman(object, taskId)
{
	var timeman = false;
	var item = BX.findChild(object.popupWindow.contentContainer, {tagName: "a", className: "menu-popup-item-add-to-tm"}, true);
	BX.remove(BX.findPreviousSibling(item));
	BX.remove(item);
	object.popupWindow.close();

	if(BX.addTaskToPlanner)
		BX.addTaskToPlanner(taskId);
	else if(window.top.BX.addTaskToPlanner)
		window.top.BX.addTaskToPlanner(taskId);
}

function ShowQuickTask(table, params)
{
	if (params)
	{
		if (params.parent)
		{
			var parentRow = BX("task-" + params.parent);
			if (parentRow)
				table = BX.findParent(parentRow, {tag: "table"});
		}
		else if (params.group)
		{
			var groupRow = BX("task-project-" + params.group.id);
			if (groupRow)
				table = BX.findParent(groupRow, {tag: "table"});
		}
	}

	if (!table)
		table = BX.findParent(BX("task-new-item-row", true), {tag: "table"});

	if (BX("task-detail-subtasks-block") && BX("task-detail-subtasks-block").style.display == "none")
		BX("task-detail-subtasks-block").style.display = "";

	var tableBody = table.tBodies[0];

	if (!params)
	{
		params = {};
	}

	if (typeof newTaskGroup === 'undefined')
	{
		newTaskDepth = newTaskParent = newTaskGroup = 0;
		newTaskGroupObj = null;
	}

	var beforeRow = tableBody.rows[0];

	if (params.group || params.parent)
	{
		if (params.group)
		{
			var wait = function(delay, timeout, groupData)
			{
				if (typeof window['groupsPopup'] === 'undefined')
				{
					if (timeout > 0)
					{
						window.setTimeout(
							function() {
								wait(delay, timeout - delay, groupData);
							},
							delay
						);
					}
				}
				else
				{
					groupsPopup.select(groupData);
				}
			}

			wait(100, 15000, params.group);	// every 100ms, not more than 15000ms

			if (params.group.id > 0)
			{
				newTaskGroup = params.group.id;

				if (BX("task-project-" + newTaskGroup))
				{
					beforeRow = BX.findNextSibling(BX("task-project-" + newTaskGroup), {tag: "tr"});
				}
			}
		}


		if (params.parent)
		{
			newTaskParent = parseInt(params.parent) > 0 ? parseInt(params.parent) : 0;
			if (BX("task-" + newTaskParent, true))
			{
				newTaskDepth = Depth(BX("task-" + newTaskParent, true)) + 1;
				beforeRow = BX.findNextSibling(BX("task-" + newTaskParent, true), {tag: "tr"});
			}
		}
	}

	if ( ! params.parent )
	{
		newTaskDepth  = 0;
		newTaskParent = 0;
	}

	if (beforeRow != BX("task-new-item-row", true))
	{
		if (beforeRow)
		{
			tableBody.insertBefore(BX("task-new-item-row", true), beforeRow);
		}
		else
		{
			tableBody.appendChild(BX("task-new-item-row", true));
		}
	}

	BX.removeClass(BX("task-new-item-row", true), "task-list-item-hidden");
	BX("task-new-item-name").focus();

	var groupId = 0;
	if (params.group && params.group.id)
		groupId = params.group.id;

	var userId = BX.message('USER_ID');
	if (params.user && params.user.id)
		userId = params.user.id;

	if (BX('task-new-item-responsible'))
	{
		// Load just once
		if ( ! window.groupsPopup )
		{
			BX('task-new-item-responsible').disabled = true;
			BX('task-new-item-link-group').style.display = 'none';

			BX.Tasks.lwPopup.__initSelectors([
				{
					requestedObject  : 'intranet.user.selector.new',
					selectedUsersIds :  userId,
					anchorId         :  'task-new-item-responsible',
					bindClickTo      :  'task-new-item-responsible',
					userInputId      :  'task-new-item-responsible',
					multiple         : 'N',
					GROUP_ID_FOR_SITE : groupId,
					callbackOnSelect : function (arUser)
					{
						BX("task-new-item-responsible-hidden").value = arUser.id
					},
					onLoadedViaAjax : function()
					{
						BX('task-new-item-responsible').disabled = false;
					}
				},
				{
					requestedObject  : 'socialnetwork.group.selector',
					bindElement      : 'task-new-item-link-group',
					callbackOnSelect : function (arGroups, params)
					{
						onGroupSelect(arGroups);
					},
					onLoadedViaAjax : function(jsObjectName)
					{
						var wait = function(delay, timeout)
						{
							if (typeof window[jsObjectName] === 'undefined')
							{
								if (timeout > 0)
									window.setTimeout(function() { wait(delay, timeout - delay); }, delay);
							}
							else
							{
								window.groupsPopup = window[jsObjectName];
								BX('task-new-item-link-group').style.display = '';
							}
						}

						wait(100, 15000);	// every 100ms, not more than 15000ms
					}
				}
			]);
		}
	}
}

function ToggleSubtasks(currentRow, depthLevel, taskID)
{
	// delay function run, if tasksListNS not ready
	if (
		( ! tasksListNS )
		|| ( ! tasksListNS.isReady )
	)
	{
		window.setTimeout(
			(function(currentRow, depthLevel, taskID){
				return function(){
					ToggleSubtasks(currentRow, depthLevel, taskID);
				};
			})(currentRow, depthLevel, taskID),
			500
		);

		return;
	}

	if (loadedTasks[taskID])
	{
		var row = BX.findNextSibling(currentRow, {tagName : "tr"});
		while(row && Depth(row) > depthLevel)
		{
			if (BX.hasClass(currentRow, "task-list-item-opened")) //collapse children
			{
				BX.addClass(row, "task-list-item-hidden");
				BX.removeClass(row, "task-list-item-opened");
			}
			else if (BX.hasClass(row, "task-depth-" + (depthLevel + 1))) // expand children
			{
				BX.removeClass(row, "task-list-item-hidden");
			}
			row =  BX.findNextSibling(row, {tagName : "tr"});
		}

		BX.toggleClass(currentRow, "task-list-item-opened");
	}
	else
	{
		var data = {
			sessid : BX.message("bitrix_sessid"),
			id : taskID,
			depth : depthLevel,
			filter: tasksListNS.arFilter,
			order: arOrder,
			columnsOrder : tasksListNS.getColumnsOrder(),
			path_to_user: BX.message("TASKS_PATH_TO_USER_PROFILE"),
			path_to_task: BX.message("TASKS_PATH_TO_TASK"),
			mode : "load"
		};
		loadedTasks[taskID] = true;

		BX.ajax({
			"method": "POST",
			"dataType": "html",
			"url": tasksListAjaxUrl,
			"data":  data,
			"processData" : false,
			"onsuccess": (function() {
				var func = function(data) {
					//converting html to dom nodes
					var tempDiv = document.createElement("div");
					tempDiv.innerHTML = "<table>" + data + "</table>";

					var arRows = tempDiv.firstChild.getElementsByTagName("TR");
					var arScripts = tempDiv.firstChild.getElementsByTagName("SCRIPT");
					for(var i = arRows.length - 1; i >= 0; i--)
					{
						if (!BX(arRows[i].id))
						{
							currentRow.parentNode.insertBefore(arRows[i], currentRow.nextSibling);

							if (
								(!BX.browser.IsIE())
								|| (!!document.documentMode && document.documentMode >= 10)
							)
							{
								var script = BX.create(
									"script", {
										props : {type : "text/javascript"},
										html: arScripts[i].innerHTML
									}
								)
							}
							else
							{
								var script = arScripts[i];
							}

							currentRow.parentNode.insertBefore(script, currentRow.nextSibling);
						}
					}

					ToggleSubtasks(currentRow, depthLevel, taskID);
				}

				return func;
			})()
		});
	}
}

function Depth(row)
{
	var regexp = /task-depth-([0-9]+)/;
	var matches = regexp.exec(row.className);
	if (matches)
	{
		return parseInt(matches[1]);
	}
	else
	{
		return 0;
	}
}

/*=====================Grade Popup==============================*/
function ShowGradePopup(taskId, bindElement, currentValues)
{
	BX.TaskGradePopup.show(
		taskId,
		bindElement,
		currentValues,
		{
			events : {
				onPopupClose : __onGradePopupClose,
				onPopupChange : __onGradePopupChange
			}
		}
	);

	BX.addClass(bindElement, "task-grade-and-report-selected");

	return false;
}


function __onGradePopupClose()
{
	BX.removeClass(this.bindElement, "task-grade-and-report-selected");
}

function __onGradePopupChange()
{
	this.bindElement.className = "task-grade-and-report" + (this.listValue != "NULL" ? " task-grade-" + this.listItem.className : "") + (this.report ? " task-in-report" : "");
	this.bindElement.title = BX.message("TASKS_MARK") + ": " + this.listItem.name;
	var data = {
		mode : "mark",
    	sessid : BX.message("bitrix_sessid"),
		id : this.id,
		mark : this.listValue,
		report : this.report
	};
	BX.ajax.post(tasksListAjaxUrl, data);

}

/*=====================Priority Popup============================*/
function ShowPriorityPopup(taskId, bindElement, currentPriority)
{
	BX.TaskPriorityPopup.show(
		taskId,
		bindElement,
		currentPriority,
		{
			events : {
				onPopupClose : __onPriorityPopupClose,
				onPopupChange : __onPriorityChange
			}
		}

	);

	BX.addClass(bindElement, "task-priority-box-selected");

	return false;
}

function __onPriorityPopupClose()
{
	BX.removeClass(this.bindElement, "task-priority-box-selected");
}
function __onPriorityChange()
{
	BX.removeClass(this.bindElement, "task-priority-box-selected");
	this.bindElement.title = BX.message("TASKS_PRIORITY_V2") + ": " + this.listItem.name;
	this.bindElement.childNodes[0].className = "task-priority-icon task-priority-" + this.listValue;
	var data = {
		mode : "priority",
    	sessid : BX.message("bitrix_sessid"),
		id : this.id,
    	path_to_user: BX.message("TASKS_PATH_TO_USER_PROFILE"),
    	path_to_task: BX.message("TASKS_PATH_TO_TASK"),
    	priority : this.listValue
	};
	BX.ajax.post(tasksListAjaxUrl, data);
}

function tasksFormatDate(date)
{
	return BX.date.format(BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME')), date);
}
