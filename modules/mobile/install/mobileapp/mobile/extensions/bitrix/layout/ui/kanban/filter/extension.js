/**
 * @module layout/ui/kanban/filter
 */
jn.define('layout/ui/kanban/filter', (require, exports, module) => {
	const { stringify } = require('utils/string');

	/**
	 * @class Filter
	 */
	class Filter
	{
		constructor(presetId = null)
		{
			this.defaultPresetId = presetId;
			this.init(presetId);
			this.wasShown = false;
		}

		/**
		 * @public
		 * @param {string} filterId
		 * @return {boolean}
		 */
		isChecked(filterId)
		{
			return (this.currentFilterId === filterId);
		}

		/**
		 * @public
		 */
		clear()
		{
			this.init();
		}

		/**
		 * @private
		 * @param {string|null} presetId
		 */
		init(presetId = null)
		{
			this.presetId = presetId;
			this.counterId = null;
			this.tmpFields = {};
			this.search = null;
			this.currentFilterId = null;
		}

		/**
		 * @public
		 * @param {object} params
		 * @return {Filter}
		 */
		set(params = {})
		{
			this.presetId = BX.prop.getString(params, 'presetId', this.presetId || this.getEmptyFilterPresetId());
			this.counterId = BX.prop.getString(params, 'counterId', this.counterId || null);
			this.tmpFields = BX.prop.getObject(params, 'tmpFields', this.tmpFields || {});
			this.search = BX.prop.getString(params, 'search', this.search || '');
			this.currentFilterId = BX.prop.getString(params, 'currentFilterId', this.currentFilterId || null);

			return this;
		}

		/**
		 * @public
		 */
		setWasShown()
		{
			this.wasShown = true;
		}

		/**
		 * @public
		 */
		unsetWasShown()
		{
			this.wasShown = false;
		}

		/**
		 * @public
		 * @return {string|null}
		 */
		getEmptyFilterPresetId()
		{
			return this.defaultPresetId;
		}

		/**
		 * @public
		 * @return {string|null}
		 */
		getPresetId()
		{
			return this.presetId;
		}

		/**
		 * @public
		 * @return {string|null}
		 */
		getCounterId()
		{
			return this.counterId;
		}

		/**
		 * @public
		 * @return {{
		 * 	search: string|null,
		 * 	tmpFields: {},
		 * 	currentFilterId: string|null,
		 * 	presetId: string|null,
		 * 	counterId: string|null
		 * }}
		 */
		getData()
		{
			return {
				presetId: this.presetId,
				counterId: this.counterId,
				tmpFields: this.tmpFields,
				search: this.search,
				currentFilterId: this.currentFilterId,
			};
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isActive()
		{
			return this.wasShown;
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		hasSearchText()
		{
			return Boolean(this.search && this.search.length > 0);
		}

		/**
		 * @public
		 * @return {string}
		 */
		getSearchString()
		{
			return stringify(this.search);
		}

		/**
		 * @public
		 * @param {object|null} searchRef
		 * @return {boolean}
		 */
		hasSelectedNotDefaultPreset(searchRef = null)
		{
			const { presetId } = this;

			return (
				presetId !== this.getEmptyFilterPresetId()
				&& presetId !== null
				&& (
					!searchRef
					|| (searchRef && searchRef.presetsWasLoaded() && presetId !== searchRef.getDefaultPresetId())
				)
			);
		}
	}

	module.exports = { Filter };
});
