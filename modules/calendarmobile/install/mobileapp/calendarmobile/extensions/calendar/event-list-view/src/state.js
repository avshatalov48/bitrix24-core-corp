/**
 * @module calendar/event-list-view/state
 */
jn.define('calendar/event-list-view/state', (require, exports, module) => {
	const { Type } = require('type');
	const { BaseState, observeState } = require('calendar/state');
	const { Counters, ViewMode } = require('calendar/enums');

	const store = require('statemanager/redux/store');

	const PRESET_INVITED = 'filter_calendar_meeting_status_q';
	const MIN_SEARCH_LENGTH = 3;

	/**
	 * @class State
	 * @property {Date} selectedDate
	 *
	 * @property {function(value)} setSelectedDate
	 * @property {function(value)} setFilterResultIds
	 * @property {function(value)} setInvitesSelected
	 * @property {function(value)} setCounters
	 * @property {function(value)} setShowDeclined
	 * @property {function(value)} setShowWeekNumbers
	 * @property {function(value)} setDenyBusyInvitation
	 * @property {function(value)} setSearchString
	 * @property {function(value)} setPreset
	 * @property {function(value)} setPresetId
	 * @property {function(value)} setHiddenSections
	 */
	class State extends BaseState
	{
		constructor(props)
		{
			super(props);

			store.subscribe(() => this.emit());
		}

		/**
		 *
		 * @param props {Object}
		 * @param [props.counters] {Object}
		 * @param [props.hiddenSections] {Array}
		 * @param [props.calType] {String}
		 * @param [props.ownerId] {Number}
		 * @param [props.viewMode] {String}
		 * @param [props.showDeclined] {Boolean}
		 * @param [props.showWeekNumbers] {Boolean}
		 * @param [props.denyBusyInvitation] {Boolean}
		 */
		init(props)
		{
			this.calType = props.calType;
			this.ownerId = Number(props.ownerId);
			this.viewMode = props.viewMode;

			this.counters = props.counters;
			this.hiddenSections = props.hiddenSections;

			this.showDeclined = props.showDeclined;
			this.showWeekNumbers = props.showWeekNumbers;
			this.denyBusyInvitation = props.denyBusyInvitation;

			this.searchString = '';
			this.presetId = '';
			this.preset = null;
			this.invitesSelected = false;

			this.isLoading = true;
			this.selectedDate = new Date();
		}

		setSelectedDate(selectedDate)
		{
			this.selectedDate = selectedDate;
			this.filterResultIds = [];
			this.isLoading = false;
			this.invitesSelected = false;
		}

		setFilterResultIds(filterResultIds)
		{
			this.filterResultIds = filterResultIds;
			this.isLoading = false;
		}

		setUserInvitesCounter(counter)
		{
			this.counters[Counters.INVITES] = counter;
		}

		setGroupCounter(counter)
		{
			this.counters[Counters.GROUP_INVITES] = counter;
		}

		closeFilter()
		{
			this.searchString = '';
			this.presetId = '';
		}

		setFilterParams(filterParams)
		{
			this.searchString = filterParams.searchString;
			this.presetId = filterParams.presetId;
			this.preset = filterParams.preset;
		}

		get isSearchMode()
		{
			return !(this.searchString.length < MIN_SEARCH_LENGTH && this.presetId === '');
		}

		get isListMode()
		{
			return !this.isSearchMode && !Type.isArrayFilled(this.filterResultIds);
		}

		get isInvitationPresetEnabled()
		{
			return this.presetId === PRESET_INVITED;
		}

		get isInvitationMode()
		{
			return this.invitesSelected || this.isInvitationPresetEnabled;
		}

		get isBaseViewMode()
		{
			return this.viewMode === ViewMode.BASE;
		}

		get isTabsViewMode()
		{
			return this.viewMode === ViewMode.TABS;
		}

		get searchData()
		{
			return {
				preset: this.preset,
				presetId: this.presetId,
				search: this.searchString,
			};
		}
	}

	const state = new State();

	module.exports = {
		State: state,
		observeState: (component, mapStateToProps) => observeState(component, mapStateToProps, state),
	};
});
