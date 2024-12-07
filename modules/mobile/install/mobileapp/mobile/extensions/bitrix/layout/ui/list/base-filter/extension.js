/**
 * @module layout/ui/list/base-filter
 */
jn.define('layout/ui/list/base-filter', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { Type } = require('type');
	const NONE_PRESET_TYPE = 'none';
	/**
	 * @class BaseListFilter
	 * @abstract
	 */
	class BaseListFilter
	{
		/**
		 * @param {String} presetId
		 * @param {String} searchString
		 * @param {Boolean} needFillPresets
		 */
		constructor(presetId, searchString = '', needFillPresets = true) {
			this.presets = [];
			this.presetId = presetId;
			this.setSearchString(searchString);

			if (needFillPresets)
			{
				void this.fillPresets(this.getFillPresetParams());
			}
		}

		/**
		 * @public
		 * @param {Object | null} params
		 * @returns {Promise}
		 */
		fillPresets(params)
		{
			return new Promise((resolve) => {
				if (params)
				{
					(new RunActionExecutor(params.action, params.options))
						.setHandler((response) => {
							this.loaded = true;
							this.setPresets(response.data);
							resolve();
						})
						.call(false);
				}
				else
				{
					resolve();
				}
			});
		}

		getPresets()
		{
			return this.presets || [];
		}

		/**
		 * @public
		 * @param {String} presetId
		 */
		setPresetId(presetId = null)
		{
			this.presetId = presetId;
		}

		/**
		 * @private
		 * @param {Array} presets
		 */
		setPresets(presets)
		{
			if (!Type.isArray(presets))
			{
				this.presets = [];
			}

			this.presets = presets;
		}

		/**
		 * @public
		 * @param {String} searchString
		 */
		setSearchString(searchString = '')
		{
			this.searchString = searchString;
		}

		/**
		 * @public
		 * @return {String}
		 */
		getSearchString()
		{
			return this.searchString;
		}

		/**
		 * @public
		 * @return {String}
		 */
		getPresetId()
		{
			return this.presetId;
		}

		/**
		 * @public
		 * @return {string|null}
		 */
		getPresetName()
		{
			return this.getPresetNameById(this.presetId);
		}

		/**
		 * @public
		 * @param {string} presetId
		 * @return {string|null}
		 */
		getPresetNameById(presetId)
		{
			const preset = this.getPresetById(presetId);
			if (!preset)
			{
				return null;
			}

			return preset.name;
		}

		/**
		 * @public
		 * @param {string} presetId
		 * @return {*}
		 */
		getPresetById(presetId)
		{
			return this.getPresets().find((item) => item.id === presetId);
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isDefault()
		{
			return this.isSearchStringEmpty() && this.isDefaultPreset();
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isEmpty()
		{
			return this.isSearchStringEmpty() && this.isEmptyPreset();
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isSearchStringEmpty()
		{
			return this.searchString === '';
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isEmptyPreset()
		{
			return (!this.presetId || this.presetId === NONE_PRESET_TYPE);
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isDefaultPreset()
		{
			return this.presetId === this.getDefaultPreset();
		}

		/**
		 * @public
		 * @abstract
		 */
		getDefaultPreset() {}

		/**
		 * @private
		 * @abstract
		 */
		getFillPresetParams() {}
	}

	module.exports = { BaseListFilter };
});
