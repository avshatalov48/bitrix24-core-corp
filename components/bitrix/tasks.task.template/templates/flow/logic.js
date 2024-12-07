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
			code: 'task-template-edit'
		},
		methods: {
			bindEvents: function()
			{
				this.analyticsData = {};

				// form events
				this.bindControl('form', 'submit', BX.delegate(this.onSubmitClick, this));

				this.bindControl('to-checklist', 'click', BX.delegate(this.onToCheckListClick, this));
				BX.bind(BX('templateEditPopupMenuOptions'), 'click', this.createTemplateMenu.bind(this));

				BX.Tasks.Util.hintManager.bindHelp(this.scope());

				this.bindNestedControls();

				// show alert on ajax errors
				BX.addCustomEvent("TaskAjaxError", function(errors)
				{
					BX.Tasks.alert(errors);
				});

				BX.Event.EventEmitter.subscribe('BX.Tasks.CheckListItem:CheckListChanged', function(eventData) {
					var action = eventData.data.action;
					var allowedActions = ['addAccomplice', 'fileUpload', 'tabIn'];

					if (BX.util.in_array(action, allowedActions))
					{
						this.analyticsData[action] = 'Y';
					}

					BX('checklistAnalyticsData').value = Object.keys(this.analyticsData).join(',');
				}.bind(this));
			},

			processEditorInit: function()
			{
				if(!this.isEditMode())
				{
					// editor events
					BX.ready(BX.delegate(function(){

						var handler = BX.delegate(this.onFormKeyDown, this);

						BX.bind(
							document,
							'keydown',
							handler
						);

						var editorId = this.option('id');
						var editor = BXHtmlEditor.Get(editorId);

						if(editor) // already initialized
						{
							this.bindEditorEvents(editor, handler);
							this.setFocusOnTitle(editor, handler);
						}
						else
						{
							BX.addCustomEvent(
								window,
								'OnEditorInitedAfter',
								BX.delegate(function(eventEditor){

									if(eventEditor != null && eventEditor.id == editorId)
									{
										this.bindEditorEvents(eventEditor, handler);
										this.setFocusOnTitle(editor, handler);
									}
								}, this)
							);
						}

					}, this));
				}
			},

			bindEditorEvents: function(editor, handler)
			{
				// to make form hotkeys work even if focus is in editor
				BX.addCustomEvent(editor, 'OnIframeKeyup', handler);
				BX.addCustomEvent(editor, 'OnTextareaKeyup', handler);
			},

			bindNestedControls: function()
			{
				// frame events
				BX.Tasks.Util.Dispatcher.bindEvent(this.id()+'-frame', 'block-toggle', this.onFrameBlockToggle.bind(this));
			},

			onFrameBlockToggle: function(name, way)
			{
				// pre-open checklist add form on empty checklist
				if (name === 'se_checklist' && way)
				{
					var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
					if (treeStructure.getDescendantsCount() === 0)
					{
						BX.Tasks.CheckListInstance.addCheckList().then(function(newCheckList) {
							newCheckList.addCheckListItem();
						});
					}
				}
			},

			setFocusOnTitle: function(editor)
			{
				setTimeout(function(){

					var input = this.control('title');

					if(input)
					{
						editor.Focus(false);
						input.focus();
						input.selectionStart = input.value.length;
						BX.focus();
					}
				}.bind(this), 500);
			},

			isFlagEnabled: function(node)
			{
				return node.checked;
			},

			isEditMode: function()
			{
				return this.option('template').ID > 0;
			},

			onSubmitClick: function(e)
			{
				e = e || window.event;

				if(this.vars.submitting)
				{
					BX.PreventDefault(e);
					return;
				}

				var csrf = this.control('csrf');
				if(csrf)
				{
					csrf.value = BX.bitrix_sessid(); // prevent sending expired csrf
				}

				BX.Tasks.Util.Dispatcher.call(this.id()+'-frame', 'showLoader', {IFRAME: 'Y'});
				this.vars.submitting = true;
			},

			onToCheckListClick: function()
			{
				var hintPopup = new BX.PopupWindow({
					bindElement: this.control('to-checklist'),
					content: BX.message('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_TO_CHECKLIST_HINT'),
					className: "tasks-to-checklist-popup",
					darkMode: true,
					autoHide: true,
					closeByEsc: true,
					angle: true,
					offsetLeft: this.control('to-checklist').offsetWidth / 2,
					events: {
						onPopupClose: function()
						{
							this.destroy();
						}
					}
				});
				var text = '';
				var container = document.getElementsByClassName('bx-html-editor')[0];
				var isBbCode = container.querySelector('.bxhtmled-iframe-cnt').style.display === 'none';

				if (isBbCode)
				{
					var textArea = container.querySelector('.bxhtmled-textarea');
					var start = textArea.selectionStart;
					var end = textArea.selectionEnd;

					text = textArea.value.substring(start, end);
				}
				else
				{
					var editor = container.querySelector('.bx-editor-iframe').contentDocument;
					if (editor.getSelection)
					{
						text = editor.getSelection().toString();
					}
					else if (editor.selection)
					{
						text = editor.selection.createRange().text;
					}
				}

				if (text !== '')
				{
					var titles = text.split(/\r\n|\r|\n/g);
					if (titles.length > 0)
					{
						var menu = new BX.PopupMenuWindow({
							bindElement: this.control('to-checklist'),
							items: this.getToCheckListPopupMenuItems(titles),
						});

						menu.show();
					}
					else
					{
						hintPopup.show();

						setTimeout(function() {
							hintPopup.close();
						}, 2000);
					}
				}
				else
				{
					hintPopup.show();

					setTimeout(function() {
						hintPopup.close();
					}, 2000);
				}
			},

			getToCheckListPopupMenuItems: function(titles)
			{
				var self = this;
				var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();

				var popupMenuItems = [
					{
						text: "+ " + BX.message('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_TO_CHECKLIST_ADD_NEW_CHECKLIST'),
						onclick: function(event, item)
						{
							item.getMenuWindow().close();

							var items = self.getCheckListItemsFromTitles(titles);

							BX.Tasks.CheckListInstance.addCheckList().then(function(newCheckList) {
								items.forEach(function(item) {
									newCheckList.addCheckListItem(item);
								});
								newCheckList.handleTaskOptions();

								BX('checklistFromDescription').value = 'fromDescription';
							});

							var checkListContainer = self.scope().querySelector('.task-checklist-container');
							if (BX.hasClass(checkListContainer, 'invisible'))
							{
								BX.Tasks.Util.fadeSlideToggleByClass(checkListContainer, 400);
							}
						}
					},
					{ delimiter: true }
				];

				treeStructure.getDescendants().forEach(function(descendant) {
					popupMenuItems.push({
						text: descendant.fields.getTitle(),
						onclick: function(event, item) {
							item.getMenuWindow().close();

							var items = self.getCheckListItemsFromTitles(titles);

							items.forEach(function(item) {
								descendant.addCheckListItem(item);
							});
							items[0].handleTaskOptions();

							BX('checklistFromDescription').value = 'fromDescription';

							var checkListContainer = self.scope().querySelector('.task-checklist-container');
							if (BX.hasClass(checkListContainer, 'invisible'))
							{
								BX.Tasks.Util.fadeSlideToggleByClass(checkListContainer, 400);
							}
						}
					});
				});

				return popupMenuItems;
			},

			getCheckListItemsFromTitles: function(titles)
			{
				var items = [];

				titles.forEach(function(title) {
					var parsedTitle = BX.util.htmlspecialchars(title.trim());
					if (parsedTitle.length > 0)
					{
						var newCheckListItem = new BX.Tasks.CheckListItem({TITLE: parsedTitle});
						items.push(newCheckListItem);
					}
				});

				return items;
			},

			createTemplateMenu: function()
			{
				var menuItemsList = [
					{
						delimiter: true,
						text: BX.message("TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_POPUP_MENU_CHECKLIST_SECTION")
					}
				];

				menuItemsList.push({
					tabId: "showCompleted",
					text: BX.message("TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_POPUP_MENU_SHOW_COMPLETED"),
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
					text: BX.message("TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_POPUP_MENU_SHOW_ONLY_MINE"),
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
					BX('templateEditPopupMenuOptions'),
					menuItemsList,
					{
						closeByEsc: true,
						offsetLeft: BX('templateEditPopupMenuOptions').getBoundingClientRect().width / 2,
						angle: true
					}
				);

				menu.popupWindow.show();
			},

			getInstanceDispatcher: function(code)
			{
				return BX.Tasks.Util.Dispatcher.find(this.id()+'-'+code);
			}
		}
	});

}).call(this);