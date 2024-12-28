'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.Task != 'undefined')
	{
		return;
	}

	BX.Tasks.Component.Task = BX.Tasks.Util.Widget.extend({
		options: {
			removeTemplates: false, // temporal, until the bug fixed
			registerDispatcher: true,
			data: {}
		},
		constants: {
			PRIORITY_AVERAGE: 1,
			PRIORITY_HIGH: 2
		},
		sys: {
			code: 'task-edit'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.Widget);

				this.instances.calendar = false;
				this.instances.helpWindow = false;

				this.analyticsData = {};

				this.toggleFlowParams = this.option('toggleFlowParams');
				this.flowParams = this.option('flowParams');

				this.isFlowForm = (this.option('isFlowForm') === true);
				this.isProjectLimitExceeded = (this.option('isProjectLimitExceeded') === true);
				this.projectLimitCode = this.option('projectLimitCode');

				this.taskStatusSummaryEnabled = (this.option('taskStatusSummaryEnabled') === true);
				this.relatedSubTaskDeadlinesEnabled = (this.option('relatedSubTaskDeadlinesEnabled') === true);
				this.taskRecurringEnabled = (this.option('taskRecurringEnabled') === true);

				this.isExtranetUser = (this.option('isExtranetUser') === true);
				this.canEditTask = (this.option('canEditTask') === true);

				this.fireTaskEvent();

				if(this.option('doInit'))
				{
					this.bindEvents();

					this.initFlowSelector();
					this.initReminder();
					this.initProjectDependence();
					this.initProjectPlan();
					this.initState();

					this.clearNewAnalyticsParams();
					this.doSomeTricks();
				}

				this.onTitleChange();
				this.onResponsibleChange();
				this.checkNoWorkDays(this.getTaskData().MATCH_WORK_TIME === 'Y');

				this.calendarSettings = this.option('calendarSettings');

				this.aiCommandExecutor = null;

				this.changingFlow = false;
			},

			initFlowSelector()
			{
				const flowSelectorNode = document.getElementById('tasks-flow-selector-container');
				if (!flowSelectorNode)
				{
					return;
				}

				const selectorParams = {
					taskId: this.getTaskId(),
					canEditTask: this.canEditTask,
					isExtranet: this.isExtranetUser,
					flowParams: this.flowParams,
					toggleFlowParams: this.toggleFlowParams,
				};

				this.flowSelector = new BX.Tasks.Flow.EntitySelector(selectorParams);
				this.flowSelector.show(flowSelectorNode, 'edit');
			},

			getUser: function()
			{
				return this.option('auxData').USER;
			},

			restrictMemberSelectors: function()
			{
				if(this.getUser().IS_SUPER_USER)
				{
					return;
				}

				this.vars.responsible = null;
				this.vars.originator = null;

				BX.Tasks.Util.Dispatcher.find(this.option('id')+'-responsible').then(function(responsible){
					this.vars.responsible = responsible;
					return BX.Tasks.Util.Dispatcher.find(this.option('id') + '-originator');
				}.bind(this)).then(function(originator){

					this.vars.originator = originator;
					var responsible = this.vars.responsible;

					this.vars.responsibleRestrLock = false;
					this.vars.originatorRestrLock = false;

					originator.bindEvent('change', this.restrictResponsible.bind(this));
					responsible.bindEvent('change', this.restrictOriginator.bind(this));

				}.bind(this));
			},

			restrictResponsible: function()
			{
				if(this.vars.responsibleRestrLock)
				{
					return;
				}
				this.vars.originatorRestrLock = true;

				var responsible = this.vars.responsible;
				var originator = this.vars.originator;

				var user = this.getUser().DATA;
				var values = originator.value();
				var valueOrig = false;
				if(typeof values != 'undefined' && typeof values[0] != 'undefined')
				{
					valueOrig = values[0];
				}

				// other originator. then set responsible to current user and make it read-only
				if(valueOrig)
				{
					values = responsible.value();
					var valueResp = false;
					if(typeof values != 'undefined' && typeof values[0] != 'undefined')
					{
						valueResp = values[0];
					}

					if(valueOrig != 'U'+user.ID)
					{
						if(valueResp != user.ID)
						{
							responsible.replaceItem(valueResp, user);
						}
						responsible.readOnly(true);
					}
					else
					{
						responsible.readOnly(false);
					}
				}

				this.vars.originatorRestrLock = false;
			},

			restrictOriginator: function()
			{
				if(this.vars.originatorRestrLock)
				{
					return;
				}
				this.vars.responsibleRestrLock = true;

				var originator = this.vars.originator;
				var responsible = this.vars.responsible;

				if(originator)
				{
					// multiple responsibles. show originator, set to current user and make read-only
					if(responsible.count() > 1)
					{
						var user = this.getUser().DATA;
						var values = originator.value();
						var value = false;
						if(typeof values != 'undefined' && typeof values[0] != 'undefined')
						{
							value = values[0];
						}

						if(value)
						{
							originator.replaceItem(value, user);

							if(BX.hasClass(this.control('originator'), 'invisible'))
							{
								this.toggleBlock('originator');
							}
						}

						originator.readOnly(true);
					}
					else
					{
						originator.readOnly(false);
					}
				}

				this.vars.responsibleRestrLock = false;
			},

			disableHints: function()
			{
				BX.Tasks.Util.hintManager.disableSeveral(this.option('auxData').HINT_STATE);
			},

			fireTaskEvent: function()
			{
				const eType = this.option('componentData').EVENT_TYPE.toString().toUpperCase();
				const task = this.option('data').EVENT_TASK;
				const uglyTask = this.option('data').EVENT_TASK_UGLY;
				const eventOptions = this.option('componentData').EVENT_OPTIONS;

				if (eType && (task || uglyTask))
				{
					if (eType === 'ADD')
					{
						var top = window.top;
						top.BX.UI.Notification.Center.notify({
							content: BX.message('TASKS_NOTIFY_TASK_CREATED'),
							actions: [{
								title: BX.message('TASKS_NOTIFY_TASK_DO_VIEW'),
								events: {
									click: function(event, balloon, action) {
										balloon.close();
										top.BX.SidePanel.Instance.open(uglyTask.url);
									},
								},
							}],
						});

						const analyticsLabels = {
							action: 'taskAdding',
							source: 'addButton',
						};
						if (eventOptions.FIRST_GRID_TASK_CREATION_TOUR_GUIDE)
						{
							analyticsLabels.tourGuide = 'firstGridTaskCreation';
						}

						if (eventOptions.SCOPE)
						{
							analyticsLabels.scope = eventOptions.SCOPE;
						}

						BX.ajax.runAction('tasks.analytics.hit', { analyticsLabel: analyticsLabels });
					}

					BX.Tasks.Util.fireGlobalTaskEvent(eType, task, eventOptions, uglyTask);
				}
			},

			doSomeTricks: function()
			{
				this.disableHints();
				this.replaceCmdBtn();

				// fix replication checkbox when user press "back" button in browser
				var cb = this.control('flag-replication');
				if(cb.checked)
				{
					BX.removeClass(this.control('replication-panel'), 'invisible');
				}
			},

			replaceCmdBtn: function()
			{
				if(BX.browser.IsMac())
				{
					var cmd = this.control('cmd');
					if(cmd)
					{
						cmd.innerHTML = "&#8984;"
					}
				}
			},

			bindEditorEvents: function(editor, handler)
			{
				// to make form hotkeys work even if focus is in editor
				BX.addCustomEvent(editor, 'OnIframeKeyup', handler);
				BX.addCustomEvent(editor, 'OnTextareaKeyup', handler);
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

			isEditMode: function()
			{
				return this.option('template').EDIT_MODE;
			},

			bindEvents: function()
			{
				this.bindAIEvents();
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

						var editorId = this.option('template').ID;
						var editor = BXHtmlEditor.Get(editorId);

						if(editor) // already initialized
						{
							this.bindEditorEvents(editor, handler);
							this.setFocusOnTitle(editor, handler);
							this.setEditorTextFromHash();
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
										this.setEditorTextFromHash();
									}
								}, this)
							);
						}

					}, this));
				}

				// all block togglers
				this.bindDelegateControl('toggler', 'click', this.passCtx(this.onToggleBlock));

				// all flag togglers
				this.bindDelegateControl('flag', 'click', this.passCtx(this.onToggleFlag));

				// all block choosers
				this.bindDelegateControl('chooser', 'click', this.passCtx(this.onChooseBlock));

				// additional
				this.bindDelegateControl('additional-header', 'click', this.passCtx(this.onToggleAdditionalBlock));

				// priority button
				this.bindDelegateControl('priority-cb', 'change', this.passCtx(this.onPriorityChange));

				this.bindDelegateControl('pin-footer', 'click', BX.delegate(this.onPinFooterClick, this));

				this.bindControl('cancel-button', 'click', BX.delegate(this.onCancelButtonClick, this));
				this.bindControl('title', 'keyup', BX.delegate(this.onTitleChange, this));
				this.bindControl('to-checklist', 'click', BX.delegate(this.onToCheckListClick, this));
				this.bindControl('ai-checklist', 'click', BX.delegate(this.onAiCheckListClick, this));

				const elements = this.scope().getElementsByClassName("js-id-wg-optbar-flag-match-work-time");
				if (elements.length)
				{
					BX.bind(elements[0], "change", this.passCtx(this.onWorktimeChange));
				}

				this.bindControl('form', 'submit', BX.delegate(this.onFormSubmit, this));
				this.bindDelegateControl('submit', 'click', this.passCtx(this.onSubmitClick));

				this.bindNestedControls();
				this.bindSliderEvents();

				BX.Event.EventEmitter.subscribe('BX.Tasks.CheckListItem:CheckListChanged', (eventData) => {
					const action = eventData.data.action;
					const allowedActions = ['addAccomplice', 'fileUpload', 'tabIn'];

					if (BX.util.in_array(action, allowedActions))
					{
						this.analyticsData[action] = 'Y';
					}

					BX('checklistAnalyticsData').value = Object.keys(this.analyticsData).join(',');
				});

				BX.Tasks.Util.hintManager.bindHelp(this.control('options'));

				BX.Event.EventEmitter.subscribe(
					'BX.Tasks.MemberSelector:projectSelected',
					BX.delegate(this.onProjectSelected, this)
				);

				BX.Event.EventEmitter.subscribe(
					'BX.Tasks.MemberSelector:projectDeselected',
					BX.delegate(this.onProjectDeselected, this)
				);

				BX.Event.EventEmitter.subscribe(
					'BX.Main.User.SelectorController:itemRendered',
					BX.delegate(this.onItemRendered, this)
				);

				var instance = BX.Tasks.Component.TasksWidgetMemberSelector.getInstance(this.sys.id + '-project');
				var preselectedGroup = instance.getSelector().getDialog().getPreselectedItems();
				if (preselectedGroup.length)
				{
					var groupId = parseInt(preselectedGroup[0][1], 10);

					this.showScrumFields(groupId);

					BX.Event.EventEmitter.emit('BX.Tasks.Component.Task:projectPreselected', { groupId: groupId });
				}
			},

			bindAIEvents: function() {
				BX.addCustomEvent('AI.Copilot:save', function(event) {
					if (event.data.code === 'create_checklist')
					{
						this.onToCheckListClick(event.data.result);
					}
				}.bind(this));
				BX.addCustomEvent('AI.Copilot:add_below', function(event) {
					if (event.data.code === 'create_checklist')
					{
						this.onToCheckListClick(event.data.result);
					}
				}.bind(this));
			},

			bindNestedControls: function()
			{
				// multiple responsibe hint
				BX.Tasks.Util.Dispatcher.bindEvent(this.option('id')+'-responsible', 'change', this.onResponsibleChange.bind(this));
				BX.Tasks.Util.Dispatcher.bindEvent(this.option('id')+'-originator', 'change', this.onOriginatorChange.bind(this));

				this.restrictMemberSelectors();

				// option toggle
				this.getDispatcher().bindEvent('options-'+this.option('id'), 'toggle', this.processToggleFlag.bind(this));
			},

			bindSliderEvents: function()
			{
				BX.addCustomEvent("SidePanel.Slider:onLoad", this.setEditorBeforeUnloadEvent.bind(this, true));
				BX.addCustomEvent("SidePanel.Slider:onClose", this.setEditorBeforeUnloadEvent.bind(this, false));
				BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(function(event) {
					if (event.getEventId() === 'sonetGroupEvent')
					{
						var eventData = event.getData();
						if (
							BX.type.isNotEmptyString(eventData.code)
							&& eventData.code === 'afterCreate'
							&& typeof eventData.data != 'undefined'
							&& typeof eventData.data.group != 'undefined'
						)
						{
							var group = eventData.data.group;
							var instance = BX.Tasks.Component.TasksWidgetMemberSelector.getInstance(this.sys.id + '-project');
							instance.getSelector().onSelectorItemSelected({
								id: group.ID,
								entityType: 'SG',
								networkId: '',
								DISPLAY: BX.util.htmlspecialcharsback(group.FIELDS.NAME)
							});
						}
					}
				}, this));
				BX.addCustomEvent('SidePanel.Slider:onCloseByEsc', function(event) {
					event.denyAction();
				});
			},

			setEditorBeforeUnloadEvent: function(flag)
			{
				var editorId = this.option("template").ID;
				var editor = BXHtmlEditor.Get(editorId);

				if (editor)
				{
					flag ? editor.AllowBeforeUnloadHandler() : editor.DenyBeforeUnloadHandler();
				}
			},

			getTaskData: function()
			{
				return this.option('data').TASK;
			},

			getTaskActions: function()
			{
				return this.getTaskData().ACTION;
			},

			getTaskId: function()
			{
				return this.getTaskData().ID ?? 0;
			},

			getTaskDescription: function()
			{
				return (this.option('data').TASK.DESCRIPTION ?? '').trim();
			},

			initProjectDependence: function()
			{
				var inst = BX.Tasks.Util.Dispatcher.get('projectdependence-'+this.id());

				inst.assignCalendar(this.getCalendar());
				inst.option('task', {data: this.getTaskData()});
				inst.load(
					this.getTaskData().SE_PROJECTDEPENDENCE,
					this.getTaskActions().SE_PROJECTDEPENDENCE
				);
			},

			initProjectPlan: function()
			{
				this.instances.projectPlan = new BX.Tasks.Shared.Form.ProjectPlan({
					scope: this.control('date-plan-manager'),
					parent: this,
					matchWorkTime: (this.getTaskData().MATCH_WORK_TIME === 'Y')
				});
				this.instances.projectPlan.bindEvent('change-deadline', BX.delegate(function(stamp, local) {
					if (!this.isEditMode() && stamp)
					{
						this.showExpiredDeadlineHint(local.getTime());
					}
					// fire event on reminder block, if any
					BX.Tasks.Util.Dispatcher.fireEvent('reminder-' + this.id(), 'setTaskDeadLine', [stamp]);
				}, this));

				if (!this.isEditMode() && this.getTaskData().DEADLINE_ISO)
				{
					this.showExpiredDeadlineHint((new Date(this.getTaskData().DEADLINE_ISO)).getTime());
				}
			},

			showExpiredDeadlineHint: function(deadline)
			{
				if (deadline < Date.now())
				{
					BX.Tasks.Util.hintManager.show(
						this.instances.projectPlan.getDeadlinePicker().control('display'),
						BX.message('TASKS_TASK_COMPONENT_TEMPLATE_HINT_DEADLINE_EXPIRED'),
						null,
						null,
						{autoHide: true}
					);
				}
			},

			initReminder: function()
			{
				var reminder = BX.Tasks.Util.Dispatcher.get('reminder-'+this.id());
				if(reminder !== null)
				{
					reminder.load(
						this.getTaskData().SE_REMINDER,
						this.getTaskActions().SE_REMINDER
					);
					reminder.setTaskId(this.getTaskData().ID);
					reminder.setTaskDeadLine(this.getTaskData().DEADLINE);
				}
			},

			initState: function()
			{
				this.vars.state = BX.clone(this.option('state'));
				this.redrawState();
			},

			onPinFooterClick: function()
			{
				var pinned = !this.vars.state.FLAGS.FORM_FOOTER_PIN;
				var footer = this.control('footer');

				if(footer)
				{
					BX[pinned ? 'addClass' : 'removeClass'](footer, 'pinned');
				}
				this.setState('FLAGS', 'FORM_FOOTER_PIN', false, pinned);
			},

			onPriorityChange: function(node)
			{
				var input = this.control('priority');
				if(BX.type.isElementNode(input))
				{
					input.value = node.checked ? this.PRIORITY_HIGH : this.PRIORITY_AVERAGE;
				}
			},

			onFormSubmit: function()
			{
				var csrf = this.control('csrf');
				if(csrf)
				{
					csrf.value = BX.bitrix_sessid(); // prevent sending expired csrf
				}

				this.vars.submitting = true;
			},

			onSubmitClick: function(node, e)
			{
				if(this.vars.submitting)
				{
					BX.PreventDefault(e);
					return;
				}

				BX.addClass(node, 'ui-btn-clock');

				this.vars.submitting = true;
			},

			onProjectSelected: function(event)
			{
				this.showScrumFields(event.data.ID);
			},

			onItemRendered: function(event)
			{
				const node = document.querySelector('input.tasks-task-temporary-crm-input');
				node && node.remove();
			},

			onProjectDeselected: function(event)
			{
				var unChosenContainer = this.control('unchosen-blocks');
				if (!BX.type.isElementNode(unChosenContainer))
				{
					return;
				}

				var scrumFields = ['EPIC'];

				scrumFields
					.forEach(function (scrumField) {
						var scrumControl = unChosenContainer
							.querySelector('[data-block-name=' + scrumField + ']')
						;
						setTimeout(function () {
							if (parseInt(scrumControl.dataset.groupId, 10) === parseInt(event.data.ID, 10))
							{
								scrumControl.dataset.groupId = 0;
								BX.addClass(scrumControl, 'hidden');
							}
						}, 100)
					})
				;
			},

			showScrumFields: function(groupId)
			{
				BX.ajax.runComponentAction(
					'bitrix:tasks.task',
					'needShowEpicField',
					{
						mode: 'class',
						data: {
							groupId: groupId
						}
					}
				)
					.then(
						function(response)
						{
							var isScrumProject = response.data;

							var unChosenContainer = this.control('unchosen-blocks');
							if (!BX.type.isElementNode(unChosenContainer))
							{
								return;
							}

							var scrumFields = ['EPIC'];

							if (isScrumProject)
							{
								scrumFields
									.forEach(function (scrumField) {
										var scrumControl = unChosenContainer
											.querySelector('[data-block-name=' + scrumField + ']')
										;
										scrumControl.dataset.groupId = groupId;
										BX.removeClass(scrumControl, 'hidden');
									})
								;
							}
							else
							{
								scrumFields
									.forEach(function (scrumField) {
										var scrumControl = unChosenContainer
											.querySelector('[data-block-name=' + scrumField + ']')
										;
										scrumControl.dataset.groupId = groupId;
										BX.addClass(scrumControl, 'hidden');
									})
								;
							}
						}.bind(this)
					)
					.catch(function(response) {}.bind(this))
				;
			},

			submit: function()
			{
				BX.Tasks.CheckListInstance.getTreeStructure().appendRequestLayout();
				this.control('form').submit();
			},

			onFormKeyDown: function(e)
			{
				e = e || window.event;

				var prevent = false;
				if(BX.Tasks.Util.isEnter(e))
				{
					if((e.ctrlKey || e.metaKey) && e.type === 'keydown')
					{
						var tagDialog = BX.UI.EntitySelector.Dialog.getById('tasksTagSelector');
						if (tagDialog && tagDialog.isOpen())
						{
							return;
						}

						this.submit();
						prevent = true;
					}
				}

				if(prevent)
				{
					BX.PreventDefault(e);
				}
			},

			onChooseBlock: function(node)
			{
				var chosenContainer = this.control('chosen-blocks');
				var unChosenContainer = this.control('unchosen-blocks');

				if(!BX.type.isElementNode(chosenContainer) || !BX.type.isElementNode(unChosenContainer))
				{
					return;
				}

				var target = BX.data(node, 'target');
				if(typeof target != 'undefined' && BX.type.isNotEmptyString(target))
				{
					var node = this.control(target);
					var blockName = BX.data(node, 'block-name');

					if(BX.type.isNotEmptyString(blockName) && BX.type.isElementNode(node))
					{
						var stateBlock = this.vars.state['BLOCKS'][blockName];

						if(typeof stateBlock.C != 'undefined')
						{
							var toPin = !stateBlock.C;

							// find block exact place
							var to = this.control(target+'-place', toPin ? chosenContainer : unChosenContainer);
							var from = this.control(target+'-place', toPin ? unChosenContainer : chosenContainer);
							if(to) // if there is an exact place, relocate to it
							{
								if (!toPin)
								{
									var additionalBlock = this.control('additional');
									if (BX.hasClass(additionalBlock, 'hidden'))
									{
										BX.removeClass(additionalBlock, 'hidden');
									}
								}

								BX.Tasks.Util.fadeSlideToggleByClass(from, 200, function(){
									BX.addClass(to, 'invisible');
									BX.append(node, to);
									BX.Tasks.Util.fadeSlideToggleByClass(to, 200);

									BX.removeClass(from, 'invisible');
								});
							}
							else // static block, then just pin it
							{
								BX.toggleClass(node, 'pinned');
							}

							// update state
							this.setState('BLOCKS', blockName, 'C', !stateBlock.C);
						}
					}
				}
			},

			onToggleAdditionalBlock: function(node)
			{
				var opened = BX.hasClass(node, 'opened');
				BX.toggleClass(node, 'opened');

				this.toggleBlock('unchosen-blocks');
			},

			onToggleBlock: function(node)
			{
				const target = BX.data(node, 'target');

				if (typeof target != 'undefined' && BX.type.isNotEmptyString(target))
				{
					// pre-open checklist add form on empty checklist
					if (target === 'checklist')
					{
						var self = this;
						this.toggleBlock(target).then(function() {
							if (!BX.hasClass(self.control(target), 'invisible'))
							{
								var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
								if (treeStructure.getDescendantsCount() === 0)
								{
									BX.Tasks.CheckListInstance.addCheckList().then(function(newCheckList) {
										newCheckList.addCheckListItem();
									});
								}
							}
						});
					}
					else
					{
						this.toggleBlock(target);
					}
				}
			},

			toggleBlock: function(target, duration)
			{
				return BX.Tasks.Util.fadeSlideToggleByClass(this.control(target), duration || 100);
			},

			toggleOption: function(code, way)
			{
				var ctrl = this.getOptionNode(code);
				if(ctrl)
				{
					ctrl.checked = !!way;
					this.onToggleFlag(ctrl);
				}
			},

			switchOption: function(code, way)
			{
				var ctrl = this.getOptionNode(code);
				if(ctrl)
				{
					ctrl.disabled = !!way;
				}
			},

			getOptionNode: function(code)
			{
				code = code.toLowerCase().replace(/_/g, '-');

				return this.control('flag-'+code);
			},

			onToggleFlag: function(node)
			{
				var target = BX.data(node, 'target');
				if (typeof target != 'undefined' && BX.type.isNotEmptyString(target))
				{
					var flagNode = this.control(target);
					var flagName = BX.data(node, 'flag-name');

					if (BX.type.isElementNode(flagNode))
					{
						flagNode.value = node.checked ? 'Y' : 'N';
					}

					const limitExceeded = this.isLimitExceeded(flagName);
					if (limitExceeded)
					{
						this.performExceededActions(flagName, flagNode, node);

						this.showLimitDialog(
							this.getFeatureId(flagName),
							null,
							{
								module: 'tasks',
								source: 'taskEdit',
							}
						);
					}
					else
					{
						this.processToggleFlag(flagName, flagNode.value === 'Y');
					}
				}
			},

			isLimitExceeded(flagName)
			{
				switch (flagName)
				{
					case 'REPLICATE':
					case 'SAVE_AS_TEMPLATE':
						return Boolean(
							this.option('auxData').TASK_LIMIT_EXCEEDED
							|| this.option('auxData').TASK_RECURRENT_RESTRICT
						);
					case 'TASK_PARAM_1':
					case 'TASK_PARAM_2':
						return !this.relatedSubTaskDeadlinesEnabled;
					case 'TASK_PARAM_3':
						return !this.taskStatusSummaryEnabled;
					default:
						return false;
				}
			},

			performExceededActions(flagName, flagNode, parenNode)
			{
				switch (flagName)
				{
					case 'REPLICATE':
					case 'SAVE_AS_TEMPLATE':
					case 'TASK_PARAM_1':
					case 'TASK_PARAM_2':
					case 'TASK_PARAM_3':
						if (flagNode.value === 'Y')
						{
							flagNode.value = 'N';
							parenNode.checked = false;
						}
				}
			},

			getFeatureId(flagName)
			{
				switch (flagName)
				{
					case 'REPLICATE':
						return 'tasks_recurring_tasks';
					case 'SAVE_AS_TEMPLATE':
						return 'tasks_template';
					case 'TASK_PARAM_1':
					case 'TASK_PARAM_2':
						return 'tasks_related_subtask_deadlines';
					case 'TASK_PARAM_3':
						return 'tasks_status_summary';
					default:
						return '';
				}
			},

			showLimitDialog(featureId, bindElement, limitAnalyticsLabels)
			{
				return new Promise((resolve, reject) => {
					BX.Runtime.loadExtension('tasks.limit').then((exports) => {
						const { Limit } = exports;
						Limit.showInstance({
							featureId,
							bindElement,
							limitAnalyticsLabels,
						});

						resolve();
					});
				});
			},

			processToggleFlag: function(name, value)
			{
				if (name == 'REPLICATE')
				{
					var taskLimitExceeded = this.option('auxData').TASK_LIMIT_EXCEEDED;
					var taskRecurrentRestrict = this.option('auxData').TASK_RECURRENT_RESTRICT;
					if (
						(!taskLimitExceeded || (taskLimitExceeded && !value))
						|| (!taskRecurrentRestrict || (taskRecurrentRestrict && !value))
					)
					{
						BX.Tasks.Util.fadeSlideToggleByClass(this.control('replication-panel'));
						this.toggleOption('SAVE_AS_TEMPLATE', value);
						this.switchOption('SAVE_AS_TEMPLATE', value);
					}
				}
				else if (name === 'REGULAR')
				{
					BX.Tasks.Util.fadeSlideToggleByClass(this.control('regular-panel'));
				}
				else if (name == 'TASK_PARAM_1')
				{
					this.toggleDateParameters(value);
				}

				this.setState('FLAGS', name, false, value);
			},

			toggleDateParameters: function(flag)
			{
				// date inputs
				BX[flag ? 'addClass' : 'removeClass'](this.control('date-plan'), 'disabled-block');

				// match work time
				BX.Tasks.Util.Dispatcher.find('options-'+this.option('id')).then(function(ctrl){

					ctrl.switchOption('MATCH_WORK_TIME', !flag);

				}.bind(this));
			},

			onOriginatorChange: function()
			{
				BX.Tasks.Util.Dispatcher.find(this.option('id')+'-originator').then(function(ctrl){

					if(ctrl.count() && ctrl.value())
					{
						var userMatch = 'U'+this.getUser().DATA.ID.toString() == ctrl.value()[0].toString();

						BX.Tasks.Util.Dispatcher.find('options-'+this.option('id')).then(function(optCtrl){

							optCtrl.switchOption('ADD_TO_TIMEMAN', userMatch);

						}.bind(this));
					}

				}.bind(this));
			},

			onResponsibleChange: function()
			{
				BX.Tasks.Util.Dispatcher.find(this.option('id')+'-responsible').then(function(ctrl)
				{
					if (ctrl.count() > 1)
					{
						BX.Tasks.Util.hintManager.showDisposable(
							ctrl.scope(),
							BX.message('TASKS_TASK_COMPONENT_TEMPLATE_MULTIPLE_ASSIGNEE_NOTICE'),
							'TASK_EDIT_MULTIPLE_RESPONSIBLES'
						);
					}
					else
					{
						BX.Tasks.Util.hintManager.hide('TASK_EDIT_MULTIPLE_RESPONSIBLES');
					}

					if (ctrl.count() > 0 && !this.isFlowForm)
					{
						var absenceNode = this.control('absence-message');

						absenceNode.style.display = 'none';
						while (absenceNode.lastChild)
						{
							absenceNode.removeChild(absenceNode.lastChild);
						}

						BX.ajax.runComponentAction('bitrix:tasks.widget.member.selector', 'isAbsence', {
							mode: 'class',
							data: {
								userIds: ctrl.value().map(function(userId){ return userId.substring(1); })
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
								if (!response.data.length)
								{
									return;
								}
								var text = response.data.reduce(function(sum, current)
								{
									return sum + '<br />' + current; //TODO HTMLSPECIALCHARS!
								});

								var absenceAlert = new BX.UI.Alert({
									icon: BX.UI.Alert.Icon.INFO,
									color: BX.UI.Alert.Color.WARNING,
									text: text
								});

								absenceNode.appendChild(absenceAlert.getContainer());
								absenceNode.style.display = 'block';
							}.bind(this),
							function(response)
							{

							}.bind(this)
						);
					}
				}.bind(this));
			},

			onCancelButtonClick: function(e)
			{
				if(this.option('cancelActionIsEvent')) // let iframe popup close window, dont go to url
				{
					BX.Tasks.Util.fireGlobalTaskEvent('NOOP', {}, {STAY_AT_PAGE: false});
					BX.PreventDefault(e);
				}
			},

			onWorktimeChange: function(node)
			{
				this.checkNoWorkDays(node.checked);
				this.instances.projectPlan.setMatchWorkTime(node.checked);
			},

			checkNoWorkDays: function(matchWorkTime)
			{
				if (!matchWorkTime)
				{
					if (BX.type.isElementNode(BX('date-plan-alert')))
					{
						BX('date-plan-alert').remove();
					}

					return false;
				}

				var result = true;
				var calender = this.getCalendar();
				var weekends = calender.weekends;
				var dayNumbers = [0, 1, 2, 3, 4, 5, 6];

				dayNumbers.forEach(function(dayNumber)
				{
					if (!(dayNumber in weekends))
					{
						result = false;
					}
				});

				if (result && !BX.type.isElementNode(BX('date-plan-alert')))
				{
					var alert = BX.create("div", {
						props : { id : 'date-plan-alert', className : "ui-alert ui-alert-danger" },
						attrs: { style: 'margin-top: 10px'},
						children: [
							BX.create("span", {
								props: { className: "ui-alert-message" },
								text: BX.message('TASKS_TASK_COMPONENT_TEMPLATE_NO_WORK_DAYS_ERROR')
							})
						]
					});

					this.control('date-plan-manager').appendChild(alert);
				}

				return result;
			},

			onTitleChange: function()
			{
				var title = this.control('title');
				if (title.value.length > 250)
				{
					BX.addClass(title, 'task-field-error');
				}
				else
				{
					BX.removeClass(title, 'task-field-error')
				}
			},

			showToCheckListHintNoItems: function()
			{
				var hintPopup = new BX.PopupWindow({
					bindElement: this.control('to-checklist'),
					content: BX.message('TASKS_TASK_COMPONENT_TEMPLATE_TO_CHECKLIST_HINT'),
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
				hintPopup.show();
				setTimeout(function() {
					hintPopup.close();
				}, 2000);
			},

			showToCheckListHintCreated: function()
			{
				var hintPopup = new BX.PopupWindow({
					bindElement: this.control('to-checklist'),
					content: BX.message('TASKS_TASK_COMPONENT_TEMPLATE_TO_CHECKLIST_HINT_CREATED'),
					className: 'tasks-to-checklist-popup',
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
				hintPopup.show();
				setTimeout(function() {
					hintPopup.close();
				}, 2000);
			},

			setEditorTextFromHash: function()
			{
				const text = decodeURIComponent(location.hash.slice(1));
				history.replaceState(null, null, ' ');
				this.setEditorText(text.trim());
			},

			setEditorText: function(text)
			{
				const editorId = this.option('template').ID;
				const editor = BXHtmlEditor.Get(editorId);
				if (BX.Type.isStringFilled(text))
				{
					editor.SetContent(text);
				}
			},

			getEditorSelectedText: function()
			{
				var text = '';
				var container = this.control('editor-container');
				var isBbCode = (container.querySelector('.bxhtmled-iframe-cnt').style.display === 'none');

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

				return text;
			},

			getEditorText: function()
			{
				const container = this.control('editor-container');
				const isBbCode = (container.querySelector('.bxhtmled-iframe-cnt').style.display === 'none');

				if (isBbCode)
				{
					const textArea = container.querySelector('.bxhtmled-textarea');

					return textArea.value;
				}

				const editor = container.querySelector('.bx-editor-iframe').contentDocument;

				return editor.body.innerText;
			},

			getTitlesFromText: function(text)
			{
				if (text === '')
				{
					return [];
				}

				return text.split(/\r\n|\r|\n/g);
			},

			onToCheckListClick: function(text = '')
			{
				if (typeof text !== 'string' || (typeof text === 'string' && text === ''))
				{
					text = this.getEditorSelectedText();
				}
				var titles = this.getTitlesFromText(text);

				if (titles.length <= 0)
				{
					this.showToCheckListHintNoItems();
					return;
				}

				var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
				if (treeStructure.getDescendantsCount() === 0)
				{
					var items = this.getCheckListItemsFromTitles(titles);

					BX.Tasks.CheckListInstance.addCheckList().then(function(newCheckList) {
						items.forEach(function(item) {
							newCheckList.addCheckListItem(item);
						});
						newCheckList.handleTaskOptions();
						BX('checklistFromDescription').value = 'fromDescription';
						this.showToCheckListHintCreated();
					}.bind(this));

					if (BX.hasClass(this.control('checklist'), 'invisible'))
					{
						this.toggleBlock('checklist');
					}
				}
				else
				{
					var menu = new BX.PopupMenuWindow({
						bindElement: this.control('to-checklist'),
						items: this.getToCheckListPopupMenuItems(titles)
					});
					menu.show();
				}
			},

			onAiCheckListClick: async function(event)
			{
				if (this.option('canUseAIChecklistButton'))
				{
					this.getAiCommandExecutorPromise ??= this.getAiCommandExecutor();
					const commandExecutor = await this.getAiCommandExecutorPromise;
					if (commandExecutor.isProcessing)
					{
						return;
					}

					const makeChecklistBtn = event.target.parentElement;
					BX.Dom.addClass(makeChecklistBtn, 'tasks-btn-ai-checklist-wait');
					commandExecutor.isProcessing = true;

					commandExecutor.makeChecklistFromText(this.getEditorText() || 'empty').then((checklistString) => {
						const titles = this.getTitlesFromText(checklistString);
						const items = this.getCheckListItemsFromTitles(titles);

						BX.Tasks.CheckListInstance.addCheckList().then((newCheckList) => {
							items.forEach((item) => newCheckList.addCheckListItem(item));
							newCheckList.handleTaskOptions();
						});

						this.openCheckLists();
					}).catch(async (err) => {
						const { AjaxErrorHandler } = await BX.Runtime.loadExtension('ai.ajax-error-handler');

						AjaxErrorHandler?.handleTextGenerateError({
							baasOptions: {
								bindElement: makeChecklistBtn,
								context: 'tasks_field_checklist',
								useAngle: false,
							},
							errorCode: err?.errors?.[0]?.code ?? 'undefined_error',
						});
					}).finally(() => {
						BX.Dom.removeClass(makeChecklistBtn, 'tasks-btn-ai-checklist-wait');
						commandExecutor.isProcessing = false;
					});
				}
				else
				{
					BX.UI.InfoHelper.show('limit_copilot_off', {
						isLimit: true,
					});
				}

			},

			getAiCommandExecutor: async function()
			{
				if (!this.aiCommandExecutor)
				{
					const { CommandExecutor } = await BX.Runtime.loadExtension('ai.command-executor');

					this.aiCommandExecutor = new CommandExecutor({
						moduleId: 'tasks',
						contextId: 'tasks_field_checklist',
					});
				}

				return this.aiCommandExecutor;
			},

			openCheckLists: function()
			{
				if (BX.hasClass(this.control('checklist'), 'invisible'))
				{
					this.toggleBlock('checklist');
				}
			},

			getToCheckListPopupMenuItems: function(titles)
			{
				var self = this;
				var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();

				var popupMenuItems = [
					{
						text: "+ " + BX.message('TASKS_TASK_COMPONENT_TEMPLATE_TO_CHECKLIST_ADD_NEW_CHECKLIST'),
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
								self.showToCheckListHintCreated();
							});

							if (BX.hasClass(self.control('checklist'), 'invisible'))
							{
								self.toggleBlock('checklist');
							}
						}
					},
					{delimiter: true}
				];

				treeStructure.getDescendants().forEach(function(descendant) {
					popupMenuItems.push({
						text: descendant.fields.getTitle(),
						onclick: function(event, item)
						{
							item.getMenuWindow().close();

							var items = self.getCheckListItemsFromTitles(titles);

							items.forEach(function(item) {
								descendant.addCheckListItem(item);
							});
							items[0].handleTaskOptions();

							BX('checklistFromDescription').value = 'fromDescription';
							self.showToCheckListHintCreated();

							if (BX.hasClass(self.control('checklist'), 'invisible'))
							{
								self.toggleBlock('checklist');
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
					title = title.trim().substring(0, 255).trim();
					if (title.length > 0)
					{
						var newCheckListItem = new BX.Tasks.CheckListItem({TITLE: title});
						items.push(newCheckListItem);
					}
				});

				return items;
			},

			getCalendar: function()
			{
				if(this.instances.calendar == false)
				{
					this.instances.calendar = new BX.Tasks.Calendar(BX.Tasks.Calendar.adaptSettings(this.option('auxData').COMPANY_WORKTIME));
				}

				return this.instances.calendar;
			},

			getState: function(type, name, actionName)
			{
				if (type == 'BLOCKS') {
					return this.vars.state[type][name][actionName];
				}
				if (type == 'FLAGS') {
					return this.vars.state[type][name];
				}
			},

			setState: function(type, name, actionName, value)
			{
				if(!BX.type.isNotEmptyString(name))
				{
					return;
				}

				if(type == 'FLAGS')
				{
					var allowed = {
						'ALLOW_TIME_TRACKING': true,
						'TASK_CONTROL': true,
						'ALLOW_CHANGE_DEADLINE': true,
						'MATCH_WORK_TIME': true,
						'FORM_FOOTER_PIN': true,
						'REQUIRE_RESULT': true,
						'TASK_PARAM_3': true
					};

					if(!(name in allowed))
					{
						return;
					}
				}

				if(typeof this.vars.state[type] == 'undefined')
				{
					this.vars.state[type] = {};
				}
				if(typeof this.vars.state[type][name] == 'undefined')
				{
					this.vars.state[type][name] = {};
				}

				if(type == 'BLOCKS')
				{
					this.vars.state[type][name][actionName] = value;
				}
				if(type == 'FLAGS')
				{
					this.vars.state[type][name] = value;
				}

				this.submitState();
				this.redrawState(); // for submitting with form
			},

			submitState: function()
			{
				var st = BX.clone(this.vars.state);

				// send FORM_FOOTER_PIN, but dont send other flags in this manner, it looks pretty awkward
				// other flags will be saved when form actually submitted
				var fp = st.FLAGS.FORM_FOOTER_PIN;
				delete(st.FLAGS);
				st.FLAGS = {
					FORM_FOOTER_PIN: fp
				};

				BX.ajax.runComponentAction('bitrix:tasks.task', 'setState', {
					mode: 'class',
					data: {
						state:st,
						isFlowForm: this.isFlowForm,
					}
				}).then(
					function(response)
					{

					}.bind(this)
				).catch(
					function(response)
					{

					}.bind(this)
				);
			},

			redrawState: function()
			{
				var container = this.control('state');
				if(BX.type.isElementNode(container))
				{
					var html = '';

					if(typeof this.vars.state['BLOCKS'] != 'undefined')
					{
						for(var bName in this.vars.state['BLOCKS'])
						{
							var opened = this.vars.state['BLOCKS'][bName]['O'];
							var chosen = this.vars.state['BLOCKS'][bName]['C'];

							if(typeof opened != 'undefined')
							{
								html += this.getHTMLByTemplate('state-block', {
									NAME: bName,
									TYPE: 'O',
									VALUE: opened === true || opened === 'true' ? '1' : '0'
								});
							}
							if(typeof chosen != 'undefined')
							{
								html += this.getHTMLByTemplate('state-block', {
									NAME: bName,
									TYPE: 'C',
									VALUE: chosen === true || chosen === 'true' ? '1' : '0'
								});
							}
						}
					}

					if(typeof this.vars.state['FLAGS'] != 'undefined')
					{
						for(var fName in this.vars.state['FLAGS'])
						{
							var checked = this.vars.state['FLAGS'][fName];

							html += this.getHTMLByTemplate('state-flag', {
								NAME: fName,
								VALUE: checked === true || checked === 'true' ? '1' : '0'
							});
						}
					}

					container.innerHTML = html;
				}
			},

			clearNewAnalyticsParams: function ()
			{
				const url = new URL(window.location.href);
				const section = url.searchParams.get('ta_sec');

				if (section)
				{
					url.searchParams.delete('ta_cat');
					url.searchParams.delete('ta_sec');
					url.searchParams.delete('ta_sub');
					url.searchParams.delete('ta_el');
					url.searchParams.delete('p1');
					url.searchParams.delete('p2');
					url.searchParams.delete('p3');
					url.searchParams.delete('p4');
					url.searchParams.delete('p5');

					window.history.replaceState(null, null, url.toString());
				}
			},
		},
	});

	BX.Tasks.Component.Task.UserItemSet = BX.Tasks.UserItemSet.extend({
		methods: {

			onSearchBlurred: function()
			{
				if(this.callMethod(BX.Tasks.UserItemSet, 'onSearchBlurred'))
				{
					this.restoreKept();
				}
			},

			restoreKept: function()
			{
				if(this.vars.toDelete)
				{
					this.addItem(this.vars.toDelete, {checkRestrictions: false});
					this.vars.toDelete = false;
				}
			},

			onSelectorItemSelected: function(data)
			{
				var value = this.extractItemValue(data);

				if(!this.hasItem(value))
				{
					var max = this.option('max');

					this.addItem(data);
					this.vars.toDelete = false;

					if(max == 1)
					{
						this.instances.selector.close();
						this.onSearchBlurred();
					}
				}

				this.resetInput();
			},

			openAddForm: function(node, e, keepValue)
			{
				var min = this.option('min');
				var max = this.option('max');

				if(keepValue || (max == 1 && (min == 0 || min == 1)))
				{
					var first = this.getItemFirst();
					if(first)
					{
						this.vars.toDelete = first.data();
						this.callMethod(BX.Tasks.UserItemSet, 'deleteItem', [first.value(), {checkRestrictions: false}]);
					}
				}

				this.callMethod(BX.Tasks.UserItemSet, 'openAddForm');
			},

			deleteItem: function(value)
			{
				if(!this.callMethod(BX.Tasks.UserItemSet, 'deleteItem', arguments))
				{
					this.openAddForm(false, false, true);
					return false;
				}

				return true;
			}
		}
	});

	BX.Tasks.Component.Task.GroupItemSet = BX.Tasks.Component.Task.UserItemSet.extend({
		sys: {
			code: 'group-item-set'
		},
		methods: {
			extractItemDisplay: function(data)
			{
				return data.NAME || BX.util.htmlspecialcharsback(data.nameFormatted); // socnetlogdest returns escaped name, we want unescaped
			},
			getNSMode: function()
			{
				return 'group';
			}
		}
	});

	// legacy popup - task selector
	BX.Tasks.Component.Task.TaskItemSet = BX.Tasks.PopupItemSet.extend({
		sys: {
			code: 'task-item-set'
		},
		options: {
			itemFx: 'horizontal'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.PopupItemSet);

				this.instances.selector = window['O_'+this.option('selectorCode')];
			},
			extractItemDisplay: function(data)
			{
				if(typeof data.DISPLAY != 'undefined')
				{
					return data.DISPLAY;
				}

				if(typeof data.name != 'undefined')
				{
					return data.name;
				}

				return data.TITLE + ' [' + data.ID + ']';
			},
			extractItemValue: function(data)
			{
				return (typeof data.ID == 'undefined' ? data.id : data.ID);
			},
			bindFormEvents: function()
			{
				if(typeof this.instances.selector != 'undefined' && this.instances.selector != null && this.instances.selector != false)
				{
					BX.addCustomEvent(this.instances.selector, 'on-change', BX.delegate(this.itemsChanged, this));

					if(typeof this.instances.window != 'undefined')
					{
						var selectorCtrl = this.instances.selector;
						BX.addCustomEvent(this.instances.window, "onAfterPopupShow", function(){
							setTimeout(function(){
								selectorCtrl.searchInput.focus();
							}, 100);
						});
					}
				}
			},
			deleteItem: function(value, parameters)
			{
				// todo: in some cases we got numeric in value, in other cases - object. re-check it and unify
				var taskId = (BX.type.isNumber(value) || BX.type.isString(value)) ? value : value.value();

				if(this.callMethod(BX.Tasks.PopupItemSet, 'deleteItem', arguments))
				{
					this.instances.selector.unselect(taskId);
				}
			}
		}
	});
}).call(this);
