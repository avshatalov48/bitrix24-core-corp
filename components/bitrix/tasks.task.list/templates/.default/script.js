BX.namespace('BX.Tasks.Grid');

BX.Tasks.GridActions = {
	gridId: null,
	groupSelector: null,
	registeredTimerNodes: {},
	defaultPresetId: '',
	getTotalCountProceed: false,
	currentGroupAction: null,

	checkCanMove: function() {
		return !BX.Tasks.GridInstance || BX.Tasks.GridInstance.checkCanMove();
	},

	initPopupBalloon: function(mode, searchField, groupIdField) {
		this.groupSelector = null;

		BX.bind(BX(searchField + '_control'), 'click', BX.delegate(function() {
			if (!this.groupSelector)
			{
				this.groupSelector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
					scope: BX(searchField + '_control'),
					id: 'group-selector-' + this.gridId,
					mode: mode,
					query: false,
					useSearch: true,
					useAdd: false,
					parent: this,
					popupOffsetTop: 5,
					popupOffsetLeft: 40,
				});

				this.groupSelector.bindEvent('item-selected', BX.delegate(function(data) {
					BX(searchField + '_control').value = BX.util.htmlspecialcharsback(data.nameFormatted) || '';
					BX(groupIdField + '_control').value = data.id || '';
					this.groupSelector.close();
				}, this));
			}
			this.groupSelector.open();
		}, this));
	},

	onTagUpdateClick: function(taskId, groupId, event) {
		var onRowUpdate = function(event) {
			var id = event.getData().id;
			if (Number(id) === Number(taskId))
			{
				var row = BX.Main.gridManager.getById(this.gridId).instance.getRows().getById(id);
				var button = row.getCellById('TAG').querySelector('.main-grid-tag-add');

				dialog.setTargetNode(button);
			}
		};
		var onRowRemove = function(event) {
			var id = event.getData().id;
			if (Number(id) === Number(taskId))
			{
				dialog.hide();
			}
		};

		var statusSuccess = false;

		var onTagsChange = function(event) {
			const dialog = event.getTarget();
			const selectedItem = event.getData().item;
			selectedItem.setSort(1);
			dialog.getTab('all').getRootNode().addItem(selectedItem);

			if (statusSuccess)
			{
				dialog.clearSearch();
				BX.Tasks.GridActions.reloadRow(taskId);
				statusSuccess = false;
				hideAddButton();
				return;
			}
			var tags = dialog.getSelectedItems().map(function(item) {
				return item.getTitle();
			});
			hideAddButton();
			BX.Tasks.GridActions.action('setTags', taskId, { tags: tags });
		};
		var onSearch = function(event) {
			var dialog = event.getTarget();
			var query = event.getData().query;
			if (query.trim() !== '')
			{
				showAddButton();
			}
			else
			{
				hideAddButton();
			}
		};

		var showAddButton = function()
		{
			dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-add-new').hidden = false;
			dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-conjunction').hidden = false;
		}

		var hideAddButton = function()
		{
			dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-add-new').hidden = true;
			dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-conjunction').hidden = true;
		}

		var showAlert = function(className, error)
		{
			if (dialog.getContainer().querySelector(`div.${className}`))
			{
				return;
			}
			var alert = document.createElement('div');
			alert.className = className;
			alert.innerHTML = `
				<div class='ui-alert ui-alert-xs ui-alert-danger'  
					<span class='ui-alert-message'>
						${error}
					</span> 
				</div>
			`;
			dialog.getFooterContainer().before(alert);
		};

		var onTagsLoad = function()
		{
			var events = ['click', 'keydown'];

			var handler = function(event) {
				if (event.type === 'keydown')
				{
					if (!((event.ctrlKey || event.metaKey) && event.keyCode === 13))
					{
						return;
					}
				}

				var tags = dialog.getSelectedItems().map(function(item) {
					return item.getTitle();
				});

				var newTag = dialog.getTagSelectorQuery();
				if (newTag.trim() === '')
				{
					return;
				}
				BX.ajax.runComponentAction('bitrix:tasks.task', 'setTags', {
					mode: 'class',
					data: {
						taskId: taskId,
						tags: tags,
						newTag: newTag,
					},
				}).then(function(response) {
					if (response.data.success)
					{
						statusSuccess = true;
						const item = dialog.addItem({
							id: newTag,
							entityId: 'task-tag',
							title: newTag,
							sort: 1,
							badges: [
								{
									title: response.data.owner,
								},
							],
						});

						dialog.getTab('all').getRootNode().addItem(item);
						item.select();
					}
					else
					{
						const alertClass = 'tasks-list-tag-already-exists-alert';
						showAlert(alertClass, response.data.error);
						const removeAlert = function() {
							const notification = dialog.getContainer().querySelector(`div.${alertClass}`);
							notification && notification.remove();
						};
						setTimeout(removeAlert, 2000);
					}
				});
			};

			events.forEach(function(ev) {
				if (ev === 'click')
				{
					dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-add-new')
					.addEventListener(ev, handler);
				}
				else
				{
					dialog.getContainer().addEventListener(ev, handler);
				}
			});
		};

		var eventData = event.getData();

		if (BX.Tasks.GridActions.tagsAreConverting)
		{
			var message = new top.BX.UI.Dialogs.MessageBox({
				title: BX.message('TASKS_TASK_LIST_TAGS_ARE_CONVERTING_TITLE'),
				message: BX.message('TASKS_TASK_LIST_TAGS_ARE_CONVERTING_TEXT'),
				buttons: top.BX.UI.Dialogs.MessageBoxButtons.OK,
				okCaption: BX.message('TASKS_TASK_LIST_TAGS_ARE_CONVERTING_COME_BACK_LATER'),
				onOk: function(){
					message.close();
				}
			});
			message.show();
			return;
		};

		var getTargetContainer = function()
		{
			var fields = document.querySelectorAll('td.main-grid-cell.main-grid-cell-left');
			var target = eventData.button;
			fields.forEach(function(field){
				if (field.contains(target))
				{
					target = field;
				}
			});

			return target;
		};

		//widget in tasks list
		var dialog = new BX.UI.EntitySelector.Dialog({
			id: 'tasks-task-list-tag-widget',
			targetNode: getTargetContainer(),
			enableSearch: true,
			width: 350,
			height: 400,
			multiple: true,
			dropdownMode: true,
			compactView: true,
			entities: [
				{
					id: 'task-tag',
					options: {
						taskId: taskId,
						groupId: groupId,
					},
				},
			],
			searchOptions: {
				allowCreateItem: false,
			},
			footer: BX.Tasks.EntitySelector.Footer,
			footerOptions: {
				userId: BX.Tasks.GridInstance.userId,
				taskId: taskId,
				groupId: groupId,
			},
			clearUnavailableItems: true,
			events: {
				'onLoad': function() {
					dialog.getFooterContainer().style.zIndex = 1;
					BX.addCustomEvent('Tasks.Tasks.Grid:RowRemove', BX.proxy(onRowRemove, this));
					onTagsLoad();
				}.bind(this),
				'onHide': function() {
					BX.removeCustomEvent('Tasks.Tasks.Grid:RowRemove', BX.proxy(onRowRemove, this));
				}.bind(this),
				'onSearch': function(event){
					onSearch(event)
				}.bind(this),
				'Item:onSelect': function(event){
					onTagsChange(event)
				}.bind(this),
				'Item:onDeselect': function(event){
					onTagsChange(event)
				}.bind(this),
			},
		});
		dialog.show();
	},

	onProjectAddClick: function(taskId, target) {
		var dialog = new BX.UI.EntitySelector.Dialog({
			targetNode: target,
			enableSearch: true,
			context: 'TASKS_PROJECT',
			entities: [
				{
					id: 'project',
				},
			],
			events: {
				'Item:onSelect': function(event) {
					var item = event.getData().item;
					BX.Tasks.GridActions.action('setGroup', taskId, { groupId: item.getId() });
					event.getTarget().hide();
				},
			},
		});
		dialog.show();
	},

	toggleFilter: function(options, selected, extendable) {
		var filterManager = BX.Main.filterManager.getById(this.gridId);
		if (!filterManager)
		{
			console.log('BX.Main.filterManager not initialised');
			return;
		}

		if (selected)
		{
			this.reduceFilter(filterManager, options, extendable);
		}
		else
		{
			this.extendFilter(filterManager, options, extendable);
		}
	},

	extendFilter: function(filterManager, options, extendable) {
		if (extendable)
		{
			options = this.extendCurrentFieldsValues(filterManager, options);
		}
		filterManager.getApi().extendFilter(options);
	},

	reduceFilter: function(filterManager, options, extendable) {
		var clearFilterFields = !extendable;

		if (extendable)
		{
			options = this.reduceCurrentFieldsValues(filterManager, options);

			Object.values(options).forEach(function(values) {
				if (BX.Type.isArray(values) && values.length <= 0)
				{
					clearFilterFields = true;
				}
			});
		}

		if (!clearFilterFields)
		{
			filterManager.getApi().extendFilter(options);
			return;
		}

		var filterFields = filterManager.getFilterFields();

		Object.keys(options).forEach(function(optionKey) {
			filterFields.forEach(function(field) {
				if (field.getAttribute('data-name') === optionKey)
				{
					filterManager.getFields().deleteField(field);
				}
			});
		});

		filterManager.getSearch().apply();
	},

	extendCurrentFieldsValues: function(filterManager, options) {
		var extendedOptions = options;
		var filterFieldsValues = filterManager.getFilterFieldsValues();

		Object.entries(options).forEach(function([key, values]) {
			var currentValues = filterFieldsValues[key];

			if (BX.Type.isArray(currentValues) && BX.Type.isArray(values))
			{
				values.forEach(function(value) {
					if (!currentValues.includes(value))
					{
						currentValues.push(value);
						extendedOptions[key] = currentValues;
					}
				});
			}
		});

		return extendedOptions;
	},

	reduceCurrentFieldsValues: function(filterManager, options) {
		var reducedOptions = options;
		var filterFieldsValues = filterManager.getFilterFieldsValues();

		Object.entries(options).forEach(function([key, values]) {
			var currentValues = filterFieldsValues[key];

			if (BX.Type.isArray(currentValues) && BX.Type.isArray(values))
			{
				values.forEach(function(value) {
					if (currentValues.includes(value))
					{
						currentValues.splice(currentValues.indexOf(value), 1);
						reducedOptions[key] = currentValues;
					}
				});
			}
		});

		return reducedOptions;
	},

	filter: function(options) {
		var filterManager = BX.Main.filterManager.getById(this.gridId);
		if (!filterManager)
		{
			console.log('BX.Main.filterManager not initialised');
			return;
		}
		var fields = filterManager.getFilterFieldsValues();
		var filterApi = filterManager.getApi();

		Object.keys(options).forEach(function(key) {
			fields[key] = options[key];
		});

		filterApi.setFields(fields);
		filterApi.apply();
	},

	changePin: function(taskId, groupId, event) {
		var eventData = event.getData();
		var button = eventData.button;

		if (BX.Dom.hasClass(button, BX.Grid.CellActionState.ACTIVE))
		{
			BX.Tasks.GridActions.action('unpin', taskId, { groupId: groupId });

			BX.Dom.removeClass(button, BX.Grid.CellActionState.ACTIVE);
			BX.Dom.addClass(button, BX.Grid.CellActionState.SHOW_BY_HOVER);
		}
		else
		{
			BX.Tasks.GridActions.action('pin', taskId, { groupId: groupId });

			BX.Dom.addClass(button, BX.Grid.CellActionState.ACTIVE);
			BX.Dom.removeClass(button, BX.Grid.CellActionState.SHOW_BY_HOVER);
		}
	},

	changeMute: function(taskId, event) {
		var eventData = event.getData();
		var button = eventData.button;

		if (BX.Dom.hasClass(button, BX.Grid.CellActionState.ACTIVE))
		{
			BX.Tasks.GridActions.action('unmute', taskId);

			BX.Dom.removeClass(button, BX.Grid.CellActionState.ACTIVE);
			BX.Dom.addClass(button, BX.Grid.CellActionState.SHOW_BY_HOVER);
		}
		else
		{
			BX.Tasks.GridActions.action('mute', taskId);

			BX.Dom.addClass(button, BX.Grid.CellActionState.ACTIVE);
			BX.Dom.removeClass(button, BX.Grid.CellActionState.SHOW_BY_HOVER);
		}
	},

	action: function(code, taskId, args) {
		if (code === 'add2Timeman')
		{
			if (BX.addTaskToPlanner)
			{
				BX.addTaskToPlanner(taskId);
			}
			else if (window.top.BX.addTaskToPlanner)
			{
				window.top.BX.addTaskToPlanner(taskId);
			}
		}
		else if (code === 'copyLink')
		{
			if (BX.clipboard.copy(args.copyLink))
			{
				BX.UI.Notification.Center.notify({ content: BX.message('TASKS_LIST_ACTION_COPY_LINK_NOTIFICATION') });
			}
		}
		else if (code === 'complete' || code === 'renew')
		{
			BX.ajax.runAction(
				'bitrix:tasks.scrum.task.isScrumTask',
				{
					mode: 'class',
					data: { taskId: taskId },
				},
			).then(function(response) {
				if (response.data === true)
				{
					this.doScrumAction(code, taskId, args);
				}
				else
				{
					this.doAction(code, taskId, args);
				}
			}.bind(this));
		}
		else
		{
			this.doAction(code, taskId, args);
		}
	},

	doAction: function(action, taskId, args) {
		args = args || {};
		args['taskId'] = taskId;

		var component = 'bitrix:tasks.task';
		if (action === 'pin' || action === 'unpin')
		{
			component = 'bitrix:tasks.task.list';
		}

		if (action === 'ping')
		{
			BX.UI.Notification.Center.notify({ content: BX.message('TASKS_LIST_ACTION_PING_NOTIFICATION') });
		}

		return BX.ajax.runComponentAction(component, action, {
			mode: 'class',
			data: args,
		}).then(
			function(response) {
				if (action === 'delete')
				{
					BX.Tasks.Util.fireGlobalTaskEvent('DELETE', { ID: taskId });
					BX.UI.Notification.Center.notify({ content: BX.message('TASKS_DELETE_SUCCESS') });
				}
				if (!this.gridId)
				{
					window.location.href = window.location.href;

					return false;
				}
				if (!this.checkCanMove())
				{
					this.reloadRow(taskId);
				}

				return true;
			}.bind(this),
		).catch(
			function(response) {
				if (response.errors)
				{
					var content = response.errors[0].message || response.errors[0].MESSAGE;
					BX.UI.Notification.Center.notify({ content: content });
				}
			}.bind(this),
		);
	},

	doScrumAction(action, taskId, args)
	{
		var promise = new BX.Promise();

		var taskStatus = null;
		var isParentScrumTask = false;

		top.BX.loadExt('tasks.scrum.task-status')
		.then(function() {
			if (
				!BX.type.isUndefined(top.BX.Tasks.Scrum)
				&& !BX.type.isUndefined(top.BX.Tasks.Scrum.TaskStatus)
			)
			{
				taskStatus = new top.BX.Tasks.Scrum.TaskStatus({
					taskId: taskId,
					action: action,
					performActionOnParentTask: true,
				});
				taskStatus.updateState()
				.then(function() {
					taskStatus.isParentScrumTask()
					.then(function(result) {
						isParentScrumTask = result;
						if (isParentScrumTask)
						{
							promise.fulfill();
						}
						else
						{
							if (action === 'complete')
							{
								return taskStatus.showDod(taskId)
								.then(function() {
									promise.fulfill();
								}.bind(this))
								.catch(function() {
									promise.reject();
								}.bind(this))
									;
							}
							else
							{
								promise.fulfill();
							}
						}
					}.bind(this))
					;
				}.bind(this))
				;
			}
			else
			{
				promise.fulfill();
			}
		}.bind(this))
		;

		promise.then(function() {
			this.doAction(action, taskId, args)
			.then(function() {
				if (taskStatus && isParentScrumTask)
				{
					taskStatus.update();
				}
			}.bind(this))
			;
		}.bind(this));
	},

	getTotalCount: function(prefix, userId, groupId, parameters) {
		if (this.getTotalCountProceed)
		{
			return;
		}
		this.getTotalCountProceed = true;

		var container = document.getElementById(prefix + '_row_count_wrapper');
		this.showCountLoader(container);

		BX.ajax.runComponentAction('bitrix:tasks.task.list', 'getTotalCount', {
			mode: 'class',
			data: {
				userId: userId,
				groupId: groupId,
				parameters: JSON.stringify(parameters),
			},
		}).then(
			function(response) {
				this.hideCountLoader(container);
				if (response.data)
				{
					response.data = (typeof response.data == 'number') ? response.data : 0;
					var button = container.querySelector('a');
					if (button)
					{
						button.remove();
					}
					container.append(response.data);
				}
				this.getTotalCountProceed = false;
			}.bind(this),
		).catch(
			function(response) {
				if (response.errors)
				{
					BX.Tasks.alert(response.errors);
				}
				this.getTotalCountProceed = false;
			}.bind(this),
		);
	},

	showCountLoader: function(container) {
		var button = container.querySelector('a');
		if (button)
		{
			button.style.display = 'none';
		}

		var loader = container.querySelector('.tasks-circle-loader-circular');
		if (loader)
		{
			loader.style.display = 'inline';
		}
	},

	hideCountLoader: function(container) {
		var loader = container.querySelector('.tasks-circle-loader-circular');
		if (loader)
		{
			loader.style.display = 'none';
		}
	},

	reloadRow: function(taskId) {
		var grid = BX.Main.gridManager.getById(this.gridId);
		if (grid && grid.hasOwnProperty('instance'))
		{
			grid.instance.updateRow(taskId.toString());
		}
	},

	reloadGrid: function() {
		if (
			BX.SidePanel
			&& BX.SidePanel.Instance
			&& BX.SidePanel.Instance.getLastOpenSlider()
		)
		{
			BX.SidePanel.Instance.destroy(BX.SidePanel.Instance.getLastOpenSlider().getUrl());
		}

		var filterManager = BX.Main.filterManager.getById(this.gridId);
		if (!filterManager)
		{
			console.log('BX.Main.filterManager not initialised');
			return;
		}
		filterManager.getSearch().apply();
	},

	setCurrentGroupAction: function(groupAction) {
		this.currentGroupAction = groupAction;
	},

	processBeforeGroupActionSent: function() {
		if (this.currentGroupAction === 'ping')
		{
			BX.UI.Notification.Center.notify({ content: BX.message('TASKS_LIST_GROUP_ACTION_PING_NOTIFICATION') });
		}
		this.currentGroupAction = null;
	},

	confirmGroupAction: function(gridId) {
		BX.Tasks.confirm(BX.message('TASKS_CONFIRM_GROUP_ACTION')).then(function() {
			this.processBeforeGroupActionSent();
			BX.Main.gridManager.getById(gridId).instance.sendSelected();
		}.bind(this));
	},

	onDeadlineChangeClick: function(taskId, node, curDeadline, event) {
		curDeadline = curDeadline || (new Date).getDate();
		node = node || event.getData().button;

		var calendar = BX.calendar({
			node: node,
			value: curDeadline,
			form: '',
			bTime: true,
			currentTime: Math.round((new Date()) / 1000) - (new Date()).getTimezoneOffset() * 60,
			bHideTimebar: true,
			bCompatibility: true,
			bCategoryTimeVisibilityOption: 'tasks.bx.calendar.deadline',
			bTimeVisibility: (BX.Tasks.GridInstance ?
				(BX.Tasks.GridInstance.calendarSettings.deadlineTimeVisibility === 'Y') : false),
			callback_after: (function(node, taskId) {
				return function(value) {
					var currentTime = new Date();
					if (value.getTime() > currentTime.getTime())
					{
						BX.onCustomEvent('Tasks.Tour.ExpiredTasksDeadlineChange:saveDeadline', []);
					}

					var path = BX.CJSTask.ajaxUrl;
					BX.CJSTask.ajaxUrl = BX.CJSTask.ajaxUrl + '&_CODE=CHANGE_DEADLINE&viewType=VIEW_MODE_LIST';
					BX.CJSTask.batchOperations(
						[
							{
								operation: 'CTaskItem::update()',
								taskData: {
									ID: taskId,
									DEADLINE: BX.calendar.ValueToString(value, true),
								},
							},
						],
						{
							callbackOnSuccess: (function(node, taskId, value) {
								return function(reply) {
									// if (node.parentNode.parentNode.tagName === 'TD')
									//     node.parentNode.parentNode.innerHTML = tasksListNS.renderDeadline(taskId, value, true);
									// else
									//     node.parentNode.innerHTML = tasksListNS.renderDeadline(taskId, value, true);

								};
							})(node, taskId, value),
						},
					);
					BX.CJSTask.ajaxUrl = path;
					if (!BX.Tasks.GridActions.checkCanMove())
					{
						BX.Tasks.GridActions.reloadRow(taskId);
					}
				};
			})(node, taskId),
		});

		var guide = BX.Tasks.TourGuideController.getGuide();
		if (guide && guide.setCalendarPopup && guide.isCorrectRow(taskId) && !guide.getIsStopped())
		{
			guide.setCalendarPopup(calendar.popup);
		}
	},

	onMarkChangeClick: function(taskId, bindElement, currentValues) {
		BX.TaskGradePopup.show(
			taskId,
			bindElement,
			currentValues,
			{
				events: {
					onPopupClose: this.__onGradePopupClose,
					onPopupChange: this.__onGradePopupChange,
				},
			},
		);
		BX.addClass(bindElement, 'task-grade-and-report-selected');

		return false;
	},

	__onGradePopupClose: function() {
		BX.removeClass(this.bindElement, 'task-grade-and-report-selected');
	},

	__onGradePopupChange: function() {
		this.bindElement.className = 'task-grade-and-report'
			+ (this.listValue !== 'NULL' ? ' task-grade-' + this.listItem.className : '')
			+ (this.report ? ' task-in-report' : '');
		this.bindElement.title = BX.message('TASKS_MARK') + ': ' + this.listItem.name;

		BX.Tasks.GridActions.action('legacyUpdate', this.id, {
			data: {
				MARK: (this.listValue === 'NULL' ? '' : this.listValue),
			},
		});
	},

	renderTimerItem: function(taskId, timeSpentInLogs, timeEstimate, isRunning, taskTimersTotalValue, canStartTimeTracking) {
		canStartTimeTracking = canStartTimeTracking || false;

		var className = 'task-timer-inner';
		var timeSpent = timeSpentInLogs + taskTimersTotalValue;

		if (isRunning)
		{
			className = className + ' task-timer-play';
		}
		else if (canStartTimeTracking)
		{
			className = className + ' task-timer-pause';
		}
		else
		{
			className = className + ' task-timer-clock';
		}

		if (timeEstimate > 0 && timeSpent > timeEstimate)
		{
			className = className + ' task-timer-overdue';
		}

		return (
			BX.create('span', {
				props: {
					id: 'task-timer-block-' + taskId,
					className: 'task-timer-block',
				},
				events: {
					click: (function(taskId, canStartTimeTracking) {
						return function() {
							if (BX.hasClass(BX('task-timer-block-inner-' + taskId), 'task-timer-play'))
							{
								BX.TasksTimerManager.stop(taskId);
							}
							else if (canStartTimeTracking)
							{
								BX.TasksTimerManager.start(taskId);
							}
						};
					})(taskId, canStartTimeTracking),
				},
				children: [
					BX.create('span', {
						props: {
							id: 'task-timer-block-inner-' + taskId,
							className: className,
						},
						children: [
							BX.create('span', {
								props: {
									className: 'task-timer-icon',
								},
							}),
							BX.create('span', {
								props: {
									id: 'task-timer-block-value-' + taskId,
									className: 'task-timer-time',
								},
								text: BX.Tasks.GridActions.renderTimerTimes(timeSpent, timeEstimate, isRunning),
							}),
						],
					}),
				],
			})
		);
	},

	renderTimerTimes: function(timeSpent, timeEstimate, isRunning) {
		var str = '';
		var showSeconds = !!isRunning;

		str = BX.Tasks.GridActions.renderSecondsToHHMMSS(timeSpent, showSeconds);

		if (timeEstimate > 0)
		{
			str = str + ' / ' + BX.Tasks.GridActions.renderSecondsToHHMMSS(timeEstimate, false);
		}

		return str;
	},

	renderSecondsToHHMMSS: function(totalSeconds, showSeconds) {
		var pad = '00';
		var hours = '';
		var minutes = '';
		var seconds = 0;

		if (totalSeconds > 0)
		{
			hours += Math.floor(totalSeconds / 3600);
			minutes += Math.floor(totalSeconds / 60) % 60;
		}
		else
		{
			hours += Math.ceil(totalSeconds / 3600);
			minutes += Math.ceil(totalSeconds / 60) % 60;
		}

		var result = pad.substring(0, 2 - hours.length) + hours + ':' + pad.substring(0, 2 - minutes.length) + minutes;

		if (showSeconds)
		{
			seconds = '' + totalSeconds % 60;
			result = result + ':' + pad.substring(0, 2 - seconds.length) + seconds;
		}

		return result;
	},

	redrawTimerNode: function(taskId, timeSpentInLogs, timeEstimate, isRunning, taskTimersTotalValue, canStartTimeTracking) {
		var taskTimerBlock = BX('task-timer-block-' + taskId);
		var newTaskTimerBlock = BX.Tasks.GridActions.renderTimerItem(
			taskId,
			timeSpentInLogs,
			timeEstimate,
			isRunning,
			taskTimersTotalValue,
			canStartTimeTracking,
		);

		if (taskTimerBlock)
		{
			taskTimerBlock.parentNode.replaceChild(newTaskTimerBlock, taskTimerBlock);
		}
		else
		{
			var container = BX('task-timer-block-container-' + taskId);
			if (container)
			{
				// Unregister callback function for this item (if it exists)
				if (this.registeredTimerNodes[taskId])
				{
					BX.removeCustomEvent(window, 'onTaskTimerChange', this.registeredTimerNodes[taskId]);
				}

				container.appendChild(newTaskTimerBlock);

				// If row inserted into DOM -> register callback function
				if (BX('task-timer-block-' + taskId))
				{
					this.registeredTimerNodes[taskId] = this.__getTimerChangeCallback(taskId);
					BX.addCustomEvent(window, 'onTaskTimerChange', this.registeredTimerNodes[taskId]);
				}
			}
		}
	},

	removeTimerNode: function(taskId) {
		if (this.registeredTimerNodes[taskId])
		{
			BX.removeCustomEvent(window, 'onTaskTimerChange', this.registeredTimerNodes[taskId]);
		}

		var taskTimerBlock = BX('task-timer-block-' + taskId);
		if (taskTimerBlock)
		{
			taskTimerBlock.parentNode.removeChild(taskTimerBlock);
		}
	},

	__getTimerChangeCallback: function(selfTaskId) {
		var state = null;

		return function(params) {
			var switchStateTo = null;
			var innerTimerBlock = null;

			if (params.action === 'refresh_daemon_event')
			{
				if (Number(params.taskId) !== Number(selfTaskId))
				{
					if (state === 'paused')
					{
						return;
					}
					switchStateTo = 'paused';
				}
				else
				{
					if (state !== 'playing')
					{
						switchStateTo = 'playing';
					}

					BX.Tasks.GridActions.redrawTimerNode(
						params.taskId,
						params.data.TASK.TIME_SPENT_IN_LOGS,
						params.data.TASK.TIME_ESTIMATE,
						true,	// IS_TASK_TRACKING_NOW
						params.data.TIMER.RUN_TIME,
						true,
					);
				}
			}
			else if (params.action === 'start_timer')
			{
				if (
					Number(selfTaskId) === Number(params.taskId)
					&& params.timerData
					&& Number(selfTaskId) === Number(params.timerData.TASK_ID)
				)
				{
					switchStateTo = 'playing';
				}
				else
				{
					switchStateTo = 'paused'; // other task timer started, so we need to be paused
				}
			}
			else if (params.action === 'stop_timer')
			{
				if (Number(selfTaskId) == Number(params.taskId))
				{
					switchStateTo = 'paused';
				}
			}
			else if (params.action === 'init_timer_data')
			{
				if (params.data.TIMER)
				{
					if (Number(params.data.TIMER.TASK_ID) === Number(selfTaskId))
					{
						switchStateTo = (params.data.TIMER.TIMER_STARTED_AT > 0 ? 'playing' : 'paused');
					}
					else if (params.data.TIMER.TASK_ID > 0)
					{
						// our task is not playing now
						switchStateTo = 'paused';
					}
				}
			}

			if (switchStateTo !== null)
			{
				innerTimerBlock = BX('task-timer-block-inner-' + selfTaskId);
				if (innerTimerBlock && !BX.hasClass(innerTimerBlock, 'task-timer-clock'))
				{
					if (switchStateTo === 'paused')
					{
						BX.removeClass(innerTimerBlock, 'task-timer-play');
						BX.addClass(innerTimerBlock, 'task-timer-pause');
					}
					else if (switchStateTo === 'playing')
					{
						BX.removeClass(innerTimerBlock, 'task-timer-pause');
						BX.addClass(innerTimerBlock, 'task-timer-play');
					}
				}

				state = switchStateTo;
			}
		};
	},
};

