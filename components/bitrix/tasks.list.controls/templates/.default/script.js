BX.namespace("BX.Tasks");

(function(){

	if (BX.Tasks.ListControlsNS)
		return;

	BX.Tasks.ListControlsNS = {
		ready : false,
		params: {
			appendUrlParams: {
				SW_FF: 'FOR'
			}
		},
		init  : function() {
			this.ready = true;
		},
		menu : {
			menus : {},
			create : function(menuId){
				this.menus[menuId] = {
					items : []
				}
			},
			show : function(menuId, anchor, parameters)
			{
				if ( ! self.ready )
					return;

				if ( ! this.menus[menuId] )
					return;

				if ( ! this.menus[menuId].items.length )
					return;

				if(typeof parameters == 'undefined')
					parameters = {};

				var anchorPos = BX.pos(anchor);

				var items = BX.clone(this.menus[menuId].items);

				var params = [];
				if(parameters.useAppendParams)
				{
					for(var i in self.params.appendUrlParams)
					{
						params.push(i+'='+self.params.appendUrlParams[i]);
					}

					for(var k = 0; k < items.length; k++)
					{
						if(typeof items[k].href == 'undefined')
						{
							items[k].href = '';
						}
						items[k].href = BX.util.add_url_param(items[k].href, self.params.appendUrlParams);
					}
				}

				BX.PopupMenu.show(
					'task-top-panel-menu' + menuId + params.join('_'),
					anchor,
					items,
					{
						autoHide : true,
						//"offsetLeft": -1 * anchorPos["width"],
						"offsetTop": 4,
						"events":
						{
							"onPopupClose" : function(ind){
							}
						}
					}
				);
			},
			addItem : function (menuId, title, className, href)
			{
				this.menus[menuId].items.push({
					text      : title,
					className : className,
					href      : href
				});
			},
			addDelimiter : function(menuId)
			{
				this.menus[menuId].items.push({
					delimiter : true
				});
			}
		},
		createGanttHint: function(){

			var ganttHint = BX('gantt-hint');
			var ganttHintClose = BX('gantt-hint-close');

			if(BX.type.isElementNode(ganttHint) && BX.type.isElementNode(ganttHintClose))
			{
				BX.bind(ganttHintClose, 'click', function(){
					BX.remove(ganttHint);
					BX.userOptions.save(
						"tasks",
						"task_list",
						"enable_gantt_hint",
						"N",
						false
					);
				});
			}
		},
		createViewModeHint: function(){
			this.viewModeHint = BX.PopupWindowManager.create("view_mode_hint",
				BX('task-top-panel-view-mode-selector'),
				{
					offsetTop : -1,
					autoHide : true,
					closeByEsc : false,
					angle: { position: "bottom", offset: 24 },
					events: { onPopupClose : BX.delegate(this.onViewModeHintClose, this) },
					content : BX.create("DIV",
						{
							attrs: { className: "task-hint-popup-contents" },
							children:
							[
								BX.create("SPAN",
									{ attrs: { className: "task-hint-popup-title" }, text: BX.message("TASKS_PANEL_VM_HINT_TITLE")  }
								),
								BX.create("P", { text: BX.message("TASKS_PANEL_VM_HINT_BODY") }),
								BX.create("P",
									{
										children:
										[
											BX.create("A",
												{
													props: { href: "javascript:void(0)" },
													text: BX.message("TASKS_PANEL_VM_HINT_DISABLE"),
													events: { "click": BX.delegate(this.onViewModeDisableHint, this)  }
												}
											)
										]
									}
								)
							]
						}
					)
				}
			);
			this.viewModeHint.show();
		},
		onViewModeDisableHint: function(e)
		{
			if(this.viewModeHint)
			{
				this.viewModeHint.close();

				BX.userOptions.save(
					"tasks",
					"task_list",
					"enable_viewmode_hint",
					"N",
					false
				);
			}
			return BX.PreventDefault(e);
		},
		onViewModeHintClose: function()
		{
			if(this.viewModeHint)
			{
				this.viewModeHint.destroy();
				this.viewModeHint = null;
			}
		}
	};

	var self = BX.Tasks.ListControlsNS;

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
			form: BX("task-new-item-form"),
			responsible: BX("task-new-item-responsible"),
			responsibleId: BX("task-new-item-responsible-id"),
			deadline: BX("task-new-item-deadline"),
			menu: BX("task-new-item-menu"),
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
		this.messages = this.parameters.messages || {};
		this.canManageTask = this.parameters.canManageTask !== false;
		this.canAddMailUsers = this.parameters.canAddMailUsers === true;

		if (this.canManageTask)
		{
			BX.bind(this.layout.title, "keypress", BX.proxy(this.fireEnterKey, this));

			BX.bind(this.layout.menu, "click", BX.proxy(this.show, this));
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
			columnsOrder : window.tasksListNS && tasksListNS.getColumnsOrder ? tasksListNS.getColumnsOrder() : null,
			project : this.layout.projectId.value,
			pathToUser: BX.message("TASKS_PATH_TO_USER_PROFILE"),
			pathToTask: BX.message("TASKS_PATH_TO_TASK"),
			siteId: BX.message("SITE_ID"),
			nameTemplate: this.parameters.nameTemplate,
			filter: this.parameters.filter,
			order: this.parameters.order,
			navigation: this.parameters.navigation,
			select: this.parameters.select,
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
		//console.log(result);

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
			if (data.task)
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
			else if (data.html)
			{
				this.insertIntoList(data.html, data.position);
			}

			this.highlight(taskId, false);
		}

		var title = data["taskRaw"] && data["taskRaw"]["TITLE"] ? data["taskRaw"]["TITLE"] : "";
		var path = data["taskPath"];

		this.notification.show(taskId, title, path, found);

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

		if (json.projectId && !ganttChart.getProjectById(json.projectId))
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

	BX.Tasks.QuickForm.prototype.insertIntoList = function(html, position)
	{
		//console.log(position);
		var div = document.createElement("div");
		div.innerHTML = "<table>" + html + "</table>";
		var rows = div.firstChild.getElementsByTagName("tr");
		var scripts = div.firstChild.getElementsByTagName("script");

		var script = BX.create("script", {
				props : { type : "text/javascript"},
				html: scripts[0].innerHTML
		});
		var newRow = rows[0];

		var table = BX("task-list-table-body");
		var taskProjectId = this.projectSelector.projectId;
		if (taskProjectId && !BX("task-project-" + taskProjectId))
		{
			var project = __CreateProjectRow({
				id: taskProjectId,
				title: BX.util.htmlspecialcharsback(this.projectSelector.projectName)
			});

			var nextProject = this.getNextProject(table, taskProjectId);
			if (nextProject !== null)
			{
				table.insertBefore(project, nextProject);
			}
			else
			{
				table.appendChild(project);
			}
		}

		var nextTask = BX("task-" + position.nextTaskId);
		var prevTask = BX("task-" + position.prevTaskId);
		var nextTaskProjectId = nextTask ? parseInt(nextTask.getAttribute("data-project-id"), 10) : null;
		var prevTaskProjectId = prevTask ? parseInt(prevTask.getAttribute("data-project-id"), 10) : null;
		if (nextTask && nextTaskProjectId === taskProjectId)
		{
			table.insertBefore(newRow, nextTask);
			table.insertBefore(script, nextTask);
		}
		else if (prevTask && prevTaskProjectId === taskProjectId)
		{
			var nextSibling = BX.findNextSibling(prevTask, { tagName: "tr", className: "task-depth-0" });
			if (nextSibling)
			{
				table.insertBefore(newRow, nextSibling);
				table.insertBefore(script, nextSibling);
			}
			else
			{
				table.appendChild(newRow);
				table.appendChild(script);
			}
		}
		else
		{
			nextProject = this.getNextProject(table, taskProjectId);
			if (nextProject)
			{
				table.insertBefore(newRow, nextProject);
				table.insertBefore(script, nextProject);
			}
			else
			{
				table.appendChild(newRow);
				table.appendChild(script);
			}
		}

		var stubRow = BX("task-list-no-tasks");
		if (stubRow)
		{
			stubRow.style.display = "none";
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

	BX.Tasks.QuickForm.prototype.onPopupErrorClose = function()
	{
		this.errorPopup.close();
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
			bHideTimebar: false,
			callback_after: function () {
				var defaultTime = BX.CJSTask.ui.extractDefaultTimeFromDataAttribute(deadlineInput);
				deadlineInput.value = BX.CJSTask.addTimeToDateTime(deadlineInput.value, defaultTime);
			}
		});

		BX.SocNetLogDestination.closeDialog();
	};

	BX.Tasks.QuickForm.prototype.show = function()
	{
		BX.addClass(this.layout.container, "task-top-panel-righttop-open");
		this.focus();
	};

	BX.Tasks.QuickForm.prototype.hide = function()
	{
		BX.removeClass(this.layout.container, "task-top-panel-righttop-open");
		this.focus();
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

	BX.Tasks.QuickForm.prototype.disable = function()
	{
		this.layout.title.disabled = true;
		this.layout.deadline.disabled = true;
		this.layout.responsible.disabled = true;
		this.layout.description.disabled = true;

		BX.addClass(this.layout.saveButton, "webform-small-button-wait webform-button-disable");
	};

	BX.Tasks.QuickForm.prototype.enable = function()
	{
		this.layout.title.disabled = false;
		this.layout.deadline.disabled = false;
		this.layout.responsible.disabled = false;
		this.layout.description.disabled = false;

		BX.removeClass(this.layout.saveButton, "webform-small-button-wait webform-button-disable");
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
		else
		{
			var row = BX("task-" + taskId);
			if (row)
			{
				BX.addClass(row, "task-list-item-highlighted");
				setTimeout(function() {
					BX.removeClass(row, "task-list-item-highlighted");
				}, 1000);

				if (scroll === true)
				{
					row.scrollIntoView(false);
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
			searchInput : null,
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
				sonetgroups: settings["SONETGROUPS"] || {}
			},
			itemsLast: {
				users: {},
				groups: {},
				department: {},
				sonetgroups: settings["LAST"]["SONETGROUPS"] || {}
			},
			itemsSelected : {}
		});

		BX.bind(this.form.layout.projectLink, "click", BX.proxy(this.openDialog, this));
		BX.bind(this.form.layout.projectClearing, "click", BX.proxy(this.clearProject, this));
	};

	BX.Tasks.QuickForm.ProjectSelector.prototype.openDialog = function()
	{
		if (!BX.SocNetLogDestination.isOpenDialog())
		{
			BX.SocNetLogDestination.openDialog(this.id);
		}

		BX.PreventDefault(event);
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
				users: settings["USERS"],
				department: settings["DEPARTMENT"] || {},
				departmentRelation: settings["DEPARTMENT_RELATION"] || {}
			},
			itemsLast: {
				users: settings["LAST"]["USERS"]
			},
			itemsSelected : settings["SELECTED"] || {}
		});

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
