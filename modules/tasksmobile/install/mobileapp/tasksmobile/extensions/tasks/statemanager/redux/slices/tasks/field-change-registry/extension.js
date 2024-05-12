/**
 * @module tasks/statemanager/redux/slices/tasks/field-change-registry
 */
jn.define('tasks/statemanager/redux/slices/tasks/field-change-registry', (require, exports, module) => {
	class FieldChangeRegistry
	{
		constructor()
		{
			this.fieldsRegistry = new Map();
			this.counterRegistry = new Map();
		}

		/**
		 * @public
		 */
		clear()
		{
			this.fieldsRegistry.clear();
			this.counterRegistry.clear();
		}

		/**
		 * @public
		 * @param {Object} task
		 * @returns {Object}
		 */
		removeChangedFields(task)
		{
			const resultTask = { ...task };

			let changedFields = [];
			this.fieldsRegistry.forEach((tasks) => {
				changedFields = [...changedFields, ...(tasks[resultTask.id] || [])];
			});
			changedFields.forEach((field) => delete resultTask[field]);

			return resultTask;
		}

		/**
		 * @public
		 * @param {string} requestId
		 * @param {number} taskId
		 * @param {Array<string>} fields
		 */
		registerFieldsChange(requestId, taskId, fields)
		{
			if (!this.fieldsRegistry.has(requestId))
			{
				this.fieldsRegistry.set(requestId, {});
			}

			this.fieldsRegistry.set(requestId, { ...this.fieldsRegistry.get(requestId), [taskId]: fields });
		}

		/**
		 * @public
		 * @param {string} requestId
		 */
		unregisterFieldsChange(requestId)
		{
			this.fieldsRegistry.delete(requestId);
		}

		/**
		 * @returns {number}
		 */
		getCounter()
		{
			return [...this.counterRegistry.values()].reduce((sum, counter) => sum + counter, 0);
		}

		/**
		 * @public
		 * @param {string} requestId
		 * @param {number} counter
		 */
		registerCounterChange(requestId, counter)
		{
			if (!this.counterRegistry.has(requestId))
			{
				this.counterRegistry.set(requestId, 0);
			}

			this.counterRegistry.set(requestId, this.counterRegistry.get(requestId) + counter);
		}

		/**
		 * @public
		 * @param {string} requestId
		 */
		unregisterCounterChange(requestId)
		{
			this.counterRegistry.delete(requestId);
		}
	}

	module.exports = {
		FieldChangeRegistry: new FieldChangeRegistry(),
	};
});
