/**
 * @module layout/ui/stateful-list/src/cache
 */
jn.define('layout/ui/stateful-list/src/cache', (require, exports, module) => {
	const { set } = require('utils/object');

	/**
	 * @class StatefulListCache
	 */
	class StatefulListCache
	{
		constructor()
		{
			/** @type {RunActionExecutor|null} */
			this.runActionExecutor = null;
		}

		/**
		 * @public
		 * @param {RunActionExecutor} runActionExecutor
		 */
		setRunActionExecutor(runActionExecutor)
		{
			this.runActionExecutor = runActionExecutor;
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isEnabled()
		{
			return Boolean(this.runActionExecutor);
		}

		/**
		 * @public
		 * @param {object[]} newItems
		 */
		modifyCache(newItems)
		{
			if (!this.isEnabled())
			{
				return;
			}

			const runActionCache = this.runActionExecutor.getCache();
			const modifiedCache = set(runActionCache.getData() || {}, ['data', 'items'], newItems);

			runActionCache.saveData(modifiedCache);
		}
	}

	module.exports = { StatefulListCache };
});
