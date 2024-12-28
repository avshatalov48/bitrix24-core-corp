/**
 * @module tasks/layout/task/view-new/services/pull-listener
 */
jn.define('tasks/layout/task/view-new/services/pull-listener', (require, exports, module) => {
	const { assertDefined } = require('utils/validation');
	const { Type } = require('type');
	const { PullCommand, TaskRole } = require('tasks/enum');

	const store = require('statemanager/redux/store');
	const { dispatch } = store;
	const {
		selectByTaskIdOrGuid,
		selectIsMember,
		selectIsResponsible,
		selectIsAccomplice,
		selectIsPureCreator,
		selectIsAuditor,
		commentWritten,
		taskRemoved,
		tasksRead,
	} = require('tasks/statemanager/redux/slices/tasks');

	class PullListener
	{
		/**
		 * @param options {{
		 *     taskId: String|Number,
		 *     callbacks: Object<String, Function>,
		 * }}
		 */
		constructor(options)
		{
			assertDefined(options.taskId, 'TaskView.PullListener: taskId option must be specified');

			this.taskId = options.taskId;
			this.userId = Number(env.userId);
			this.callbacks = options.callbacks;

			this.unsubscribeCallback = null;
			this.commentsCountToSkip = 0;
		}

		/**
		 * @public
		 */
		subscribe()
		{
			this.unsubscribeCallback = BX.PULL.subscribe({
				moduleId: 'tasks',
				callback: this.#executePullEvent.bind(this),
			});

			BX.addCustomEvent('tasks.task.comments:onCommentWritten', (eventData) => {
				if (Number(eventData.taskId) === Number(this.#task.id))
				{
					dispatch(
						commentWritten({
							taskId: this.#task.id,
						}),
					);
					this.commentsCountToSkip += 1;
				}
			});
		}

		/**
		 * @public
		 */
		unsubscribe()
		{
			this.unsubscribeCallback?.();
		}

		/**
		 * @private
		 * @param {string} command
		 * @param {object} params
		 */
		#executePullEvent({ command, params })
		{
			if (this.#isEventHandleable(command, params))
			{
				this.#eventHandlers[command]
					.apply(this, [params])
					.then(() => this.callbacks[command]?.apply(this))
					.catch(() => {})
				;
			}
		}

		/**
		 * @param {string} command
		 * @param {object} params
		 */
		#isEventHandleable(command, params)
		{
			const isEventForAllTasks = ['comment_read_all', 'project_read_all'].includes(command);
			const isEventForCurrentTask = (Number(params.TASK_ID || params.taskId) === Number(this.#task.id));
			const isCommandHandlerExist = Type.isFunction(this.#eventHandlers[command]);

			return ((isEventForAllTasks || isEventForCurrentTask) && isCommandHandlerExist);
		}

		/**
		 * @return {TaskReduxModel}
		 */
		get #task()
		{
			return selectByTaskIdOrGuid(store.getState(), this.taskId);
		}

		get #eventHandlers()
		{
			return {
				[PullCommand.TASK_UPDATE]: this.#onTaskUpdated,
				[PullCommand.TASK_REMOVE]: this.#onTaskRemoved,
				[PullCommand.TASK_VIEW]: this.#onTaskViewed,

				[PullCommand.COMMENT_ADD]: this.#onTaskCommentAdded,
				[PullCommand.COMMENT_READ_ALL]: this.#onCommentsReadAll,
				[PullCommand.PROJECT_READ_ALL]: this.#onProjectCommentsReadAll,

				[PullCommand.TASK_RESULT_CREATE]: this.#onTaskResultAdded,
				[PullCommand.TASK_RESULT_UPDATE]: this.#onTaskResultUpdated,
				[PullCommand.TASK_RESULT_DELETE]: this.#onTaskResultRemoved,

				[PullCommand.TASK_TIMER_START]: this.#onTaskTimerStarted,
				[PullCommand.TASK_TIMER_STOP]: this.#onTaskTimerStopped,

				[PullCommand.USER_OPTION_CHANGED]: this.#onTaskUserOptionChanged,
			};
		}

		#onTaskUpdated(data)
		{
			return new Promise((resolve, reject) => {
				if (data.params.updateCommentExists)
				{
					reject();

					return;
				}

				resolve();
			});
		}

		#onTaskRemoved()
		{
			return new Promise((resolve) => {
				dispatch(
					taskRemoved({ taskId: this.#task.id }),
				);
				resolve();
			});
		}

		#onTaskViewed()
		{
			return new Promise((resolve) => {
				dispatch(
					tasksRead({ taskIds: [this.#task.id] }),
				);
				resolve();
			});
		}

		#onTaskCommentAdded(data)
		{
			return new Promise((resolve, reject) => {
				if (this.commentsCountToSkip > 0 && Number(data.ownerId) === this.userId)
				{
					this.commentsCountToSkip -= 1;

					reject();

					return;
				}

				resolve();
			});
		}

		#onCommentsReadAll(data)
		{
			return new Promise((resolve) => {
				const roleCondition = {
					[TaskRole.ALL]: selectIsMember(this.#task),
					[TaskRole.RESPONSIBLE]: selectIsResponsible(this.#task),
					[TaskRole.ACCOMPLICE]: selectIsAccomplice(this.#task),
					[TaskRole.ORIGINATOR]: selectIsPureCreator(this.#task),
					[TaskRole.AUDITOR]: selectIsAuditor(this.#task),
				};
				const role = (data.ROLE || TaskRole.ALL);
				const groupId = Number(data.GROUP_ID);
				const groupCondition = (!groupId || this.#task.groupId === groupId);

				if (roleCondition[role] && groupCondition)
				{
					dispatch(
						tasksRead({ taskIds: [this.#task.id] }),
					);
				}

				resolve();
			});
		}

		#onProjectCommentsReadAll(data)
		{
			return new Promise((resolve) => {
				const groupId = Number(data.GROUP_ID);
				const isGroupSpecified = Boolean(groupId);
				const isForAllGroups = !isGroupSpecified;

				if (
					(isGroupSpecified && this.#task.groupId === groupId)
					|| (isForAllGroups && Boolean(this.#task.groupId))
				)
				{
					dispatch(
						tasksRead({ taskIds: [this.#task.id] }),
					);
				}

				resolve();
			});
		}

		#onTaskResultAdded()
		{
			return this.#callEmptyHandler();
		}

		#onTaskResultUpdated()
		{
			return this.#callEmptyHandler();
		}

		#onTaskResultRemoved()
		{
			return this.#callEmptyHandler();
		}

		#onTaskTimerStarted()
		{
			return this.#callEmptyHandler();
		}

		#onTaskTimerStopped()
		{
			return this.#callEmptyHandler();
		}

		#onTaskUserOptionChanged()
		{
			return this.#callEmptyHandler();
		}

		#callEmptyHandler()
		{
			return new Promise((resolve) => {
				resolve();
			});
		}
	}

	module.exports = { PullListener };
});
