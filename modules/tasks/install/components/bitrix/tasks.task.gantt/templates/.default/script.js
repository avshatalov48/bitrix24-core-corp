BX.namespace('BX.Tasks.Grid');

BX.Tasks.GanttActions = {
	defaultPresetId: ''
};

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

function onPopupTaskAdded(task) {
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

BX.addCustomEvent("SidePanel.Slider:onCloseByEsc", function(event) {
	var reg = /tasks\/task\/edit/;
	var str = event.getSlider().getUrl();
	if (reg.test(str) && !confirm(BX.message('TASKS_CLOSE_PAGE_CONFIRM')))
	{
		event.denyAction();
	}
});

BX.addCustomEvent('BX.Main.Filter:apply', function(id, data, ctx, promise, params) {
	var fields = ctx.getFields();

	if(fields.ROLEID == undefined || fields.ROLEID == 0)
	{
		// window.location.href = '?F_CANCEL=Y&F_SECTION=ADVANCED&clear_filter=Y&apply_filter=Y';
		window.location.href = window.location.href;
	}
	else
	{
		window.location.reload();
	}
});

BX.addCustomEvent('Tasks.TopMenu:onItem', function(roleId, url) {
	var filterManager = BX.Main.filterManager.getById(ganttFilterId);
	if (!filterManager)
	{
		alert('BX.Main.filterManager not initialised');
		return;
	}

	var fields = {
		preset_id: BX.Tasks.GanttActions.defaultPresetId,
		additional: {ROLEID: (roleId === 'view_all' ? 0 : roleId)}
	};
	var filterApi = filterManager.getApi();
	filterApi.setFilter(fields);

	window.history.pushState(null, null, url);
});

BX.addCustomEvent('Tasks.Toolbar:onItem', function(counterId) {
	var filterManager = BX.Main.filterManager.getById(ganttFilterId);
	if (!filterManager)
	{
		alert('BX.Main.filterManager not initialised');
		return;
	}
	var filterApi = filterManager.getApi();
	var filterFields = filterManager.getFilterFieldsValues();

	if (Number(counterId) === 12582912 || Number(counterId) === 6291456)
	{
		var fields = {
			ROLEID: (filterFields.hasOwnProperty('ROLEID') ? filterFields.ROLEID : 0),
			PROBLEM: counterId
		};
		filterApi.setFields(fields);
		filterApi.apply();
	}
	else
	{
		fields = {
			preset_id: BX.Tasks.GanttActions.defaultPresetId,
			additional: {
				PROBLEM: counterId,
			}
		};
		if (filterFields.hasOwnProperty('ROLEID'))
		{
			fields.additional.ROLEID = filterFields.ROLEID;
		}
		filterApi.setFilter(fields);
	}
});