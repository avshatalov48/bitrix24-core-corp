/**
 * @module bizproc/task/tasks-performer/rules/rule
 */
jn.define('bizproc/task/tasks-performer/rules/rule', (require, exports, module) => {
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { useCallback } = require('utils/function');

	const { ButtonsWrapper, StartButton } = require('bizproc/task/buttons');
	const { TaskOpener } = require('bizproc/task/tasks-performer/rules/task-opener');

	class Rule
	{
		/**
		 * @param {[]} tasks
		 * @param {Object} targetTask
		 */
		static isApplicable(tasks, targetTask)
		{
			return false;
		}

		/**
		 * @param {Object} props
		 * @param {Object} props.layout
		 * @param {[]} props.tasks
		 * @param {?Function} props.onTasksCancel
		 * @param {?Function} props.onTasksCompleted
		 * @param {?Function} props.onTasksDelegated
		 * @param {?Function} props.onTaskNotFoundError
		 * @param {?Function} props.onFinishRule
		 * @param {?Function} props.generateExitButton
		 */
		constructor(props = {})
		{
			this.props = props;

			this.layout = props.layout;
			this.tasks = Type.isArrayFilled(props.tasks) ? clone(props.tasks) : null;

			this.uid = 'tasks-performer-rule';
			this.completedTasks = [];
			this.delegatedTasks = [];

			this.onStartButtonClick = this.onStartButtonClick.bind(this);
		}

		renderEntryPoint()
		{
			return ButtonsWrapper(
				{},
				new StartButton({
					style: { width: '100%' },
					onClick: useCallback(this.onStartButtonClick),
					testId: 'MBP_TASKS_PERFORMER_RULES_START_BUTTON',
				}),
			);
		}

		calculateEntryPointButtons()
		{
			return 1;
		}

		onStartButtonClick()
		{
			this.start()
				.then(() => {
					this.onFinishRule();
				})
				.catch((errors) => {
					if (errors)
					{
						console.error(errors);
					}
					this.onFinishRule(Boolean(errors));
				})
			;
		}

		async start()
		{
			throw new Error('Method "start" must be implemented');
		}

		doTaskCollection(tasks, taskRequest)
		{
			return new Promise((resolve, reject) => {
				this.onTasksCompleted(tasks);

				const taskIds = tasks.map((task) => task.id);
				BX.ajax.runAction('bizprocmobile.Task.doCollection', {
					json: {
						taskIds: taskIds.map((id) => parseInt(id, 10)),
						taskRequest,
					},
				}).then(resolve).catch(reject);
			});
		}

		delegateTasks(tasks, delegateRequest)
		{
			return new Promise((resolve, reject) => {
				this.onTasksDelegated(tasks);

				const taskIds = tasks.map((task) => task.id);
				const data = { taskIds, toUserId: delegateRequest.toUserId, fromUserId: delegateRequest.fromUserId };
				BX.ajax.runAction('bizproc.task.delegate', { data }).then(resolve).catch(reject);
			});
		}

		onFinishRule(isEarlyFinish = false)
		{
			if (Type.isFunction(this.props.onFinishRule))
			{
				this.props.onFinishRule(this.completedTasks, this.delegatedTasks, isEarlyFinish);
			}
		}

		async showTask(task)
		{
			return new Promise((resolve, reject) => {
				const taskOpener = new TaskOpener({
					parentLayout: this.layout,
					widgetTitle: task.typeName,
					uid: this.uid,
					taskId: task.id,
					generateExitButton: this.generateExitButton,
				});

				taskOpener.open()
					.then((result) => {
						if (
							!result.doTaskRequest
							&& !result.delegateRequest
							&& !result.finishRule
							&& !result.taskNotFound
						)
						{
							this.onTasksCancel([task]);
						}

						if (result.doTaskRequest)
						{
							this.onTasksCompleted([task]);
						}

						if (result.delegateRequest)
						{
							this.onTasksDelegated([task]);
						}

						if (result.taskNotFound)
						{
							this.onTaskNotFoundError([task]);
						}

						resolve(result);
					})
					.catch(reject)
				;
			});
		}

		onTasksCompleted(tasks)
		{
			this.completedTasks.push(...tasks);

			if (Type.isFunction(this.props.onTasksCompleted))
			{
				this.props.onTasksCompleted(tasks);
			}
		}

		onTasksDelegated(tasks)
		{
			this.delegatedTasks.push(...tasks);

			if (Type.isFunction(this.props.onTasksDelegated))
			{
				this.props.onTasksDelegated(tasks);
			}
		}

		onTasksCancel(tasks)
		{
			if (Type.isFunction(this.props.onTasksCancel))
			{
				this.props.onTasksCancel(tasks);
			}
		}

		onTaskNotFoundError(tasks)
		{
			if (Type.isFunction(this.props.onTaskNotFoundError))
			{
				this.props.onTaskNotFoundError(tasks);
			}
		}

		get generateExitButton()
		{
			return this.props.generateExitButton;
		}
	}

	module.exports = { Rule };
});
