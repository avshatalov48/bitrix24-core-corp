/**
 * @module calendar/ajax/event
 */
jn.define('calendar/ajax/event', (require, exports, module) => {
	const { BaseAjax } = require('calendar/ajax/base');

	const EventActions = {
		LOAD_MAIN: 'loadMain',
		GET_LIST: 'getList',
		GET_FILTERED_LIST: 'getFilteredList',
		SET_AHA_VIEWED: 'setAhaViewed',
		GET_SECTION_LIST: 'getSectionList'
	};

	class EventAjax extends BaseAjax
	{
		getEndpoint()
		{
			return 'calendarmobile.event';
		}

		/**
		 *
		 * Returns init config for calendar
		 * @returns {Promise<Object, void>}
		 */
		loadMain()
		{
			return this.fetch(EventActions.LOAD_MAIN);
		}

		/**
		 * Returns events
		 * @param {{yearFrom, yearTo, monthFrom, monthTo, sectionIdList}} params
		 * @returns {Promise<Object, void>}
		 */
		getList(params)
		{
			return this.fetch(EventActions.GET_LIST, {
				yearFrom: params.yearFrom,
				yearTo: params.yearTo,
				monthFrom: params.monthFrom,
				monthTo: params.monthTo,
				sectionIdList: params.sectionIdList,
			});
		}

		/**
		 * Returns events by filter
		 * @param {{search, preset}} params
		 * @returns {Promise<Object, void>}
		 */
		getFilteredList(params)
		{
			const { search, preset } = params;

			return this.fetch(EventActions.GET_FILTERED_LIST, {
				search,
				preset,
			});
		}

		/**
		 * @param name
		 * @returns {Promise<Object, void>}
		 */
		setAhaViewed(name)
		{
			return this.fetch(EventActions.SET_AHA_VIEWED, {
				name,
			});
		}

		/**
		 *
		 * Return list of sections
		 * @returns {Promise<Object, void>}
		 */
		getSectionList()
		{
			return this.fetch(EventActions.GET_SECTION_LIST);
		}
	}

	module.exports = {
		EventAjax: new EventAjax(),
		EventActions,
	};
});
