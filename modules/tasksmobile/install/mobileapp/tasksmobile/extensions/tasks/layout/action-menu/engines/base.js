/**
 * @module tasks/layout/action-menu/engines/base
 */
jn.define('tasks/layout/action-menu/engines/base', (require, exports, module) => {
	/**
	 * @abstract
	 */
	class BaseEngine
	{
		/**
		 * @public
		 * @abstract
		 * @param {{
		 *     id: string,
		 *     title: string,
		 *     onClickCallback: function,
		 *     isDestructive?: boolean,
		 *     sectionCode?: string,
		 *     data?: { svgUri?: string, outlineIconUri?: string },
		 * }[]} actions
		 * @param {object} options
		 */
		show(actions, options)
		{}

		/**
		 * @public
		 * @abstract
		 * @param {function} callback
		 */
		close(callback)
		{}
	}

	module.exports = { BaseEngine };
});
