;(function() {
	var BX = window.BX;
	if (BX && BX["Mobile"] && BX["Mobile"]["Tasks"] && BX["Mobile"]["Tasks"]["detail"])
		return;
	BX.namespace("BX.Mobile.Tasks.detail");

	BX.Mobile.Tasks.detail = function(opts, nf)
	{
		this.parentConstruct(BX.Mobile.Tasks.detail, opts);

		BX.merge(this, {
			sys: {
				classCode: 'detail'
			},
			vars: {
				objectId: BX.util.getRandomString()
			},
			task: opts.taskData,
			taskEditObj: new BX.Mobile.Tasks.edit({
				taskData: opts.taskData,
				formId: opts.formId,
				setTitle: false
			}),
			currentTs: opts.currentTs,
			guid: opts.guid,
			statuses: opts.statuses
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

			this.stub = null;
			this.topPanelHeight = 75;
			this.platformDelta = (window.platform === 'ios' ? 60 : 0);
			this.formInterface = BX.Mobile.Grid.Form.getByFormId(this.option('formId'));
			this.forceHideButtons = false;

			this.timeout = 0;
			this.timeoutSec = 2000;
			this.commentsList = null;
			this.unreadComments = new Map();
			this.commentsToRead = new Map();

			BX.hide(BX('commentsStub'));

			BX.onCustomEvent("onTaskWasLoaded", [this.task]);
			if (typeof this.task['LOG_ID'] != 'undefined')
			{
				var params = {
					log_id: this.task['LOG_ID'],
					ts: this.currentTs,
					bPull: false
				};

				BXMobileApp.onCustomEvent("onLogEntryRead", params, true);
			}

			this.bindControls();
			this.bindEvents();
		},

		bindControls: function()
		{
			BX.bind(BX('options_button'), 'click', BX.proxy(function() {
				var params = [{
					guid: this.guid,
					taskId: this.task.ID
				}];
				BXMobileApp.onCustomEvent('onTaskDetailOptionsButtonClick', params, true);
			}, this));

			BX.bind(BX('down_button'), 'click', BX.delegate(function() {
				this.scrollToBottom();
			}, this));

			BX.bind(BX('up_button'), 'click', BX.delegate(function() {
				this.scrollToTop();
			}, this));

			if (BX("favorites" + this.task['ID']))
			{
				BX.bind(BX("favorites" + this.task['ID']), "click", BX.proxy(function() {
					if (this.task['ACTION']['ADD_FAVORITE'])
					{
						this.act('addFavorite');
						BX.addClass(BX("favorites" + this.task['ID']), "active");
					}
					else if (this.task['ACTION']['DELETE_FAVORITE'])
					{
						this.act('deleteFavorite');
						BX.removeClass(BX("favorites" + this.task['ID']), "active");
					}
				}, this));
			}
		},

		bindEvents: function()
		{
			BX.addCustomEvent(window, 'OnUCHasBeenInitialized', function(xmlId, list) {
				console.log('OnUCHasBeenInitialized', list);
				if (xmlId === 'TASK_' + this.task.ID)
				{
					this.commentsList = list;
					BXMobileApp.Events.postToComponent('task.view.onCommentsRead', {taskId: this.task.ID, newCommentsCount: 0}, 'tasks.list');
					console.log('comments:' + this.commentsList.getCommentsCount());
				}
			}.bind(this));

			BX.addCustomEvent(window, 'onUCFormSubmit', BX.delegate(function() {
				console.log('tasks: onUCFormSubmit');
				this.hideCommentsStub();
			}, this));

			BX.addCustomEvent(window, 'OnUCCommentWasPulled', function(id, data) {
				console.log('OnUCCommentWasPulled');
				if (id[0] === 'TASK_' + this.task.ID)
				{
					var author = data.messageFields['AUTHOR'];
					if (Number(author['ID']) !== Number(BX.message('USER_ID')))
					{
						this.unreadComments.set(id[1], new Date());
					}
					console.log('Unread comments:' + this.unreadComments);
					this.hideCommentsStub();
				}
			}.bind(this));

			BX.addCustomEvent(window, 'OnUCommentWasDeleted', function(xmlId, id) {
				console.log('OnUCommentWasDeleted:', arguments);
				var commentId = id[1];
				if (xmlId === ('TASK_' + this.task.ID) && this.unreadComments.delete(commentId))
				{
					BXMobileApp.Events.postToComponent('task.view.onCommentsRead', {taskId: this.task.ID, newCommentsCount: this.unreadComments.size}, 'tasks.view');
					BXMobileApp.Events.postToComponent('task.view.onCommentsRead', {taskId: this.task.ID, newCommentsCount: this.unreadComments.size}, 'tasks.list');

					console.log('this.commentsList.getCommentsCount():', this.commentsList.getCommentsCount());
				}
			}.bind(this));

			BX.addCustomEvent(window, 'OnUCCommentWasRead', function(xmlId, id) {
				console.log('OnUCCommentWasRead:', arguments);
				var commentId = id[1];
				if (xmlId === ('TASK_' + this.task.ID) && this.unreadComments.has(commentId))
				{
					this.commentsToRead.set(commentId, this.unreadComments.get(commentId));
					this.unreadComments.delete(commentId);

					BXMobileApp.Events.postToComponent('task.view.onCommentsRead', {taskId: this.task.ID, newCommentsCount: this.unreadComments.size}, 'tasks.view');
					BXMobileApp.Events.postToComponent('task.view.onCommentsRead', {taskId: this.task.ID, newCommentsCount: this.unreadComments.size}, 'tasks.list');

					if (this.timeout <= 0)
					{
						this.timeout = setTimeout(this.readComments.bind(this), this.timeoutSec);
					}
				}
			}.bind(this));

			window.addEventListener('scroll', this.onScrollEvent.bind(this));

			BX.addCustomEvent('onKeyboardWillShow', function() {
				var upButton = BX('up_button');
				var downButton = BX('down_button');
				var optionsButton = BX('options_button');
				var hiddenClass = 'flow-button-hidden';

				this.forceHideButtons = true;

				if (!BX.hasClass(optionsButton, hiddenClass))
				{
					BX.addClass(optionsButton, hiddenClass);
				}
				if (!BX.hasClass(downButton, hiddenClass))
				{
					BX.addClass(downButton, hiddenClass);
				}
				BX.hide(upButton);
			}.bind(this));

			BX.addCustomEvent('onKeyboardWillHide', function() {
				var downButton = BX('down_button');
				var optionsButton = BX('options_button');
				var hiddenClass = 'flow-button-hidden';

				this.forceHideButtons = false;

				if (BX.hasClass(optionsButton, hiddenClass))
				{
					BX.removeClass(optionsButton, hiddenClass);
				}
				if (BX.hasClass(downButton, hiddenClass))
				{
					BX.removeClass(downButton, hiddenClass);
				}
				this.onScrollEvent();
			}.bind(this));

			BX.addCustomEvent("onTextPanelShown", BX.delegate(function(obj) {
				if (!this.webViewHeight)
				{
					this.webViewHeight = obj.webViewHeight || document.body.clientHeight - this.topPanelHeight;
				}
				if (BX('post-comments-wrap'))
				{
					window.scrollTo(0, this.getCommentElementScrollTo());
				}
				BXMobileApp.Events.postToComponent(
					'task.view.onPageLoaded',
					{taskId: this.task.ID},
					'tasks.view'
				);
				BXMobileApp.UI.Page.LoadingScreen.hide();
			}, this));

			BXMobileApp.addCustomEvent('tasks.view.native::onTaskUpdate', BX.delegate(function(eventData) {
				console.log('tasks.view.native::onTaskUpdate');
				if (eventData.taskId !== this.task.ID)
				{
					return;
				}

				if (eventData.hasOwnProperty('favorite'))
				{
					document.querySelector('#favorites' + this.task.ID).classList.toggle('active');
				}
				if (eventData.hasOwnProperty('responsible'))
				{
					window.app.reload();
				}
			}, this));

			BXMobileApp.addCustomEvent('tasks.view.native::onItemAction', BX.delegate(function(eventData) {
				if (Number(eventData.taskId) !== Number(this.task.ID) || eventData.taskGuid !== this.guid)
				{
					return;
				}

				var user = {};
				var group = {};

				switch (eventData.name)
				{
					default:
						break;

					case 'deadline':
						var deadline = this.getParsedDate(new Date(eventData.values.deadline));
						this.getFormElement(eventData.name).callback(deadline);
						break;

					case 'responsible':
					case 'auditor':
					case 'accomplice':
						user = eventData.values.user;
						user = {
							ID: user.id,
							NAME: user.name || user.title,
							IMAGE: user.icon || user.imageUrl || false
						};
						this.getFormElement(eventData.name).callback({a_users: [user]});
						break;

					case 'group':
						group = eventData.values.group;
						group = {
							ID: group.id,
							NAME: group.name,
							IMAGE: group.image || false
						};
						this.getFormElement(eventData.name).callback({b_groups: [group]});
						break;

					case 'status':
						var statusNode = BX('bx-task-status-' + this.task['ID']);
						if (statusNode)
						{
							statusNode.innerHTML = BX.message('TASKS_STATUS_' + this.statuses[eventData.values.status]);
						}
						break;
				}
			}, this));

			BXMobileApp.addCustomEvent("onItemSelected", BX.delegate(function() {
				this.scrollToTop();
			}, this));

			BXMobileApp.addCustomEvent("onTaskWasUpdated", BX.delegate(function(taskId, objectId, data, operationData, restricted)
			{
				if (!data)
				{
					objectId = taskId[1];
					taskId = taskId[0];
				}
				if (this.task['ID'] == taskId && objectId !== this.taskEditObj.vars["id"])
				{
					if (!restricted)
					{
						if (Application.getApiVersion() < 31)
						{
							window.app.showPopupLoader();
						}
						window.app.reload();
					}
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

			BXMobileApp.addCustomEvent("onTaskWasPerformed", BX.delegate(function(taskId, objectId, data) {
				if (!data)
				{
					data = taskId[2];
					objectId = taskId[1];
					taskId = taskId[0];
				}
				if (this.task['ID'] == taskId && objectId !== this.variable("objectId"))
				{
					if (
						data["OPERATION"] === "task.dayplan.timer.start"
						|| data["OPERATION"] === "task.dayplan.timer.stop"
						|| data["OPERATION"] === "task.complete"
					) {
						this.actSuccess(data["OPERATION"], data, true);
					} else {
						this.actSuccess("get", data, false);
					}
				}
			}, this));

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

		readComments: function()
		{
			this.timeout = 0;
			this.commentsToRead.clear();

			BX.ajax.runAction('tasks.task.view.update', {data: {taskId: this.task.ID}});
		},

		onScrollEvent: function()
		{
			if (this.forceHideButtons)
			{
				return;
			}

			var scrollTop = this.getScrollTop();
			var bottomBorder = document.body.scrollHeight - 2 * this.webViewHeight + this.topPanelHeight + this.platformDelta;
			var topBorder = this.webViewHeight - this.topPanelHeight;
			var upButton = BX('up_button');
			var downButton = BX('down_button');
			var showClass = 'task-show-button';

			if (scrollTop > topBorder)
			{
				BX.addClass(upButton, showClass);
			}
			else
			{
				BX.removeClass(upButton, showClass);
			}

			downButton.style.display = (scrollTop < bottomBorder ? 'block' : 'none');
		},

		getScrollTop: function()
		{
			return window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
		},

		scrollToTop: function()
		{
			console.log('scrollToTop');
			app.exec('setScroll', {x: 0, y: 0, animated: true}, false);
		},

		scrollToBottom: function()
		{
			console.log('scrollToBottom');
			var y = document.body.scrollHeight - document.body.clientHeight + this.platformDelta;
			app.exec('setScroll', {x: 0, y: y, animated: true}, false);
		},

		getParsedDate: function(date)
		{
			var day = ('0' + date.getDate()).slice(-2);
			var month = ('0' + (date.getMonth() + 1)).slice(-2);
			var year = date.getFullYear();
			var hours = ('0' + date.getHours()).slice(-2);
			var minutes = ('0' + date.getMinutes()).slice(-2);

			return day + '.' + month + '.' + year + ' ' + hours + ':' + minutes;
		},

		getFormElement: function(type)
		{
			var ii = 0;

			switch (type)
			{
				default:
					break;

				case 'deadline':
					for (ii = 0; ii < this.formInterface.elements.length; ii++)
					{
						if (this.formInterface.elements[ii].node && this.formInterface.elements[ii].node.name === 'data[DEADLINE]')
						{
							return this.formInterface.elements[ii];
						}
					}
					break;

				case 'responsible':
				case 'auditor':
				case 'accomplice':
					var membersMap = {
						responsible: 'data[SE_RESPONSIBLE][0][ID]',
						auditor: 'data[SE_AUDITOR][]',
						accomplice: 'data[SE_ACCOMPLICE][]'
					};
					for (ii = 0; ii < this.formInterface.elements.length; ii++)
					{
						if (this.formInterface.elements[ii].select && this.formInterface.elements[ii].select.name === membersMap[type])
						{
							return this.formInterface.elements[ii];
						}
					}
					break;

				case 'group':
					for (ii = 0; ii < this.formInterface.elements.length; ii++)
					{
						if (this.formInterface.elements[ii].select && this.formInterface.elements[ii].select.name === 'data[SE_PROJECT][ID]')
						{
							return this.formInterface.elements[ii];
						}
					}
					break;
			}

			return null;
		},

		getCommentElementScrollTo: function()
		{
			var commentsBlockBody = BX('post-comments-wrap');
			var firstNewComment = BX.findChild(commentsBlockBody, {className: 'post-comment-block-new'}, true);
			if (firstNewComment)
			{
				return firstNewComment.offsetTop - this.topPanelHeight;
			}

			var firstComment = BX.findChild(commentsBlockBody, {className: 'post-comment-block'}, true);
			if (!firstComment)
			{
				this.showCommentsStub(commentsBlockBody);
				return this.stub.offsetTop;
			}
			if (firstComment.offsetTop > 0)
			{
				return firstComment.offsetTop - this.topPanelHeight;
			}

			var collapsedBlock = BX.findChild(commentsBlockBody, {className: 'feed-com-collapsed'}, true);
			if (collapsedBlock)
			{
				return collapsedBlock.offsetTop - this.topPanelHeight;
			}

			return commentsBlockBody.offsetTop - this.topPanelHeight;
		},

		hideCommentsStub: function()
		{
			if (this.stub && BX.isShown(this.stub))
			{
				BX.hide(this.stub);
			}
		},

		showCommentsStub: function(commentsBlockBody)
		{
			if (!this.stub)
			{
				this.stub = BX('commentsStub');
				this.stub.style.height = this.webViewHeight - this.topPanelHeight + 'px';

				BX.append(this.stub, commentsBlockBody);
				BX.show(this.stub);
			}
			else
			{
				BX.show(this.stub);
			}
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
					else if (action == 'START') {
						menu_items.push({
							name: BX.message('MB_TASKS_TASK_DETAIL_BTN_START_TASK'),
							icon: 'play',
							action: BX.proxy(function () { this.act('start'); }, this)
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
							name: BX.message('MB_TASKS_TASK_DETAIL_BTN_APPROVE_TASK_MSGVER_1'),
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
							action: BX.proxy(function () { this.act('addFavorite'); }, this)
						});
					}
					else if (action == 'DELETE_FAVORITE') {
						menu_items.push({
							name: BX.message('MB_TASKS_TASK_DETAIL_BTN_DELETE_FAVORITE_TASK'),
							image: '/bitrix/templates/mobile_app/images/tasks/menu/favorite.png',
							action: BX.proxy(function () { this.act('deleteFavorite'); }, this)
						});
					}
				}
			}
			return (menu_items);
		},

		act : function(action, data, withAuth) {
			if (this.task.isBusy)
			{
				return;
			}

			if (this.appCtrls && this.appCtrls.menu)
			{
				this.appCtrls.menu.hide();
			}

			window.app.showPopupLoader();

			var params = {
				userId: data ? data["userid"] : false
			};

			if (withAuth)
			{
				window.app.BasicAuth( {
					success: BX.proxy(function() {
						BX.ajax.runComponentAction('bitrix:tasks.task', action, {
							mode: 'class',
							data: {
								taskId: this.task["ID"],
								parameters: params
							}
						}).then(
							function(response)
							{
								this.task.isBusy = false;
								window.app.hidePopupLoader();
								this.actExecute(action, response);
							}.bind(this),
							function(response)
							{
								this.task.isBusy = false;
								window.app.hidePopupLoader();
								this.actFailure();
							}.bind(this)
						);
					}, this),
					failure: this.actFailure
				});
			}
			else
			{
				BX.ajax.runComponentAction('bitrix:tasks.task', action, {
					mode: 'class',
					data: {
						taskId: this.task["ID"],
						parameters: params
					},
					onrequeststart: function (xhr) {
						this.xhr = xhr;
					}.bind(this),
				}).then(
					function(response)
					{
						this.task.isBusy = false;
						window.app.hidePopupLoader();
						this.actExecute(action, response);
					}.bind(this),
					function(response)
					{
						this.task.isBusy = false;
						window.app.hidePopupLoader();

						if (this.xhr && this.xhr.status && this.xhr.status === 401)
						{
							this.act(action, data, true);
						}
						else if (response.errors && response.errors.length)
						{
							this.actError(response.errors);
						}
						else
						{
							this.actFailure();
						}
					}.bind(this)
				);
			}
		},
		actError: function(errors) {
			if (errors && errors.length > 0)
			{
				for (var ii = 0; ii < errors.length; ii++) {
					errors[ii] = (errors[ii]["message"] || errors[ii]["code"]);
				}
				window.app.alert({text: errors.join(". "), title : BX.message("MB_TASKS_TASK_ERROR_TITLE")});
			}
		},
		actExecute : function(action, response) {
			window.app.hidePopupLoader();

			if (response.errors && response.errors.length)
			{
				this.actError(response.errors);
				return;
			}

			if (action === "delete")
			{
				window.BXMobileApp.onCustomEvent(
					"onTaskWasRemoved",
					[this.task["ID"], this.variable("objectId"), response.data],
					true,
					true
				);
				return;
			}

			window.BXMobileApp.onCustomEvent(
				"onTaskWasPerformed",
				[this.task["ID"], this.variable("objectId"), response.data],
				true,
				true
			);
			this.actSuccess(action, response.data, true);

		},
		actSuccess : function(action, data, specify) {
			var ii, reset = false;

			if (action === "deleteFavorite")
			{
				reset = true;
				this.task['ACTION']['DELETE_FAVORITE'] = false;
				this.task['ACTION']['ADD_FAVORITE'] = true;
				if (BX("favorites" + this.task['ID']))
					BX.removeClass(BX("favorites" + this.task['ID']), "active");
			}
			else if (action === "addFavorite")
			{
				reset = true;
				this.task['ACTION']['DELETE_FAVORITE'] = true;
				this.task['ACTION']['ADD_FAVORITE'] = false;
				if (BX("favorites" + this.task['ID']))
					BX.addClass(BX("favorites" + this.task['ID']), "active");
			}
			else if (action === "delegate")
			{
				BX.reload();
			}
			else if (action === "get")
			{
				this.task['ACTION'] = {};
				for (ii in data["RESULT"]["CAN"]["ACTION"])
				{
					if (data["RESULT"]["CAN"]["ACTION"].hasOwnProperty(ii))
					{
						this.task['ACTION'][ii.toUpperCase()] = (
							data["RESULT"]["CAN"]["ACTION"][ii] === "YES" ||
							data["RESULT"]["CAN"]["ACTION"][ii] === "true" ||
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
				this.act('get', data);
			}

			if (Application.getApiVersion() < 31 && reset)
			{
				this.resetMenu(this.getDefaultMenu());
			}
		},
		actFailure : function() {
			window.app.alert({text: BX.message("TASKS_LIST_GROUP_ACTION_ERROR1"), title : BX.message("MB_TASKS_TASK_ERROR_TITLE")});
		}
	});

	if (BX.MobileUI.TextField.defaultParams)
	{
		window.BX.MobileUI.TextField.show();
	}
	else
	{
		BX.addCustomEvent(BX.MobileUI.events.MOBILE_UI_TEXT_FIELD_SET_PARAMS, function(){
			window.BX.MobileUI.TextField.show();
		});
	}
}());