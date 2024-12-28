/**
 * @module calendar/ajax/event
 */
jn.define('calendar/ajax/event', (require, exports, module) => {
	const { BaseAjax } = require('calendar/ajax/base');

	const EventActions = {
		LOAD_MAIN: 'loadMain',
		GET_LIST: 'getList',
		GET_FILTERED_LIST: 'getFilteredList',
		GET_EVENT_FOR_CONTEXT: 'getEventForContext',
		SET_AHA_VIEWED: 'setAhaViewed',
		GET_SECTION_LIST: 'getSectionList',
		GET_VIEW_FORM_CONFIG: 'getViewFormConfig',
		GET_EDIT_FORM_CONFIG: 'getEditFormConfig',
		GET_EVENT_CHAT_ID: 'getEventChatId',
		GET_ICS_LINK: 'getIcsLink',
		GET_FILES_FOR_VIEW_FORM: 'getFilesForViewForm',
	};

	class EventAjax extends BaseAjax
	{
		getEndpoint()
		{
			return 'calendarmobile.event';
		}

		/**
		 * Returns init config for calendar
		 * @returns {Promise<Object, void>}
		 */
		loadMain({ ownerId, calType })
		{
			return this.fetch(EventActions.LOAD_MAIN, { ownerId, calType });
		}

		/**
		 * Returns events
		 * @param yearFrom {number}
		 * @param yearTo {number}
		 * @param monthFrom {number}
		 * @param monthTo {number}
		 * @param sectionIdList {array}
		 * @returns {Promise<Object, void>}
		 */
		getList({ yearFrom, yearTo, monthFrom, monthTo, sectionIdList })
		{
			return this.fetch(EventActions.GET_LIST, {
				yearFrom,
				yearTo,
				monthFrom,
				monthTo,
				sectionIdList,
			});
		}

		/**
		 * Returns events by filter
		 * @param search {any}
		 * @param preset {any}
		 * @param ownerId {number}
		 * @param calType {string}
		 * @returns {Promise<Object, void>}
		 */
		getFilteredList({ search, preset, ownerId, calType })
		{
			return this.fetch(EventActions.GET_FILTERED_LIST, { search, preset, ownerId, calType });
		}

		/**
		 * @param parentId {number}
		 * @param ownerId {number}
		 * @param calType {string}
		 */
		getEventForContext({ parentId, ownerId, calType })
		{
			return this.fetch(EventActions.GET_EVENT_FOR_CONTEXT, { parentId, ownerId, calType });
		}

		/**
		 * @param name
		 * @returns {Promise<Object, void>}
		 */
		setAhaViewed(name)
		{
			return this.fetch(EventActions.SET_AHA_VIEWED, { name });
		}

		/**
		 * Return list of sections
		 * @param ownerId
		 * @param calType
		 * @returns {Promise<Object, void>}
		 */
		getSectionList({ ownerId, calType })
		{
			return this.fetch(EventActions.GET_SECTION_LIST, { ownerId, calType });
		}

		/**
		 * @param eventId {number}
		 * @param eventDate {string}
		 * @param timezoneOffset {number}
		 * @param userIds {array}
		 * @param requestUsers {Y|N}
		 * @param requestCollabs {Y|N}
		 * @param getEventById {Y|N}
		 * @returns {Promise<Object, void>}
		 */
		getViewFormConfig({
			eventId,
			eventDate,
			timezoneOffset = 0,
			userIds,
			requestUsers,
			requestCollabs,
			getEventById,
		})
		{
			return this.fetch(EventActions.GET_VIEW_FORM_CONFIG, {
				eventId,
				eventDate,
				timezoneOffset,
				userIds,
				requestUsers,
				requestCollabs,
				getEventById,
			});
		}

		/**
		 * @param eventId {number}
		 * @param parentId {number}
		 * @returns {Promise<Object, void>}
		 */
		getFilesForViewForm({ eventId, parentId })
		{
			return this.fetch(EventActions.GET_FILES_FOR_VIEW_FORM, { eventId, parentId });
		}

		/**
		 * @param ownerId {number}
		 * @param calType {string}
		 * @param userIds {array}
		 * @returns {Promise<Object, void>}
		 */
		getEditFormConfig({ ownerId, calType, userIds })
		{
			return this.fetch(EventActions.GET_EDIT_FORM_CONFIG, { ownerId, calType, userIds });
		}

		/**
		 * @param eventId {number}
		 * @returns {Promise<Object, void>}
		 */
		getEventChatId(eventId)
		{
			return this.fetch(EventActions.GET_EVENT_CHAT_ID, { eventId });
		}

		/**
		 * @param eventId {number}
		 * @returns {Promise<Object, void>}
		 */
		getIcsLink({ eventId })
		{
			return this.fetch(EventActions.GET_ICS_LINK, { eventId });
		}
	}

	module.exports = {
		EventAjax: new EventAjax(),
	};
});
