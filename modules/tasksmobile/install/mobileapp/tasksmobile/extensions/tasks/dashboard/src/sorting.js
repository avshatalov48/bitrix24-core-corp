/**
 * @module tasks/dashboard/src/sorting
 */
jn.define('tasks/dashboard/src/sorting', (require, exports, module) => {
	class Sorting
	{
		/**
		 * @public
		 * @returns {{DEADLINE: string, ACTIVITY: string}}
		 */
		static get type()
		{
			return {
				ACTIVITY: 'ACTIVITY',
				DEADLINE: 'DEADLINE',
			};
		}

		constructor(data = {})
		{
			this.type = data.type ?? Sorting.type.ACTIVITY;
			// this.isASC = data.isASC ?? false;
			// todo: temporary solution, until user will be able to configure order, too.
			this.isASC = !(this.type === Sorting.type.ACTIVITY);
			this.noPropertyValue = data.noPropertyValue ?? Infinity;

			this.getPropertyValue = this.getPropertyValue.bind(this);
		}

		/**
		 * @public
		 * @returns {*}
		 */
		toggle()
		{
			this.type = (this.type === Sorting.type.ACTIVITY ? Sorting.type.DEADLINE : Sorting.type.ACTIVITY);

			return this.getType();
		}

		/**
		 * @public
		 * @returns {string}
		 */
		getType()
		{
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
				case Sorting.type.ACTIVITY:
					return 'activityDate';
				case Sorting.type.DEADLINE:
					return 'deadline';
				default:
					return null;
			}
		}

		/**
		 * @public
		 * @param type
		 */
		setType(type)
		{
			if (Object.values(Sorting.type).includes(type))
			{
				this.type = type;
			}
		}

		/**
		 * @public
		 * @param {boolean} isASC
		 */
		setOrder(isASC)
		{
			this.isASC = isASC;
		}

		getSortingConfig()
		{
			return {
				noPropertyValue: this.noPropertyValue,
				isASC: this.isASC,
				sortByProperty: this.getType(),
				getPropertyValue: this.getPropertyValue,
				getSection: Sorting.getSortingSection,
			};
		}

		/**
		 * @private
		 * @param {object} item
		 * @return {number}
		 */
		getPropertyValue(item)
		{
			return (new Date(item[this.getConvertedType()])).getTime();
		}

		/**
		 * @private
		 * @param {object} item
		 * @return {number}
		 */
		static getSortingSection(item)
		{
			return item.isPinned ? 0 : 1;
		}
	}

	module.exports = { Sorting };
});
