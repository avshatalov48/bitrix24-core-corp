/**
 * @module tasks/layout/action-menu/actions/src/error
 */
jn.define('tasks/layout/action-menu/actions/src/error', (require, exports, module) => {
	/**
	 * @class ActionMenuError
	 */
	class ActionMenuError extends Error
	{
		/**
		 * @param {string} message
		 */
		constructor(message)
		{
			super(message);
			this.name = 'ActionMenuError';
		}
	}

	module.exports = { ActionMenuError };
});
