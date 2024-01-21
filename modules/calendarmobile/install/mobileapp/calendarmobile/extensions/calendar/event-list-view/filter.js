/**
 * @module calendar/event-list-view/filter
 */
jn.define('calendar/event-list-view/filter', (require, exports, module) => {
	const presets = [

	];
	const PRESET_INVITED = 'filter_calendar_meeting_status_q';
	const MIN_SEARCH_LENGTH = 3;

	/**
	 * @class Filter
	 */
	class Filter
	{
		constructor()
		{
			this.init();
			this.wasShown = false;
		}

		init()
		{
			this.presetId = this.getDefaultPresetId();
			this.preset = null;
			this.search = '';
			this.currentFilterId = null;
		}

		clear()
		{
			this.init();
		}

		set(params)
		{
			this.presetId = BX.prop.getString(params, 'presetId', this.presetId || null);
			this.preset = BX.prop.getObject(params, 'preset', this.preset || null);
			this.search = BX.prop.getString(params, 'search', this.search || null);
			this.currentFilterId = BX.prop.getString(params, 'currentFilterId', this.currentFilterId || null);
		}

		getDefaultPresetId()
		{
			return '';
		}

		setWasShown()
		{
			this.wasShown = true;
		}

		unsetWasShown()
		{
			this.wasShown = false;
		}

		isActive()
		{
			return this.wasShown;
		}

		isEmpty()
		{
			return this.search.length < MIN_SEARCH_LENGTH && this.presetId === '';
		}

		isInvitationPresetEnabled()
		{
			return this.presetId === PRESET_INVITED;
		}

		isSearchByPreset()
		{
			return this.presetId && this.search.length < MIN_SEARCH_LENGTH;
		}

		getData()
		{
			return {
				preset: this.preset,
				presetId: this.presetId,
				search: this.search,
				currentFilterId: this.currentFilterId,
			};
		}
	}

	module.exports = { Filter };
});
