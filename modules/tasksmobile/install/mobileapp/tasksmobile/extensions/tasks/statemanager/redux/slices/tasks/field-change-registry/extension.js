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
		 * @param {string} requestId
		 * @param {number} taskId
		 * @returns {Object}
		 */
		getChangedFields(requestId, taskId)
		{
			return (this.fieldsRegistry.get(requestId)?.[taskId] ?? {});
		}

		/**
		 * @public
		 * @param {string} requestId
		 * @param {number} taskId
		 * @param {Object} fields
		 */
		updateChangedFieldsAfterRequest(requestId, taskId, fields)
		{
			let canUpdateRequest = false;

			this.fieldsRegistry.forEach((tasks, key) => {
				if (canUpdateRequest)
				{
					const changedFields = Object.keys(tasks[taskId] || {});
					if (changedFields.length > 0)
					{
						const fieldsToUpdate = Object.fromEntries(
							Object.entries(fields).filter(([field]) => changedFields.includes(field)),
						);
						this.fieldsRegistry.set(key, { ...tasks, [taskId]: { ...tasks[taskId], ...fieldsToUpdate } });
					}
				}
				else if (key === requestId)
				{
					canUpdateRequest = true;
				}
			});
		}

		/**
		 * @public
		 * @param {number} taskId
		 * @param {Object} task
		 * @returns {Object}
		 */
		removeChangedFields(taskId, task)
		{
			const resultTask = { ...task };

			let changedFields = [];
			this.fieldsRegistry.forEach((tasks) => {
				changedFields = [...changedFields, ...(Object.keys(tasks[taskId] || {}))];
			});
			changedFields.forEach((field) => delete resultTask[field]);

			return resultTask;
		}

		/**
		 * @public
		 * @param {string} requestId
		 * @param {number} taskId
		 * @param {Object} fields
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
