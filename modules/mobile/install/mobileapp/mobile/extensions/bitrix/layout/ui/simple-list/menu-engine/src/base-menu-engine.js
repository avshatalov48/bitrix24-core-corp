/**
 * @module layout/ui/simple-list/menu-engine/src/base-menu-engine
 */
jn.define('layout/ui/simple-list/menu-engine/src/base-menu-engine', (require, exports, module) => {
	/**
	 * @abstract
	 */
	class BaseMenuEngine
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

	module.exports = { BaseMenuEngine };
});
