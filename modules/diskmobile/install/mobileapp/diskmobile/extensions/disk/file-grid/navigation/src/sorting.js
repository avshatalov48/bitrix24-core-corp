/**
 * @module disk/file-grid/navigation/src/sorting
 */
jn.define('disk/file-grid/navigation/src/sorting', (require, exports, module) => {
	const { BaseListSorting } = require('layout/ui/list/base-sorting');

	/**
	* @class FileGridSorting
	* @extends BaseListSorting
	*/
	class FileGridSorting extends BaseListSorting
	{
		/**
		* @public
		* @static
		* @returns {Object}
		*/
		static get types()
		{
			return {
				UPDATE_TIME: 'UPDATE_TIME',
				CREATE_TIME: 'CREATE_TIME',
				NAME: 'NAME',
				SIZE: 'SIZE',
			};
		}

		static get typeToProperty()
		{
			return {
				[FileGridSorting.types.UPDATE_TIME]: 'updateTime',
				[FileGridSorting.types.CREATE_TIME]: 'createTime',
				[FileGridSorting.types.NAME]: 'name',
				[FileGridSorting.types.SIZE]: 'size',
			};
		}

		/**
		* @param {Object} data
		*/
		constructor(data = {})
		{
			super({
				types: FileGridSorting.types,
				type: data.type,
				isASC: data.isASC,
				noPropertyValue: data.noPropertyValue ?? Infinity,
			});
		}

		/**
		 * @private
		 * @param {object} item
		 * @return {number}
		 */
		getPropertyValue = (item) => {
			let value = item[FileGridSorting.typeToProperty[this.type]];

			if (this.type === FileGridSorting.types.NAME)
			{
				value = value.toLowerCase();
			}

			return value === null ? undefined : value;
		};

		/**
		 * @private
		 * @param {Object} item
		 * @return {number}
		 */
		getSortingSection(item)
		{
			if (!item.typeFile)
			{
				return 0;
			}

			return 1;
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

				const aSortProperty = this.getPropertyValue(a) ?? this.noPropertyValue;
				const bSortProperty = this.getPropertyValue(b) ?? this.noPropertyValue;

				if (
					aSortProperty === bSortProperty
					&& this.type !== FileGridSorting.types.NAME
				)
				{
					const aName = a.name ?? this.noPropertyValue;
					const bName = b.name ?? this.noPropertyValue;

					return aName.localeCompare(bName, 'en', { numeric: true });
				}

				if (this.type === FileGridSorting.types.NAME)
				{
					return aSortProperty.localeCompare(bSortProperty, 'en', { numeric: true }) * (this.isASC ? 1 : -1);
				}

				return (aSortProperty < bSortProperty ? -1 : 1) * (this.isASC ? 1 : -1);
			};
		}
	}

	module.exports = { FileGridSorting };
});
