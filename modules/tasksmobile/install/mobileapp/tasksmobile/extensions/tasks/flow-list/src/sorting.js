/**
 * @module tasks/flow-list/src/sorting
 */
jn.define('tasks/flow-list/src/sorting', (require, exports, module) => {
	const { BaseListSorting } = require('layout/ui/list/base-sorting');
	const { FLOWS_INFO_ITEM_ID } = require('tasks/flow-list/simple-list/items/type');

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
		 * @param {object} item
		 * @return {number}
		 */
		getSortingSection(item)
		{
			if (item.id === FLOWS_INFO_ITEM_ID)
			{
				return 0;
			}

			if (item.isPinned)
			{
				return 1;
			}

			return 2;
		}
	}

	module.exports = { TasksFlowListSorting };
});
