;(function() {
	var BX = window.BX;
	if (BX && BX["Mobile"] && BX["Mobile"]["Tasks"] && BX["Mobile"]["Tasks"]["detail"])
		return;
	BX.namespace("BX.Mobile.Tasks.detail");

	BX.Mobile.Tasks.detail = function(opts, nf){

		this.parentConstruct(BX.Mobile.Tasks.detail, opts);

		BX.merge(this, {
			sys: {
				classCode: 'detail'
			},
			vars: {
				objectId : BX.util.getRandomString()
			},
			task : opts.taskData,
			taskEditObj : new BX.Mobile.Tasks.edit({
				taskData : opts.taskData,
				formId : opts.formId,
				setTitle : false
			}),
			currentTs : opts.currentTs
		});

		this.handleInitStack(nf, BX.Mobile.Tasks.detail, opts);
		app.getPageParams(
			{
				callback: function(data)
				{
					if (data && data["bSetFocusOnCommentsList"] == "YES") // from LJ
					{
						BX.ready(function(){
							var node;
							if ((node = BX("task-comments-block")) && node)
							{
								var curPos = BX.pos(node);
								window.scrollTo(0, curPos["top"]);
							}
						});
					}
				}
			}
		);
	};
	BX.extend(BX.Mobile.Tasks.detail, BX.Mobile.Tasks.page);
	// the following functions can be overrided with inheritance
	BX.merge(BX.Mobile.Tasks.detail.prototype, {
		// member of stack of initializers, must be defined even if do nothing
		init: function()
		{
			window.app.hidePopupLoader();
			this.act = BX.delegate(this.act, this);
			this.actExecute = BX.delegate(this.actExecute, this);
			this.actSuccess = BX.delegate(this.actSuccess, this);
			this.actFailure = BX.delegate(this.actFailure, this);

			BX.onCustomEvent("onTaskWasLoaded", [this.task]);
			if (typeof this.task['LOG_ID'] != 'undefined')
			{
				BXMobileApp.onCustomEvent("onLogEntryRead", {log_id: this.task['LOG_ID'], ts: this.currentTs, bPull: false}, true);
			}

			BXMobileApp.addCustomEvent("onTaskWasUpdated", BX.delegate(function(taskId, objectId, data) {
			
				if (!data)
				{
					objectId = taskId[1];
					taskId = taskId[0];
				}
				if (this.task['ID'] == taskId && objectId !== this.taskEditObj.vars["id"])
				{
					window.app.showPopupLoader();
					window.app.reload();
				}
			}, this));
			BXMobileApp.addCustomEvent("onTaskWasRemoved", BX.delegate(function(taskId, objectId, data) {
				if (!data)
				{
					taskId = taskId[0];
				}
				if (this.task['ID'] == taskId)
				{
					window.app.closeController({drop: true});
				}
			}, this));
			BXMobileApp.addCustomEvent("onTaskWasPerformed", BX.delegate(function(taskId, objectId, data)
			{
				if (!data)
				{
					data = taskId[2];
					objectId = taskId[1];
					taskId = taskId[0];
				}
				if (this.task['ID'] == taskId && objectId !== this.variable("objectId"))
				{
					if (data["OPERATION"] == "task.dayplan.timer.start" ||
						data["OPERATION"] == "task.dayplan.timer.stop" ||
						data["OPERATION"] == "task.complete")
						this.actSuccess(data, true);
					else
						this.actSuccess(data, false);
				}
			}, this));

			if (BX("favorites" + this.task['ID']))
			{
				BX.bind(BX("favorites" + this.task['ID']), "click", BX.proxy(function(){
					if (this.task['ACTION']['ADD_FAVORITE'])
					{
						this.act('favorite.add');
						BX.addClass(BX("favorites" + this.task['ID']), "active");
					}
					else if (this.task['ACTION']['DELETE_FAVORITE'])
					{
						this.act('favorite.delete');
						BX.removeClass(BX("favorites" + this.task['ID']), "active");
					}
				}, this));
			}

			BX.MobileUI.addLivefeedLongTapHandler(BX("post_inform_wrap_two"), {
				likeNodeClass: "post-item-informer-like"
//				copyItemClass: "post-item-copyable",
//				copyTextClass: "post-item-copytext"
			});

			BX.MobileUI.addLivefeedLongTapHandler(BX("task-comments-block"), {
				likeNodeClass: "post-comment-control-item-like"
//				copyItemClass: "post-comment-block",
//				copyTextClass: "post-comment-text"
			});
		},

		////////// CLASS-SPECIFIC: free to modify in a child class
		getDefaultMenu : function(){
			var menu_items = [ {
					name: BX.message('MB_TASKS_TASK_DETAIL_TASK_ADD'),
					arrowFlag: false,
					icon: 'add',
					action: BX.Mobile.Tasks.createWindow
				},
				{
					name: BX.message('MB_TASKS_TASK_DETAIL_TASK_ADD_SUBTASK'),
					arrowFlag: false,
					icon: 'add',
					action: BX.proxy(function(){BX.Mobile.Tasks.createWindow(null, this.task['ID'])}, this)
				}
			];
			var action,
				actions = this.task['ACTION'],
				task_id = this.task['ID'];
			for (var index in this.task['ACTION'])
			{
				if (this.task['ACTION'].hasOwnProperty(index))
				{
					if (!actions[index])
						continue;

					action = (index + '').toUpperCase();

					if (action == 'EDIT') {
						menu_items.push({
							name: BX.message('MB_TASKS_TASK_DETAIL_BTN_EDIT'),
							icon: 'edit',
							arrowFlag: false,
							action: function() {
								var url = BX.message('TASK_PATH_TO_EDIT').
										replace(/#TASK_ID#/gi, task_id).
										replace(/#USER_ID#/gi, BX.message('USER_ID')).
										replace(/#SALT#/gi, new Date().getTime());
								window.BXMobileApp.PageManager.loadPageModal({
									url: url,
									bx24ModernStyle : true,
									cache : false
								});
							}
						});
					}
					else if (action == 'REMOVE') {
						menu_items.push({
							name: BX.message('MB_TASKS_TASK_DETAIL_BTN_REMOVE'),
							icon: 'delete',
							action: BX.proxy(function() {
								window.app.confirm({
									title : BX.message('MB_TASKS_TASK_REMOVE_CONFIRM_TITLE'),
									text : BX.message('MB_TASKS_TASK_DETAIL_CONFIRM_REMOVE'),
									buttons : ["OK", BX.message('MB_TASKS_TASK_DETAIL_USER_SELECTOR_BTN_CANCEL')],
									callback : BX.proxy(function (btnNum) { if (btnNum == 1) { this.act('delete'); } }, this)
									});
								}, this
							)
						});
					}
					else if (action == 'ACCEPT') {
						menu_items.push({
							name: BX.message('MB_TASKS_TASK_DETAIL_BTN_ACCEPT_TASK'),
							icon: 'check',
							action: BX.proxy(function () { this.act('accept'); }, this)
						});
					}
					else if (action == 'START') {
						menu_items.push({
							name: BX.message('MB_TASKS_TASK_DETAIL_BTN_START_TASK'),
							icon: 'play',
							action: BX.proxy(function () { this.act('start'); }, this)
						});
					}
					else if (action == 'DECLINE') {
						menu_items.push({
							name: BX.message('MB_TASKS_TASK_DETAIL_BTN_DECLINE_TASK'),
							icon: 'cancel',
							action: BX.proxy(function () { this.act('decline'); }, this)
						});
					}
					else if (action == 'RENEW') {
						menu_items.push({
							name: BX.message('MB_TASKS_TASK_DETAIL_BTN_RENEW_TASK'),
							icon: 'reload',
							action: BX.proxy(function () { this.act('renew'); }, this)
						});
					}
					else if (action == 'COMPLETE') {
						menu_items.push({
							name: BX.message('MB_TASKS_TASK_DETAIL_BTN_CLOSE_TASK'),
							icon: 'finish',
							action: BX.proxy(function () { this.act('complete'); }, this)
						});
					}
					else if (action == 'DEFER') {
						menu_items.push({
							name: BX.message('MB_TASKS_TASK_DETAIL_BTN_PAUSE_TASK'),
							icon: 'pause',
							action: BX.proxy(function () { this.act('defer'); }, this)
						});
					}
					else if (action == 'APPROVE') {
						menu_items.push({
							name: BX.message('MB_TASKS_TASK_DETAIL_BTN_APPROVE_TASK'),
							icon: 'check',
							action: BX.proxy(function () { this.act('approve'); }, this)
						});
					}
					else if (action == 'DISAPPROVE') {
						menu_items.push({
							name: BX.message('MB_TASKS_TASK_DETAIL_BTN_REDO_TASK'),
							icon: 'reload',
							action: BX.proxy(function () { this.act('disapprove'); }, this)
						});
					}
					else if (action == 'DELEGATE') {
						menu_items.push({
							name: BX.message('MB_TASKS_TASK_DETAIL_BTN_DELEGATE_TASK'),
							icon: 'finish',
							action: BX.proxy(function() {
								(new window.BXMobileApp.UI.Table({
									url: BX.message('SITE_DIR') + 'mobile/index.php?mobile_action=get_user_list',
									table_settings : {
										callback: BX.proxy(function(data) {
											if ( ! (data && data.a_users && data.a_users[0]) )
												return;
											this.act('delegate', { userid : data.a_users[0]["ID"].toString() } );
										}, this),
										markmode: true,
										multiple: false,
										return_full_mode: true,
										skipSpecialChars : true,
										modal: true,
										alphabet_index: true,
										outsection: false,
										cancelname: BX.message('MB_TASKS_TASK_DETAIL_USER_SELECTOR_BTN_CANCEL')
									}
								}, "users")).show();
							}, this)
						});
					}
					else if (action == 'ADD_FAVORITE') {
						menu_items.push({
							name: BX.message('MB_TASKS_TASK_DETAIL_BTN_ADD_FAVORITE_TASK'),
							image: '/bitrix/templates/mobile_app/images/tasks/menu/favorite.png',
							action: BX.proxy(function () { this.act('favorite.add'); }, this)
						});
					}
					else if (action == 'DELETE_FAVORITE') {
						menu_items.push({
							name: BX.message('MB_TASKS_TASK_DETAIL_BTN_DELETE_FAVORITE_TASK'),
							image: '/bitrix/templates/mobile_app/images/tasks/menu/favorite.png',
							action: BX.proxy(function () { this.act('favorite.delete'); }, this)
						});
					}
				}
			}
			return (menu_items);
		},

		act : function(action, data) {
			if (this.task.isBusy)
				return;
			if (this.appCtrls && this.appCtrls.menu)
				this.appCtrls.menu.hide();

			//this.task.isBusy = true;

			window.app.showPopupLoader();
			// UUID generation

			var url = BX.util.add_url_param(BX.message("TASK_PATH_TO_AJAX"), {act : action, id : this.task["ID"]});
			(new BX.Tasks.Util.Query({url: url})).
				add('task.' + action, {
					id: this.task["ID"],
					taskid : this.task["ID"],
					userid : (data ? data["userid"] : false),
					objectId : this.variable("objectId"),
					parameters : { RETURN_ENTITY: true}
				}, {}, {onExecuted: BX.proxy(function(response){
					if (response && response.response && response.response.status == 'failed')
					{
						window.app.BasicAuth( {
							success: BX.proxy(function() {
								(new BX.Tasks.Util.Query({url: url})).
									add('task.' + action, {
										id: this.task["ID"],
										taskid: this.task["ID"],
										userid : (data ? data["userid"] : false),
										objectId : this.variable("objectId"),
										parameters : { RETURN_ENTITY: true}
									}, {}, {onExecuted: this.actExecute }).
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
				for (var ii = 0; ii < errors.length; ii++)
					errors[ii] = (errors[ii]["MESSAGE"] || errors[ii]["CODE"]);
				window.app.alert({text: errors.join(". "), title : BX.message("MB_TASKS_TASK_ERROR_TITLE")});
			}
			else if (data["OPERATION"] == "task.delete")
			{
				window.BXMobileApp.onCustomEvent(
					"onTaskWasRemoved",
					[this.task["ID"], this.variable("objectId"), data],
					true,
					true
				);
			}
			else
			{
				window.BXMobileApp.onCustomEvent(
					"onTaskWasPerformed",
					[this.task["ID"], this.variable("objectId"), data],
					true,
					true
				);
				this.actSuccess(data, true);
			}
		},
		actSuccess : function(data, specify) {
			var ii, reset = false;
			if (data["OPERATION"] == "task.favorite.delete")
			{
				reset = true;
				this.task['ACTION']['DELETE_FAVORITE'] = false;
				this.task['ACTION']['ADD_FAVORITE'] = true;
				if (BX("favorites" + this.task['ID']))
					BX.removeClass(BX("favorites" + this.task['ID']), "active");
			}
			else if (data["OPERATION"] == "task.favorite.add")
			{
				reset = true;
				this.task['ACTION']['DELETE_FAVORITE'] = true;
				this.task['ACTION']['ADD_FAVORITE'] = false;
				if (BX("favorites" + this.task['ID']))
					BX.addClass(BX("favorites" + this.task['ID']), "active");
			}
			else if (data["OPERATION"] == "task.delegate")
			{
				BX.reload();
			}
			else if (data["OPERATION"] == "task.get")
			{
				this.task['ACTION'] = {};
				for (ii in data["RESULT"]["CAN"]["ACTION"])
				{
					if (data["RESULT"]["CAN"]["ACTION"].hasOwnProperty(ii))
					{
						this.task['ACTION'][ii.toUpperCase()] = (
							data["RESULT"]["CAN"]["ACTION"][ii] == "YES" ||
							data["RESULT"]["CAN"]["ACTION"][ii] == "true" ||
							data["RESULT"]["CAN"]["ACTION"][ii] === true);
					}
				}
				reset = true;
				var status = data["RESULT"]["DATA"]["REAL_STATUS"];
				if (BX("bx-task-status-" + this.task["ID"]) && status != this.task["REAL_STATUS"])
				{
					this.task["REAL_STATUS"] = data["RESULT"]["DATA"]["REAL_STATUS"];
					this.task["STATUS"] = data["RESULT"]["DATA"]["STATUS"];
					var s = BX.Mobile.Tasks.statusMap[status] || "STATE_UNKNOWN";
					BX("bx-task-status-" + this.task["ID"]).innerHTML = BX.message("TASKS_STATUS_" + s);
				}
			}
			else if (specify === true)
			{
				var url = BX.util.add_url_param(BX.message("TASK_PATH_TO_AJAX"), {act : 'get', id : this.task["ID"]});
				(new BX.Tasks.Util.Query({url: url})).add('task.get', {id: this.task["ID"], "jsAction" : "perform"}, {}, {onExecuted: this.actExecute}).execute();
			}
			if (reset)
				this.resetMenu(this.getDefaultMenu());
		},
		actFailure : function() {
			window.app.alert({text: BX.message("TASKS_LIST_GROUP_ACTION_ERROR1"), title : BX.message("MB_TASKS_TASK_ERROR_TITLE")});
		}
	});
}());