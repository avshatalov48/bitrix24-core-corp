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

				this.query = new BX.Tasks.Util.Query({url: "/bitrix/components/bitrix/tasks.task.template/ajax.php"});

				this.checkListChanged = false;
				this.showCloseConfirmation = false;
				this.analyticsData = {};
			},

			bindEvents: function()
			{
				var self = this;

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

				// show alert on ajax errors, reload page then
				BX.addCustomEvent("TaskAjaxError", function(errors) {
					BX.Tasks.alert(errors).then(function() {
						BX.reload();
					});
				});
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
							titleBar: BX.message('TASKS_TTV_CLOSE_SLIDER_CONFIRMATION_POPUP_HEADER'),
							content: BX.message('TASKS_TTV_CLOSE_SLIDER_CONFIRMATION_POPUP_CONTENT'),
							closeIcon: false,
							buttons: [
								new BX.PopupWindowButton({
									text: BX.message('TASKS_TTV_CLOSE_SLIDER_CONFIRMATION_POPUP_BUTTON_CLOSE'),
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
									text: BX.message('TASKS_TTV_CLOSE_SLIDER_CONFIRMATION_POPUP_BUTTON_CANCEL'),
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

			onImportantButtonClick: function(node)
			{
				var priority = this.option('data').PRIORITY;
				var newPriority = priority == 2 ? 1 : 2;

				this.callRemote('task.template.update', {id: this.option('data').ID, data: {
					PRIORITY: newPriority
				}}).then(function(result){
					if(result.isSuccess())
					{
						this.option('can').PRIORITY = newPriority;
						BX.toggleClass(node, 'no');
					}
				}.bind(this));
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

				this.callRemote('task.template.update', {id: this.option('data').ID, data: {
					SE_TAG: tags
				}});
			},

			onButtonClick: function(code)
			{
				if (code == 'DELETE')
				{
					this.callRemote('task.template.delete', {id: this.option('data').ID}, {},
						function()
						{
							BX.UI.Notification.Center.notify({
								content: BX.message('TASKS_NOTIFY_TASK_DELETED')
							});

							window.location = this.option('backUrl');
						}
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

			saveCheckList: function()
			{
				var self = this;
				var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
				var args = {
					items: treeStructure.getRequestData(),
					templateId: this.option('data').ID,
					userId: this.option('data').USER_ID,
					params: {
						analyticsData: Object.assign(this.analyticsData, {
							checklistCount: treeStructure.getDescendantsCount()
						})
					}
				};

				this.query.run('TasksTaskTemplateComponent.saveCheckList', args).then(function(result) {
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

						this.analyticsData = {};

						self.toggleFooterWrap(false);
					}

					this.isSaving = false;
				}.bind(this));

				this.query.execute();
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