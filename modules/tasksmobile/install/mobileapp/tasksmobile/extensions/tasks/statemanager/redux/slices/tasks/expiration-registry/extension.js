/**
 * @module tasks/statemanager/redux/slices/tasks/expiration-registry
 */
jn.define('tasks/statemanager/redux/slices/tasks/expiration-registry', (require, exports, module) => {
	const store = require('statemanager/redux/store');
	const { dispatch } = store;

	class ExpirationRegistry
	{
		constructor()
		{
			this.registry = new Map();
			this.reducers = null;
			this.selectors = null;
		}

		/**
		 * @public
		 * @param {Object<string, function>} reducers
		 */
		setReducers(reducers)
		{
			this.reducers = reducers;
		}

		/**
		 * @public
		 * @param {Object<string, function>} selectors
		 */
		setSelectors(selectors)
		{
			this.selectors = selectors;
		}

		/**
		 * @public
		 * @param {TaskModel|undefined} task
		 */
		handleDeadlineTimerForTask(task)
		{
			if (!task)
			{
				return;
			}

			const { id, deadline } = task;
			const { selectWillExpire } = this.selectors;

			this.removeTimer(id);

			if (selectWillExpire && selectWillExpire(task))
			{
				this.setTimer(id, deadline);
			}
		}

		/**
		 * @public
		 * @param taskId
		 * @returns {boolean}
		 */
		isTimerExist(taskId)
		{
			return this.registry.has(taskId);
		}

		/**
		 * @private
		 * @param taskId
		 * @param deadline
		 */
		setTimer(taskId, deadline)
		{
			if (this.isTimerExist(taskId))
			{
				this.removeTimer(taskId);
			}

			const oneDayInMs = 86_400_000;
			const deadlineInMs = deadline * 1000;
			const timeout = Math.max(deadlineInMs - Date.now(), 0);

			if (timeout > oneDayInMs * 3)
			{
				return;
			}

			const timerId = setTimeout(() => {
				this.removeTimer(taskId);
				if (this.reducers.taskExpired)
				{
					dispatch(
						this.reducers.taskExpired({
							taskId,
							deadline: deadlineInMs,
						}),
					);
				}
			}, timeout);

			this.registry.set(taskId, timerId);
		}

		/**
		 * @private
		 * @param taskId
		 */
		removeTimer(taskId)
		{
			if (!this.isTimerExist(taskId))
			{
				return;
			}

			clearTimeout(this.registry.get(taskId));
			this.registry.delete(taskId);
		}
	}

	module.exports = {
		ExpirationRegistry: new ExpirationRegistry(),
	};
});
