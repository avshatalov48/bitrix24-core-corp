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

		this.paramsToLazyLoadTabs = parameters.paramsToLazyLoadTabs || {};
		this.listTabIdUploadedContent = {};

		this.messages = this.parameters.messages || {};
		for (var key in this.messages)
		{
			BX.message[key] = this.messages[key];
		}

		this.paths = this.parameters.paths || {};
		this.createButtonMenu = [];

		this.query = new BX.Tasks.Util.Query({url: "/bitrix/components/bitrix/tasks.task/ajax.php"});
		this.query.bindEvent("executed", BX.proxy(this.onQueryExecuted, this));

		var self = this;
		this.checkListChanged = false;
		this.showCloseConfirmation = false;

		BX.addCustomEvent(window, "tasksTaskEvent", this.onTaskEvent.bind(this));
		BX.addCustomEvent('SidePanel.Slider:onClose', function(event) {
			if (self.checkListChanged && typeof BX.Tasks.CheckListInstance !== 'undefined')
			{
				var checkListSlider = BX.Tasks.CheckListInstance.optionManager.slider;
				if (!checkListSlider || checkListSlider !== event.getSlider())
				{
					return;
				}

				if (!self.showCloseConfirmation)
				{
					self.showCloseConfirmation = true;
					return;
				}

				event.denyAction();

				var popup = new BX.PopupWindow({
					titleBar: BX.message('TASKS_CLOSE_SLIDER_CONFIRMATION_POPUP_HEADER'),
					content: BX.message('TASKS_CLOSE_SLIDER_CONFIRMATION_POPUP_CONTENT'),
					closeIcon: false,
					buttons: [
						new BX.PopupWindowButton({
							text: BX.message('TASKS_CLOSE_SLIDER_CONFIRMATION_POPUP_BUTTON_CLOSE'),
							className: 'popup-window-button-accept',
							events: {
								click: function() {
									self.showCloseConfirmation = false;
									popup.close();
									checkListSlider.close();
								}
							}
						}),
						new BX.PopupWindowButton({
							className: 'popup-window-button popup-window-button-link',
							text: BX.message('TASKS_CLOSE_SLIDER_CONFIRMATION_POPUP_BUTTON_CANCEL'),
							events: {
								click: function() {
									popup.close();
								}
							}
						})
					],
					events: {
						onPopupClose: function() {
							this.destroy();
						}
					}
				});
				popup.show();
			}
		});

		BX.Event.EventEmitter.subscribe(
			'BX.Tasks.CheckListItem:CheckListChanged',
			this.toggleFooterWrap.bind(this, true)
		);

		this.initFavorite();
		this.initCreateButton();
		this.initSwitcher();
		this.initViewer();
		this.initAjaxErrorHandler();
		this.initImportantButton();
		this.initFooterButtons();

		var stayAtPage = parameters.componentData.EVENT_OPTIONS.STAY_AT_PAGE;
		this.fireTaskEvent(stayAtPage);

		this.temporalCommentFix();

		if (
			!!window.mplCheckForQuote
			&& BX('task-detail-author-info')
		)
		{
			BX.bind(BX("task-detail-content"), "mouseup", function(e) { window.mplCheckForQuote(e, e.currentTarget, 'TASK_' + this.taskId, 'task-detail-author-info') }.bind(this));
		}
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

	BX.Tasks.Component.TaskView.prototype.fireTaskEvent = function(STAY_AT_PAGE) {
		var STAY_AT_PAGE = STAY_AT_PAGE != false;
		var self = this;
		if(this.parameters.eventTaskUgly != null)
		{
			if (this.parameters.componentData.EVENT_TYPE == 'ADD')
			{
				window.top.BX.UI.Notification.Center.notify({
					content: BX.message('TASKS_NOTIFY_TASK_CREATED'),
					actions: [{
						title: BX.message('TASKS_NOTIFY_TASK_DO_VIEW'),
						events: {
							click: function(event, balloon, action) {
								balloon.close();
								window.top.BX.SidePanel.Instance.open(self.parameters.eventTaskUgly.url);
							}
						}
					}]

				});
			}

			BX.Tasks.Util.fireGlobalTaskEvent(this.parameters.componentData.EVENT_TYPE, { ID: this.parameters.eventTaskUgly.id }, { STAY_AT_PAGE: STAY_AT_PAGE }, this.parameters.eventTaskUgly);
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

						var query = new BX.Tasks.Util.Query({
							autoExec: true
						});

						var submenu = this.getSubMenu();
						submenu.removeMenuItem("loading");

						query.add(
							'task.template.find',
							{
								parameters: {
									select: ['ID', 'TITLE'],
									order: {ID: 'DESC'},
									filter: {ZOMBIE: 'N'}
								}
							},
							{},
							BX.delegate(function(errors, data)
							{
								this.subMenuLoaded = true;

								if (!errors.checkHasErrors())
								{

									var tasksTemplateUrlTemplate = paths.newTask + (paths.newTask.indexOf('?') !== -1? '&' : '?') + 'TEMPLATE=';

									var subMenu = [];
									if (data.RESULT.DATA.length > 0)
									{
										BX.Tasks.each(data.RESULT.DATA, function(item, k)
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
								}
								else
								{
									this.addSubMenu([
										{text: self.messages.tasksAjaxErrorLoad}
									]);

									this.showSubMenu();
								}
							}, this)
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

		this.saveCheckList();
	};

	BX.Tasks.Component.TaskView.prototype.saveCheckList = function()
	{
		var self = this;
		var args = {
			items: BX.Tasks.CheckListInstance.getTreeStructure().getRequestData(),
			taskId: this.taskId,
			userId: this.userId
		};

		this.query.run('TasksTaskComponent.saveCheckList', args).then(function(result) {
			if (result.isSuccess())
			{
				var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
				var traversedItems = result.getData().TRAVERSED_ITEMS;

				if (traversedItems)
				{
					Object.keys(traversedItems).forEach(function(nodeId) {
						var item = treeStructure.findChild(nodeId);
						if (item !== 'undefined' && item.fields.getId() === null)
						{
							item.fields.setId(traversedItems[nodeId].ID);
						}
					});
				}

				BX.Tasks.CheckListInstance.saveStableTreeStructure();
				BX.Tasks.CheckListInstance.deactivateLoading();

				self.toggleFooterWrap(false);
			}

			this.isSaving = false;
		}.bind(this));

		this.query.execute();
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
		var newPriority = priority == 2 ? 1 : 2;

		this.query.run('task.update', {id: this.parameters.taskId, data: {
			PRIORITY: newPriority
		}}).then(function(result){
			if(result.isSuccess())
			{
				BX.data(node, 'priority', newPriority);
				BX.toggleClass(node, 'no');
			}
		}.bind(this));
		this.query.execute();
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
		var action = BX.hasClass(this.layout.favorite, "task-detail-favorite-active") ? "task.favorite.delete" : "task.favorite.add";

		this.query.deleteAll();
		this.query.add(
			action,
			{
				taskId: this.taskId
			},
			{
				code: action
			}
		);

		this.query.execute();

		BX.toggleClass(this.layout.favorite, "task-detail-favorite-active");
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
		else
		{
			var method = "TasksTaskComponent.get"+tabId.charAt(0).toUpperCase()+tabId.substr(1),
				args = {params: this.paramsToLazyLoadTabs[tabId]};
			this.query.run(method, args).then(function(result) {
				var data = result.getData();
				if (data.html && BX.type.isNotEmptyString(data.html))
				{
					this.listTabIdUploadedContent[tabId] = true;
					BX("task-"+tabId+"-block").innerHTML = data.html;
					BX.ajax.processScripts(BX.processHTML(data.html).SCRIPT);
					this.switchTabStyle(tab);
				}
			}.bind(this));
			this.query.execute();
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
		var args = {
			params: {
				"FORUM_ID": this.paramsToLazyLoadTabs["files"]["FORUM_ID"],
				"FORUM_TOPIC_ID": this.paramsToLazyLoadTabs["files"]["FORUM_TOPIC_ID"]
			}
		};
		this.query.run("TasksTaskComponent.getFileCount", args).then(function(result) {
			var data = result.getData();
			if (data.fileCount)
			{
				BX.findChildByClassName(
					BX("task-files-switcher"), "task-switcher-text-counter").innerHTML = parseInt(data.fileCount);
			}
		}.bind(this));
		this.query.execute();
	};

	BX.Tasks.Component.TaskView.prototype.initViewer = function()
	{
		var fileAreas = ["task-detail-description", "task-detail-files", "task-comments-block", "task-files-block"];

		for (var i = 0; i < fileAreas.length; i++)
		{
			var area = BX(fileAreas[i]);
			if (area)
			{
				top.BX.viewElementBind(
					area,
					{},
					function(node){
						return BX.type.isElementNode(node) &&
							(node.getAttribute("data-bx-viewer") || node.getAttribute("data-bx-image"));
					}
				);
			}
		}
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

}).call(this);