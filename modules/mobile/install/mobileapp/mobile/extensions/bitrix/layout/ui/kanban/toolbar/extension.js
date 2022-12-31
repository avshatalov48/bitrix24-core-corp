/**
 * @module layout/ui/kanban/toolbar
 */
jn.define('layout/ui/kanban/toolbar', (require, exports, module) => {
	/**
	 * @class ToolbarFactory
	 * @abstract
	 */
	class ToolbarFactory
	{
		/**
		 * @param {String} type
		 * @return {Boolean}
		 */
		has(type)
		{
			throw new Error('Must be implemented by specific factory.');
		}

		/**
		 * @param {String} type
		 * @param {Object} data
		 */
		create(type, data)
		{
			throw new Error('Must be implemented by specific factory.');
		}
	}

	module.exports = { ToolbarFactory };
});
