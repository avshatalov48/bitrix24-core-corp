/**
 * @module calendar/statemanager/redux/slices/events/model
 */
jn.define('calendar/statemanager/redux/slices/events/model', (require, exports, module) => {
	const { Type } = require('type');
	const { DateHelper } = require('calendar/date-helper');
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
			const preparedEvent = {
				...existingReduxEvent,
				id: BX.prop.getNumber(eventData, 'ID', 0),
				parentId: BX.prop.getNumber(eventData, 'PARENT_ID', 0),
				dateFromFormatted: BX.prop.getString(eventData, 'DATE_FROM_FORMATTED', ''),
				fullDay: BX.prop.getString(eventData, 'DT_SKIP_TIME', 'N') === 'Y',
				timezone: BX.prop.getString(eventData, 'TZ_FROM', null),
				timezoneOffset: BX.prop.getNumber(eventData, 'TZ_OFFSET_FROM', 0),
				isDaylightSavingTimezone: BX.prop.getString(eventData, 'IS_DAYLIGHT_SAVING_TZ', ''),
				sectionId: BX.prop.getNumber(eventData, 'SECTION_ID', 0),
				name: BX.prop.getString(eventData, 'NAME', '').replaceAll('\r\n', ' '),
				description: BX.prop.getString(eventData, 'DESCRIPTION', ''),
				location: BX.prop.getString(eventData, 'LOCATION', ''),
				color: BX.prop.getString(eventData, 'COLOR', ''),
				eventType: BX.prop.getString(eventData, 'EVENT_TYPE', ''),
				meetingStatus: BX.prop.getString(eventData, 'MEETING_STATUS', ''),
				recurrenceRule: BX.prop.getObject(eventData, 'RRULE', null),
				recurrenceRuleDescription: BX.prop.getString(eventData, '~RRULE_DESCRIPTION', ''),
				recurrenceId: BX.prop.getNumber(eventData, 'RECURRENCE_ID', 0),
				excludedDates: BX.prop.getString(eventData, 'EXDATE', '').split(';'),
				eventLength: BX.prop.getNumber(eventData, 'DT_LENGTH', null) ?? BX.prop.getNumber(eventData, 'DURATION', 0),
				calType: BX.prop.getString(eventData, 'CAL_TYPE', CalendarType.USER),
				attendees: BX.prop.getArray(eventData, 'ATTENDEE_LIST', []).map(({ id, status }) => ({ id, status })),
				reminders: BX.prop.getArray(eventData, 'REMIND', []),
				collabId: BX.prop.getNumber(eventData, 'COLLAB_ID', 0),
				ownerId: BX.prop.getNumber(eventData, 'OWNER_ID', 0),
				meetingHost: BX.prop.getNumber(eventData, 'MEETING_HOST', 0),
				accessibility: BX.prop.getString(eventData, 'ACCESSIBILITY', EventAccessibility.BUSY),
				importance: BX.prop.getString(eventData, 'IMPORTANCE', EventImportance.NORMAL),
				privateEvent: BX.prop.getNumber(eventData, 'PRIVATE_EVENT', 0),
				permissions: BX.prop.getObject(eventData, 'permissions', null),
				files: BX.prop.getArray(eventData, 'files', null),
				entityRelation: BX.prop.getObject(eventData, 'entityRelation', null),
			};

			if (
				Type.isObject(existingReduxEvent?.permissions)
				&& !Type.isUndefined(existingReduxEvent?.permissions?.edit)
			)
			{
				preparedEvent.permissions = existingReduxEvent?.permissions;
			}

			if (Type.isArrayFilled(existingReduxEvent?.files))
			{
				preparedEvent.files = existingReduxEvent?.files;
			}

			if (Type.isObject(existingReduxEvent?.entityRelation))
			{
				preparedEvent.entityRelation = existingReduxEvent?.entityRelation;
			}

			const meeting = BX.prop.getObject(eventData, 'MEETING', {});
			preparedEvent.chatId = BX.prop.getNumber(meeting, 'CHAT_ID', 0);

			const dateFrom = preparedEvent.dateFromFormatted ? new Date(preparedEvent.dateFromFormatted) : new Date();
			if (preparedEvent.fullDay)
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
			preparedEvent.longWithTime = preparedEvent.fullDay
				? preparedEvent.eventLength > 86400
				: DateHelper.getDayCode(dateFrom) !== DateHelper.getDayCode(dateTo)
			;

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
				fullDay: false,
				longWithTime: false,
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
				entityRelation: null,
				hasUploadedFiles: false,
			};
		}
	}

	module.exports = { Model };
});
