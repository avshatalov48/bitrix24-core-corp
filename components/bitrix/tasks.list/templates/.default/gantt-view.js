var tasksListNS = {
	approveTask : function(taskId)
	{
		ganttChart.updateTask(taskId, {status: "completed", dateCompleted: new Date()});
		SetServerStatus(taskId, "approve", { bGannt: true });
	},
	disapproveTask : function(taskId)
	{
		ganttChart.updateTask(taskId, {status: "new", dateCompleted: null});
		SetServerStatus(taskId, "disapprove", { bGannt: true });
	}
};

function SetServerCloseStatus(taskId, status, params)
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

				ganttChart.updateTask(taskId, {status: "completed", dateCompleted: new Date()});

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

function CloseTask(taskId, analyticsSection = 'tasks')
{
	SetServerCloseStatus(taskId, "close", { bGannt: true });

	const analyticsData = {
		tool: 'tasks',
		category: 'task_operations',
		event: 'task_complete',
		type: 'task',
		c_section: analyticsSection,
		c_element: 'context_menu',
		c_sub_section: 'gantt',
	};

	if (BX.UI.Analytics)
	{
		BX.UI.Analytics.sendData(analyticsData);
	}
	else
	{
		BX.Runtime.loadExtension('ui.analytics').then(() => {
			BX.UI.Analytics.sendData(analyticsData);
		});
	}
}

function StartTask(taskId)
{
	ganttChart.updateTask(taskId, {status: "in-progress", dateCompleted: null});
	SetServerStatus(taskId, "start", { bGannt: true });
}

function AcceptTask(taskId)
{
	ganttChart.updateTask(taskId, {status: "accepted", dateCompleted: null});
	SetServerStatus(taskId, "accept", { bGannt: true });
}

function PauseTask(taskId)
{
	ganttChart.updateTask(taskId, {status: "accepted", dateCompleted: null});
	SetServerStatus(taskId, "pause", { bGannt: true });
}

function RenewTask(taskId)
{
	ganttChart.updateTask(taskId, {status: "new", dateCompleted: null});
	SetServerStatus(taskId, "renew", { bGannt: true });
}

function DeferTask(taskId)
{
	ganttChart.updateTask(taskId, {status: "delayed"});
	SetServerStatus(taskId, "defer", { bGannt: true });
}

function AddToFavorite(taskId, parameters)
{
	var data = {
		mode : "favorite",
		add : 1,
		sessid : BX.message("bitrix_sessid"),
		id : taskId,
		bGannt: true
	};

	BX.ajax({
		"method": "POST",
		"dataType": "html",
		"url": tasksListAjaxUrl,
		"data":  data,
		"processData" : false,
		"onsuccess": (function(taskId){
			return function(datum) {
				//TASKS_table_view_onDeleteClick_onSuccess(taskId, datum, parameters);
			};
		})(taskId)
	});
}

function DeleteFavorite(taskId, parameters)
{
	var data = {
		mode : "favorite",
		sessid : BX.message("bitrix_sessid"),
		id : taskId,
		bGannt: true
	};

	BX.ajax({
		"method": "POST",
		"dataType": "html",
		"url": tasksListAjaxUrl,
		"data":  data,
		"processData" : false,
		"onsuccess": (function(taskId){

			if(parameters.rowDelete)
			{
				return function(datum) {
					TASKS_table_view_onDeleteClick_onSuccess(taskId, datum, parameters);
				};
			}
		})(taskId)
	});
}

function DeleteTask(taskId)
{
	var data = {
		mode : "delete",
		sessid : BX.message("bitrix_sessid"),
		id : taskId,
		bGannt: true
	};

	BX.ajax({
		"method": "POST",
		"dataType": "html",
		"url": tasksListAjaxUrl,
		"data":  data,
		"processData" : false,
		"onsuccess": (function(taskId){
			return function(datum) {
				TASKS_table_view_onDeleteClick_onSuccess(taskId, datum);

			};
		})(taskId)
	});
}


function TASKS_table_view_onDeleteClick_onSuccess(taskId, data)
{
	data = data.toString().trim();

	if (data && data.length > 0)
	{
		// there is an error occured
	}
	else
	{
		ganttChart.removeTask(taskId);
		BX.onCustomEvent('onTaskListTaskDelete', [taskId]);

		BX.UI.Notification.Center.notify({
			content: BX.message('TASKS_DELETE_SUCCESS')
		});
	}
}


function onPopupTaskChanged(task) {
	__RenewMenuItems(task);
	__InvalidateMenus([task.id, "c" + task.id]);

	if (task.parentTaskId)
	{
		var parentTask = ganttChart.getTaskById(task.parentTaskId);
		if (parentTask)
		{
			if (parentTask.hasChildren)
			{
				parentTask.expand();
				ganttChart.updateTask(task.id, task);
			}
			else
			{
				ganttChart.updateTask(task.id, task);
				parentTask.expand();
			}
		}
		else
		{
			ganttChart.updateTask(task.id, task);
		}

	}
	else if(task.projectId && !ganttChart.getProjectById(task.projectId))
	{
		var project = ganttChart.addProjectFromJSON({
			id: task.projectId,
			name: task.projectName,
			opened: true,
			canCreateTasks: task.projectCanCreateTasks,
			canEditTasks: task.projectCanEditTasks
		});
		ganttChart.updateTask(task.id, task);
	}
	else
	{
		ganttChart.updateTask(task.id, task);
	}
}

function onPopupTaskAdded(task)
{
	BX.onCustomEvent("onTaskListTaskAdd", [task]);

	__RenewMenuItems(task);

	if(task.projectId && !ganttChart.getProjectById(task.projectId))
	{
		ganttChart.addProjectFromJSON({
			id: task.projectId,
			name: task.projectName,
			opened: true,
			canCreateTasks: task.projectCanCreateTasks,
			canEditTasks: task.projectCanEditTasks
		});
	}

	ganttChart.addTaskFromJSON(task);

	if (task.parentTaskId)
	{
		var parentTask = ganttChart.getTaskById(task.parentTaskId);
		if (parentTask)
		{
			parentTask.expand();
		}
	}
}

function onPopupTaskDeleted(taskId) {
	ganttChart.removeTask(taskId);
}

var lastScroll;
function onBeforeShow() {
	if (BX.browser.IsOpera())
	{
		lastScroll = ganttChart.layout.timeline.scrollLeft;
	}
}
function onAfterShow() {
	if (typeof(lastScroll) != "undefined" && BX.browser.IsOpera())
	{
		ganttChart.layout.timeline.scrollLeft = lastScroll;
	}
}
function onBeforeHide() {
	if (BX.browser.IsOpera())
	{
		lastScroll = ganttChart.layout.timeline.scrollLeft;
	}
}
function onAfterHide() {
	if (typeof(lastScroll) != "undefined" && BX.browser.IsOpera())
	{
		ganttChart.layout.timeline.scrollLeft = lastScroll;
	}
}

function __RenewMenuItems(task)
{
	if(!task)
	{
		return;
	}

	quickInfoData[task.id] = BX.clone(task, true);
	task.menuItems = __FilterMenuByStatus(task);
}