/**
 * @module calendar/data-managers/event-manager
 */
jn.define('calendar/data-managers/event-manager', (require, exports, module) => {
	const { Type } = require('type');
	const { SectionManager } = require('calendar/data-managers/section-manager');
	const { EventAjax } = require('calendar/ajax');
	const { CalendarType } = require('calendar/enums');

	const store = require('statemanager/redux/store');
	const {
		eventsAdded,
		eventDeleted,
		eventMeetingStatusChanged,
		selectByParentId,
		selectById,
	} = require('calendar/statemanager/redux/slices/events');

	/**
	 * @class EventManager
	 */
	class EventManager
	{
		constructor()
		{
			this.isRefreshing = false;

			this.loadedRange = {
				start: new Date(),
				end: new Date(),
			};
		}

		/**
		 * @public
		 * Initialises EventManager and loads the list of events
		 */
		async init()
		{
			const today = new Date();
			const start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
			const end = new Date(today.getFullYear(), today.getMonth() + 2, 1);

			this.initPromise ??= this.loadList(start, end, true);

			await this.initPromise;

			this.inited = true;
		}

		/**
		 * @public
		 * @param {Date} startDate
		 * @param {Date} endDate
		 * @param {boolean} [init = false]
		 * @returns {Promise<void>}
		 */
		// eslint-disable-next-line consistent-return
		async loadList(startDate, endDate, init = false)
		{
			if (!this.inited && !init)
			{
				return this.init();
			}

			if (this.hasRange(startDate, endDate))
			{
				// eslint-disable-next-line consistent-return
				return;
			}

			this.setLoadedRange(startDate, endDate);

			try
			{
				const { data } = await EventAjax.getList({
					yearFrom: startDate.getFullYear(),
					yearTo: endDate.getFullYear(),
					monthFrom: startDate.getMonth() + 1,
					monthTo: endDate.getMonth() + 1,
					sectionIdList: SectionManager.getActiveSectionsIds(),
				});

				const eventsData = BX.prop.getArray(data, 'events', []);

				this.addEventsToRedux(eventsData);
			}
			catch (e)
			{
				console.error('ERROR on EventManager.loadList', e);
			}
		}

		/**
		 * @public
		 * @returns {Promise<void>}
		 */
		async refresh()
		{
			if (this.isRefreshing)
			{
				return;
			}

			this.isRefreshing = true;

			try
			{
				const { data } = await EventAjax.getList({
					yearFrom: this.loadedRange.start.getFullYear(),
					yearTo: this.loadedRange.end.getFullYear(),
					monthFrom: this.loadedRange.start.getMonth() + 1,
					monthTo: this.loadedRange.end.getMonth() + 1,
					sectionIdList: SectionManager.getActiveSectionsIds(),
				});

				const eventsData = BX.prop.getArray(data, 'events', []);

				this.addEventsToRedux(eventsData);
			}
			catch (e)
			{
				console.error('ERROR on EventManager.refresh', e);
			}

			this.isRefreshing = false;
		}

		/**
		 * @public
		 * @param filter
		 * @param [filter.search] {Object}
		 * @param [filter.preset] {Object}
		 * @param [filter.ownerId] {Number}
		 * @param [filter.calType] {String}
		 * @returns {Promise<number[]>}
		 */
		async getEventsByFilter(filter)
		{
			try
			{
				const { data } = await EventAjax.getFilteredList(filter);

				const eventsData = BX.prop.getArray(data, 'events', []);

				this.addEventsToRedux(eventsData);

				return eventsData.map((eventData) => Number(eventData.ID));
			}
			catch (e)
			{
				console.error('ERROR on EventManager.getEventsByFilter', e);
			}

			return [];
		}

		handlePullMeetingStatusChanges(fields)
		{
			const userId = Number(fields.USER_ID);
			const parentId = Number(fields.PARENT_ID);
			const userMeetingStatus = fields.MEETING_STATUS;

			const event = selectByParentId(store.getState(), { parentId, userId: Number(env.userId) });
			if (!event)
			{
				return null;
			}

			const eventId = Number(event.id);

			store.dispatch(eventMeetingStatusChanged({ eventId, userMeetingStatus, userId }));

			return event;
		}

		async handlePullEventChanges(event, ownerId, calType)
		{
			const sectionId = Number(event.SECTION_ID);

			if (SectionManager.getSection(sectionId)?.id)
			{
				this.addEventsToRedux([event]);

				return selectById(store.getState(), event.ID);
			}

			const parentId = Number(event.PARENT_ID);

			if (parentId && calType !== CalendarType.USER)
			{
				const { data } = await EventAjax.getEventForContext({ parentId, ownerId, calType });

				if (Type.isArrayFilled(data))
				{
					const eventId = Number(data[0]?.ID);

					this.addEventsToRedux(data);

					return selectById(store.getState(), eventId);
				}
			}

			return null;
		}

		handlePullEventDelete(event)
		{
			const eventId = Number(event.ID);

			store.dispatch(eventDeleted({ eventId }));
		}

		addEventsToRedux(eventsData)
		{
			store.dispatch(eventsAdded(eventsData));
		}

		hasRange(startDate, endDate)
		{
			const { start, end } = this.loadedRange;

			return start.getTime() <= startDate.getTime() && endDate.getTime() <= end.getTime();
		}

		/**
		 * @private
		 */
		setLoadedRange(startDate, endDate)
		{
			this.loadedRange.start = new Date(Math.min(this.loadedRange.start, startDate));
			this.loadedRange.end = new Date(Math.max(this.loadedRange.end, endDate));
		}
	}

	module.exports = { EventManager: new EventManager() };
});