BX(function() {
	'use strict';

	BX.Tasks.Grid = function(options) {
		this.grid = BX.Main.gridManager.getInstanceById(options.gridId);

		this.userId = Number(options.userId);
		this.ownerId = Number(options.ownerId);
		this.groupId = Number(options.groupId);
		this.lastGroupId = Number(options.lastGroupId);

		this.sorting = options.sorting;
		this.groupByGroups = (options.groupByGroups === 'true');
		this.groupBySubTasks = (options.groupBySubTasks === 'true');
		this.arParams = options.arParams;
		this.migrationBarOptions = options.migrationBarOptions;

		this.calendarSettings = (options.calendarSettings ? options.calendarSettings : {});

		this.taskList = new Map();
		this.comments = new Map();

		this.queue = {
			pull: new BX.Tasks.Runtime.DebouncedQueue({
				timeout: 1000,
				events: {
					onCommitAsync: (event) => BX.Tasks.ControllerTask.getRepositoryByCollectionPushEventsAsync(event.getData().poolItems, this)
				}
			}),
			event: new BX.Tasks.Runtime.DebouncedQueue({
				timeout: 10,
				events: {
					onCommitAsync: (event) => BX.Tasks.ControllerTask.emitByCollectionEventImitterEventsAsync(event.getData().poolItems)
				}
			})
		};

		this.actions = {
			taskAdd: 'taskAdd',
			taskUpdate: 'taskUpdate',
			taskRemove: 'taskRemove',
			commentAdd: 'commentAdd',
			pinChanged: 'pinChanged',
		};
		this.classes = {
			highlighted: 'task-list-item-highlighted',
			pinned: 'tasks-list-item-pinned',
		};

		this.isMyList = this.userId === this.ownerId;
		this.canPin = this.isMyList;

		this.updateCanMove();
		this.init(options);
	};

	BX.Tasks.Grid.prototype = {
		init: function(options) {
			this.bindEvents();
			this.fillTaskListItems(options.taskList);
			this.colorPinnedRows();
			this.showStub();

			if (options.arParams && options.arParams['LAZY_LOAD'])
			{
				this.grid.reload();
			}
		},

		bindEvents: function() {
			var eventHandlers = {
				comment_read_all: this.onPullCommentReadAll,
				project_read_all: this.onPullProjectReadAll,
				tag_changed: this.onPullTagChanged,
			};

			var eventHandlersToPool = {
				comment_add: this.onPullCommentAdd,
				task_add: this.onPullTaskAdd,
				task_update: this.onPullTaskUpdate,
				task_view: this.onPullTaskView,
				task_remove: this.onPullTaskRemove,
				user_option_changed: this.onUserOptionChanged,
			};

			BX.Event.EventEmitter.subscribe('BX.Tasks.ControllerTask:onGetRepository', function(e) {
				this.queue.event.push(e.getData());
			}.bind(this));

			BX.Event.EventEmitter.subscribe('BX.Tasks.Event:onEmit', function(e) {
				var params = e.getData().params;
				var command = e.getData().command;
				var repository = e.getData().repository;

				if (eventHandlersToPool[command])
				{
					eventHandlersToPool[command].apply(this, [params, repository]);
				}
			}.bind(this));

			BX.addCustomEvent('onPullEvent-tasks', function(command, params)
			{
				if (eventHandlers[command])
				{
					eventHandlers[command].apply(this, [params]);
				}
				else
				{
					var inx = (new BX.Tasks.ControllerTaskEvent()).resolveIdByParam(command, params);
					if (inx > 0 )
					{
						this.queue.pull.push({
							id: inx,
							params
						}, command);
					}
				}
			}.bind(this));

			BX.addCustomEvent('BX.Main.grid:sort', function(column, grid) {
				if (grid === this.grid)
				{
					this.sorting.sort = {};
					this.sorting.sort[column.sort_by] = column.sort_order;
				}
			}.bind(this));

			BX.addCustomEvent('BX.Main.grid:paramsUpdated', function() {
				this.updateCanMove();
				this.colorPinnedRows();
				this.clearTaskListItems();
				this.clearLastGroupId();
				this.getRows()
				.map(function(row) {
					return row.getId();
				})
				.filter(function(id) {
					return id !== 'template_0';
				})
				.forEach(function(id) {
					this.addTaskListItem(id);
					this.updateLastGroupId(id);
				}.bind(this))
				;
				this.showStub();
			}.bind(this));

			BX.addCustomEvent('BX.Tasks.Filter.group', function(grid, groupType, value) {
				if (this.getGrid() === grid)
				{
					this[groupType] = value;
				}
			}.bind(this));

			// this solution cause http://jabber.bx/view.php?id=155858
			// BX.addCustomEvent('Grid::beforeRequest', function(gridData, eventArgs) {
			// 	eventArgs.url = BX.util.add_url_param(eventArgs.url, {lastGroupId: this.lastGroupId});
			// }.bind(this));
		},

		updateCanMove: function() {
			this.canMove = (
				this.isMyList
				&& this.sorting.sort.ACTIVITY_DATE
				&& this.sorting.sort.ACTIVITY_DATE === 'desc'
				&& !this.groupByGroups
				&& !this.groupBySubTasks
			);
		},

		checkCanMove: function() {
			return this.canMove;
		},

		colorPinnedRows: function() {
			this.getRows().forEach(function(row) {
				var node = row.getNode();
				this.getIsPinned(row.getId())
					? BX.addClass(node, this.classes.pinned)
					: BX.removeClass(node, this.classes.pinned)
				;
			}.bind(this));
		},

		getIsPinned: function(rowId) {
			return this.isRowExist(rowId)
				&& this.getRowNodeById(rowId).querySelector('.main-grid-cell-content-action-pin.main-grid-cell-content-action-active') instanceof HTMLElement;
		},

		onPullTaskView: function(data, repository) {
			if (this.userId !== Number(data.USER_ID) || !this.isRowExist(data.TASK_ID.toString()))
			{
				return;
			}
			this.updateActivityDateCellForTask(data.TASK_ID, {}, {}, repository);
		},

		onPullCommentReadAll: function(data) {
			this.onReadAll(data);
		},

		onPullProjectReadAll: function(data) {
			this.onReadAll(data);
		},

		onReadAll: function(data) {
			if (this.userId !== Number(data.USER_ID))
			{
				return;
			}
			this.updateActivityDateCellForTasksFromLocalStorage();
		},

		updateActivityDateCellForTasksHandler: function(response, rowData, parameters)
		{
			Object.keys(response.data).forEach(function(taskId) {
				if (this.isRowExist(taskId))
				{
					var row = this.getRowById(taskId);
					var rowData = response.data[taskId];

					if (row.getCellById('ACTIVITY_DATE'))
					{
						row.setCellsContent({ ACTIVITY_DATE: rowData.content.ACTIVITY_DATE });
					}
					row.setActions(rowData.actions);
					row.setCellActions(rowData.cellActions);
					row.setCounters(rowData.counters);

					if (parameters.highlightRow === true)
					{
						this.highlightGridRow(taskId);
					}
				}
			}.bind(this))
		},

		updateActivityDateCellForTasksFromLocalStorage: function()
		{
			var parameters = {};
			var taskIds = this.getTaskListItems();

			var params = {
				taskIds: taskIds,
				arParams: this.arParams,
			};

			BX.ajax.runComponentAction('bitrix:tasks.task.list', 'getGridRows', {
				mode: 'class',
				data: params,
			}).then(
				function(response) {
					this.updateActivityDateCellForTasksHandler(response, {},{});
				}.bind(this),
			).catch(
				function(response) {
					if (response.errors)
					{
						BX.Tasks.alert(response.errors);
					}
				}.bind(this),
			);
		},

		updateActivityDateCellForTask: function(taskId, rowData, parameters, repository)
		{
			parameters = parameters || {};

			var item = repository.collectionGrid.get(BX.Text.toNumber(taskId));

			if (item[taskId])
			{
				this.updateActivityDateCellForTasksHandler({data: item}, rowData, parameters);
			}
			else
			{
				BX.Tasks.alert('grid.id is empty' + taskId);
			}
		},

		onPullCommentAdd: function(data, repository) {
			if (this.checkComment(data))
			{
				var xmlId = data.entityXmlId.split('_');
				if (xmlId)
				{
					this.checkTask(xmlId[1], {
						action: this.actions.commentAdd,
						userId: Number(data.ownerId),
						isCompleteComment: data.isCompleteComment,
					}, repository);
				}
			}
		},

		onPullTagChanged: function(params)
		{
			if (this.groupId === 0)
			{
				this.getGrid().reload();
			}
			if (this.groupId !== 0 && params.groupId === this.groupId)
			{
				this.getGrid().reload();
			}
			var dialog = BX.UI.EntitySelector.Dialog.getById('tasks-task-list-tag-widget');
			dialog && dialog.hide();
		},

		onPullTaskAdd: function(data, repository) {
			if (data.params.addCommentExists === false)
			{
				this.checkTask(data.TASK_ID.toString(), {
					action: this.actions.taskAdd,
					userId: this.ownerId,
				}, repository);
			}
		},

		onPullTaskUpdate: function(data, repository) {
			var dialog = BX.UI.EntitySelector.Dialog.getById('tasks-task-list-tag-widget');
			if (dialog)
			{
				if ('GROUP_ID' in data.AFTER && data.AFTER.GROUP_ID !== data.BEFORE.GROUP_ID)
				{
					dialog.hide()
				}
			}
			if (data.params.updateCommentExists === false)
			{
				this.checkTask(data.TASK_ID.toString(), {
					action: this.actions.taskUpdate,
					userId: this.ownerId,
				}, repository);
			}
			else if (
				data.params.removedParticipants.includes(this.ownerId.toString())
				&& (!this.groupId || this.groupId !== Number(data.AFTER.GROUP_ID))
			)
			{
				this.removeItem(data.TASK_ID.toString());
			}
		},

		onPullTaskRemove: function(data, repository) {
			if (this.checkCanMove())
			{
				this.removeItem(data.TASK_ID.toString());
			}
		},

		onUserOptionChanged: function(data, repository) {
			if (!this.checkCanMove() || this.userId !== Number(data.USER_ID))
			{
				return;
			}

			var taskId = data.TASK_ID.toString();

			switch (Number(data.OPTION))
			{
				case 1:
					this.updateActivityDateCellForTask(taskId, {}, {}, repository);
					break;

				case 2:
				case 3:
					if (this.canPin)
					{
						this.placeToNearTasks(taskId, null, { action: this.actions.pinChanged }, repository);
					}
					break;

				default:
					break;
			}
		},

		placeToNearTasks: function(taskId, taskData, parameters, repository) {
			var queryParams = {
				taskId: taskId,
				navigation: {
					pageNumber: this.getPageNumber(),
					pageSize: this.getPageSize(),
				},
				arParams: this.arParams,
			};

			BX.ajax.runComponentAction('bitrix:tasks.task.list', 'getNearTasks', {
				mode: 'class',
				data: queryParams,
			}).then(
				function(response) {
					if (response.data)
					{
						var before = response.data.before;
						var after = response.data.after;

						if ((before && this.isRowExist(before)) || (after && this.isRowExist(after)))
						{
							var params = {
								before: before,
								after: after,
							};
							Object.keys(parameters).forEach(function(key) {
								params[key] = parameters[key];
							});
							this.updateItem(taskId, taskData, params, repository);
						}
						else
						{
							this.removeItem(taskId);
						}
					}
				}.bind(this),
			).catch(
				function(response) {
					if (response.errors)
					{
						BX.Tasks.alert(response.errors);
					}
				}.bind(this),
			);
		},

		checkComment: function(data) {
			var xmlId = data.entityXmlId.split('_');
			if (!xmlId)
			{
				return false;
			}

			var entityType = xmlId[0];
			var taskId = xmlId[1];

			if (entityType !== 'TASK')
			{
				return false;
			}

			if (!this.comments.has(taskId))
			{
				this.comments.set(taskId, new Set());
			}

			var taskComments = this.comments.get(taskId);
			var messageId = data.messageId;
			var participants = data.participants.map(function(id) {
				return id.toString();
			});

			if (taskComments.has(messageId))
			{
				return false;
			}

			taskComments.add(messageId);

			return (participants.includes(this.userId.toString()) || this.groupId === data.groupId);
		},

		checkTask: function(taskId, parameters, repository) {
			parameters = parameters || {};

			var task = null;
			var collection = repository.collection;
			var collectionSiftThroughFilter = repository.collectionSiftThroughFilter;

			if (parameters.isCompleteComment === false || parameters.userId === this.userId)
			{
				task = collectionSiftThroughFilter.getById(taskId);
			}
			else
			{
				task = collection.getById(taskId);
			}

			this.onCheckTaskSuccess(task, taskId, parameters, repository);
		},

		onCheckTaskSuccess: function(model, taskId, parameters, repository)
		{
			if (BX.Type.isNil(model))
			{
				this.removeItem(taskId);
				return;
			}

			var taskData = model.getFields();

			if (this.isRowExist(taskId))
			{
				if (this.checkCanMove())
				{
					parameters.canMoveRow = (parameters.action !== this.actions.taskUpdate);
					this.updateGridRow(taskId, taskData, parameters, repository);
				}
				else
				{
					if (parameters.action === this.actions.commentAdd)
					{
						if (parameters.isCompleteComment === true)
						{
							this.getGrid().updateRow(taskId);
						}
						else
						{
							var rowData = {};
							rowData[taskId] = taskData;

							this.updateActivityDateCellForTask(
								taskId,
								rowData,
								{ highlightRow: true },
								repository
							);
						}
					}
					else if (parameters.action === this.actions.taskUpdate)
					{
						this.getGrid().updateRow(taskId);
					}
				}
			}
			else if (this.checkCanMove())
			{
				if (parameters.action === this.actions.taskUpdate)
				{
					this.placeToNearTasks(taskId, taskData, parameters);
				}
				else
				{
					this.updateItem(taskId, taskData, parameters, repository);
				}
			}
		},

		updateItem: function(taskId, rowData, parameters, repository) {
			rowData = rowData || null;
			parameters = parameters || {};

			if (!this.hasTaskListItem(taskId))
			{
				this.addTaskListItem(taskId);
				this.addGridRow(taskId, rowData, parameters, repository);
			}
			else
			{
				this.updateGridRow(taskId, rowData, parameters, repository);
			}
		},

		addGridRow: function(rowId, rowData, parameters, repository) {

			var item = repository.collectionGrid.get(BX.Text.toNumber(rowId));

			if (item[rowId])
			{
				var rowData = item[rowId];
				var options = {
					id: rowId,
					columns: rowData.content,
					actions: rowData.actions,
					cellActions: rowData.cellActions,
					counters: rowData.counters,
				};
				var moveRows = this.getGridMoveRows(rowId, parameters);

				if (moveRows.rowAfter)
				{
					options.insertAfter = moveRows.rowAfter;
				}
				else if (moveRows.rowBefore)
				{
					options.insertBefore = moveRows.rowBefore;
				}
				else
				{
					options.append = true;
				}

				if (this.taskList.size > this.getPageNumber() * this.getPageSize())
				{
					var lastRowId = this.getLastRowId();

					this.removeTaskListItem(lastRowId);
					BX.remove(this.getRowNodeById(lastRowId));
					this.showMoreButton();
				}

				this.hideStub();
				this.getRealtime().addRow(options);
				this.colorPinnedRows();
			}
			else
			{
				BX.Tasks.alert('grid.id is empty' + rowId);
			}
		},

		updateGridRow: function(rowId, rowData, parameters, repository) {
			if (!this.isRowExist(rowId))
			{
				return;
			}

			var item = repository.collectionGrid.get(BX.Text.toNumber(rowId));

			if (item[rowId])
			{
				var rowData = item[rowId];
				var row = this.getRowById(rowId);
				row.setCellsContent(rowData.content);
				row.setActions(rowData.actions);
				row.setCellActions(rowData.cellActions);
				row.setCounters(rowData.counters);

				if (parameters.canMoveRow !== false)
				{
					this.getGrid().getRows().reset();

					var moveRows = this.getGridMoveRows(rowId, parameters);
					this.moveRow(rowId, moveRows.rowAfter, moveRows.rowBefore);
				}
				this.highlightGridRow(rowId).then(function() {
					this.colorPinnedRows();
				}.bind(this));

				this.getGrid().bindOnRowEvents();

				BX.onCustomEvent('Tasks.Tasks.Grid:RowUpdate', { id: rowId });
			}
			else
			{
				BX.Tasks.alert('grid.id is empty' + rowId);
			}
		},

		removeItem: function(id) {
			if (!this.isRowExist(id))
			{
				return;
			}

			this.removeTaskListItem(id);
			this.getGrid().removeRow(id);

			BX.onCustomEvent('Tasks.Tasks.Grid:RowRemove', { id: id });
		},

		getGridMoveRows: function(rowId, parameters) {
			var rowBefore;
			var rowAfter;

			switch (parameters.action)
			{
				case this.actions.pinChanged:
				case this.actions.taskUpdate:
					rowBefore = parameters.before;
					rowAfter = parameters.after;
					break;

				default:
					rowBefore = this.getFirstRowId();
					rowAfter = (this.getIsPinned(rowId) ? 0 : this.getLastPinnedRowId());
					break;
			}

			return {
				rowBefore: rowBefore,
				rowAfter: rowAfter,
			};
		},

		moveRow: function(rowId, after, before) {
			if (after)
			{
				this.getGrid().getRows().insertAfter(rowId, after);
			}
			else if (before)
			{
				this.getGrid().getRows().insertBefore(rowId, before);
			}
		},

		getLastPinnedRowId: function() {
			var pinnedRows = Object.values(this.getRows()).filter(function(row) {
				return this.getIsPinned(row.getId());
			}.bind(this));
			var keys = Object.keys(pinnedRows);

			if (keys.length > 0)
			{
				return pinnedRows[keys[keys.length - 1]].getId();
			}

			return 0;
		},

		getFirstRowId: function() {
			var rows = this.getRows();
			return (rows.length ? this.getRowProp(rows[0], 'id') : 0);
		},

		getLastRowId: function() {
			var lastRow = this.getGrid().getRows().getBodyLastChild();
			return (lastRow ? this.getRowProp(lastRow, 'id') : 0);
		},

		highlightGridRow: function(rowId) {
			var promise = new BX.Promise();

			if (!this.isRowExist(rowId))
			{
				promise.reject();
				return promise;
			}

			var node = this.getRowNodeById(rowId);
			var isPinned = BX.hasClass(node, this.classes.pinned);

			if (isPinned)
			{
				BX.removeClass(node, this.classes.pinned);
			}

			BX.addClass(node, this.classes.highlighted);
			setTimeout(function() {
				BX.removeClass(node, this.classes.highlighted);
				if (isPinned)
				{
					BX.addClass(node, this.classes.pinned);
				}
				promise.fulfill();
			}.bind(this), 900);

			return promise;
		},

		getGrid: function() {
			return this.grid;
		},

		getRows: function() {
			return this.getGrid().getRows().getBodyChild();
		},

		isRowExist: function(id) {
			return this.getRowById(id) !== null;
		},

		getRowById: function(id) {
			return this.getGrid().getRows().getById(id);
		},

		getRowNodeById: function(id) {
			return this.getRowById(id).getNode();
		},

		getRowProp: function(row, propName) {
			return BX.data(row.getNode(), propName);
		},

		setRowProp: function(row, propName, propValue) {
			row.getNode().setAttribute('data-' + propName, propValue);
		},

		getPageNumber: function() {
			var pageNumber = 1;
			var navPanel = this.getGrid().getContainer().querySelector('.main-grid-nav-panel');
			if (navPanel)
			{
				var pagination = navPanel.querySelector('.main-ui-pagination');
				if (pagination)
				{
					var activePagination = pagination.querySelector('.main-ui-pagination-active');
					if (activePagination)
					{
						pageNumber = activePagination.innerText;
					}
				}
			}

			return pageNumber;
		},

		getPageSize: function() {
			var pageSize = 50;
			var selector = BX(this.getGrid().getContainerId() + '_' + this.getGrid().settings.get('pageSizeId'));

			if (selector)
			{
				pageSize = BX.data(selector, 'value');
			}

			return pageSize;
		},

		getRealtime: function() {
			return this.getGrid().getRealtime();
		},

		showStub: function() {
			// this.showMigrationBar();
		},

		hideStub: function() {
			this.getGrid().hideEmptyStub();
		},

		showMigrationBar: function() {
			if (
				this.taskList.size > 0
				|| this.groupId
			)
			{
				return;
			}

			var filterManager = BX.Main.filterManager.getById(BX.Tasks.GridActions.gridId);
			if (
				!filterManager
				|| !BX.Dom.hasClass(filterManager.getSearch().getContainer(), 'main-ui-filter-default-applied')
			)
			{
				return;
			}

			if (!this.migrationContainer)
			{
				var migration = new BX.UI.MigrationBar({
					title: this.migrationBarOptions.title,
					minWidth: 600,
					minHeight: 200,
					cross: false,
					items: this.migrationBarOptions.items.map(function(item) {
						return { src: item };
					}),
					buttons: [
						{
							text: this.migrationBarOptions.buttonMigrate,
							color: BX.UI.ButtonColor.PRIMARY,
							round: true,
							link: '/marketplace/?tag[]=migrator&tag[]=tasks',
						},
					],
					link: {
						text: this.migrationBarOptions.other,
					},
				});
				migration.show();
				this.migrationContainer = migration.getContainer();
				BX.Dom.style(this.migrationContainer, 'marginTop', '50px');
			}
			BX.Dom.append(this.migrationContainer, document.querySelector('.main-grid-empty-inner'));
		},

		showMoreButton: function() {
			this.getGrid().getMoreButton().getNode().style.display = 'inline-block';
		},

		hideMoreButton: function() {
			this.getGrid().getMoreButton().getNode().style.display = 'none';
		},

		getTaskListItems: function() {
			return Array.from(this.taskList.keys());
		},

		hasTaskListItem: function(id) {
			return this.taskList.has(parseInt(id, 10));
		},

		addTaskListItem: function(id) {
			this.taskList.set(parseInt(id, 10));
		},

		removeTaskListItem: function(id) {
			this.taskList.delete(parseInt(id, 10));
		},

		fillTaskListItems: function(items) {
			Object.keys(items).forEach(function(id) {
				this.addTaskListItem(id);
			}.bind(this));
		},

		clearTaskListItems: function() {
			this.taskList.clear();
		},

		updateLastGroupId: function(id) {
			if (id.indexOf('group_') === 0)
			{
				this.lastGroupId = Number(id.replace('group_', ''));
			}
		},

		clearLastGroupId: function() {
			this.lastGroupId = 0;
		},
	};

	BX.addCustomEvent('tasksTaskEvent', BX.delegate(function(type, data) {
		if (!BX.Tasks.GridActions.checkCanMove())
		{
			BX.Tasks.GridActions.reloadGrid();
		}
	}, this));

	BX.addCustomEvent('SidePanel.Slider:onCloseByEsc', function(event) {
		var reg = /tasks\/task\/edit/;
		var str = event.getSlider().getUrl();
		if (reg.test(str) && !confirm(BX.message('TASKS_CLOSE_PAGE_CONFIRM')))
		{
			event.denyAction();
		}
	});

	BX.addCustomEvent('BX.Main.Filter:apply', function(filterId, data, ctx) {
		var stringUrl = window.location.href;
		var url = new URL(stringUrl);
		var state = url.searchParams.get('F_STATE');
		var newUrl = (state === 'sR' ? stringUrl.replace('&F_STATE=sR', '') : stringUrl);

		window.history.replaceState(null, null, newUrl);
	}.bind(this));

	BX.addCustomEvent('Tasks.TopMenu:onItem', function(roleId, url) {
		var filterManager = BX.Main.filterManager.getById(BX.Tasks.GridActions.gridId);
		if (!filterManager)
		{
			console.log('BX.Main.filterManager not initialised');
			return;
		}

		var fields = {
			preset_id: BX.Tasks.GridActions.defaultPresetId,
			additional: { ROLEID: (roleId === 'view_all' ? 0 : roleId) },
		};
		var filterApi = filterManager.getApi();
		filterApi.setFilter(fields, { ROLE_TYPE: 'TASKS_ROLE_TYPE_' + (roleId === '' ? 'view_all' : roleId) });

		window.history.pushState(null, null, url);
	});

	BX.addCustomEvent('Tasks.Toolbar:onItem', function(event) {
		var data = event.getData();
		if (data.counter && data.counter.filter)
		{
			data.counter.filter.toggleByField({ PROBLEM: data.counter.filterValue });
		}
	});

	BX.Tasks.Grid.Sorting = function(options) {
		this.grid = BX.Main.gridManager.getInstanceById(options.gridId);
		this.currentGroupId = options.currentGroupId;
		this.treeMode = options.treeMode;

		BX.message(options.messages);

		this.init();

		BX.addCustomEvent('BX.Main.grid:rowDragStart', this.handleRowDragStart.bind(this));
		BX.addCustomEvent('BX.Main.grid:rowDragMove', this.handleRowDragMove.bind(this));
		BX.addCustomEvent('BX.Main.grid:rowDragEnd', this.handleRowDragEnd.bind(this));
	};

	BX.Tasks.Grid.Sorting.prototype = {

		init: function() {
			this.dragRow = null;
			this.targetRow = null;
			this.error = false;

			this.targetTask = null;
			this.before = true;
			this.newGroup = null;
			this.newParentId = null;
		},

		/**
		 *
		 * @returns {BX.Main.grid}
		 */
		getGrid: function() {
			return this.grid;
		},

		/**
		 *
		 * @param {Element} node
		 * @return {BX.Grid.Row}
		 */
		getRow: function(node) {
			return this.getGrid().getRows().get(node);
		},

		/**
		 *
		 * @param id
		 * @return {BX.Grid.Row}
		 */
		getRowById: function(id) {
			return this.getGrid().getRows().getById(id);
		},

		/**
		 *
		 * @returns {BX.Grid.Row[]}
		 */
		getRows: function() {
			return this.getGrid().getRows().getBodyChild();
		},

		/**
		 *
		 * @param {BX.Grid.Row} row
		 * @param {string} propName
		 * @return {string}
		 */
		getRowProp: function(row, propName) {
			return row.getNode().dataset[propName];
		},

		/**
		 *
		 * @param {BX.Grid.Row} row
		 * @returns {string}
		 */
		getRowType: function(row) {
			return this.getRowProp(row, 'type');
		},

		/**
		 *
		 * @param {BX.Grid.Row} row
		 * @returns {string}
		 */
		getRowGroupId: function(row) {
			return this.getRowProp(row, 'groupId');
		},

		/**
		 *
		 * @param {BX.Grid.RowDragEvent} dragEvent
		 * @param {BX.Main.grid} grid
		 */
		handleRowDragStart: function(dragEvent, grid) {
			this.dragRow = this.getRow(dragEvent.getDragItem());
		},

		/**
		 *
		 * @param {BX.Grid.RowDragEvent} dragEvent
		 * @param {BX.Main.grid} grid
		 */
		handleRowDragMove: function(dragEvent, grid) {
			this.targetRow = this.getRow(dragEvent.getTargetItem());
			var targetType = this.targetRow ? this.getRowType(this.targetRow) : null;

			this.newParentId = null;
			this.error = false;
			var newGroup = null;

			if (targetType === 'task')
			{
				var targetParentId = this.targetRow.getParentId();
				if (targetParentId !== this.dragRow.getParentId())
				{
					this.newParentId = targetParentId;
				}

				newGroup = this.getGroupByRow(this.targetRow);

				this.targetTask = this.targetRow;
				this.before = true;
			}
			else
			{
				if (targetType === 'group')
				{
					newGroup = this.getPreviousGroup(this.targetRow);
				}
				else
				{
					newGroup = this.getLastGroup();
				}

				var target = this.getClosestTask(this.targetRow);
				this.targetTask = target.task;
				this.before = target.before;
			}

			this.newGroup = newGroup.id !== this.getGroupByRow(this.dragRow).id ? newGroup : null;

			if (targetType === 'task' && this.isChildOf(this.targetTask, this.dragRow))
			{
				this.error = true;
			}
			else if (
				this.newGroup
				&& this.newGroup.id > 0
				&& (
					this.getRowProp(this.dragRow, 'canEdit') === 'false' ||
					!this.newGroup.canCreateTasks
				)
			)
			{
				this.error = true;
			}
			else if (
				this.newParentId !== null &&
				this.getRowProp(this.dragRow, 'canEdit') === 'false'
			)
			{
				this.error = true;
			}

			this.error ? dragEvent.disallowMove(BX.message('TASKS_ACCESS_DENIED')) : dragEvent.allowMove();
		},

		handleRowDragEnd: function(dragEvent, grid) {
			if (!this.error)
			{
				this.save();
			}

			this.init();
		},

		save: function() {
			var sourceId = this.dragRow.getId();
			var targetId = this.targetTask ? this.targetTask.getId() : null;

			if (sourceId === targetId)
			{
				return;
			}

			var data = {
				sourceId: sourceId,
				targetId: targetId,
				before: this.before ? 1 : 0,
				currentGroupId: this.currentGroupId,
			};

			if (this.newGroup !== null)
			{
				data.newGroupId = this.newGroup.id;
				this.setGroupId(this.dragRow, data.newGroupId);
			}

			if (this.newParentId !== null && this.treeMode)
			{
				data.newParentId = this.newParentId;
			}

			BX.ajax.runComponentAction('bitrix:tasks.task.list', 'sortTask', {
				mode: 'class',
				data: {
					data: data,
				},
			}).then(
				function(response) {

				}.bind(this),
			).catch(
				function(response) {
					if (response.errors)
					{
						BX.Tasks.alert(response.errors);
					}
				}.bind(this),
			);
		},

		getParentRow: function(row) {
			return this.getRowById(row.getParentId());
		},

		/**
		 *
		 * @param {BX.Grid.Row} child
		 * @param {BX.Grid.Row} parent
		 * @return {Boolean}
		 */
		isChildOf: function(child, parent) {
			var parentTask = this.getParentRow(child);
			while (parentTask !== null)
			{
				if (parentTask === parent)
				{
					return true;
				}

				parentTask = this.getParentRow(parentTask);
			}

			return false;
		},

		getGroupById: function(groupId) {
			var rows = this.getRows();

			for (var i = 0; i < rows.length; i++)
			{
				var row = rows[i];
				if (this.getRowType(row) === 'group' && this.getRowGroupId(row) === String(groupId))
				{
					return this.getGroupByRow(row);
				}
			}

			return this.getDefaultProject();
		},

		setGroupId: function(row, groupId) {
			row.getDataset().groupId = groupId;

			var children = row.getChildren();
			for (var i = 0; i < children.length; i++)
			{
				this.setGroupId(children[i], groupId);

			}
		},

		getDefaultProject: function() {
			return {
				id: '0',
				canCreateTasks: true,
			};
		},

		/**
		 *
		 * @param {BX.Grid.Row} row
		 */
		getGroupByRow: function(row) {
			if (this.getRowType(row) === 'group')
			{
				return {
					id: this.getRowGroupId(row),
					canCreateTasks: this.getRowProp(row, 'canCreateTasks') === 'true',
				};
			}
			else
			{
				return this.getGroupById(this.getRowGroupId(row));
			}
		},

		getLastGroup: function() {
			var group = null;
			var rows = this.getRows();

			for (var i = rows.length - 1; i >= 0; i--)
			{
				var row = rows[i];
				if (this.getRowType(row) === 'group')
				{
					return this.getGroupByRow(row);
				}
			}

			return this.getDefaultProject();
		},

		getPreviousGroup: function(currentGroup) {
			var group = null;
			var rows = this.getRows();
			var found = false;

			for (var i = rows.length - 1; i >= 0; i--)
			{
				var row = rows[i];
				if (currentGroup === row)
				{
					found = true;
					continue;
				}

				if (found && this.getRowType(row) === 'group')
				{
					return this.getGroupByRow(row);
				}
			}

			return this.getDefaultProject();
		},

		getClosestTask: function(currentRow) {
			var rows = this.getRows();
			var index = currentRow ? currentRow.getIndex() - 1 : rows.length;

			for (var i = index - 1; i >= 0; i--)
			{
				if (this.getRowType(rows[i]) === 'task' && rows[i].getDepth() === '0')
				{
					return {
						task: rows[i],
						before: false,
					};
				}
			}

			for (i = index + 1; i < rows.length; i++)
			{
				if (this.getRowType(rows[i]) === 'task' && rows[i].getDepth() === '0')
				{
					return {
						task: rows[i],
						before: true,
					};
				}
			}

			return {
				task: null,
				before: true,
			};
		},
	};

	BX.Tasks.TourGuideController = function(options) {
		this.tours = options.tours;
		this.guide = null;

		this.initGuides(options);
	};

	BX.Tasks.TourGuideController.prototype = {
		initGuides: function(options) {
			var firstGridTaskCreation = this.tours.firstGridTaskCreation;
			var expiredTasksDeadlineChange = this.tours.expiredTasksDeadlineChange;

			if (firstGridTaskCreation.show)
			{
				this.guide = new BX.Tasks.TourGuideController.FirstGridTaskCreationTourGuide(options);
			}
			else if (expiredTasksDeadlineChange.show || expiredTasksDeadlineChange.backgroundCheck)
			{
				this.guide = new BX.Tasks.TourGuideController.ExpiredTasksDeadlineChangeTourGuide(options);
			}
		},

		getGuide: function() {
			return this.guide;
		},
	};

	BX.Tasks.TourGuideController.FirstGridTaskCreationTourGuide = function(options) {
		this.tour = options.tours.firstGridTaskCreation;
		this.popupData = this.tour.popupData;

		this.start();
	};

	BX.Tasks.TourGuideController.FirstGridTaskCreationTourGuide.prototype = {
		start: function() {
			var addButton = BX('tasks-buttonAdd');
			if (!addButton)
			{
				return;
			}
			addButton.href = BX.Uri.addParam(addButton.href, { FIRST_GRID_TASK_CREATION_TOUR_GUIDE: 'Y' });

			this.guide = new BX.UI.Tour.Guide({
				steps: [
					{
						target: addButton,
						title: this.popupData[0].title,
						text: this.popupData[0].text,
					},
				],
				onEvents: true,
			});

			BX.addCustomEvent('UI.Tour.Guide:onFinish', function(event) {
				if (event.getData().guide === this.guide)
				{
					addButton.href = BX.Uri.removeParam(addButton.href, ['FIRST_GRID_TASK_CREATION_TOUR_GUIDE']);
				}
			}.bind(this));

			this.showNextStep();
		},

		showNextStep: function() {
			setTimeout(function() {
				this.guide.showNextStep();
			}.bind(this), 500);
		},
	};

	BX.Tasks.TourGuideController.ExpiredTasksDeadlineChangeTourGuide = function(options) {
		this.userId = options.userId;
		this.tour = options.tours.expiredTasksDeadlineChange;
		this.popupData = this.tour.popupData;
		this.counterToCheck = this.tour.counterToCheck;

		this.rowId = 0;
		this.calendarPopup = 0;
		this.isStopped = false;
		this.viewMode = 'grid';
		this.ajaxActionPrefix = 'tasks.tourguide.expiredtasksdeadlinechange.';

		this.grid = BX.Main.gridManager.getInstanceById(options.gridId);

		if (this.tour.show)
		{
			this.start();
		}
		else if (this.tour.backgroundCheck)
		{
			this.isPullListening = true;
		}

		this.bindEvents();
	};

	BX.Tasks.TourGuideController.ExpiredTasksDeadlineChangeTourGuide.prototype = {
		bindEvents: function() {
			var eventHandlers = {
				user_counter: this.onUserCounter.bind(this),
			};
			BX.addCustomEvent('onPullEvent-tasks', function(command, params) {
				if (eventHandlers[command])
				{
					eventHandlers[command].apply(this, [params]);
				}
			}.bind(this));

			BX.addCustomEvent('UI.Tour.Guide:onPopupClose', function() {
				this.stop();
			}.bind(this));

			BX.addCustomEvent('UI.Tour.Guide:onFinish', function(event) {
				if (event.getData().guide === this.guide && this.getCurrentStepIndex() === 0)
				{
					BX.addCustomEvent('BX.Main.grid:paramsUpdated', BX.proxy(this.onExpiredCounterGridReloaded, this));
				}
			}.bind(this));
		},

		onUserCounter: function(data) {
			if (!this.isPullListening || this.userId !== Number(data.userId))
			{
				return;
			}

			var newCounter = Number(data[0].view_role_originator.expired) + Number(data[0].view_role_responsible.expired);
			if (newCounter >= Number(this.counterToCheck))
			{
				this.isPullListening = false;

				BX.ajax.runAction(this.ajaxActionPrefix + 'proceed', {
					analyticsLabel: {
						viewMode: this.viewMode,
					},
				}).then(function(result) {
					if (result.data)
					{
						this.start();
					}
				}.bind(this));
			}
		},

		start: function() {
			this.guide = new BX.UI.Tour.Guide({
				steps: [
					{
						target: document.querySelector('.tasks-counters--item-counter'),
						title: this.popupData[0].title,
						text: this.popupData[0].text,
					},
				],
				onEvents: true,
			});

			this.showNextStep();
		},

		markShowedStep: function(step) {
			BX.ajax.runAction(this.ajaxActionPrefix + 'markShowedStep', {
				analyticsLabel: {
					viewMode: this.viewMode,
					step: step,
				},
			});
		},

		onExpiredCounterGridReloaded: function() {
			BX.removeCustomEvent('BX.Main.grid:paramsUpdated', BX.proxy(this.onExpiredCounterGridReloaded, this));

			var target = null;
			Object.values(this.grid.getRows().getBodyChild()).forEach(function(row) {
				if (!this.rowId && row.getNode().querySelector('.ui-label.ui-label-danger.ui-label-fill.ui-label-link'))
				{
					this.rowId = row.getId();
					target = row.getNode().querySelector('.ui-label.ui-label-danger.ui-label-fill.ui-label-link');
				}
			}.bind(this));

			if (this.rowId > 0 && target)
			{
				this.guide.steps.push(
					new BX.UI.Tour.Step({
						target: target,
						title: this.popupData[1].title,
						text: this.popupData[1].text,
					}),
				);
				this.showNextStep();
			}
		},

		setCalendarPopup: function(popup) {
			if (!this.calendarPopup && this.getCurrentStepIndex() === 1)
			{
				this.calendarPopup = popup;

				BX.addCustomEvent(this.calendarPopup, 'onPopupAfterClose', BX.proxy(this.onCalendarPopupClose, this));
			}
		},

		onCalendarPopupClose: function() {
			BX.removeCustomEvent(this.calendarPopup, 'onPopupAfterClose', BX.proxy(this.onCalendarPopupClose, this));

			setTimeout(function() {
				BX.removeCustomEvent('Tasks.Tour.ExpiredTasksDeadlineChange:saveDeadline', BX.proxy(this.onDeadlineSave, this));
			}.bind(this), 500);

			BX.addCustomEvent('Tasks.Tour.ExpiredTasksDeadlineChange:saveDeadline', BX.proxy(this.onDeadlineSave, this));
		},

		onDeadlineSave: function() {
			BX.addCustomEvent('BX.Main.grid:paramsUpdated', BX.proxy(this.onGridReloaded, this));
		},

		onGridReloaded: function() {
			BX.removeCustomEvent('BX.Main.grid:paramsUpdated', BX.proxy(this.onGridReloaded, this));

			if (this.grid.getRows().getById(this.rowId) === null)
			{
				this.guide.steps.push(
					new BX.UI.Tour.Step({
						title: this.popupData[2].title,
						text: this.popupData[2].text,
						buttons: [
							{
								text: this.popupData[2].buttons[0],
								event: function() {
									this.stop();
								}.bind(this),
							},
						],
					}),
				);
				this.showNextStep();
			}
		},

		showNextStep: function() {
			if (this.isStopped)
			{
				return;
			}

			setTimeout(function() {
				this.guide.showNextStep();
				this.markShowedStep(this.getCurrentStepIndex());
			}.bind(this), 500);
		},

		getCurrentStepIndex: function() {
			return this.guide.currentStepIndex;
		},

		isCorrectRow: function(rowId) {
			return Number(this.rowId) === Number(rowId);
		},

		getIsStopped: function() {
			return this.isStopped;
		},

		stop: function() {
			this.isStopped = true;
			this.guide.close();
		},
	};
});

