;(function() {
	var BX = window.BX, currentList;
	if (BX && BX["Mobile"] && BX["Mobile"]["Tasks"] && BX["Mobile"]["Tasks"]["list"])
		return;
	BX.namespace("BX.Mobile.Tasks.list");
	window.BXMobileApp.addCustomEvent("onTasksListSort", function(data) {
		if (data && data["action"] == "sort")
		{
			var url = BX.util.add_url_param(top.location.href, {SORTF : data["sortBy"], SORTD : data["sortOrder"].toUpperCase()});
			window.app.showPopupLoader();
			window.app.reload({url : url});
		}
	});
	window.BXMobileApp.addCustomEvent("onTasksListFields", function() {
		BX.reload();
	});

	var tasksInProgress = new ((function(){
		var d = function() {
			this.tasks = {};
			this.timer = null;
			this.check = BX.delegate(this.check, this);
		};
		d.prototype = {
			activate : function(id) {
				var node = BX("bx-task-timetracking-" + id);
				if (node)
					node.disabled = false;
			},
			register : function(id, data) {
				var node = BX("bx-task-timetracking-" + id);
				if (!node)
					return;
				node.checked = true;
				node.disabled = false;
				this.tasks[id] = {
					trueTime : parseInt(node.value),
					currentTime : 0
				};
				if (data && data["TIME_ELAPSED"])
					this.tasks[id].trueTime = parseInt(data["TIME_ELAPSED"]);

				if (this.timer === null)
					this.timer = setInterval(this.check, 1000);
			},
			deregister : function(id, disable) {
				if (BX("bx-task-timetracking-" + id))
				{
					BX("bx-task-timetracking-" + id).checked = false;
					if (disable === true)
						BX("bx-task-timetracking-" + id).disabled = true;
					if (this.tasks[id])
						BX("bx-task-timetracking-" + id).value = (this.tasks[id].trueTime + this.tasks[id].currentTime);
				}
				delete this.tasks[id];
				var i = 0;
				for (var ii in this.tasks)
				{
					if (this.tasks.hasOwnProperty(ii))
					{
						i++;
						break;
					}
				}
				if (i === 0)
				{
					clearInterval(this.timer);
					this.timer = null;
				}
			},
			check : function() {
				for (var ii in this.tasks)
				{
					if (this.tasks.hasOwnProperty(ii))
					{
						this.refresh(ii, (++this.tasks[ii].currentTime) + this.tasks[ii].trueTime);
					}
				}
			},
			refresh : function(id, time) {
				var node = BX("bx-task-timetracking-" + id + "-value"),
					t = [
						Math.floor(time / 3600),
						(Math.floor(time / 60) % 60),
						time % 60
					], i;
				for (i = 0; i < t.length; i++) {
					t[i] += '';
					t[i] = '00'.substring(0, 2 - t[i].length) + t[i];
				}
				node.innerHTML = t.join(":");
			}
		};
		return d;
	})());
	BX.Mobile.Tasks.list = function(opts, nf){

		this.parentConstruct(BX.Mobile.Tasks.list, opts);

		BX.merge(this, {
			sys: {
				classCode: 'list'
			},
			vars: {
				objectId : BX.util.getRandomString(),
				task : {}
			}
		});

		for (var ii in opts.tasksData)
		{
			if (opts.tasksData.hasOwnProperty(ii))
			{
				this.bindTask(opts.tasksData[ii]["ID"], opts.tasksData[ii]);
			}
		}
		this.handleInitStack(nf, BX.Mobile.Tasks.list, opts);
	};
	BX.extend(BX.Mobile.Tasks.list, BX.Mobile.Tasks.page);
	// the following functions can be overrided with inheritance
	BX.merge(BX.Mobile.Tasks.list.prototype, {
		// member of stack of initializers, must be defined even if do nothing
		bindTask : function(id, task) {
			this.variable("task" + id, task);
			if (BX("bx-task-priority-" + task["ID"]) && !BX("bx-task-priority-" + task["ID"]).hasAttribute("bx-bound"))
			{
				BX("bx-task-priority-" + task["ID"]).setAttribute("bx-bound", "Y");
				BX.bind(BX("bx-task-priority-" + task["ID"]), "click", BX.delegate(function(){ this.act("changePriority", id); }, this));
			}
			if (BX("bx-task-favorites-" + task["ID"]) && !BX("bx-task-favorites-" + task["ID"]).hasAttribute("bx-bound"))
			{
				BX("bx-task-favorites-" + task["ID"]).setAttribute("bx-bound", "Y");
				BX.bind(BX("bx-task-favorites-" + task["ID"]), "click", BX.delegate(function(){ this.act("favorite.addDelete", id); }, this));
			}
			if (BX("bx-task-timetracking-" + task["ID"]) && !BX("bx-task-timetracking-" + task["ID"]).hasAttribute("bx-bound"))
			{
				BX("bx-task-timetracking-" + task["ID"]).setAttribute("bx-bound", "Y");
				if (BX("bx-task-timetracking-" + task["ID"]).checked)
					tasksInProgress.register(task["ID"], task);

				var f = BX.proxy(function(e) {
					BX.eventCancelBubble(e);
					this.act('dayplan.timer.' + (BX("bx-task-timetracking-" + task["ID"]).checked ? 'start' : 'stop'), task["ID"]);
					return BX.PreventDefault(e);
				}, this);
				BX.bind(BX("bx-task-timetracking-" + task["ID"]), "click", f);
			}
		},
		init: function()
		{
			currentList = this;
			window.app.hidePopupLoader();
			BX.Mobile.Tasks.list.actShow = BX.delegate(this.actShow, this);
			BX.Mobile.Tasks.list.act = BX.delegate(this.act, this);

			this.actExecute = BX.delegate(this.actExecute, this);
			this.actSuccess = BX.delegate(this.actSuccess, this);
			this.actFailure = BX.delegate(this.actFailure, this);

			BXMobileApp.addCustomEvent("onTaskWasUpdated", BX.delegate(function(taskId, objectId, data) {
				if (!data)
				{
					data = taskId[2];
					//objectId = taskId[1];
					taskId = taskId[0];
				}
				var task = this.variable("task" + taskId),
					reload = false;

				if (task)
				{
					if (data["TITLE"] && data["TITLE"] != task["TITLE"] && BX("bx-task-title-" + task["ID"]))
					{
						BX("bx-task-title-" + task["ID"]).innerHTML = data["TITLE"];
					}
					if (data["DESCRIPTION"] && data["DESCRIPTION"] != task["DESCRIPTION"])
					{
						if (BX("bx-task-description-" + task["ID"]))
							BX("bx-task-description-" + task["ID"]).innerHTML = data["DESCRIPTION"];
						else
							reload = true;
					}
					if (data["RESPONSIBLE"] && data["RESPONSIBLE_ID"] && (data["RESPONSIBLE_ID"] + '') != (task["RESPONSIBLE_ID"] + ''))
					{
						if (BX("bx-task-responsible_id-" + task["ID"]))
						{
							BX("bx-task-responsible_id-" + task["ID"]).innerHTML = data["RESPONSIBLE"]["NAME"];
							BX("bx-task-responsible_id-" + task["ID"]).setAttribute("bx-user_id", data["RESPONSIBLE_ID"]);
						}
						else
							reload = true;
					}
					if (data["PRIORITY"] && (data["PRIORITY"] + '') != (task["PRIORITY"] + '') && BX("bx-task-priority-" + task["ID"]))
					{
						BX("bx-task-priority-" + task["ID"]).checked = data["PRIORITY"] == 2;
					}

					if (data["REAL_STATUS"] && (data["STATUS"] + '') != (task["STATUS"] + '') && BX("bx-task-status-" + task["ID"]))
					{
						var map = BX.Mobile.Tasks.statusMap;
						BX("bx-task-status-" + task["ID"]).innerHTML = BX.message("TASKS_STATUS_" + map[task["STATUS"]]) || BX.message("TASKS_STATUS_STATE_UNKNOWN");
					}
					if (data["DEADLINE"] && (data["DEADLINE"] + '') != (task["DEADLINE"] + ''))
					{
						if (BX("bx-task-deadline-" + task["ID"]))
							BX("bx-task-deadline-" + task["ID"]).innerHTML = data["DEADLINE"];
						else
							reload = true;
					}

					if (reload)
					{
						BX.reload();
					}
				}
			}, this));
			BXMobileApp.addCustomEvent("onTaskWasCreated", function(){
				BX.reload();}
			);

			BXMobileApp.addCustomEvent("onTaskWasPerformed", BX.proxy(function(taskId, objectId, data) {
				if (!data)
				{
					data = taskId[2];
					objectId = taskId[1];
					taskId = taskId[0];
				}
				if (this.variable("task" + taskId) && objectId != this.variable("objectId"))
					this.actSuccess(data);
			}, this));
			BXMobileApp.addCustomEvent("onTaskWasRemoved", BX.delegate(function(taskId, objectId, data) {
				if (!data)
				{
					taskId = taskId[0];
				}

				if (this.variable("task" + taskId))
				{
					var node = BX.findChild(BX('bx-mobile-grid'), {attribute : { "data-id" : "mobile-grid-item-" + taskId } }, true, false );
					if (node)
					{
						BX.fx.hide(node, {type : 'fold', time : 0.2});
					}
				}
			}, this));
		},

		////////// CLASS-SPECIFIC: free to modify in a child class
		getDefaultMenu : function(){
			return [ {
					name: BX.message('MB_TASKS_ROLES_TASK_ADD'),
					arrowFlag: false,
					icon: 'add',
					action: BX.Mobile.Tasks.createWindow
				},
				{
					name: BX.message('TASKS_LIST_SORT'),
					image: "/bitrix/js/mobile/images/sort.png",
					arrowFlag: false,
					icon: '',
					action: function() {
						window.BXMobileApp.PageManager.loadPageModal({
							url: top.location.href.replace(/routePage=list/gi, "routePage=listsorter").
								replace(/SORTF=[a-z]+/gi, "").
								replace(/SORTD=[a-z]+/gi, "").
								replace(/&&/gi, "&"),
							bx24ModernStyle : true
						});
					}
				},
				{
					name: BX.message('TASKS_LIST_FIELDS'),
					image: "/bitrix/js/mobile/images/fields.png",
					arrowFlag: false,
					icon: '',
					action: function() {
						window.BXMobileApp.PageManager.loadPageModal({
							url: top.location.href.replace(/routePage=list/gi, "routePage=listfields"),
							bx24ModernStyle : true
						});
					}
				}

				/*items.push({
					name: BX.message("CRM_GRID_FILTER"),
					image: "/bitrix/js/mobile/images/settings.png",
					action: BX.proxy(function()
					{
						this.loadPageBlank(this.filterPath);
					}, this)
				});

				items.push({
					name: BX.message("CRM_GRID_FIELDS"),
					image: "/bitrix/js/mobile/images/fields.png",
					action: BX.proxy(function()
					{
						this.loadPageBlank(this.fieldsPath);
					}, this)
				});*/
			];
		},
		actShow : function(taskId) {
			var task = this.variable("task" + taskId);
			if (task)
			{
				var buttons = [];
				buttons.push({
					title : BX.message("TASKS_LIST_GROUP_ACTION_VIEW"),
					callback : function(){
						window.BXMobileApp.PageManager.loadPageUnique({
							url: BX.message('TASK_PATH_TO_READ').replace(/#TASK_ID#/gi, taskId).replace(/#USER_ID#/gi, BX.message('USER_ID')),
							bx24ModernStyle : true
						});
					}
				});
				if (task["ALLOWED_ACTIONS"]["ACTION_EDIT"])
					buttons.push({
						title : BX.message("TASKS_LIST_GROUP_ACTION_EDIT"),
						callback : function(){
							var url = BX.message('TASK_PATH_TO_EDIT').
									replace(/#TASK_ID#/gi, taskId).
									replace(/#USER_ID#/gi, BX.message('USER_ID')).
									replace(/#SALT#/gi, new Date().getTime());
							window.BXMobileApp.PageManager.loadPageModal({
								url: url,
								bx24ModernStyle : true,
								cache : false
							});
						}
					});
				if (task["ALLOWED_ACTIONS"]["ACTION_COMPLETE"])
					buttons.push({
						title : BX.message("TASKS_LIST_GROUP_ACTION_COMPLETE"),
						callback : function(){ BX.Mobile.Tasks.list.act('complete', taskId); }
					});
				if (task["ALLOWED_ACTIONS"]["ACTION_DEFER"])
					buttons.push({
						title : BX.message("TASKS_LIST_GROUP_ACTION_DEFER"),
						callback : function(){ BX.Mobile.Tasks.list.act('defer', taskId); }
					});
				if (buttons.length > 0)
					new window.BXMobileApp.UI.ActionSheet({ buttons : buttons }, 'actionSheet').show();
			}
		},
		act : function(action, id, add) {
			if (this.appCtrls && this.appCtrls.menu)
				this.appCtrls.menu.hide();
			var userId = (BX.type.isPlainObject(id) ? id["id"] : 0),
				taskId =  (BX.type.isPlainObject(id) ? id["id"] : id);

			id = ((id + 0) > 0 ? id : id["id"]);

			var task = this.variable("task" + taskId),
				act = action,
				data = {act : act, id : taskId, userid : BX.message("USER_ID"), taskid : taskId};
			if (action == "favorite.addDelete")
			{
				act = task["ALLOWED_ACTIONS"]["ACTION_ADD_FAVORITE"] ? "favorite.add" : "favorite.delete";
			}
			else if (action == "changePriority")
			{
				act = "update";
				data["task.action"] = "changePriority";
				data.data = { PRIORITY : (task["PRIORITY"] + "" == "2" ? "0" : "2")};
			}
			if (BX.type.isPlainObject(add))
			{
				for (var ii in add)
				{
					if (add.hasOwnProperty(ii))
					{
						data[ii] = add[ii];
					}
				}
			}
			var url = BX.util.add_url_param(BX.message("TASK_PATH_TO_AJAX"), {act : act, id : taskId});
			data["parameters"] = (data["parameters"] || {});
			data["parameters"]["RETURN_ENTITY"] = true;
			window.app.showPopupLoader();
			(new BX.Tasks.Util.Query({url: url})).
				add('task.' + act, data, {}, {onExecuted: BX.proxy(function(response){
					if (response && response.response && response.response.status == 'failed')
					{
						window.app.BasicAuth( {
							success: BX.proxy(function() {
								(new BX.Tasks.Util.Query({url: url})).
									add('task.' + act, data, {}, {onExecuted: this.actExecute }).
									execute();
								}, this),
							failure: this.actFailure
						});
					}
					else
						this.actExecute.apply(this, arguments);
				}, this)}).
				execute();
		},
		actExecute : function(errors, data) {
			window.app.hidePopupLoader();
			if (errors && errors.length > 0)
			{
				var text = [], error;
				for (var ii = 0; ii < errors.length; ii++)
				{
					if (errors[ii]["CODE"] == "ACTION_FAILED.OTHER_TASK_ON_TIMER")
					{
						window.app.confirm({
							title: BX.message("TASKS_TT_ERROR1_TITLE"),
							text: BX.message("TASKS_TT_ERROR1_DESC").replace("#TITLE#", errors[ii]["DATA"]["TASK"]["TITLE"]),
							callback: (BX.proxy(function(stopPrevious){return BX.proxy(function(index) {
								if (index <= 1)
								{
									this.act('dayplan.timer.start', data["ARGUMENTS"]["taskid"], {stopPrevious : stopPrevious});
								}
							}, this)}, this))(errors[ii]["DATA"]["TASK"]["ID"]),
							buttons: [BX.message("TASKS_TT_CONTINUE"), BX.message("TASKS_TT_CANCEL")]
						});
						return;
					}
					else
					{
						text.push(errors[ii]["MESSAGE"] || errors[ii]["CODE"]);
					}
				}
				window.app.alert({text: text.join(". "), title : BX.message("MB_TASKS_TASK_ERROR_TITLE")});
			}
			else if (data["OPERATION"] == "task.delete")
			{
				window.BXMobileApp.onCustomEvent("onTaskWasRemoved", [data["ARGUMENTS"]["taskid"], this.variable("objectId"), data], true, true);
			}
			else
			{
				window.BXMobileApp.onCustomEvent("onTaskWasPerformed", [data["ARGUMENTS"]["taskid"], this.variable("objectId"), data], true, true);
				this.actSuccess(data);
			}
		},
		actSuccess : function(data) {
			var id = (data["ARGUMENTS"] ? (data["ARGUMENTS"]["id"] || data["ARGUMENTS"]["taskid"]) : 0),
				task = this.variable("task" + id),
				nodeStatus = BX("bx-task-status-" + task["ID"]),
				nodeIcon = BX("bx-task-icon-" + task["ID"]),
				map = BX.Mobile.Tasks.statusMap;

			if (!task)
				return;

			if (data["OPERATION"] == "task.renew")
			{
				tasksInProgress.activate(task["ID"]);
			}
			else if (data["OPERATION"] == "task.start" || data["OPERATION"] == "task.dayplan.timer.start")
			{
				task["ALLOWED_ACTIONS"]["ACTION_START"] = false;
				task["ALLOWED_ACTIONS"]["ACTION_COMPLETE"] = true;
				BX.hide(BX("bx-task-start-" + task["ID"]));
				BX.show(BX("bx-task-complete-" + task["ID"]));
				nodeStatus.innerHTML = BX.message("TASKS_STATUS_STATE_IN_PROGRESS");
				nodeIcon.className = "mobile-grid-fields-task-icon state_in_progress";
				tasksInProgress.activate(task["ID"]);
				if (data["OPERATION"] == "task.dayplan.timer.start")
				{
					BX("bx-task-timetracking-" + task["ID"]).checked = true;
					tasksInProgress.register(task["ID"], data["RESULT"]["TASK"]);
					tasksInProgress.deregister(data["ARGUMENTS"]["stopPrevious"]);
					tasksInProgress.deregister(data["ARGUMENTS"]["stopprevious"]);
				}
			}
			else if (data["OPERATION"] == "task.complete")
			{
				task["ALLOWED_ACTIONS"]["ACTION_START"] = false;
				task["ALLOWED_ACTIONS"]["ACTION_COMPLETE"] = false;
				BX.hide(BX("bx-task-start-" + task["ID"]));
				BX.hide(BX("bx-task-complete-" + task["ID"]));
				nodeStatus.innerHTML = BX.message("TASKS_STATUS_STATE_COMPLETED");
				nodeIcon.className = "mobile-grid-fields-task-icon state_completed";
				tasksInProgress.deregister(task["ID"], true);
			}
			else if (data["OPERATION"] == "task.defer")
			{
				task["ALLOWED_ACTIONS"]["ACTION_START"] = true;
				task["ALLOWED_ACTIONS"]["ACTION_COMPLETE"] = true;
				BX.show(BX("bx-task-start-" + task["ID"]));
				BX.show(BX("bx-task-complete-" + task["ID"]));
				nodeStatus.innerHTML = BX.message("TASKS_STATUS_STATE_DEFERRED");
				nodeIcon.className = "mobile-grid-fields-task-icon state_deferred";
				tasksInProgress.deregister(task["ID"]);
			}
			else if (data["OPERATION"] == "task.favorite.add")
			{
				task["ALLOWED_ACTIONS"]["ACTION_ADD_FAVORITE"] = false;
				task["ALLOWED_ACTIONS"]["ACTION_DELETE_FAVORITE"] = true;
				if (BX("bx-task-favorites-" + id))
					BX("bx-task-favorites-" + id).checked = true;
			}
			else if (data["OPERATION"] == "task.dayplan.timer.stop")
			{
				tasksInProgress.deregister(task["ID"]);
			}
			else if (data["OPERATION"] == "task.favorite.delete")
			{
				task["ALLOWED_ACTIONS"]["ACTION_ADD_FAVORITE"] = true;
				task["ALLOWED_ACTIONS"]["ACTION_DELETE_FAVORITE"] = false;
				if (BX("bx-task-favorites-" + id))
					BX("bx-task-favorites-" + id).checked = false;
			}
			else if (data["OPERATION"] == "task.update" && data["ARGUMENTS"] && data["ARGUMENTS"]["task.action"] == "changePriority")
			{
				task["PRIORITY"] = data["ARGUMENTS"]["data"]["PRIORITY"] + "";
				if (BX("bx-task-priority-" + id))
					BX("bx-task-priority-" + task["ID"]).checked = (task["PRIORITY"] == "2");
			}
			else if (data["OPERATION"] == "task.get")
			{
				for (var ii in data["RESULT"]["CAN"]["ACTION"])
				{
					if (data["RESULT"]["CAN"]["ACTION"].hasOwnProperty(ii))
					{
						task["ALLOWED_ACTIONS"]["ACTION_" + ii] =  (
							data["RESULT"]["CAN"]["ACTION"][ii] == "YES" ||
							data["RESULT"]["CAN"]["ACTION"][ii] == "true" ||
							data["RESULT"]["CAN"]["ACTION"][ii] === true);
					}
				}
				BX[task["ALLOWED_ACTIONS"]["ACTION_START"] ? "show" : "hide"](BX("bx-task-start-" + task["ID"]));
				BX[task["ALLOWED_ACTIONS"]["ACTION_COMPLETE"] ? "show" : "hide"](BX("bx-task-complete-" + task["ID"]));
				if (data["RESULT"]["DATA"]["REAL_STATUS"] && (data["RESULT"]["DATA"]["REAL_STATUS"] + '') != (task["REAL_STATUS"] + '') && BX("bx-task-status-" + task["ID"]))
				{
					for (ii in data["RESULT"]["DATA"])
					{
						if (data["RESULT"]["DATA"].hasOwnProperty(ii))
						{
							if (task.hasOwnProperty(ii))
								task[ii] = data["RESULT"]["DATA"][ii];
						}
					}
					var s = map[task["REAL_STATUS"]] || "STATE_UNKNOWN";
					nodeStatus.innerHTML = BX.message("TASKS_STATUS_" + s);
					nodeIcon.className = "mobile-grid-fields-task-icon " + s.toLowerCase();
				}
				if (BX("bx-task-favorites-" + id))
					BX("bx-task-favorites-" + id).checked = task["ALLOWED_ACTIONS"]["ACTION_DELETE_FAVORITE"];
			}
			else if (data["OPERATION"] == "task.delegate")
			{
				BX.reload();
			}
		},
		actFailure : function() {
			window.app.alert({text: BX.message("TASKS_LIST_GROUP_ACTION_ERROR1"), title : BX.message("MB_TASKS_TASK_ERROR_TITLE")});
		}
	});

	BX.Mobile.Tasks.go = function(node) {
		window.BXMobileApp.PageManager.loadPageUnique({url : BX.message("TASK_PATH_TO_USER_PROFILE").replace("#USER_ID#", node.getAttribute("bx-user_id")), bx24ModernStyle: true});
	};

	BX.Mobile.Tasks.list.addCurrent = function(id, task){
		if (currentList) {
			currentList.bindTask(id, task);
		}
	}
}());