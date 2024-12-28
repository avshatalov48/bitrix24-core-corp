/**
 * @module calendar/event-view-form/data-loader
 */
jn.define('calendar/event-view-form/data-loader', (require, exports, module) => {
	const { Type } = require('type');

	const { UserManager } = require('calendar/data-managers/user-manager');
	const { SectionManager } = require('calendar/data-managers/section-manager');
	const { CollabManager } = require('calendar/data-managers/collab-manager');
	const { EventAjax } = require('calendar/ajax');
	const { SettingsManager } = require('calendar/data-managers/settings-manager');
	const { LocationManager } = require('calendar/data-managers/location-manager');
	const { BooleanParams } = require('calendar/enums');

	const store = require('statemanager/redux/store');
	const {
		selectByIdAndDate,
		eventsAdded,
		eventFilesChanged,
	} = require('calendar/statemanager/redux/slices/events');

	/**
	 * @class DataLoader
	 */
	class DataLoader
	{
		constructor()
		{
			this.isFilesLoading = false;
		}

		getEvent({ eventId, dateFromTs })
		{
			return selectByIdAndDate(store.getState(), { eventId, dateFromTs });
		}

		async loadEvent({
			eventId,
			eventDate = '',
			event = null,
			notRequestedUsers = [],
			requestCollabs = false,
			getEventById = false,
		})
		{
			let requestUsers = true;
			if (event && notRequestedUsers.length === 0)
			{
				requestUsers = false;
			}

			const { data } = await EventAjax.getViewFormConfig({
				eventId,
				eventDate,
				userIds: notRequestedUsers,
				requestUsers: requestUsers ? BooleanParams.YES : BooleanParams.NO,
				requestCollabs: requestCollabs ? BooleanParams.YES : BooleanParams.NO,
				getEventById: getEventById ? BooleanParams.YES : BooleanParams.NO,
			});

			if (!data.event)
			{
				return {
					eventId: null,
					dateFromTs: null,
				};
			}

			this.addUsersToRedux(event, data.users);

			const eventData = {
				...data.event,
				permissions: data.permissions,
				files: data.files,
			};

			if (requestCollabs && Type.isArrayFilled(data.sections))
			{
				SectionManager.addSections(data.sections);
			}

			if (Type.isArrayFilled(data.collabs))
			{
				CollabManager.setCollabs(data.collabs);
			}

			if (Type.isObject(data.settings) && data.settings.firstWeekday)
			{
				SettingsManager.setBaseSettings(data.settings);
				SettingsManager.setCalendarSettings(data.settings);
			}

			if (Type.isArrayFilled(data.locations))
			{
				LocationManager.setLocations(data.locations);
			}

			if (Type.isArrayFilled(data.categories))
			{
				LocationManager.setCategories(data.categories);
			}

			store.dispatch(
				eventsAdded([eventData]),
			);

			return this.getEventParamsForOpen(eventData);
		}

		getEventParamsForOpen(eventData)
		{
			const eventId = BX.prop.getNumber(eventData, 'ID', 0);
			const timezoneOffset = BX.prop.getNumber(eventData, 'TZ_OFFSET_FROM', 0);
			const fullDay = BX.prop.getString(eventData, 'DT_SKIP_TIME', 'N') === 'Y';
			const dateFormatted = BX.prop.getString(eventData, 'DATE_FROM_FORMATTED', '');

			const dateFrom = dateFormatted ? new Date(dateFormatted) : new Date();
			if (fullDay)
			{
				dateFrom.setHours(0, 0, 0, 0);
			}
			else
			{
				const userTimezoneOffsetFrom = timezoneOffset - (dateFrom.getTimezoneOffset() * -60);
				dateFrom.setTime(dateFrom.getTime() - userTimezoneOffsetFrom * 1000);
			}

			return {
				eventId,
				dateFromTs: dateFrom.getTime(),
			};
		}

		async loadFiles({ eventId, parentId })
		{
			if (this.isFilesLoading)
			{
				return;
			}

			this.isFilesLoading = true;

			const { data } = await EventAjax.getFilesForViewForm({ eventId, parentId });

			const { files } = data;

			if (Type.isArray(files))
			{
				store.dispatch(
					eventFilesChanged({ eventId, files }),
				);
			}

			this.isFilesLoading = false;
		}

		getNotRequestedUserIds(event)
		{
			let result = [];

			if (event && event.attendees)
			{
				result = UserManager.getNotRequestedUserIds(event.attendees.map((user) => user.id));
			}

			return result;
		}

		addUsersToRedux(event, users)
		{
			if (users && users.length > 0)
			{
				UserManager.addUsersToRedux(users);
			}
		}
	}

	module.exports = { DataLoader: new DataLoader() };
});
