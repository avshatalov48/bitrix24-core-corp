/**
 * @module tasks/dashboard/src/sorting
 */
jn.define('tasks/dashboard/src/sorting', (require, exports, module) => {
	const { Views } = require('tasks/statemanager/redux/types');
	const { BaseListSorting } = require('layout/ui/list/base-sorting');

	/**
	 * @class TasksDashboardSorting
	 * @extends BaseListSorting
	 */
	class TasksDashboardSorting extends BaseListSorting
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
				DEADLINE: 'DEADLINE',
			};
		}

		/**
		 * @param {Object} data
		 */
		constructor(data = {})
		{
			super({
				types: TasksDashboardSorting.types,
				type: data.type,
				isASC: !(data.type === TasksDashboardSorting.types.ACTIVITY),
				noPropertyValue: data.noPropertyValue ?? Infinity,
			});

			this.view = data.view;
			this.isProject = data.isProject ?? false;
			this.ownerId = Number(data.ownerId ?? env.userId);
		}

		/**
		 * @public
		 * @param {string} view
		 */
		setView(view)
		{
			this.view = view;
		}

		/**
		 * @public
		 * @returns {string}
		 */
		toggle()
		{
			this.type = (this.type === this.types.ACTIVITY ? this.types.DEADLINE : this.types.ACTIVITY);

			return this.type;
		}

		/**
		 * @public
		 * @return {null|string}
		 */
		getConvertedType()
		{
			switch (this.type)
			{
				case TasksDashboardSorting.types.ACTIVITY:
					return 'activityDate';
				case TasksDashboardSorting.types.DEADLINE:
					return 'deadline';
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
			const value = item[this.getConvertedType()];

			return value === null ? undefined : (new Date(value)).getTime();
		};

		/**
		 * @private
		 * @param {Object} item
		 * @return {number}
		 */
		getSortingSection = (item) => {
			if (this.view === Views.LIST)
			{
				if (item.isCreationErrorExist)
				{
					return 0;
				}

				const isPinned = this.ownerId === Number(env.userId)
					&& (this.isProject ? item.isPinnedInGroup : item.isPinned);

				if (isPinned)
				{
					return 1;
				}

				return 2;
			}

			if (item.isCreationErrorExist)
			{
				return 0;
			}

			return 1;
		};

		getSortItemsCallback = () => (a, b) => {
			const aSection = this.getSortingSection(a) ?? 0;
			const bSection = this.getSortingSection(b) ?? 0;

			if (aSection !== bSection)
			{
				return Math.sign(aSection - bSection);
			}

			const aSortProperty = this.getPropertyValue(a, this.getType()) ?? this.noPropertyValue;
			const bSortProperty = this.getPropertyValue(b, this.getType()) ?? this.noPropertyValue;

			if (aSortProperty !== bSortProperty)
			{
				if (this.getType() === TasksDashboardSorting.types.DEADLINE)
				{
					if (aSortProperty === this.noPropertyValue && bSortProperty !== this.noPropertyValue)
					{
						return 1;
					}

					if (bSortProperty === this.noPropertyValue && aSortProperty !== this.noPropertyValue)
					{
						return -1;
					}
				}

				return (aSortProperty < bSortProperty ? -1 : 1) * (this.isASC ? 1 : -1);
			}

			return 0;
		};
	}

	module.exports = { TasksDashboardSorting };
});
