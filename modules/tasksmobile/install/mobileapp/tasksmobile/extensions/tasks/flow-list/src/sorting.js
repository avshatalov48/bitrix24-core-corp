/**
 * @module tasks/flow-list/src/sorting
 */
jn.define('tasks/flow-list/src/sorting', (require, exports, module) => {
	const { BaseListSorting } = require('layout/ui/list/base-sorting');

	/**
	 * @class TasksFlowListSorting
	 * @extends BaseListSorting
	 */
	class TasksFlowListSorting extends BaseListSorting
	{
		/**
		 * @public
		 * @static
		 * @returns {Object}
		 */
		static get types()
		{
			return {
				ACTIVITY: 'ACTIVITY',
			};
		}

		/**
		 * @param {Object} data
		 */
		constructor(data = {})
		{
			super({
				types: TasksFlowListSorting.types,
				type: data.type,
				isASC: false,
				noPropertyValue: data.noPropertyValue ?? Infinity,
			});
		}

		/**
		 * @public
		 * @return {null|string}
		 */
		getConvertedType()
		{
			switch (this.type)
			{
				case TasksFlowListSorting.types.ACTIVITY:
					return 'activity';
				default:
					return null;
			}
		}

		/**
		 * @private
		 * @param {object} item
		 * @return {number}
		 */
		getPropertyValue = (item) => {
			return (new Date(item[this.getConvertedType()])).getTime();
		};

		/**
		 * @private
		 * @return {function(*): number}
		 */
		getSortingSectionCallback()
		{
			return (item) => 0;
		}

		/**
		 * @private
		 * @static
		 * @param {object} item
		 * @return {number}
		 */
		static getSortingSection(item)
		{
			return 0;
		}
	}

	module.exports = { TasksFlowListSorting };
});
