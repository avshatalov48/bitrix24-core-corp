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
				if (this.option('isNewUserResponsible'))
				{
					this.vars.prevResponsibles = [this.option('currentUser')];
				}
				this.vars.parentType = ((!parseInt(this.option('template').BASE_TEMPLATE_ID) && parseInt(this.option('template').PARENT_ID))? 'task' : 'template');
				this.vars.currentLock = false;
				this.vars.taskLimitExceeded = this.option('taskLimitExceeded');
				this.vars.templateTaskRecurrentLimitExceeded = this.option('templateTaskRecurrentLimitExceeded');

				this.analyticsData = {};

				//this.processEditorInit(); // todo: deal with IT!

				// all flag togglers (checkboxes), like MATCH_WORK_TIME, REPLICATE, etc...
				this.bindDelegateControl('flag', 'click', this.passCtx(this.onToggleFlag));

				// footer pin
				this.bindDelegateControl('pin-footer', 'click', BX.delegate(this.onPinFooterClick, this));

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

			toggleReplicationLock: function(way)
			{
				var replFlagNode = this.control('flag-replication');

				if(way)
				{
					BX.Tasks.Util.disable(replFlagNode);
					if(this.isFlagEnabled(replFlagNode))
					{
						this.onToggleFlag(replFlagNode);
						replFlagNode.checked = false;
					}
				}
				else
				{
					BX.Tasks.Util.enable(replFlagNode);
				}

				BX.data(this.control('hint-replication'), 'hint-enabled', way);
			},

			toggleBaseTemplateLock: function(way)
			{
				var templateButton = this.control('hint-base-template');

				if(way)
				{
					// "click" on tasks
					this.onParentTypeClick(this.control('parent-type-option-task'));
					BX.addClass(templateButton, 'disabled');
				}
				else
				{
					BX.removeClass(templateButton, 'disabled');
				}

				BX.data(templateButton, 'hint-enabled', way);
			},

			switchOption: function(name, way)
			{
				BX.Tasks.Util.Dispatcher.find(this.id()+'-options').then(function(ctrl){

					ctrl.switchOption(name, way);

				}.bind(this));
			},

			toggleTypeNewLock: function(way)
			{
				if(!way && this.isEditMode())
				{
					return; // cant switch TPARAM_TYPE_NEW for existing template
				}

				this.switchOption('TPARAM_TYPE', !way);
			},

			solveFieldOpLock: function(field, value)
			{
				// reactive programming, where are you?
				if(this.vars.currentLock != false && this.vars.currentLock != field)
				{
					return;
				}

				var unLockAll = function()
				{
					this.toggleReplicationLock(false);
					this.toggleBaseTemplateLock(false);
					this.toggleTypeNewLock(false);

					this.vars.currentLock = false;
				}.bind(this);

				if(field == 'TPARAM_TYPE')
				{
					if(value == 1)
					{
						this.toggleReplicationLock(true);
						this.toggleBaseTemplateLock(true);

						this.vars.currentLock = field;
					}
					else
					{
						unLockAll();
					}
				}
				else if(field == 'REPLICATE')
				{
					if(value)
					{
						this.toggleTypeNewLock(true);
						this.toggleBaseTemplateLock(true);

						this.vars.currentLock = field;
					}
					else
					{
						unLockAll();
					}
				}
				else if(field == 'BASE_TEMPLATE_ID')
				{
					if(value)
					{
						this.toggleReplicationLock(true);
						this.toggleTypeNewLock(true);

						this.vars.currentLock = field;
					}
					else
					{
						unLockAll();
					}
				}
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

				// multiple responsibe hint
				BX.Tasks.Util.Dispatcher.bindEvent(this.id()+'-responsible', 'change', this.onResponsibleChange.bind(this));

				// option panel
				BX.Tasks.Util.Dispatcher.bindEvent(this.id()+'-options', 'toggle', this.processToggleFlag.bind(this));

				// parent task\template switch
				this.bindDelegateControl('parent-type-option', 'click', this.passCtx(this.onParentTypeClick));
				// on parent selector change we change values of parent inputs
				this.getInstanceDispatcher('parent').then(function(ctrl){
					this.subInstance('parent', ctrl);
					ctrl.bindEvent('change', this.onParentChange.bind(this));
				}.bind(this));

				// dates block
				this.getDeadline(); // just init
				this.getStartDate().bindEvent('change', function(value){
					this.updateEndDatePlan(value, this.getDuration().getValue());
				}.bind(this));
				this.getDuration().bindEvent('change', function(value){
					this.updateEndDatePlan(this.getStartDate().getValue(), value);
				}.bind(this));
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

			onResponsibleChange: function()
			{
				BX.Tasks.Util.Dispatcher.find(this.id()+'-responsible').then(function(ctrl){

					if(ctrl.count() > 1)
					{
						BX.Tasks.Util.hintManager.showDisposable(
							ctrl.scope(),
							BX.message('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_MULTIPLE_RESPONSIBLE_NOTICE'),
							'TASK_EDIT_MULTIPLE_RESPONSIBLES'
						);
					}
					else
					{
						BX.Tasks.Util.hintManager.hide('TASK_EDIT_MULTIPLE_RESPONSIBLES');
					}

				}.bind(this));
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

			onToggleFlag: function(node)
			{
				var target = BX.data(node, 'target');
				if(typeof target != 'undefined' && BX.type.isNotEmptyString(target))
				{
					var flagNode = this.control(target);
					var flagName = BX.data(node, 'flag-name');

					var yesValue = BX.data(node, 'yes-value') || 'Y';
					var noValue = BX.data(node, 'no-value') || 'N';

					if(BX.type.isElementNode(flagNode))
					{
						flagNode.value = node.checked ? yesValue : noValue;
					}

					if (
						flagName === 'REPLICATE'
						&& node.checked
						&&
						(
							this.vars.taskLimitExceeded
							|| this.vars.templateTaskRecurrentLimitExceeded
						)
					)
					{
						flagNode.value = 'N';
						node.checked = false;
						BX.UI.InfoHelper.show('limit_tasks_recurring_tasks', {
							isLimit: true,
							limitAnalyticsLabels: {
								module: 'tasks',
								source: 'templateEdit'
							}
						});
						return;
					}

					this.processToggleFlag(flagName, flagNode.value == yesValue);
				}
			},

			processToggleFlag: function(name, value)
			{
				if (name == 'REPLICATE')
				{
					if (
						(
							!this.vars.taskLimitExceeded
							&& !this.vars.templateTaskRecurrentLimitExceeded
						)
						||
						(
							(
								this.vars.taskLimitExceeded
								|| this.vars.templateTaskRecurrentLimitExceeded
							)
							&& !value
						)
					)
					{
						BX.Tasks.Util.fadeSlideToggleByClass(this.control('replication-panel'));
						this.solveFieldOpLock('REPLICATE', value);
					}
				}
				else if(name == 'ALLOW_TIME_TRACKING')
				{
					BX.Tasks.Util.fadeToggleByClass(this.control('timeman-estimate-time'), 200);
				}
				else if(name == 'TPARAM_TYPE')
				{
					BX.Tasks.Util.Dispatcher.find(this.id()+'-responsible').then(function(ctrl)
					{
						// replace responsible with a pseudo-user
						if(value)
						{
							this.vars.prevResponsibles = ctrl.export();
							ctrl.replaceAll([{
								ID: 0,
								NAME: BX.message('TASKS_COMMON_NEW_USER'),
								entityType: 'U',
								URL: 'javascript:void(0);'
							}]);
							ctrl.readOnly(true);
						}
						else
						{
							ctrl.replaceAll(this.vars.prevResponsibles);
							this.vars.prevResponsibles = null;
							ctrl.readOnly(false);
						}

					}.bind(this));

					this.solveFieldOpLock('TPARAM_TYPE', value);
				}
				else if (name === 'MATCH_WORK_TIME')
				{
					Object.keys(this.instances).forEach(function(instanceKey)
					{
						if (instanceKey === 'deadline' || instanceKey === 'start-date' || instanceKey === 'duration')
						{
							var instance = this.instances[instanceKey];
							instance.updateRealValue(instance.getDisplayValue(), instance.vars.unit);
						}
					}, this);
				}
			},

			isEditMode: function()
			{
				return this.option('template').ID > 0;
			},

			getDeadline: function()
			{
				return this.subInstance('deadline', function(){
					return new BX.Tasks.Component.TasksTaskTemplate.DurationPicker({
						scope: this.control('deadline'),
						unit: this.option('deadline').UNIT,
						displayValue: this.option('deadline').VALUE,
						companyWorkTime: this.option('auxData').COMPANY_WORKTIME
					});
				});
			},

			getStartDate: function()
			{
				return this.subInstance('start-date', function(){
					return new BX.Tasks.Component.TasksTaskTemplate.DurationPicker({
						scope: this.control('start-date'),
						unit: this.option('startDate').UNIT,
						displayValue: this.option('startDate').VALUE,
						companyWorkTime: this.option('auxData').COMPANY_WORKTIME
					});
				});
			},

			getDuration: function()
			{
				return this.subInstance('duration', function(){
					return new BX.Tasks.Component.TasksTaskTemplate.DurationPicker({
						scope: this.control('duration'),
						unit: this.option('duration').UNIT,
						displayValue: this.option('duration').VALUE,
						companyWorkTime: this.option('auxData').COMPANY_WORKTIME
					});
				});
			},

			updateEndDatePlan: function(start, duration)
			{
				this.control('end-date-input').value = start + duration;
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

			onParentChange: function(items)
			{
				var parentTask = this.control('parent-type-task');
				var parentTemplate = this.control('parent-type-template');

				var parentCtrl = this.subInstance('parent');

				var newId = '';
				if(items.length)
				{
					newId = parentCtrl.getIdByValue(items[0]);
				}

				if(this.vars.parentType == 'task')
				{
					parentTemplate.value = '';
					parentTask.value = items.length ? newId : '';
				}
				else if(this.vars.parentType == 'template')
				{
					parentTemplate.value = items.length ? newId : '';
					parentTask.value = '';

					this.solveFieldOpLock('BASE_TEMPLATE_ID', !!parentTemplate.value);
				}
			},

			onParentTypeClick: function(node)
			{
				var type = BX.data(node, 'type');

				if(BX.hasClass(node, 'disabled'))
				{
					return;
				}

				this.setCSSMode('type', type, this.control('parent-type'));
				this.vars.parentType = type;

				this.getInstanceDispatcher('parent').then(function(ctrl){
					// todo: may be save previous?
					ctrl.setTypes(type == 'task' ? {TASK: true} : {TEMPLATE: true});
				}.bind(this));

				// reset both inputs
				this.control('parent-type-task').value = '';
				this.control('parent-type-template').value = '';
			},

			getInstanceDispatcher: function(code)
			{
				return BX.Tasks.Util.Dispatcher.find(this.id()+'-'+code);
			}
		}
	});

	BX.Tasks.Component.TasksTaskTemplate.DurationPicker = BX.Tasks.Util.Widget.extend({
		sys: {
			code: 'dateplanmanager'
		},
		options: {
			controlBind: 'class',
			unit: 'mins',
			displayValue: 0,
			companyWorkTime: false
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.Widget);

				this.bindDelegateControl('unit-setter', 'click', this.passCtx(this.onUnitChange));

				if (typeof this.instances === 'undefined')
				{
					this.instances = {};
				}
				this.instances.calendar = false;

				this.vars.unit = this.option('unit');
				this.vars.displayValue = this.option('displayValue');
				this.vars.companyWorkTime = this.option('companyWorkTime');
				this.vars.realValue = this.calculateRealValue(this.getDisplayValue(), this.getUnit());

				// only digits
				this.bindDelegateControl('display', 'keypress', this.onDisplayKeydown.bind(this));
				this.bindDelegateControl('display', 'input', this.onDisplayInput.bind(this));

				this.bindDelegateControl('display', 'bxchange', this.onDisplayChange.bind(this));
			},

			onUnitChange: function(node)
			{
				var unit = BX.data(node, 'unit');

				if(unit)
				{
					this.setCSSMode('mode-unit-selected', unit);
					this.vars.unit = unit;
					this.updateRealValue(this.getDisplayValue(), unit);
				}
			},

			updateRealValue: function(value, unit)
			{
				value = this.calculateRealValue(value, unit);

				var valueInput = this.control('value');
				if(valueInput)
				{
					valueInput.value = value;
				}

				this.vars.realValue = value;

				this.fireEvent('change', [value]);
			},

			calculateRealValue: function(value, unit)
			{
				value = parseInt(value);
				if(isNaN(value))
				{
					value = 0;
				}

				if(unit == 'days')
				{
					return value * (this.getMatchWorkTime()? this.getWorkDayDuration() : 86400);
				}
				else if(unit == 'hours')
				{
					return value * 3600;
				}
				else
				{
					return value * 60;
				}
			},

			getCalendar: function()
			{
				if (this.instances.calendar === false)
				{
					this.instances.calendar = new BX.Tasks.Calendar(BX.Tasks.Calendar.adaptSettings(this.vars.companyWorkTime));
				}

				return this.instances.calendar;
			},

			getWorkDayDuration: function()
			{
				var duration = this.getCalendar().getWorkDayDuration();
				return (duration > 0? duration / 1000 : 86400);
			},

			getMatchWorkTime: function()
			{
				var elements = document.getElementsByClassName('js-id-wg-optbar-flag-match-work-time');
				if (elements.length)
				{
					return elements[0].checked;
				}

				return false;
			},

			getDisplayValue: function()
			{
				return this.vars.displayValue;
			},

			getValue: function()
			{
				return this.vars.realValue;
			},

			getUnit: function()
			{
				return this.vars.unit;
			},

			onDisplayChange: function(e)
			{
				e = e || window.event;
				var input = e.target;

				if(input.value != this.vars.displayValue)
				{
					this.updateRealValue(input.value, this.getUnit());

					this.vars.displayValue = input.value;
				}
			},

			onDisplayKeydown: function(e)
			{
				e = e || window.event;
				var key = e.which || e.keyCode;

				if (event.charCode && (key < 48 || key > 57)) // char is printable and not a digit
				{
					event.preventDefault();
				}
			},

			onDisplayInput: function(e)
			{
				e = e || window.event;
				var input = e.target;
				input.value = input.value.toString().replace(/[^\d]+/, '');
			}
		}
	});

}).call(this);