this.BX = this.BX || {};
(function (exports,main_core_events,main_core,tasks_taskModel) {
	'use strict';

	var Action = Object.freeze({
	  COMMENT_ADD: 'comment_add',
	  TASK_ADD: 'task_add',
	  TASK_UPDATE: 'task_update',
	  TASK_VIEW: 'task_view',
	  TASK_REMOVE: 'task_remove',
	  USER_OPTION_CHANGED: 'user_option_changed'
	});

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _timeout = /*#__PURE__*/new WeakMap();
	var _resolveIndexByEvent = /*#__PURE__*/new WeakSet();
	var _isDefinedAction = /*#__PURE__*/new WeakSet();
	var _emit = /*#__PURE__*/new WeakSet();
	var ControllerTaskEvent = /*#__PURE__*/function () {
	  function ControllerTaskEvent() {
	    var _options$timeout;
	    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, ControllerTaskEvent);
	    _classPrivateMethodInitSpec(this, _emit);
	    _classPrivateMethodInitSpec(this, _isDefinedAction);
	    _classPrivateMethodInitSpec(this, _resolveIndexByEvent);
	    _classPrivateFieldInitSpec(this, _timeout, {
	      writable: true,
	      value: 0
	    });
	    options = main_core.Type.isPlainObject(options) ? options : {};
	    babelHelpers.classPrivateFieldSet(this, _timeout, (_options$timeout = options.timeout) !== null && _options$timeout !== void 0 ? _options$timeout : 100);
	  }
	  babelHelpers.createClass(ControllerTaskEvent, [{
	    key: "resolveIdByParam",
	    value: function resolveIdByParam(cmd, params) {
	      if (_classPrivateMethodGet(this, _isDefinedAction, _isDefinedAction2).call(this, cmd)) {
	        var inx = _classPrivateMethodGet(this, _resolveIndexByEvent, _resolveIndexByEvent2).call(this, cmd, params);
	        if (inx > 0) {
	          return inx;
	        } else {
	          throw new Error("Index is not resolved for command: " + cmd);
	        }
	      }
	    }
	  }, {
	    key: "intervalEmitByParams",
	    value: function intervalEmitByParams(params) {
	      var _this = this;
	      return new Promise(function (resolve, reject) {
	        var items = Object.values(params);
	        var tm = setInterval(function () {
	          if (items.length === 0) {
	            clearTimeout(tm);
	            resolve();
	            return;
	          }
	          var item = items.shift();
	          var poolItem = item.poolItem,
	            repository = item.repository;

	          // console.log('poolItemsShift', items);
	          _classPrivateMethodGet(_this, _emit, _emit2).call(_this, poolItem, repository);
	        }, babelHelpers.classPrivateFieldGet(_this, _timeout));
	      });
	    }
	  }, {
	    key: "batchEmitByParams",
	    value: function batchEmitByParams(params) {
	      var _this2 = this;
	      return new Promise(function (resolve, reject) {
	        var items = Object.values(params);
	        // console.log('poolItemsShift', items);
	        for (var inx in items) {
	          if (!items.hasOwnProperty(inx)) {
	            continue;
	          }
	          var item = items[inx];
	          var poolItem = item.poolItem,
	            repository = item.repository;
	          _classPrivateMethodGet(_this2, _emit, _emit2).call(_this2, poolItem, repository);
	        }
	        resolve();
	      });
	    }
	  }]);
	  return ControllerTaskEvent;
	}();
	function _resolveIndexByEvent2(cmd, params) {
	  var result = '';
	  if ([Action.TASK_ADD, Action.TASK_UPDATE, Action.TASK_REMOVE, Action.TASK_VIEW, Action.USER_OPTION_CHANGED].includes(cmd)) {
	    result = params.TASK_ID;
	  } else {
	    result = params.taskId;
	  }
	  return result;
	}
	function _isDefinedAction2(value) {
	  var types = Object.values(Action);
	  return types.includes(value);
	}
	function _emit2(item, repository) {
	  var _Object$values = Object.values(item),
	    _Object$values2 = babelHelpers.slicedToArray(_Object$values, 1),
	    param = _Object$values2[0];
	  var _Object$keys = Object.keys(item),
	    _Object$keys2 = babelHelpers.slicedToArray(_Object$keys, 1),
	    command = _Object$keys2[0];
	  var params = param.fields.params;
	  // console.log('repository', repository);
	  main_core_events.EventEmitter.emit('BX.Tasks.Event:onEmit', {
	    command: command,
	    params: params,
	    repository: repository
	  });
	}

	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _repository = /*#__PURE__*/new WeakMap();
	var _internalize = /*#__PURE__*/new WeakSet();
	var _buildQuery = /*#__PURE__*/new WeakSet();
	var _init = /*#__PURE__*/new WeakSet();
	var ControllerTaskRepository = /*#__PURE__*/function () {
	  function ControllerTaskRepository() {
	    babelHelpers.classCallCheck(this, ControllerTaskRepository);
	    _classPrivateMethodInitSpec$1(this, _init);
	    _classPrivateMethodInitSpec$1(this, _buildQuery);
	    _classPrivateMethodInitSpec$1(this, _internalize);
	    _classPrivateFieldInitSpec$1(this, _repository, {
	      writable: true,
	      value: {
	        collectionGrid: new Map(),
	        collection: new tasks_taskModel.TaskCollection(),
	        collectionSiftThroughFilter: new tasks_taskModel.TaskCollection()
	      }
	    });
	  }
	  babelHelpers.createClass(ControllerTaskRepository, [{
	    key: "callByFilter",
	    value: function callByFilter(fields, params) {
	      var _this = this;
	      return new Promise(function (resolve, reject) {
	        var items = _classPrivateMethodGet$1(_this, _internalize, _internalize2).call(_this, fields, params);
	        var sets = _classPrivateMethodGet$1(_this, _buildQuery, _buildQuery2).call(_this, items);
	        if (Object.keys(sets).length > 0) {
	          BX.rest.callBatch(sets, function (result) {
	            Object.keys(sets).forEach(function (type) {
	              var _result$type;
	              var set = (_result$type = result[type]) !== null && _result$type !== void 0 ? _result$type : null;
	              if (main_core.Type.isNull(set) === false) {
	                var _items = set.answer.result;
	                _classPrivateMethodGet$1(_this, _init, _init2).call(_this, type, _items);
	              }
	            });
	            resolve(_this.get());
	          });
	        }
	      });
	    }
	  }, {
	    key: "get",
	    value: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _repository);
	    }
	  }]);
	  return ControllerTaskRepository;
	}();
	function _internalize2(fields, params) {
	  var result = {};
	  var items = {
	    collectionSiftThroughFilter: {
	      cmd: 'tasks.task.list',
	      filter: {
	        id: fields.id,
	        returnAccess: fields.returnAccess,
	        siftThroughFilter: {
	          userId: fields.siftThroughFilter.userId,
	          groupId: fields.siftThroughFilter.groupId
	        }
	      }
	    },
	    collection: {
	      cmd: 'tasks.task.list',
	      filter: {
	        id: fields.id,
	        returnAccess: fields.returnAccess
	      }
	    },
	    collectionGrid: {
	      cmd: 'tasks.task.getGridRows',
	      filter: {
	        id: fields.id
	      },
	      params: params.arParams
	    }
	  };
	  Object.keys(items).forEach(function (type) {
	    var item = items[type];
	    switch (type) {
	      case 'collection':
	      case 'collectionSiftThroughFilter':
	        result[type] = {
	          cmd: item.cmd,
	          param: tasks_taskModel.TaskCollection.internalize(item.filter)
	        };
	        break;
	      case 'collectionGrid':
	        result[type] = {
	          cmd: item.cmd,
	          param: {
	            taskIds: main_core.Type.isArrayFilled(item.filter.id) ? item.filter.id : [item.filter.id],
	            arParams: item.params
	          }
	        };
	        break;
	    }
	  });
	  return result;
	}
	function _buildQuery2(items) {
	  var result = {};
	  Object.keys(items).forEach(function (inx) {
	    var item = items[inx];
	    result[inx] = [item.cmd, item.param];
	  });
	  return result;
	}
	function _init2(type, items) {
	  var _this2 = this;
	  if (Object.keys(babelHelpers.classPrivateFieldGet(this, _repository)).includes(type)) {
	    switch (type) {
	      case 'collection':
	      case 'collectionSiftThroughFilter':
	        babelHelpers.classPrivateFieldGet(this, _repository)[type].init(items.tasks);
	        break;
	      case 'collectionGrid':
	        Object.keys(items).forEach(function (id) {
	          if (id > 0) {
	            var row = {};
	            row[id] = items[id];
	            babelHelpers.classPrivateFieldGet(_this2, _repository)[type].set(main_core.Text.toNumber(id), row);
	          }
	        });
	        break;
	    }
	  }
	}

	var ControllerTask = /*#__PURE__*/function () {
	  function ControllerTask() {
	    babelHelpers.classCallCheck(this, ControllerTask);
	  }
	  babelHelpers.createClass(ControllerTask, null, [{
	    key: "getRepositoryByCollectionPushEventsAsync",
	    value: function getRepositoryByCollectionPushEventsAsync(items, context) {
	      var taskRepository = new ControllerTaskRepository();
	      var id = ControllerTask.getValuesId(items);
	      return new Promise(function (resolve, reject) {
	        taskRepository.callByFilter({
	          id: id,
	          returnAccess: 'Y',
	          siftThroughFilter: {
	            userId: context.ownerId,
	            groupId: context.groupId
	          }
	        }, {
	          arParams: context.arParams
	        }).then(function () {
	          var repository = taskRepository.get();
	          main_core_events.EventEmitter.emit('BX.Tasks.ControllerTask:onGetRepository', {
	            params: {
	              items: items,
	              repository: repository
	            }
	          });
	          resolve();
	        });
	      });
	    }
	  }, {
	    key: "emitByCollectionEventImitterEventsAsync",
	    value: function emitByCollectionEventImitterEventsAsync(items) {
	      var eventLib = new ControllerTaskEvent();
	      var params = ControllerTask.prepareByPoolToEmit(items);
	      return new Promise(function (resolve, reject) {
	        eventLib.batchEmitByParams(params).then(function () {
	          return resolve();
	        });
	      });
	    }
	  }, {
	    key: "getValuesId",
	    value: function getValuesId(collection) {
	      var result = [];
	      try {
	        for (var inx in collection) {
	          if (!collection.hasOwnProperty(inx)) {
	            continue;
	          }
	          var _Object$keys = Object.keys(collection[inx]),
	            _Object$keys2 = babelHelpers.slicedToArray(_Object$keys, 1),
	            cmd = _Object$keys2[0];
	          var _Object$values = Object.values(collection[inx]),
	            _Object$values2 = babelHelpers.slicedToArray(_Object$values, 1),
	            params = _Object$values2[0];
	          var id = params.fields.id;
	          if (result.includes(id) === false) {
	            result.push(id);
	          }
	        }
	      } catch (e) {}
	      return result;
	    }
	  }, {
	    key: "prepareByPoolToEmit",
	    value: function prepareByPoolToEmit(items) {
	      var result = [];
	      Object.keys(items).forEach(function (key) {
	        var item = items[key];
	        var poolItems = item['default'].fields.params.items;
	        var repository = item['default'].fields.params.repository;
	        Object.keys(poolItems).forEach(function (key) {
	          var poolItem = poolItems[key];
	          result.push({
	            poolItem: poolItem,
	            repository: repository
	          });
	        });
	      });
	      // console.log('collection', result);
	      return result;
	    }
	  }]);
	  return ControllerTask;
	}();

	exports.Action = Action;
	exports.ControllerTask = ControllerTask;
	exports.ControllerTaskEvent = ControllerTaskEvent;
	exports.ControllerTaskRepository = ControllerTaskRepository;

}((this.BX.Tasks = this.BX.Tasks || {}),BX.Event,BX,BX.Tasks.TaskModel));


//# sourceMappingURL=script.js.map