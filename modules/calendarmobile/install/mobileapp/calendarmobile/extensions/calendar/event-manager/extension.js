/**
 * @module calendar/event-manager
 */
jn.define('calendar/event-manager', (require, exports, module) => {
	const { Type } = require('type');
	const { DateHelper } = require('calendar/date-helper');
	const { EventStorage } = require('calendar/storage/event');
	const { EventModel } = require('calendar/model/event');
	const { EventListItemType } = require('calendar/event-list-view/layout/event-list');

	/**
	 * @class EventManager
	 */
	class EventManager
	{
		constructor(props)
		{
			this.props = props;
			this.sectionManager = props.sectionManager;
			this.locationManager = props.locationManager;

			this.isRefreshing = false;

			this.storage = [];
			this.loadedEventsIndex = {};
			this.filteredEventsIndex = {};
			this.eventsMap = {};
			this.eventIdsMap = {};

			this.nowDate = new Date();

			this.loadedRange = {
				start: null,
				end: null,
			};
		}

		/**
		 * @public
		 * Initialises EventManager and loads the list of events
		 */
		init()
		{
			const start = new Date(this.nowDate.getFullYear(), this.nowDate.getMonth() - 1, 1);
			const end = new Date(this.nowDate.getFullYear(), this.nowDate.getMonth() + 2, 1);

			this.loadList(start, end);
		}

		/**
		 * @public
		 * @param startDate
		 * @param endDate
		 * @returns {null|false|boolean}
		 */
		doesDateRangeLoaded(startDate, endDate)
		{
			return this.loadedRange.start
				&& startDate.getTime() >= this.loadedRange.start.getTime()
				&& endDate.getTime() <= this.loadedRange.end.getTime()
			;
		}

		/**
		 * @public
		 * @param startDate
		 * @param endDate
		 * @returns {Promise<void>}
		 */
		async loadList(startDate, endDate)
		{
			try
			{
				const result = await EventStorage.getList({
					yearFrom: startDate.getFullYear(),
					yearTo: endDate.getFullYear(),
					monthFrom: startDate.getMonth() + 1,
					monthTo: endDate.getMonth() + 1,
					sectionIdList: this.sectionManager.getActiveSectionsIds(),
					onDataSynced: this.onEventListReceived.bind(this, startDate, endDate),
				});

				this.onEventListReceived(startDate, endDate, result);
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
		async refresh(force = false)
		{
			if (this.isRefreshing && !force)
			{
				return;
			}

			this.isRefreshing = true;

			try
			{
				const result = await EventStorage.getList({
					yearFrom: this.loadedRange.start.getFullYear(),
					yearTo: this.loadedRange.end.getFullYear(),
					monthFrom: this.loadedRange.start.getMonth() + 1,
					monthTo: this.loadedRange.end.getMonth() + 1,
					sectionIdList: this.sectionManager.getActiveSectionsIds(),
					onDataSynced: this.onEventListRefreshed.bind(this),
				});

				this.onEventListRefreshed(result);
			}
			catch (e)
			{
				console.error('ERROR on EventManager.refresh', e);
			}
			finally
			{
				this.isRefreshing = false;
			}
		}

		/**
		 * @public
		 * @param uid
		 * @returns {*}
		 */
		getByUniqueId(uid)
		{
			return this.storage[this.loadedEventsIndex[uid]] ?? this.filteredEventsStorage[this.filteredEventsIndex[uid]];
		}

		/**
		 * @public
		 * @returns {*|{}}
		 */
		getEventsMap()
		{
			return this.eventsMap;
		}

		/**
		 * @public
		 * @param dayCode
		 * @returns {*|[]}
		 */
		getEventsByDay(dayCode)
		{
			return this.sortEvents(this.eventsMap[dayCode]);
		}

		/**
		 * @public
		 * @param filter
		 * @type {{filter: {searchQuery: Object, preset: Object}}}
		 * @returns {Promise<*[]>}
		 */
		async getEventsByFilter(filter)
		{
			let events = [];

			try
			{
				const params = {
					search: filter.search,
					preset: filter.preset,
				};
				const result = await EventStorage.getFilteredList(params);

				if (result.data)
				{
					const eventsRaw = BX.prop.getArray(result.data, 'events', []);
					const eventsMap = this.createFilteredEventsMap(eventsRaw);

					events = this.getSortedDayKeys(eventsMap)
						.reduce((p, day) => [...p, ...this.sortEvents(eventsMap[day])], []);
				}
			}
			catch (e)
			{
				console.error('ERROR on EventManager.getEventsByFilter', e);
			}

			return events;
		}

		/**
		 * @private
		 * @param eventsMap
		 * @returns {string[]}
		 */
		getSortedDayKeys(eventsMap)
		{
			return Object.keys(eventsMap).map((key) => {
				const dayMonthYearA = key.split('.');
				const day = parseInt(dayMonthYearA[0], 10);
				const month = parseInt(dayMonthYearA[1], 10);
				const year = parseInt(dayMonthYearA[2], 10);

				return { key, day, month, year };
			}).sort((a, b) => {
				if (a.year !== b.year)
				{
					return a.year - b.year;
				}

				if (a.month !== b.month)
				{
					return a.month - b.month;
				}

				return a.day - b.day;
			}).map((e) => e.key);
		}

		/**
		 * @private
		 * @param eventsRaw
		 * @returns {{}}
		 */
		createLoadedEventsMap(eventsRaw)
		{
			this.storage = [];
			this.loadedEventsIndex = {};

			return this.appendEventsRawToIndex(eventsRaw, this.storage, this.loadedEventsIndex);
		}

		/**
		 * @private
		 * @param eventsRaw
		 * @returns {{}}
		 */
		createFilteredEventsMap(eventsRaw)
		{
			this.filteredEventsStorage = [];
			this.filteredEventsIndex = {};

			return this.appendEventsRawToIndex(
				eventsRaw,
				this.filteredEventsStorage,
				this.filteredEventsIndex,
				true,
			);
		}

		/**
		 * @private
		 * @param eventsRaw
		 * @param storage
		 * @param index
		 * @param appendDates
		 * @returns {{}}
		 */
		// eslint-disable-next-line sonarjs/cognitive-complexity
		appendEventsRawToIndex(eventsRaw, storage, index, appendDates = false)
		{
			this.appendEventsToStorage(eventsRaw, storage, index);

			const eventsMap = {};
			storage.forEach((event) => {
				const fromCode = DateHelper.getDayCode(event.getDateFrom());
				const toCode = DateHelper.getDayCode(event.getDateTo());

				// eslint-disable-next-line unicorn/no-negated-condition
				if (fromCode !== toCode)
				{
					let from = new Date(event.getDateFrom().getTime());
					let fromTs = new Date(
						event.getDateFrom().getFullYear(),
						event.getDateFrom().getMonth(),
						event.getDateFrom().getDate(),
					).getTime();
					const toTs = new Date(
						event.getDateTo().getFullYear(),
						event.getDateTo().getMonth(),
						event.getDateTo().getDate(),
					).getTime();

					while (fromTs <= toTs)
					{
						const dayCode = DateHelper.getDayCode(from);

						if (Type.isNil(eventsMap[dayCode]))
						{
							eventsMap[dayCode] = [];

							if (appendDates)
							{
								eventsMap[dayCode].push({
									type: EventListItemType.TYPE_DAY_LABEL,
									date: from.getTime(),
									dayCode,
								});
							}
						}

						eventsMap[dayCode].push({
							type: EventListItemType.TYPE_EVENT,
							event,
							eventUniqueId: event.getUniqueId(),
							dayCode,
							isLongWithTime: true,
							isFullDay: event.isFullDay() || (dayCode !== fromCode && dayCode !== toCode),
							isUntil: (dayCode !== fromCode && dayCode === toCode),
						});

						fromTs += 86_400_000;
						from = new Date(from.getTime() + 86_400_000);
					}
				}
				else
				{
					if (Type.isNil(eventsMap[fromCode]))
					{
						eventsMap[fromCode] = [];

						if (appendDates)
						{
							eventsMap[fromCode].push({
								type: EventListItemType.TYPE_DAY_LABEL,
								date: event.getDateFrom().getTime(),
								dayCode: DateHelper.getDayCode(event.getDateFrom()),
							});
						}
					}

					eventsMap[fromCode].push({
						type: EventListItemType.TYPE_EVENT,
						event,
						eventUniqueId: event.getUniqueId(),
						dayCode: fromCode,
						isLongWithTime: false,
						isFullDay: event.isFullDay(),
						isUntil: false,
					});
				}
			});

			return eventsMap;
		}

		/**
		 * @private
		 * @param eventsRaw
		 * @param storage
		 * @param index
		 */
		appendEventsToStorage(eventsRaw, storage, index)
		{
			for (const eventRaw of eventsRaw)
			{
				const event = this.createEventFromEventRaw(eventRaw);
				this.appendEvent(event, storage, index);
			}
		}

		/**
		 * @private
		 * @param eventRaw
		 * @returns {EventModel}
		 */
		createEventFromEventRaw(eventRaw)
		{
			const event = new EventModel(eventRaw);

			if (!event.getColor())
			{
				event.setColor(this.sectionManager.getSection(event.getSectionId()).getColor());
			}

			if (event.getLocation())
			{
				const locationName = this.locationManager.getTextLocation(event.getLocation());
				event.setLocation(locationName);
			}

			return event;
		}

		/**
		 * @private
		 * @param event
		 * @param storage
		 * @param index
		 */
		appendEvent(event, storage, index)
		{
			if (!this.props.showDeclined && event.isDeclined())
			{
				return;
			}

			const uniqueId = event.getUniqueId();

			if (Type.isNil(index[uniqueId]))
			{
				storage.push(event);
				// eslint-disable-next-line no-param-reassign
				index[uniqueId] = storage.length - 1;
			}
			else
			{
				// eslint-disable-next-line no-param-reassign
				storage[index[uniqueId]] = event;
			}
		}

		deleteEvent(id)
		{
			this.storage = this.storage.filter((event) => event.getUniqueId() !== id);
		}

		/**
		 * @private
		 * @param events
		 * @returns {*|*[]}
		 */
		// eslint-disable-next-line sonarjs/cognitive-complexity
		sortEvents(events)
		{
			if (events)
			{
				events.sort((a, b) => {
					if (a.type !== b.type)
					{
						return a.type === EventListItemType.TYPE_DAY_LABEL ? -1 : 1;
					}

					if (a.isFullDay !== b.isFullDay)
					{
						return a.isFullDay ? -1 : 1;
					}

					if (a.isFullDay && b.isFullDay)
					{
						if (a.event.getDateFrom().getTime() === b.event.getDateFrom().getTime())
						{
							return a.event.getId() - b.event.getId();
						}

						return a.event.getDateFrom().getTime() - b.event.getDateFrom().getTime();
					}

					if (a.isUntil !== b.isUntil)
					{
						return a.isUntil ? -1 : 1;
					}

					if (a.isUntil && b.isUntil)
					{
						return a.event.getDateTo().getTime() - b.event.getDateTo().getTime();
					}

					if (a.event.getDateFrom().getTime() === b.event.getDateFrom().getTime())
					{
						if (a.event.getDateTo().getTime() === b.event.getDateTo().getTime())
						{
							return a.event.getId() - b.event.getId();
						}

						return a.event.getDateTo().getTime() - b.event.getDateTo().getTime();
					}

					return a.event.getDateFrom().getTime() - b.event.getDateFrom().getTime();
				});

				return events;
			}

			return [];
		}

		/**
		 * @private
		 * @param data
		 */
		onEventListRefreshed(data)
		{
			if (data && data.events)
			{
				const eventsRaw = BX.prop.getArray(data, 'events', []);
				this.eventsMap = this.createLoadedEventsMap(eventsRaw);
				this.props.onRefresh();
			}
		}

		/**
		 * @private
		 * @param startDate
		 * @param endDate
		 * @param data
		 */
		onEventListReceived(startDate, endDate, data)
		{
			if (!data?.events)
			{
				return;
			}

			const rangeKey = `${DateHelper.getDayCode(startDate)}:${DateHelper.getDayCode(endDate)}`;
			const currentIds = this.eventIdsMap[rangeKey] || [];
			const receivedIds = data.events.map((event) => parseInt(event.ID, 10));
			this.eventIdsMap[rangeKey] = receivedIds;
			const deletedIds = currentIds.filter((id) => !receivedIds.includes(id));
			deletedIds.forEach((id) => this.deleteEvent(id));

			const eventsRaw = BX.prop.getArray(data, 'events', []);
			this.eventsMap = this.appendEventsRawToIndex(eventsRaw, this.storage, this.loadedEventsIndex);

			this.setLoadedRange(startDate, endDate);
			this.props.onEventLoaded();
		}

		/**
		 * @private
		 * @param startDate
		 * @param endDate
		 */
		setLoadedRange(startDate, endDate)
		{
			if (!this.loadedRange.start || this.loadedRange.start.getTime() > startDate.getTime())
			{
				this.loadedRange.start = startDate;
			}

			if (!this.loadedRange.end || this.loadedRange.end.getTime() < endDate.getTime())
			{
				this.loadedRange.end = endDate;
			}
		}
	}

	module.exports = { EventManager };
});
