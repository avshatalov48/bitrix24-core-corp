/**
 * @module crm/entity-tab/filter
 */
jn.define('crm/entity-tab/filter', (require, exports, module) => {
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

		isChecked(filterId)
		{
			return (this.currentFilterId === filterId);
		}

		clear()
		{
			this.init();
		}

		init(presetId = null)
		{
			this.presetId = presetId;
			this.counterId = null;
			this.tmpFields = {};
			this.search = null;
			this.currentFilterId = null;
		}

		set(params)
		{
			this.presetId = BX.prop.getString(params, 'presetId', this.presetId || this.getEmptyFilterPresetId());
			this.counterId = BX.prop.getString(params, 'counterId', this.counterId || null);
			this.tmpFields = BX.prop.getObject(params, 'tmpFields', this.tmpFields || {});
			this.search = BX.prop.getString(params, 'search', this.search || '');
			this.currentFilterId = BX.prop.getString(params, 'currentFilterId', this.currentFilterId || null);

			return this;
		}

		setWasShown()
		{
			this.wasShown = true;
		}

		unsetWasShown()
		{
			this.wasShown = false;
		}

		prepareActionParams(actionParams, defaultPresetId)
		{
			if (this.search)
			{
				actionParams.loadItems.extra.search = this.search;
			}

			let filterPresetId = this.getEmptyFilterPresetId();
			if (defaultPresetId && !this.wasShown)
			{
				filterPresetId = defaultPresetId;
			}

			if (this.presetId)
			{
				filterPresetId = this.presetId;
				actionParams.loadItems.extra.filter = this.getData();
			}

			actionParams.loadItems.extra.filterParams = (actionParams.loadItems.extra.filterParams || {});
			actionParams.loadItems.extra.filterParams.FILTER_PRESET_ID = filterPresetId;

			return actionParams;
		}

		getEmptyFilterPresetId()
		{
			return this.defaultPresetId;
		}

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

		isActive()
		{
			return this.wasShown;
		}

		hasSearchText()
		{
			return Boolean(this.search && this.search.length > 0);
		}

		hasSelectedNotDefaultPreset(searchRef = null)
		{
			const { presetId } = this;

			return (
				presetId !== this.getEmptyFilterPresetId()
				&& presetId !== null
				&& (
					!searchRef
					|| (searchRef && searchRef.arePresetsLoaded() && presetId !== searchRef.getDefaultPresetId())
				)
			);
		}
	}

	module.exports = { Filter };
});
