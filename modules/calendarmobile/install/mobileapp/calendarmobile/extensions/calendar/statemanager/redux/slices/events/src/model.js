/**
 * @module calendar/statemanager/redux/slices/events/model
 */
jn.define('calendar/statemanager/redux/slices/events/model', (require, exports, module) => {
	const { Type } = require('type');
	const { EventAccessibility, EventImportance, EventMeetingStatus, CalendarType } = require('calendar/enums');

	class Model
	{
		/**
		 * @public
		 * @param {object} eventData
		 * @param {object|null} existingReduxEvent
		 * @return {object}
		 */
		static fromEventData(eventData, existingReduxEvent = null)
		{
			const preparedEvent = { ...existingReduxEvent };

			preparedEvent.id = BX.prop.getNumber(eventData, 'ID', preparedEvent.id);
			preparedEvent.parentId = BX.prop.getNumber(eventData, 'PARENT_ID', 0);
			preparedEvent.isFullDay = BX.prop.getString(eventData, 'DT_SKIP_TIME', 'N') === 'Y';
			preparedEvent.timezone = BX.prop.getString(eventData, 'TZ_FROM', null);
			preparedEvent.timezoneOffset = BX.prop.getNumber(eventData, 'TZ_OFFSET_FROM', 0);
			preparedEvent.sectionId = BX.prop.getNumber(eventData, 'SECTION_ID', 0);
			preparedEvent.name = BX.prop.getString(eventData, 'NAME', '').replaceAll('\r\n', ' ');
			preparedEvent.description = BX.prop.getString(eventData, 'DESCRIPTION', '');
			preparedEvent.location = BX.prop.getString(eventData, 'LOCATION', '');
			preparedEvent.color = BX.prop.getString(eventData, 'COLOR', '');
			preparedEvent.eventType = BX.prop.getString(eventData, 'EVENT_TYPE', '');
			preparedEvent.meetingStatus = BX.prop.getString(eventData, 'MEETING_STATUS', '');
			preparedEvent.recurrenceRule = BX.prop.getObject(eventData, 'RRULE', null);
			preparedEvent.recurrenceRuleDescription = BX.prop.getString(eventData, '~RRULE_DESCRIPTION', '');
			preparedEvent.recurrenceId = BX.prop.getNumber(eventData, 'RECURRENCE_ID', 0);
			preparedEvent.excludedDates = BX.prop.getString(eventData, 'EXDATE', '').split(';');
			preparedEvent.eventLength = BX.prop.getNumber(eventData, 'DT_LENGTH', null) ?? BX.prop.getNumber(eventData, 'DURATION', 0);
			preparedEvent.calType = BX.prop.getString(eventData, 'CAL_TYPE', '');
			preparedEvent.attendees = BX.prop.getArray(eventData, 'ATTENDEE_LIST', []).map(({ id, status }) => ({ id, status }));
			preparedEvent.reminders = BX.prop.getArray(eventData, 'REMIND', []);
			preparedEvent.collabId = BX.prop.getNumber(eventData, 'COLLAB_ID', 0);
			preparedEvent.ownerId = BX.prop.getNumber(eventData, 'OWNER_ID', 0);
			preparedEvent.meetingHost = BX.prop.getNumber(eventData, 'MEETING_HOST', 0);
			preparedEvent.accessibility = BX.prop.getString(eventData, 'ACCESSIBILITY', EventAccessibility.BUSY);
			preparedEvent.importance = BX.prop.getString(eventData, 'IMPORTANCE', EventImportance.NORMAL);
			preparedEvent.privateEvent = BX.prop.getNumber(eventData, 'PRIVATE_EVENT', 0);

			if (
				existingReduxEvent?.permissions
				&& Type.isObject(existingReduxEvent.permissions)
				&& !Type.isUndefined(existingReduxEvent.permissions?.edit)
			)
			{
				preparedEvent.permissions = existingReduxEvent.permissions;
			}
			else
			{
				preparedEvent.permissions = eventData.permissions;
			}

			if (existingReduxEvent?.files && Type.isArrayFilled(existingReduxEvent.files))
			{
				preparedEvent.files = existingReduxEvent.files;
			}
			else
			{
				preparedEvent.files = eventData.files;
			}

			const meeting = BX.prop.getObject(eventData, 'MEETING', {});
			preparedEvent.chatId = BX.prop.getNumber(meeting, 'CHAT_ID', 0);

			preparedEvent.dateFromFormatted = BX.prop.getString(eventData, 'DATE_FROM_FORMATTED', '');

			const dateFrom = preparedEvent.dateFromFormatted ? new Date(preparedEvent.dateFromFormatted) : new Date();
			if (preparedEvent.isFullDay)
			{
				preparedEvent.eventLength ||= 86400;
				dateFrom.setHours(0, 0, 0, 0);
			}
			else
			{
				const userTimezoneOffsetFrom = preparedEvent.timezoneOffset - (dateFrom.getTimezoneOffset() * -60);
				dateFrom.setTime(dateFrom.getTime() - userTimezoneOffsetFrom * 1000);
			}
			const dateTo = new Date(dateFrom.getTime() + preparedEvent.eventLength * 1000);

			preparedEvent.dateFromTs = dateFrom.getTime();
			preparedEvent.dateToTs = dateTo.getTime();

			return preparedEvent;
		}

		/**
		 * @returns {EventReduxModel}
		 */
		static getDefault()
		{
			const fiveMinutes = 5 * 60 * 1000;
			const hour = 60 * 60 * 1000;
			const dateFromTs = Math.ceil(Date.now() / fiveMinutes) * fiveMinutes;

			return {
				id: `tmp-id-${Date.now()}`,
				parentId: 0,
				dateFromTs,
				dateToTs: dateFromTs + hour,
				isFullDay: false,
				timezone: null,
				sectionId: 0,
				name: '',
				location: '',
				color: '#9DCF00',
				eventType: '',
				meetingHost: Number(env.userId),
				meetingStatus: EventMeetingStatus.HOST,
				recurrenceRule: null,
				recurrenceRuleDescription: '',
				recurrenceId: 0,
				eventLength: null,
				calType: CalendarType.USER,
				ownerId: Number(env.userId),
				accessibility: EventAccessibility.BUSY,
				importance: EventImportance.NORMAL,
				privateEvent: 0,
				attendees: [{ id: Number(env.userId), status: EventMeetingStatus.HOST }],
				reminders: [{ type: 'min', count: 15 }],
				collabId: 0,
				chatId: 0,
				permissions: {},
				files: [],
				hasUploadedFiles: false,
			};
		}
	}

	module.exports = { Model };
});
