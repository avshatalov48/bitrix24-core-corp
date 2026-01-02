/**
 * @module tasks/layout/task/view-new/services/pull-listener
 */
jn.define('tasks/layout/task/view-new/services/pull-listener', (require, exports, module) => {
	const { assertDefined } = require('utils/validation');
	const { Type } = require('type');
	const { PullCommand, TaskRole, ReactionActionType, EntityType } = require('tasks/enum');

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
		updateViewsCount,
		taskRemoved,
		tasksRead,
	} = require('tasks/statemanager/redux/slices/tasks');
	const { reactionAdded, reactionChanged, reactionCancelled } = require('statemanager/redux/slices/reactions');

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

			this.unsubscribeCallbacks = [];
			this.commentsCountToSkip = 0;
		}

		/**
		 * @public
		 */
		subscribe()
		{
			this.unsubscribeCallbacks.push(
				BX.PULL.subscribe({
					moduleId: 'main',
					callback: this.#updateReaction.bind(this),
				}),
				BX.PULL.subscribe({
					moduleId: 'tasks',
					callback: this.#executePullEvent.bind(this),
				}),
				BX.PULL.subscribe({
					moduleId: 'contentview',
					callback: this.#updateViewersCounter.bind(this),
				}),
			);

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
			if (this.unsubscribeCallbacks)
			{
				this.unsubscribeCallbacks.forEach((unsubscribe) => {
					unsubscribe();
				});
				this.unsubscribeCallbacks = [];
			}
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

		#updateReaction({ command, params })
		{
			if (
				Number(params.ENTITY_ID) === Number(this.#task.id)
				&& params.ENTITY_TYPE_ID === EntityType.TASK
				&& command === PullCommand.REACTION_UPDATE
			)
			{
				const actionMap = {
					[ReactionActionType.ADD]: reactionAdded,
					[ReactionActionType.CHANGE]: reactionChanged,
					[ReactionActionType.CANCEL]: reactionCancelled,
				};

				const action = actionMap[params.TYPE];

				if (action)
				{
					dispatch(action({
						taskId: this.#task.id,
						entityId: params.ENTITY_ID,
						entityType: params.ENTITY_TYPE_ID,
						userId: params.USER_ID,
						reactionId: params.REACTION,
						actionType: params.RESULT,
					}));
				}
			}
		}

		#updateViewersCounter({ command, params })
		{
			if (
				Number(params.USER_ID) !== this.userId
				&& Number(params.ENTITY_ID) === Number(this.#task.id)
				&& params.TYPE_ID === EntityType.TASK
				&& command === PullCommand.TASK_VIEW_ADD
			)
			{
				dispatch(
					updateViewsCount({
						taskId: params.ENTITY_ID,
						totalCounter: params.TOTAL_VIEWS,
					}),
				);
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
				if (
					data.params.updateCommentExists
					|| data.updateDate === this.#task.updateDate
				)
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
