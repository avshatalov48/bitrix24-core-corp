'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksTaskTemplate != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksTaskTemplate = BX.Tasks.Component.extend({
		sys: {
			code: 'task-template-view'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);
				this.initFileView();

				this.checkListChanged = false;
				this.showCloseConfirmation = false;
				this.analyticsData = {};
			},

			bindEvents: function()
			{
				BX.Tasks.Util.Dispatcher.bindEvent(this.id()+'-buttons', 'button-click', this.onButtonClick.bind(this));

				if(this.option('can').edit)
				{
					// tag update
					BX.addCustomEvent("onTaskTagSelect", BX.proxy(this.syncTags, this));

					// importance update
					this.bindControl('importance-switch', 'click', BX.Tasks.passCtx(this.onImportantButtonClick, this));
				}

				BX.bind(BX('saveButton'), 'click', this.onSaveButtonClick.bind(this));
				BX.bind(BX('cancelButton'), 'click', this.onCancelButtonClick.bind(this));
				BX.bind(BX('templateViewPopupMenuOptions'), 'click', this.createTemplateMenu.bind(this));
				BX.bind(BX('subTemplateAdd'), 'click', this.onSubTemplateAddClick.bind(this));

				// show alert on ajax errors, reload page then
				BX.addCustomEvent("TaskAjaxError", function(errors) {
					BX.Tasks.alert(errors).then(function() {
						BX.reload();
					});
				});
				BX.addCustomEvent('SidePanel.Slider:onClose', this.onSliderClose.bind(this));

				BX.Event.EventEmitter.subscribe('BX.Tasks.CheckListItem:CheckListChanged', function(eventData) {
					var action = eventData.data.action;
					var allowedActions = ['addAccomplice', 'fileUpload', 'tabIn'];

					if (BX.util.in_array(action, allowedActions))
					{
						this.analyticsData[action] = 'Y';
					}

					this.toggleFooterWrap(true);
				}.bind(this));
			},

			onSliderClose: function(event)
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

				if (!this.showCloseConfirmation)
				{
					this.showCloseConfirmation = true;
					return;
				}

				event.denyAction();
				this.showChecklistCloseSliderPopup(checkListSlider);
			},

			showChecklistCloseSliderPopup: function(checkListSlider)
			{
				if (!this.checklistCloseSliderPopup)
				{
					this.checklistCloseSliderPopup = new BX.PopupWindow({
						titleBar: BX.message('TASKS_TTV_CLOSE_SLIDER_CONFIRMATION_POPUP_HEADER'),
						content: BX.message('TASKS_TTV_CLOSE_SLIDER_CONFIRMATION_POPUP_CONTENT'),
						closeIcon: false,
						buttons: [
							new BX.PopupWindowButton({
								text: BX.message('TASKS_TTV_CLOSE_SLIDER_CONFIRMATION_POPUP_BUTTON_CLOSE'),
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
								text: BX.message('TASKS_TTV_CLOSE_SLIDER_CONFIRMATION_POPUP_BUTTON_CANCEL'),
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
			},

			onImportantButtonClick: function(node)
			{
				var priority = this.option('data').PRIORITY;
				var newPriority = priority == 2 ? 1 : 2;

				BX.ajax.runComponentAction('bitrix:tasks.task.template', 'setPriority', {
					mode: 'class',
					data: {
						templateId: this.option('data').ID,
						priority: newPriority
					}
				}).then(
					function(response)
					{
						if (
							!response.status
							|| response.status !== 'success'
						)
						{
							this.isSaving = false;
							return;
						}
						this.option('can').PRIORITY = newPriority;
						BX.toggleClass(node, 'no');
					}.bind(this),
					function(response)
					{

					}.bind(this)
				);
			},

			syncTags: function(tags)
			{
				tags = tags || [];
				if(tags.length)
				{
					var tmpTags = [];
					BX.Tasks.each(tags, function(tag){
						tmpTags.push({NAME: tag.name});
					});
					tags = tmpTags;
				}

				BX.ajax.runComponentAction('bitrix:tasks.task.template', 'setTags', {
					mode: 'class',
					data: {
						templateId: this.option('data').ID,
						tags: tags
					}
				}).then(
					function(response)
					{

					}.bind(this),
					function(response)
					{

					}.bind(this)
				);
			},

			onButtonClick: function(code)
			{
				if (code === 'DELETE')
				{
					BX.ajax.runComponentAction('bitrix:tasks.task.template', 'delete', {
						mode: 'class',
						data: {
							templateId: this.option('data').ID
						}
					}).then(
						function(response)
						{
							if (
								!response.status
								|| response.status !== 'success'
							)
							{
								return;
							}

							BX.UI.Notification.Center.notify({
								content: BX.message('TASKS_NOTIFY_TASK_DELETED')
							});

							window.location = this.option('backUrl');
						}.bind(this),
						function(response)
						{

						}.bind(this)
					);
				}
			},

			initFileView: function()
			{
				// "task-detail-description", "task-detail-files", "task-comments-block", "task-files-block"
				if(!this.option('publicMode'))
				{
					BX.Tasks.each(this.controlAll('file-area'), function(area){

						top.BX.viewElementBind(
							area,
							{},
							function(node){
								return BX.type.isElementNode(node) &&
									(node.getAttribute("data-bx-viewer") || node.getAttribute("data-bx-image"));
							}
						);

					});
				}
			},

			onSaveButtonClick: function()
			{
				if (this.isSaving)
				{
					return;
				}

				this.isSaving = true;
				BX.Tasks.CheckListInstance.activateLoading();

				this.saveCheckList();
			},

			onSubTemplateAddClick: function(e)
			{
				if (
					this.option('auxData').TASK_LIMIT_EXCEEDED
					|| this.option('auxData').TEMPLATE_SUBTASK_LIMIT_EXCEEDED
				)
				{
					e.preventDefault();
					BX.UI.InfoHelper.show('limit_tasks_templates_subtasks', {
						isLimit: true,
						limitAnalyticsLabels: {
							module: 'tasks',
							source: 'templateView'
						}
					});
				}
			},

			saveCheckList: function()
			{
				var self = this;
				var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();

				BX.ajax.runComponentAction('bitrix:tasks.task.template', 'saveChecklist', {
					mode: 'class',
					data: {
						templateId: this.option('data').ID,
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
						if (
							!response.status
							|| response.status !== 'success'
						)
						{
							this.isSaving = false;
							return;
						}

						var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
						var traversedItems = response.data.TRAVERSED_ITEMS;

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

						this.analyticsData = {};

						self.toggleFooterWrap(false);

						this.isSaving = false;
					}.bind(this),
					function(response)
					{
						this.isSaving = false;
					}.bind(this)
				);
			},

			onCancelButtonClick: function()
			{
				if (this.isSaving)
				{
					return;
				}

				var self = this;
				var popup = new BX.PopupWindow({
					titleBar: BX.message('TASKS_TTV_DISABLE_CHANGES_CONFIRMATION_POPUP_HEADER'),
					content: BX.message('TASKS_TTV_DISABLE_CHANGES_CONFIRMATION_POPUP_CONTENT'),
					closeIcon: false,
					buttons: [
						new BX.PopupWindowButton({
							text: BX.message('TASKS_TTV_DISABLE_CHANGES_CONFIRMATION_POPUP_BUTTON_YES'),
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
							text: BX.message('TASKS_TTV_DISABLE_CHANGES_CONFIRMATION_POPUP_BUTTON_NO'),
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
			},

			toggleFooterWrap: function(show)
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
			},

			createTemplateMenu: function()
			{
				var menuItemsList = [
					{
						delimiter: true,
						text: BX.message('TASKS_TEMPLATE_POPUP_MENU_CHECKLIST_SECTION')
					}
				];

				menuItemsList.push({
					tabId: "showCompleted",
					text: BX.message("TASKS_TEMPLATE_POPUP_MENU_SHOW_COMPLETED"),
					className: "menu-popup-item-accept",
					onclick: function(event, item)
					{
						item.getMenuWindow().close();

						if (typeof BX.Tasks.CheckListInstance !== 'undefined')
						{
							BX.toggleClass(item.layout.item, 'menu-popup-item-accept');

							var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
							var optionManager = treeStructure.optionManager;

							optionManager.setShowCompleted(!optionManager.getShowCompleted());
							treeStructure.handleTaskOptions();
						}
					}
				});

				menuItemsList.push({
					tabId: "showOnlyMine",
					text: BX.message("TASKS_TEMPLATE_POPUP_MENU_SHOW_ONLY_MINE"),
					className: "manu-popup-item",
					onclick: function(event, item)
					{
						item.getMenuWindow().close();

						if (typeof BX.Tasks.CheckListInstance !== 'undefined')
						{
							BX.toggleClass(item.layout.item, 'menu-popup-item-accept');

							var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
							var optionManager = treeStructure.optionManager;

							optionManager.setShowOnlyMine(!optionManager.getShowOnlyMine());
							treeStructure.handleTaskOptions();
						}
					}
				});

				var menu = BX.PopupMenu.create(
					"templatePopupMenuOptions",
					BX('templateViewPopupMenuOptions'),
					menuItemsList,
					{
						closeByEsc: true,
						offsetLeft: BX('templateViewPopupMenuOptions').getBoundingClientRect().width / 2,
						angle: true
					}
				);

				menu.popupWindow.show();
			}
		}
	});

}).call(this);