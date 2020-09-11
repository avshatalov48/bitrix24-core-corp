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


function CloseTask(taskId)
{
	ganttChart.updateTask(taskId, {status: "completed", dateCompleted: new Date()});
	SetServerStatus(taskId, "close", { bGannt: true });
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