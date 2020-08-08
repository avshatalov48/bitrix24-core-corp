;(function() {

"use strict";

BX.namespace("BX.Tasks");

BX.Tasks.QuickForm = function(formContainerId, parameters)
{
	var container = BX(formContainerId);
	if (!container)
	{
		throw "BX.Tasks.QuickForm: wrong container id";
	}

	this.parameters = parameters || {};
	this.layout = {
		container: container,
		button: BX(this.parameters.button),
		form: BX("task-new-item-form"),
		responsible: BX("task-new-item-responsible"),
		responsibleId: BX("task-new-item-responsible-id"),
		deadline: BX("task-new-item-deadline"),
		title: BX("task-new-item-title"),
		projectLink: BX("task-new-item-project-link"),
		projectClearing: BX("task-new-item-project-clearing"),
		projectId: BX("task-new-item-project-id"),
		descriptionBlock: BX("task-new-item-description-block"),
		descriptionLink: BX("task-new-item-description-link"),
		description: BX("task-new-item-description"),
		saveButton: BX("task-new-item-save"),
		cancelButton: BX("task-new-item-cancel")
	};

	this.gridId = BX.type.isNotEmptyString(this.parameters.gridId) ? this.parameters.gridId : null;
	this.messages = this.parameters.messages || {};
	this.canManageTask = this.parameters.canManageTask !== false;
	this.canAddMailUsers = this.parameters.canAddMailUsers === true;

	if (this.canManageTask)
	{
		BX.bind(this.layout.title, "keypress", BX.proxy(this.fireEnterKey, this));

		if (this.layout.button)
		{
			BX.bind(this.layout.button, "click", BX.proxy(this.handleButtonClick, this));
		}

		BX.bind(this.layout.cancelButton, "click", BX.proxy(this.hide, this));
		BX.bind(this.layout.saveButton, "click", BX.proxy(this.submit, this));

		BX.bind(this.layout.descriptionLink, "click", BX.proxy(this.toggleDescription, this));

		BX.bind(this.layout.deadline, "click", BX.proxy(this.calendar, this));
		BX.bind(this.layout.deadline, "focus", BX.proxy(this.calendar, this));
	}
	else
	{
		this.disable();
	}

	this.query = new BX.Tasks.Util.Query({url: "/bitrix/components/bitrix/tasks.task.list/ajax.php"});
	this.query.bindEvent("executed", BX.proxy(this.onQueryExecuted, this));

	this.operation = "taskQuickAdd";
	this.errorPopup = null;

	this.notification = new BX.Tasks.QuickForm.Notification(this);
	this.projectSelector = new BX.Tasks.QuickForm.ProjectSelector("task-new-item-project-selector", this);
	this.userSelector = new BX.Tasks.QuickForm.UserSelector("task-new-item-user-selector", this);
};

BX.Tasks.QuickForm.prototype.submit = function()
{
	var title = this.layout.title.value;
	if (BX.util.trim(title).length === 0)
	{
		return false;
	}

	var data = {
		title: title,
		deadline: this.layout.deadline.value,
		description: this.layout.description.value,
		project : this.layout.projectId.value,
		pathToTask: this.parameters.pathToTask,
		siteId: BX.message("SITE_ID"),
		nameTemplate: this.parameters.nameTemplate,
		getListParams: this.parameters.getListParams,
		ganttMode: this.parameters.ganttMode
	};

	var responsibleId = this.userSelector.getUserId();
	var email = this.userSelector.getUserEmail();
	if (responsibleId > 0)
	{
		data.responsibleId = responsibleId;
	}
	else if (this.canAddMailUsers && email.length)
	{
		data.userEmail = email;
		data.userName = this.userSelector.getUserName();
		data.userLastName = this.userSelector.getUserLastName();
	}

	this.disable();
	this.fadeGrid();

	this.query.deleteAll();
	this.query.add(
		"ui.listcontrols.add",
		{
			data: data,
			parameters: { }
		},
		{
			code: this.operation
		}
	);


	this.query.execute();
};

BX.Tasks.QuickForm.prototype.onQueryExecuted = function(result)
{
	if (!result.success)
	{
		return this.showError(result.clientProcessErrors, result.serverProcessErrors);
	}
	else if (!result.data[this.operation])
	{
		return this.showError("Could not process this operation.");
	}
	else if (!result.data[this.operation].SUCCESS)
	{
		return this.showError(result.data[this.operation].ERRORS);
	}

	var data = result.data[this.operation].RESULT;
	var taskId = data["taskId"];
	var found = data.position && data.position.found === true;
	if (found)
	{
		var grid = this.getGrid();
		if (grid)
		{
			BX.onCustomEvent(window, "onTasksQuickFormExecuted", [data]);
			return grid.reloadTable("GET", {}, this.applyChanges.bind(this, data, found));
		}
		else if (data.task)
		{
			var task = null;
			try
			{
				eval("result = " + data.task);
				task = result;
			}
			catch (e) {}

			this.insertIntoGantt(task, data.position);
		}
	}

	BX.onCustomEvent(window, "onTasksQuickFormExecuted", [data]);
	this.applyChanges(data, found);
};

BX.Tasks.QuickForm.prototype.applyChanges = function(data, found)
{
	var title = data["taskRaw"] && data["taskRaw"]["TITLE"] ? data["taskRaw"]["TITLE"] : "";
	var path = data["taskPath"];

	this.notification.show(data["taskId"], title, path, found);

	this.highlight(data["taskId"], false);

	this.unfadeGrid();
	this.enable();
	this.clear();
	this.focus();
};


BX.Tasks.QuickForm.prototype.insertIntoGantt = function(json, position)
{
	if (!window.ganttChart || !json)
	{
		return;
	}

	BX.onCustomEvent("onTaskListTaskAdd", [json]);

	if (
		json.projectId &&
		!ganttChart.getProjectById(json.projectId) &&
		this.parameters.currentGroupId === 0 &&
		this.parameters.groupByProject === true
	)
	{
		ganttChart.addProjectFromJSON({
			id: json.projectId,
			name: json.projectName,
			opened: true,
			canCreateTasks: json.projectCanCreateTasks,
			canEditTasks: json.projectCanEditTasks
		});
	}

	var task = ganttChart.addTaskFromJSON(json);

	if (task.parentTaskId)
	{
		var parentTask = ganttChart.getTaskById(task.parentTaskId);
		if (parentTask)
		{
			parentTask.expand();
		}
	}

	var nextTask = ganttChart.getTaskById(position.nextTaskId);
	var prevTask = ganttChart.getTaskById(position.prevTaskId);
	if (nextTask && nextTask.projectId === task.projectId)
	{
		ganttChart.moveTask(task.id, nextTask.id);
	}
	else if (prevTask && prevTask.projectId === task.projectId)
	{
		ganttChart.moveTask(task.id, prevTask.id, true);
	}
};

/**
 * @private
 */
BX.Tasks.QuickForm.prototype.getNextProject = function(table, projectId)
{
	var projects = BX.findChildrenByClassName(table, "task-list-project-item");
	if (!projectId && projects.length)
	{
		return projects[0];
	}

	var project = null;
	for (var i = 0; i < projects.length; i++)
	{
		if (parseInt(projects[i].getAttribute("data-project-id"), 10) > projectId)
		{
			project = projects[i];
			break;
		}
	}

	return project;
};

BX.Tasks.QuickForm.prototype.showError = function()
{
	if (this.errorPopup === null)
	{
		this.errorPopup = new BX.PopupWindow(this.operation, null, {
			lightShadow: true,
			buttons: [new BX.PopupWindowButton({
				text: BX.message("JS_CORE_WINDOW_CLOSE"),
				className: "",
				events: {
					click: BX.proxy(this.onPopupErrorClose, this)
				}
			})]
		});
	}

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

	this.errorPopup.setContent("<div class='task-new-item-error-popup'>" + popupContent + "</div>");
	this.errorPopup.show();
};

BX.Tasks.QuickForm.prototype.fadeGrid = function()
{
	if (this.getGrid())
	{
		this.getGrid().tableFade();
	}
};

BX.Tasks.QuickForm.prototype.unfadeGrid = function()
{
	if (this.getGrid())
	{
		this.getGrid().tableUnfade();
	}
};

BX.Tasks.QuickForm.prototype.onPopupErrorClose = function()
{
	this.errorPopup.close();
	this.unfadeGrid();
	this.enable();
	this.clear();
	this.focus();
};

BX.Tasks.QuickForm.prototype.calendar = function(event)
{
	BX.PreventDefault(event);
	var deadlineInput = this.layout.deadline;
	BX.calendar({
		node: deadlineInput,
		field: deadlineInput.name,
		bTime: true,
		bSetFocus: false,
		value: BX.CJSTask.ui.getInputDateTimeValue(deadlineInput),
		bHideTimebar: false
	});

	BX.SocNetLogDestination.closeDialog();
};

BX.Tasks.QuickForm.prototype.show = function()
{
	BX.addClass(this.layout.container, "task-top-panel-righttop-open");
	BX.addClass(this.layout.button, "tasks-quick-form-button-active");
	this.focus();
};

BX.Tasks.QuickForm.prototype.hide = function()
{
	BX.removeClass(this.layout.container, "task-top-panel-righttop-open");
	BX.removeClass(this.layout.button, "tasks-quick-form-button-active");
	this.notification.hide();
	this.focus();
};

BX.Tasks.QuickForm.prototype.handleButtonClick = function(event)
{
	if (BX.hasClass(this.layout.container, "task-top-panel-righttop-open"))
	{
		this.hide();
	}
	else
	{
		this.show();
	}
};

BX.Tasks.QuickForm.prototype.toggleDescription = function(event)
{
	BX.toggleClass(this.layout.descriptionBlock, "task-top-panel-leftmiddle-open");
	if (BX.hasClass(this.layout.descriptionBlock, "task-top-panel-leftmiddle-open"))
	{
		this.layout.description.focus();
	}
	else
	{
		this.focus();
	}

	BX.PreventDefault(event);
};

BX.Tasks.QuickForm.prototype.fireEnterKey = function(event)
{
	event = event || window.event;
	if (event.keyCode === 13)
	{
		this.submit();
		BX.PreventDefault(event);
	}
};

BX.Tasks.QuickForm.prototype.getGrid = function()
{
	if (
		this.gridId
		&& BX.Main
		&& BX.Main.gridManager
		&& BX.Tasks.GridInstance
		&& !BX.Tasks.GridInstance.checkCanMove()
	)
	{
		return BX.Main.gridManager.getInstanceById(this.gridId);
	}

	return null;
};

BX.Tasks.QuickForm.prototype.disable = function()
{
	this.layout.title.disabled = true;
	this.layout.deadline.disabled = true;
	this.layout.responsible.disabled = true;
	this.layout.description.disabled = true;

	BX.addClass(this.layout.saveButton, "ui-btn-clock");
};

BX.Tasks.QuickForm.prototype.enable = function()
{
	this.layout.title.disabled = false;
	this.layout.deadline.disabled = false;
	this.layout.responsible.disabled = false;
	this.layout.description.disabled = false;

	BX.removeClass(this.layout.saveButton, "ui-btn-clock");
};

BX.Tasks.QuickForm.prototype.clear = function()
{
	this.layout.title.value = "";
	this.layout.deadline.value = "";
	this.layout.description.value = "";
};

BX.Tasks.QuickForm.prototype.focus = function()
{
	this.layout.title.focus();
};

BX.Tasks.QuickForm.prototype.highlight = function(taskId, scroll)
{
	if (window.ganttChart)
	{
		var task = ganttChart.getTaskById(taskId);
		if (task)
		{
			task.highlight();
			if (scroll === true)
			{
				task.scrollIntoView(false);
			}
		}
	}
	else if (this.getGrid())
	{
		var row = this.getGrid().getRows().getById(taskId);
		if (row)
		{
			var node = row.getNode();
			BX.addClass(node, "task-list-item-highlighted");
			setTimeout(function() {
				BX.removeClass(node, "task-list-item-highlighted");
			}, 1000);

			if (scroll === true)
			{
				node.scrollIntoView(false);
			}
		}
	}
};

BX.Tasks.QuickForm.Notification = function(form)
{
	this.form = form;
	this.taskId = 0;
	this.layout = {
		block: BX("task-new-item-notification"),
		message: BX("task-new-item-message"),
		openLink: BX("task-new-item-open"),
		highlightLink: BX("task-new-item-highlight"),
		hideLink: BX("task-new-item-notification-hide")
	};

	BX.bind(this.layout.hideLink, "click", BX.proxy(this.hide, this));
	BX.bind(this.layout.highlightLink, "click", BX.proxy(this.highlight, this))
};

BX.Tasks.QuickForm.Notification.prototype.show = function(id, message, path, found)
{
	this.taskId = id;
	this.layout.message.innerHTML = message;
	this.layout.openLink.href = path;

	if (found)
	{
		this.layout.highlightLink.style.display = "inline";
	}
	else
	{
		this.layout.highlightLink.style.display = "none";
	}

	BX.addClass(this.layout.block, "task-top-notification-active");
};

BX.Tasks.QuickForm.Notification.prototype.hide = function()
{
	BX.removeClass(this.layout.block, "task-top-notification-active");
};

BX.Tasks.QuickForm.Notification.prototype.highlight = function()
{
	this.form.highlight(this.taskId, true);
};

BX.Tasks.QuickForm.ProjectSelector = function(id, form)
{
	this.id = id;
	this.form = form;
	this.projectName = this.form.layout.projectLink.innerHTML;
	this.projectId = parseInt(this.form.layout.projectId.value, 10);

	var settings = this.form.parameters.destination || {};

	BX.SocNetLogDestination.init({
		name : id,
		showSearchInput: true,
		sendAjaxSearch: (typeof settings["SONETGROUPS_LIMITED"] != 'undefined' && settings["SONETGROUPS_LIMITED"] == 'Y'),
		useClientDatabase: false,
		allowUserSearch: false,
		allowSonetGroupsAjaxSearch: (typeof settings["SONETGROUPS_LIMITED"] != 'undefined' && settings["SONETGROUPS_LIMITED"] == 'Y'),
		departmentSelectDisable: true,
		bindMainPopup : { node : this.form.layout.projectLink},
		bindSearchPopup : { node : this.form.layout.projectLink},
		callback : {
			select : BX.proxy(this.onSelect, this)
		},
		items : {
			users: {},
			groups: {},
			department: {},
			departmentRelation: {},
			projects: settings["PROJECTS"] || {},
			sonetgroups: settings["SONETGROUPS"] || {}
		},
		itemsLast: {
			users: {},
			groups: {},
			department: {},
			projects: settings["LAST"] && settings["LAST"]["PROJECTS"] ? settings["LAST"]["PROJECTS"] : {},
			sonetgroups: settings["LAST"] && settings["LAST"]["SONETGROUPS"] ? settings["LAST"]["SONETGROUPS"] : {}
		},
		itemsSelected : {}
	});

	BX.bind(this.form.layout.projectLink, "click", BX.proxy(this.openDialog, this));
	BX.bind(this.form.layout.projectClearing, "click", BX.proxy(this.clearProject, this));
};

BX.Tasks.QuickForm.ProjectSelector.prototype.openDialog = function(event)
{
	if (!BX.SocNetLogDestination.isOpenDialog())
	{
		BX.SocNetLogDestination.openDialog(this.id);
	}

	if (event)
	{
		BX.PreventDefault(event);
	}
};

BX.Tasks.QuickForm.ProjectSelector.prototype.onSelect = function(item)
{
	this.projectId = parseInt(item.entityId, 10);
	this.projectName = item.name;

	this.form.layout.projectId.value = this.projectId;
	this.form.layout.projectLink.innerHTML = this.projectName;

	BX.addClass(this.form.layout.projectClearing, "task-top-panel-tab-close-active");

	BX.SocNetLogDestination.deleteLastItem(this.id);
	BX.SocNetLogDestination.closeDialog();
};

BX.Tasks.QuickForm.ProjectSelector.prototype.clearProject = function()
{
	this.form.layout.projectLink.innerHTML = this.form.messages.taskInProject;
	this.form.layout.projectId.value = 0;

	this.projectId = 0;
	this.projectName = "";

	BX.removeClass(this.form.layout.projectClearing, "task-top-panel-tab-close-active");

	BX.SocNetLogDestination.closeDialog();
};

BX.Tasks.QuickForm.UserSelector = function(id, form)
{
	this.id = id;
	this.form = form;

	this.userId = this.form.layout.responsibleId.value;
	this.userNameFormatted = this.form.layout.responsible.value;

	this.userEmail = "";
	this.userName = "";
	this.userLastName = "";

	var settings = this.form.parameters.destination || {};

	BX.SocNetLogDestination.init({
		name : id,
		searchInput : this.form.layout.responsible,
		departmentSelectDisable: true,
		bindMainPopup : { node : this.form.layout.responsible },
		bindSearchPopup : { node : this.form.layout.responsible },
		allowAddUser: this.form.canAddMailUsers,
		callback : {
			select : BX.proxy(this.onSelect, this)
		},
		items : {
			users: settings["USERS"] || {},
			department: settings["DEPARTMENT"] || {},
			departmentRelation: settings["DEPARTMENT_RELATION"] || {}
		},
		itemsLast: {
			users: settings["LAST"] && settings["LAST"]["USERS"] ? settings["LAST"]["USERS"] : {}
		},
		itemsSelected : settings["SELECTED"] || {}
	});

	BX.addCustomEvent("BX.SocNetLogDestination:onBeforeSelectItemFocus", BX.proxy(this.onBeforeSelectItemFocus, this));

	BX.bind(this.form.layout.responsible, "focus", BX.proxy(this.openDialog, this));
	BX.bind(this.form.layout.responsible, "click", BX.proxy(this.openDialog, this));
	BX.bind(this.form.layout.responsible, "blur", BX.proxy(this.onBlur, this));

	var params = {
		formName: this.id,
		inputName: this.form.layout.responsible.getAttribute("id")
	};

	BX.bind(this.form.layout.responsible, "keyup", BX.proxy(BX.SocNetLogDestination.BXfpSearch, params));
	BX.bind(this.form.layout.responsible, "keydown", BX.proxy(BX.SocNetLogDestination.BXfpSearchBefore, params));
};

BX.Tasks.QuickForm.UserSelector.prototype.getUserId = function()
{
	return this.userId;
};

BX.Tasks.QuickForm.UserSelector.prototype.getUserEmail = function()
{
	return this.userEmail;
};

BX.Tasks.QuickForm.UserSelector.prototype.getUserName = function()
{
	return this.userName;
};

BX.Tasks.QuickForm.UserSelector.prototype.getUserLastName = function()
{
	return this.userLastName;
};

BX.Tasks.QuickForm.UserSelector.prototype.openDialog = function(event)
{
	BX.calendar.get().Close();
	BX.PreventDefault(event);
	this.form.layout.responsible.value = "";
	if (!BX.SocNetLogDestination.isOpenDialog())
	{
		BX.SocNetLogDestination.openDialog(this.id);
	}
};

BX.Tasks.QuickForm.UserSelector.prototype.onBeforeSelectItemFocus = function(sender)
{
	if (sender.id === this.id)
	{
		sender.blockFocus = true;
	}
};

BX.Tasks.QuickForm.UserSelector.prototype.onSelect = function(item, type, search)
{
	this.userId = item.entityId || 0;
	this.userNameFormatted = BX.util.htmlspecialcharsback(item.name);

	var params = item.params || {};
	this.userEmail = params.email || "";
	this.userName = params.name || "";
	this.userLastName = params.lastName || "";

	this.form.layout.responsible.value = this.userNameFormatted;
	this.form.layout.responsibleId.value = this.userId;

	BX.SocNetLogDestination.deleteLastItem(this.id);
	BX.SocNetLogDestination.closeDialog();
};

BX.Tasks.QuickForm.UserSelector.prototype.onBlur = function()
{
	setTimeout(BX.proxy(function() {
		if (!BX.SocNetLogDestination.isOpenDialog() &&
			!BX.SocNetLogDestination.isOpenSearch() &&
			this.form.layout.responsible.value.length <= 0)
		{
			this.form.layout.responsible.value = this.userNameFormatted;
			this.form.layout.responsibleId.value = this.userId;
		}
	}, this), 100);
};


})();
