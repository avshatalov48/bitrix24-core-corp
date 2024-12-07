/**
 * @module layout/ui/list/base-sorting
 */
jn.define('layout/ui/list/base-sorting', (require, exports, module) => {
	/**
	 * @abstract
	 * @class BaseListSorting
	 */
	class BaseListSorting
	{
		/**
		 * @param {Object} config
		 * @param {Object} config.types
		 * @param {string} config.type
		 * @param {boolean} [config.isASC=false]
		 * @param {*} [config.noPropertyValue=Infinity]
		 */
		constructor({ types, type, isASC = false, noPropertyValue })
		{
			this.types = types;
			this.type = type ?? Object.values(this.types)[0];
			this.isASC = isASC ?? false;

			this.noPropertyValue = noPropertyValue ?? Infinity;
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
		 * @param {string} type
		 */
		setType(type)
		{
			if (Object.values(this.types).includes(type))
			{
				this.type = type;
			}
		}

		/**
		 * @public
		 * @param {boolean} isASC
		 */
		setIsASC(isASC)
		{
			this.isASC = isASC;
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		getIsASC()
		{
			return this.isASC;
		}

		/**
		 * @public
		 */
		toggleOrder()
		{
			this.isASC = !this.isASC;
		}

		/**
		 * @public
		 * @return {string}
		 */
		getOrder()
		{
			return this.isASC ? 'ASC' : 'DESC';
		}

		/**
		 * @public
		 * @return {Object}
		 */
		getSortingConfig()
		{
			return {
				isASC: this.isASC,
				sortItemsCallback: this.getSortItemsCallback(),
			};
		}

		getSortItemsCallback()
		{
			return (a, b) => {
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
					return (aSortProperty < bSortProperty ? -1 : 1) * (this.isASC ? 1 : -1);
				}

				return 0;
			};
		}

		/**
		 * @private
		 */
		getPropertyValue()
		{
			return null;
		}

		/**
		 * @private
		 * @param {*} item
		 * @return {number}
		 */
		getSortingSection(item)
		{
			return 0;
		}
	}

	module.exports = { BaseListSorting };
});
