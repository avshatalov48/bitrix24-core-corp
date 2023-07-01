(() => {
	const { Loc } = jn.require('loc');
	const {TaskView} = jn.require('tasks/layout/task/view');
	const {EventEmitter} = jn.require('event-emitter');

	class Pull
	{
		constructor(tabs, taskId, userId)
		{
			this.tabs = tabs;
			this.taskId = taskId;
			this.userId = userId;

			this.comments = new Set();
		}

		getEventHandlers()
		{
			return {
				task_remove: {
					method: this.onPullDelete,
					context: this,
				},
			};
		}

		subscribe()
		{
			BX.PULL.subscribe({
				moduleId: 'tasks',
				callback: data => this.executePullEvent(data),
			});
		}

		executePullEvent(data)
		{
			const has = Object.prototype.hasOwnProperty;
			const eventHandlers = this.getEventHandlers();
			const {command, params} = data;

			if (has.call(eventHandlers, command))
			{
				const {method, context} = eventHandlers[command];
				if (method)
				{
					method.apply(context, [params]);
				}
			}
		}

		onPullDelete(data)
		{
			if (Number(data.TASK_ID) === this.taskId)
			{
				Notify.showMessage(
					'',
					Loc.getMessage('TASKSMOBILE_TASKS_TASK_TABS_TASK_REMOVED_NOTIFICATION'),
					{
						time: 5,
					}
				);
				this.tabs.close();
			}
		}
	}

	class TaskTabs
	{
		static get tabNames()
		{
			return {
				view: 'tasks.task.view',
				comments: 'tasks.task.comments',
			};
		}

		constructor(tabs)
		{
			this.tabs = tabs;
			this.userId = parseInt(BX.componentParameters.get('USER_ID', 0), 10);
			this.taskId = parseInt(BX.componentParameters.get('TASK_ID', 0), 10);
			this.guid = (BX.componentParameters.get('GUID') || this.createGuid());

			this.pull = new Pull(this.tabs, this.taskId, this.userId);
			this.pull.subscribe();

			this.eventEmitter = EventEmitter.createWithUid(this.guid);

			BX.onViewLoaded(() => {
				this.bindEvents();
				this.showViewWidget();
				this.setTopButtonsForCommentsTab();
			});
		}

		createGuid()
		{
			const s4 = () => Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);

			return `${s4()}${s4()}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`;
		}

		bindEvents()
		{
			this.tabs.on('onTabSelected', (tab, changed, options) => this.onTabSelected(tab,  changed, options));

			this.eventEmitter.on('tasks.task.view:updateTitle', (data) => {
				this.tabs.setTitle({
					useProgress: false,
					text: data.title,
				});
			});
			this.eventEmitter.on('tasks.task.view:setActiveTab', (data) => {
				if (Object.keys(TaskTabs.tabNames).includes(data.tab))
				{
					this.tabs.setActiveItem(TaskTabs.tabNames[data.tab]);
				}
			});
			this.eventEmitter.on('tasks.task.view:updateTab', (data) => {
				const options = {
					label: (data.value > 0 ? String(data.value) : ''),
				};
				if (data.color)
				{
					options.style = {
						activeBadgeColor: data.color,
						inactiveBadgeColor: data.color,
					};
				}
				this.tabs.updateItem(TaskTabs.tabNames[data.tab], options);
			});
			this.eventEmitter.on('tasks.task.view:close', () => this.tabs.close());
		}

		onTabSelected(tab, changed, options)
		{
			if (changed)
			{
				BX.postWebEvent('tasks.task.tabs:onTabSelected', {
					guid: this.guid,
					tab: tab.id,
				});

				if (tab.id === TaskTabs.tabNames.comments && options)
				{
					analytics.send('tasks.task.commentTab.open', {action: options.action});
				}
			}
		}

		showViewWidget()
		{
			const widgets = this.tabs.nestedWidgets();
			const viewWidget = widgets[TaskTabs.tabNames.view];
			const taskObject = BX.componentParameters.get('TASK_OBJECT');
			if (!taskObject)
			{
				this.tabs.setTitle({useProgress: true}, true);
			}

			TaskView.open({
				layoutWidget: viewWidget,
				userId: this.userId,
				taskId: this.taskId,
				guid: this.guid,
				taskObject,
			});
		}

		setTopButtonsForCommentsTab()
		{
			const widgets = this.tabs.nestedWidgets();
			const commentsWidget = widgets[TaskTabs.tabNames.comments];

			commentsWidget.setLeftButtons([{
				svg: {
					content: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.722 6.79175L10.9495 10.5643L9.99907 11.5L9.06666 10.5643L5.29411 6.79175L3.96289 8.12297L10.008 14.1681L16.0532 8.12297L14.722 6.79175Z" fill="#A8ADB4"/></svg>',
				},
				callback: () => this.eventEmitter.emit('tasks.task.tabs:onCommentsTabTopButtonCloseClick'),
			}]);
			commentsWidget.setRightButtons([{
				type: 'more',
				callback: () => this.eventEmitter.emit('tasks.task.tabs:onCommentsTabTopButtonMoreClick'),
			}]);
		}
	}

	return new TaskTabs(tabs);
})();