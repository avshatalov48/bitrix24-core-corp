/**
 * @module disk/file-grid/navigation/src/filter
 */
jn.define('disk/file-grid/navigation/src/filter', (require, exports, module) => {
	const { BaseListFilter } = require('layout/ui/list/base-filter');

	class FileGridFilter extends BaseListFilter
	{
		static get presetType()
		{
			return {
				default: null,
			};
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isDefault()
		{
			return this.isEmpty();
		}

		/**
		 * @public
		 * @return {String}
		 */
		getDefaultPreset()
		{
			return FileGridFilter.presetType.default;
		}

		getFillPresetParams()
		{
			return {
				action: 'diskmobile.Filter.getSearchBarPresets',
			};
		}
	}
	module.exports = { FileGridFilter };
});
