'use strict';

BX.namespace('Tasks.Component');

(function(){

	if (typeof BX.Tasks.Component.TaskView != 'undefined')
	{
		return;
	}

	BX.Tasks.Component.TaskView = function(parameters)
	{
		this.parameters = parameters || {};
		this.taskId = this.parameters.taskId;
		this.userId = this.parameters.userId;
		this.layout = {
			favorite: BX("task-detail-favorite"),
			switcher: BX("task-switcher"),
			switcherTabs: [],
			elapsedTime: BX("task-switcher-elapsed-time"),
			effective: BX("task-switcher-effective"),
			createButton: BX("task-detail-create-button"),
			importantButton: BX("task-detail-important-button"),
			saveButton: BX("saveButton"),
			cancelButton: BX("cancelButton")
		};
		this.openTime = this.parameters.componentData.OPEN_TIME;
		this.analyticsData = {};

		this.timeout = 0;
		this.timeoutSec = 2000;

		this.paramsToLazyLoadTabs = parameters.paramsToLazyLoadTabs || {};
		this.listTabIdUploadedContent = {};

		this.messages = this.parameters.messages || {};
		for (var key in this.messages)
		{
			BX.message[key] = this.messages[key];
		}

		this.paths = this.parameters.paths || {};
		this.createButtonMenu = [];

		this.checkListChanged = false;
		this.showCloseConfirmation = false;

		BX.addCustomEvent(window, 'tasksTaskEvent', this.onTaskEvent.bind(this));
		BX.addCustomEvent('SidePanel.Slider:onClose', this.onSliderClose.bind(this, false));
		BX.addCustomEvent('SidePanel.Slider:onCloseByEsc', this.onSliderClose.bind(this, true));
		BX.addCustomEvent(window, 'OnUCCommentWasRead', this.onCommentRead.bind(this));

		BX.Event.EventEmitter.subscribe('BX.Tasks.CheckListItem:CheckListChanged', function(eventData) {
			var action = eventData.data.action;
			var allowedActions = ['addAccomplice', 'fileUpload', 'tabIn'];

			if (BX.util.in_array(action, allowedActions))
			{
				this.analyticsData[action] = 'Y';
			}

			this.toggleFooterWrap(true);
		}.bind(this));

		this.initFavorite();
		this.initCreateButton();
		this.initSwitcher();
		this.initViewer();
		this.initAjaxErrorHandler();
		this.initImportantButton();
		this.initFooterButtons();
		this.initCommentActionController();

		this.extendWatch();

		this.handleEvent();
		this.temporalCommentFix();

		if (
			!!window.mplCheckForQuote
			&& BX('task-detail-author-info')
		)
		{
			BX.bind(BX("task-detail-content"), "mouseup", function(e) { window.mplCheckForQuote(e, e.currentTarget, 'TASK_' + this.taskId, 'task-detail-author-info') }.bind(this));
		}
	};

	BX.Tasks.Component.TaskView.prototype.extendWatch = function()
	{
		BX.PULL.extendWatch('TASK_VIEW_' + this.taskId, true);

		setTimeout(function() {
			this.extendWatch();
		}.bind(this), 29 * 60 * 1000);
	}

	BX.Tasks.Component.TaskView.prototype.onSliderClose = function(byEsc, event)
	{
		if (!this.checkListChanged || typeof BX.Tasks.CheckListInstance === 'undefined')
		{
			return;
		}

		var checkListSlider = BX.Tasks.CheckListInstance.optionManager.slider;
		if (!checkListSlider || checkListSlider !== event.getSlider())
		{
			return;
		}

		if (byEsc)
		{
			var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
			if (treeStructure && treeStructure.checkActiveUpdateExist())
			{
				event.denyAction();
				return;
			}
		}

		if (!this.showCloseConfirmation)
		{
			this.showCloseConfirmation = true;
			return;
		}

		event.denyAction();
		this.showChecklistCloseSliderPopup(checkListSlider);
	};

	BX.Tasks.Component.TaskView.prototype.showChecklistCloseSliderPopup = function(checkListSlider)
	{
		if (!this.checklistCloseSliderPopup)
		{
			this.checklistCloseSliderPopup = new BX.PopupWindow({
				titleBar: BX.message('TASKS_CLOSE_SLIDER_CONFIRMATION_POPUP_HEADER'),
				content: BX.message('TASKS_CLOSE_SLIDER_CONFIRMATION_POPUP_CONTENT'),
				closeIcon: false,
				buttons: [
					new BX.PopupWindowButton({
						text: BX.message('TASKS_CLOSE_SLIDER_CONFIRMATION_POPUP_BUTTON_CLOSE'),
						className: 'popup-window-button-accept',
						events: {
							click: function() {
								this.showCloseConfirmation = false;
								this.checklistCloseSliderPopup.close();
								checkListSlider.close();
							}.bind(this)
						}
					}),
					new BX.PopupWindowButton({
						className: 'popup-window-button popup-window-button-link',
						text: BX.message('TASKS_CLOSE_SLIDER_CONFIRMATION_POPUP_BUTTON_CANCEL'),
						events: {
							click: function() {
								this.checklistCloseSliderPopup.close();
							}.bind(this)
						}
					})
				]
			});
		}
		this.checklistCloseSliderPopup.show();
	};

	BX.Tasks.Component.TaskView.prototype.onCommentRead = function(xmlId, id) {
		if (xmlId === ('TASK_' + this.taskId) && this.timeout <= 0)
		{
			this.timeout = setTimeout(this.readComments.bind(this), this.timeoutSec);
		}
	};

	BX.Tasks.Component.TaskView.prototype.readComments = function()
	{
		this.timeout = 0;
		BX.ajax.runAction('tasks.task.view.update', {data: {taskId: this.taskId}});
	};

	// todo: remove when forum stops calling the same page for comment.add()
	BX.Tasks.Component.TaskView.prototype.temporalCommentFix = function()
	{
		BX.addCustomEvent(window, 'OnUCFormResponse', function(id, id1, obj){
			if (BX.type.isNotEmptyString(id) && id.indexOf("TASK_") === 0 && BX.proxy_context && BX.proxy_context.jsonFailure === true)
			{
				if (obj && obj["handler"] && obj.handler["oEditor"] && obj.handler.oEditor["DenyBeforeUnloadHandler"])
				{
					obj.handler.oEditor.DenyBeforeUnloadHandler();
				}
				BX.reload();
			}
		});
	};

	BX.Tasks.Component.TaskView.prototype.handleEvent = function()
	{
		var eventType = this.parameters.componentData.EVENT_TYPE;
		var eventOptions = this.parameters.componentData.EVENT_OPTIONS;

		if (eventType === 'ADD')
		{
			var analyticsLabels = {
				action: 'taskAdding',
				source: 'addButton'
			};
			if (eventOptions.FIRST_GRID_TASK_CREATION_TOUR_GUIDE)
			{
				analyticsLabels.tourGuide = 'firstGridTaskCreation';
			}
			if (eventOptions.SCOPE)
			{
				analyticsLabels.scope = eventOptions.SCOPE;
			}

			BX.ajax.runAction('tasks.analytics.hit', {analyticsLabel: analyticsLabels});
		}

		this.fireTaskEvent(eventType, eventOptions);
	};

	BX.Tasks.Component.TaskView.prototype.fireTaskEvent = function(type, options)
	{
		var self = this;

		if (this.parameters.eventTaskUgly != null)
		{
			if (type === 'ADD')
			{
				var top = window.top;
				top.BX.UI.Notification.Center.notify({
					content: BX.message('TASKS_NOTIFY_TASK_CREATED'),
					actions: [{
						title: BX.message('TASKS_NOTIFY_TASK_DO_VIEW'),
						events: {
							click: function(event, balloon, action) {
								balloon.close();
								top.BX.SidePanel.Instance.open(self.parameters.eventTaskUgly.url);
							}
						}
					}]
				});
			}

			BX.Tasks.Util.fireGlobalTaskEvent(
				type,
				{ID: this.parameters.eventTaskUgly.id},
				{STAY_AT_PAGE: (options.STAY_AT_PAGE != false)},
				this.parameters.eventTaskUgly
			);
		}
	};

	BX.Tasks.Component.TaskView.prototype.initImportantButton = function()
	{
		if(this.parameters.can.TASK.ACTION.EDIT)
		{
			BX.bind(this.layout.importantButton, "click", BX.Tasks.passCtx(this.onImportantButtonClick, this));
		}
	};

	BX.Tasks.Component.TaskView.prototype.initCreateButton = function()
	{
		BX.bind(this.layout.createButton, "click", this.onCreateButtonClick.bind(this));

		var paths = this.paths;
		var self = this;

		this.createButtonMenu = [
			{
				text : this.messages.addTask,
				className : "menu-popup-item menu-popup-no-icon",
				href: this.paths.newTask
			},
			{
				text : this.messages.addTaskByTemplate,
				className : "menu-popup-item menu-popup-no-icon menu-popup-item-submenu",
				cacheable: true,
				events:
				{
					onSubMenuShow: function()
					{
						if (this.subMenuLoaded)
						{
							return;
						}

						var submenu = this.getSubMenu();
						submenu.removeMenuItem("loading");

						BX.ajax.runComponentAction('bitrix:tasks.templates.list', 'getList', {
							mode: 'class',
							data: {
								select: ['ID', 'TITLE'],
								order: {ID: 'DESC'},
								filter: {ZOMBIE: 'N'}
							}
						}).then(
							function(response)
							{
								this.subMenuLoaded = true;

								var tasksTemplateUrlTemplate = paths.newTask + (paths.newTask.indexOf('?') !== -1? '&' : '?') + 'TEMPLATE=';

								var subMenu = [];
								if (response.data.length > 0)
								{
									BX.Tasks.each(response.data, function(item, k)
									{
										subMenu.push({
											text: BX.util.htmlspecialchars(item.TITLE),
											href: tasksTemplateUrlTemplate + item.ID
										});
									}.bind(this));
								}
								else
								{
									subMenu.push({text: self.messages.tasksAjaxEmpty});
								}
								this.addSubMenu(subMenu);
								this.showSubMenu();
							}.bind(this),
							function(response)
							{
								this.subMenuLoaded = true;

								this.addSubMenu([
									{text: self.messages.tasksAjaxErrorLoad}
								]);

								this.showSubMenu();
							}.bind(this)
						);
					}
				},
				items: [
					{
						id: "loading",
						text: "TASKS_AJAX_LOAD_TEMPLATES"
					}
				]
			},


			{
				delimiter:true
			},

			{
				text : this.messages.addSubTask,
				className : "menu-popup-item menu-popup-no-icon",
				href: this.paths.newSubTask
			},
			{
				delimiter:true
			},
			{
				text : this.messages.listTaskTemplates,
				className : "menu-popup-item menu-popup-no-icon",
				href: this.paths.taskTemplates,
				target: '_top'
			}
		];
	};

	BX.Tasks.Component.TaskView.prototype.initFooterButtons = function()
	{
		BX.bind(this.layout.saveButton, "click", this.onSaveButtonClick.bind(this));
		BX.bind(this.layout.cancelButton, "click", this.onCancelButtonClick.bind(this));
	};

	BX.Tasks.Component.TaskView.prototype.onSaveButtonClick = function()
	{
		if (this.isSaving)
		{
			return;
		}

		this.isSaving = true;

		BX.Tasks.CheckListInstance.activateLoading();
		BX.Tasks.CheckListInstance.onSave();

		this.saveCheckList();
	};

	BX.Tasks.Component.TaskView.prototype.saveCheckList = function()
	{
		var self = this;
		var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();

		BX.ajax.runComponentAction('bitrix:tasks.widget.checklist.new', 'saveChecklist', {
			mode: 'class',
			data: {
				taskId: this.taskId,
				items: treeStructure.getRequestData(),
				params: {
					analyticsData: Object.assign(this.analyticsData, {
						checklistCount: treeStructure.getDescendantsCount()
					})
				}
			}
		}).then(
			function(response)
			{
				var data = response.data;
				var preventCheckListSave = data.PREVENT_CHECKLIST_SAVE;

				if (preventCheckListSave)
				{
					var popup = new BX.PopupWindow({
						titleBar: 'Warning',
						content: preventCheckListSave,
						closeIcon: false,
						buttons: [
							new BX.PopupWindowButton({
								className: 'popup-window-button',
								text: 'OK',
								events: {
									click: function() {
										popup.close();
										BX.Tasks.CheckListInstance.deactivateLoading();
										BX.removeClass(BX('saveButton'), 'ui-btn-wait');
									}
								}
							})
						],
						events: {
							onPopupClose: function()
							{
								this.destroy();
							}
						}
					});
					popup.show();
				}
				else
				{
					var openTime = data.OPEN_TIME;
					var traversedItems = data.TRAVERSED_ITEMS;

					if (traversedItems)
					{
						var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();

						Object.keys(traversedItems).forEach(function(nodeId) {
							var item = treeStructure.findChild(nodeId);
							if (item !== 'undefined' && item.fields.getId() === null)
							{
								item.fields.setId(traversedItems[nodeId].ID);
							}
						});
					}

					if (openTime)
					{
						this.openTime = openTime;
					}
					this.analyticsData = {};

					BX.Tasks.CheckListInstance.saveStableTreeStructure();
					BX.Tasks.CheckListInstance.deactivateLoading();

					self.toggleFooterWrap(false);

					this.isSaving = false;
				}
			}.bind(this)
		).catch(
			function(response)
			{
				if (response.errors)
				{
					BX.Tasks.alert(response.errors);

					this.isSaving = false;
					BX.Tasks.CheckListInstance.deactivateLoading();
				}
			}.bind(this)
		);
	};

	BX.Tasks.Component.TaskView.prototype.onCancelButtonClick = function(e)
	{
		if (this.isSaving)
		{
			return;
		}

		var self = this;
		var popup = new BX.PopupWindow({
			titleBar: BX.message('TASKS_DISABLE_CHANGES_CONFIRMATION_POPUP_HEADER'),
			content: BX.message('TASKS_DISABLE_CHANGES_CONFIRMATION_POPUP_CONTENT'),
			closeIcon: false,
			buttons: [
				new BX.PopupWindowButton({
					text: BX.message('TASKS_DISABLE_CHANGES_CONFIRMATION_POPUP_BUTTON_YES'),
					className: 'popup-window-button-accept',
					events: {
						click: function() {
							popup.close();

							if (BX.Tasks.CheckListInstance !== 'undefined')
							{
								BX.Tasks.CheckListInstance.rerender();
							}

							self.toggleFooterWrap(false);
						}
					}
				}),
				new BX.PopupWindowButton({
					className: 'popup-window-button popup-window-button-link',
					text: BX.message('TASKS_DISABLE_CHANGES_CONFIRMATION_POPUP_BUTTON_NO'),
					events: {
						click: function() {
							popup.close();
						}
					}
				})
			],
			events: {
				onPopupClose: function()
				{
					this.destroy();
				}
			}
		});
		popup.show();
	};

	BX.Tasks.Component.TaskView.prototype.onImportantButtonClick = function(node)
	{
		var priority = BX.data(node, 'priority');
		var newPriority = (priority == 2) ? 1 : 2;

		BX.ajax.runComponentAction('bitrix:tasks.task', 'setPriority', {
			mode: 'class',
			data: {
				taskId: this.taskId,
				priority: newPriority
			}
		}).then(
			function(response)
			{
				BX.data(node, 'priority', newPriority);
				BX.toggleClass(node, 'no');
			}.bind(this)
		).catch(
			function(response)
			{
				if (response.errors)
				{
					BX.Tasks.alert(response.errors);
				}
			}.bind(this)
		);
	};

	BX.Tasks.Component.TaskView.prototype.onCreateButtonClick = function()
	{
		BX.PopupMenu.show(
			"task-detail-create-button",
			this.layout.createButton,
			this.createButtonMenu,
			{
				angle:
					{
						position: "top",
						offset: 40
					}
			}
		);
	};

	BX.Tasks.Component.TaskView.prototype.onTaskEvent = function(type, parameters)
	{
		parameters = parameters || {};
		var data = parameters.task || {};

		if(type == 'UPDATE' && data.ID == this.parameters.taskId)
		{
			if(BX.type.isNotEmptyString(data.REAL_STATUS))
			{
				this.setStatus(data.REAL_STATUS);
			}
		}

		if (type === 'ADD')
		{
			if (this.taskId === parameters.taskUgly.parentTaskId)
			{
				window.location.href = this.paths.taskView;
			}
		}
	};

	BX.Tasks.Component.TaskView.prototype.setStatus = function(status)
	{
		var statusContainer = BX("task-detail-status-below-name");
		if(statusContainer)
		{
			var statusName = BX.message("TASKS_STATUS_" + status);
			statusContainer.innerHTML = statusName.substr(0, 1).toLowerCase()+statusName.substr(1);
		}
	};

	BX.Tasks.Component.TaskView.prototype.initFavorite = function()
	{
		BX.bind(this.layout.favorite, "click", BX.proxy(this.onFavoriteClick, this));
	};

	BX.Tasks.Component.TaskView.prototype.onFavoriteClick = function()
	{
		var action = BX.hasClass(this.layout.favorite, "task-detail-favorite-active") ? "deleteFavorite" : "addFavorite";

		BX.ajax.runComponentAction('bitrix:tasks.task', action, {
			mode: 'class',
			data: {
				taskId: this.taskId
			}
		}).then(
			function(response)
			{
				BX.toggleClass(this.layout.favorite, "task-detail-favorite-active");
			}.bind(this)
		).catch(
			function(response)
			{
				if (response.errors)
				{
					BX.Tasks.alert(response.errors);
				}
			}.bind(this)
		);
	};

	BX.Tasks.Component.TaskView.prototype.initSwitcher = function()
	{
		if (!this.layout.switcher)
		{
			return;
		}

		var tabs = this.layout.switcher.getElementsByClassName("task-switcher");
		var blocks = this.layout.switcher.parentNode.getElementsByClassName("task-switcher-block");
		for (var i = 0; i < tabs.length; i++)
		{
			var tab = tabs[i], tabId = tab.dataset.id;
			var block = blocks[i];
			BX.bind(tab, "click", BX.proxy(this.onSwitch, this));
			this.layout.switcherTabs.push({
				title: tab,
				block: block
			});

			this.listTabIdUploadedContent[tabId] = false;
			switch (tabId)
			{
				case "files":
					BX.addCustomEvent("OnUCAfterRecordAdd", BX.proxy(this.onUCAfterRecordAdd, this));
					break;
			}
		}

		BX.addCustomEvent("TaskElapsedTimeUpdated", BX.proxy(function(a, b, c, totalTime) {
			this.layout.elapsedTime.innerText = BX.Tasks.Util.formatTimeAmount(totalTime.time);
		}, this));
	};

	BX.Tasks.Component.TaskView.prototype.onSwitch = function()
	{
		var currentTitle = BX.proxy_context;
		if (BX.hasClass(currentTitle, "task-switcher-selected"))
		{
			return false;
		}

		switch (currentTitle.dataset.id)
		{
			default:
				this.switchTabStyle(currentTitle);
				break;
			case "files":
				this.getTabContent(currentTitle);
				break;
		}

		return false;
	};

	BX.Tasks.Component.TaskView.prototype.getTabContent = function(tab)
	{
		var tabId = tab.dataset.id;
		if (!this.paramsToLazyLoadTabs.hasOwnProperty(tabId))
		{
			return;
		}

		if (this.listTabIdUploadedContent[tabId])
		{
			this.switchTabStyle(tab);
		}
		else if (tabId === 'files')
		{
			BX.ajax.runComponentAction('bitrix:tasks.task', 'getFiles', {
				mode: 'class',
				data: {
					taskId: this.parameters.taskId
				}
			}).then(
				function(response)
				{
					var data = response.data;
					if (
						data.asset
						&& data.html
						&& BX.type.isNotEmptyString(data.html)
					)
					{
						BX.html(null, data.asset.join(' '))
							.then(function(){
								this.listTabIdUploadedContent[tabId] = true;
								BX("task-"+tabId+"-block").innerHTML = data.html;
								BX.ajax.processScripts(BX.processHTML(data.html).SCRIPT);
								this.switchTabStyle(tab);
							}.bind(this));
					}
				}.bind(this)
			).catch(
				function(response)
				{
					if (response.errors)
					{
						BX.Tasks.alert(response.errors);
					}
				}.bind(this)
			);
		}
	};

	BX.Tasks.Component.TaskView.prototype.switchTabStyle = function(tab)
	{
		for (var i = 0; i < this.layout.switcherTabs.length; i++)
		{
			var title = this.layout.switcherTabs[i].title;
			var block = this.layout.switcherTabs[i].block;
			if (title === tab)
			{
				BX.addClass(title, "task-switcher-selected");
				BX.addClass(block, "task-switcher-block-selected");
			}
			else
			{
				BX.removeClass(title, "task-switcher-selected");
				BX.removeClass(block, "task-switcher-block-selected");
			}
		}
	};

	BX.Tasks.Component.TaskView.prototype.onUCAfterRecordAdd = function(messageId, data)
	{
		if (data.hasOwnProperty("messageFields"))
		{
			var uf = data.messageFields.UF, ufForumMessageDoc;
			if (uf && uf["UF_FORUM_MESSAGE_DOC"])
			{
				ufForumMessageDoc = uf["UF_FORUM_MESSAGE_DOC"];
				if (BX.type.isArray(ufForumMessageDoc.VALUE) && ufForumMessageDoc.VALUE.length)
				{
					this.listTabIdUploadedContent["files"] = false;
					this.setFileCount();
				}
			}
		}
	};

	BX.Tasks.Component.TaskView.prototype.setFileCount = function()
	{
		BX.ajax.runComponentAction('bitrix:tasks.task', 'getFileCount', {
			mode: 'class',
			data: {
				taskId: this.taskId
			}
		}).then(
			function(response)
			{
				if (response.data.fileCount)
				{
					BX.findChildByClassName(
						BX("task-files-switcher"), "task-switcher-text-counter").innerHTML = parseInt(response.data.fileCount);
				}
			}.bind(this)
		).catch(
			function(response)
			{
				if (response.errors)
				{
					BX.Tasks.alert(response.errors);
				}
			}.bind(this)
		);
	};

	BX.Tasks.Component.TaskView.prototype.initViewer = function()
	{
		var fileAreas = ['task-detail-description', 'task-detail-files', 'task-comments-block', 'task-files-block'];
		fileAreas.forEach(function(areaName) {
			var area = BX(areaName);
			if (area)
			{
				var currentTop = (typeof top.BX.viewElementBind === 'function' ? top.BX : BX);
				currentTop.viewElementBind(area, {}, function(node) {
					return BX.type.isElementNode(node)
						&& (node.getAttribute('data-bx-viewer') || node.getAttribute('data-bx-image'));
				});
			}
		});
	};

	BX.Tasks.Component.TaskView.prototype.initAjaxErrorHandler = function()
	{
		BX.addCustomEvent("TaskAjaxError", function(errors) {
			BX.Tasks.alert(errors).then(function(){
				BX.reload();
			});
		});
	};

	BX.Tasks.Component.TaskView.prototype.toggleFooterWrap = function(show)
	{
		var footer = BX('footerWrap');
		var saveButton = BX('saveButton');

		var classWait = 'ui-btn-wait';
		var classActive = 'task-footer-wrap-active';

		if (show)
		{
			if (!BX.hasClass(footer, classActive))
			{
				BX.addClass(footer, classActive);
			}

			this.checkListChanged = true;
			this.showCloseConfirmation = true;
		}
		else
		{
			if (BX.hasClass(footer, classActive))
			{
				BX.removeClass(footer, classActive);
			}

			BX.removeClass(saveButton, classWait);

			this.checkListChanged = false;
			this.showCloseConfirmation = false;
		}
	};

	BX.Tasks.Component.TaskView.prototype.initCommentActionController = function()
	{
		if (window.top !== window && window.BX.Tasks.CommentActionController)
		{
			window.top.BX.Tasks.CommentActionController = window.BX.Tasks.CommentActionController;
		}
		if (BX.Tasks.CommentActionController)
		{
			void BX.Tasks.CommentActionController.init({
				workHours: this.parameters.workHours,
				workSettings: this.parameters.workSettings
			});
		}
	};

}).call(this);