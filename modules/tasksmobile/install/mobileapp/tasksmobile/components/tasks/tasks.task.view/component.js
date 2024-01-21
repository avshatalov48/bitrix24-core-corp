(() => {
	const require = (ext) => jn.require(ext);
	const { Loc } = require('loc');
	const { TaskView } = require('tasks/layout/task/view');
	const { EventEmitter } = require('event-emitter');

	class Pull
	{
		constructor(layout, taskId, userId)
		{
			this.layout = layout;
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
				callback: (data) => this.executePullEvent(data),
			});
		}

		executePullEvent(data)
		{
			const has = Object.prototype.hasOwnProperty;
			const eventHandlers = this.getEventHandlers();
			const { command, params } = data;

			if (has.call(eventHandlers, command))
			{
				const { method, context } = eventHandlers[command];
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
					Loc.getMessage('TASKSMOBILE_TASKS_TASK_VIEW_TASK_REMOVED_NOTIFICATION'),
					{
						time: 5,
					},
				);
				this.layout.close();
			}
		}
	}

	class TasksTaskView
	{
		static createGuid()
		{
			const s4 = () => Math.floor((1 + Math.random()) * 0x10000).toString(16).slice(1);

			return `${s4()}${s4()}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`;
		}

		constructor(layout)
		{
			this.layout = layout;
			this.userId = parseInt(BX.componentParameters.get('USER_ID', 0), 10);
			this.taskId = parseInt(BX.componentParameters.get('TASK_ID', 0), 10);
			this.guid = (BX.componentParameters.get('GUID') || TasksTaskView.createGuid());

			this.pull = new Pull(this.layout, this.taskId, this.userId);
			this.pull.subscribe();

			this.eventEmitter = EventEmitter.createWithUid(this.guid);

			BX.onViewLoaded(() => {
				this.bindEvents();
				this.openWidget();
			});
		}

		bindEvents()
		{
			this.eventEmitter.on('tasks.task.view:close', () => this.layout.close());
		}

		openWidget()
		{
			const taskObject = BX.componentParameters.get('TASK_OBJECT');
			if (!taskObject)
			{
				this.layout.setTitle({ useProgress: true }, true);
			}
			this.layout.enableNavigationBarBorder(false);

			TaskView.open({
				layoutWidget: this.layout,
				userId: this.userId,
				taskId: this.taskId,
				guid: this.guid,
				isTabsMode: false,
				taskObject,
			});
		}
	}

	return new TasksTaskView(layout);
})();
