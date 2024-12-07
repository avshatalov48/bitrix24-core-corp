/**
 * @module tasks/dashboard/settings-action-executor
 */
jn.define('tasks/dashboard/settings-action-executor', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');

	/**
	 * @class SettingsActionExecutor
	 */
	class SettingsActionExecutor
	{
		/**
		 * @param {Number|null} [projectId=null]
		 * @param {Number|null} [ownerId=null]
		 */
		constructor({ projectId = null, ownerId = null } = {})
		{
			this.executor = null;
			this.projectId = projectId;
			this.ownerId = ownerId;
		}

		/**
		 * @public
		 * @param {boolean} [useCache]
		 * @return {*}
		 */
		call(useCache = false)
		{
			return this.#getExecutor().call(useCache);
		}

		/**
		 * @public
		 * @return {RunActionCache}
		 */
		getCache()
		{
			return this.#getExecutor().getCache();
		}

		/**
		 * @private
		 * @return {RunActionExecutor}
		 */
		#getExecutor()
		{
			if (this.executor === null)
			{
				this.executor = this.#createExecutor();
			}

			return this.executor;
		}

		/**
		 * @private
		 * @return {RunActionExecutor}
		 */
		#createExecutor()
		{
			return new RunActionExecutor(
				'tasksmobile.Task.getDashboardSettings',
				{
					projectId: this.projectId,
					ownerId: this.ownerId,
				},
			);
		}
	}

	module.exports = { SettingsActionExecutor };
});
