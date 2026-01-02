/**
 * @module calendar/model/event
 */
jn.define('calendar/model/event', (require, exports, module) => {
	const { Type } = require('type');
	const { DateHelper, Moment } = require('calendar/date-helper');
	const { CalendarType, EventTypes, EventMeetingStatus } = require('calendar/enums');

	/**
	 * @class EventModel
	 */
	class EventModel
	{
		static fromReduxModel(reduxEvent)
		{
			return new this(reduxEvent, true);
		}

		constructor(props, fromReduxModel = false)
		{
			if (fromReduxModel)
			{
				this.buildFromReduxModel(props);
			}
			else
			{
				this.buildFromRaw(props);
			}
		}

		buildFromReduxModel(props)
		{
			const reduxProps = { ...props };
			this.dateFrom = new Date(reduxProps.dateFromTs);
			this.dateTo = new Date(reduxProps.dateToTs);

			const isCached = !Type.isNil(reduxProps.isFullDay);

			if (isCached)
			{
				this.fullDay = reduxProps.isFullDay;
				this.longWithTime = this.fullDay
					? reduxProps.eventLength > 86400
					: DateHelper.getDayCode(this.dateFrom) !== DateHelper.getDayCode(this.dateTo)
				;
				delete reduxProps.isFullDay;
			}

			Object.assign(this, reduxProps);
		}

		buildFromRaw(props)
		{
			this.id = BX.prop.getNumber(props, 'ID', 0);
			this.parentId = BX.prop.getNumber(props, 'PARENT_ID', 0);
			this.fullDay = BX.prop.getString(props, 'DT_SKIP_TIME', 'N') === 'Y';
			this.timezone = BX.prop.getString(props, 'TZ_FROM', null);
			this.timezoneOffset = BX.prop.getNumber(props, 'TZ_OFFSET_FROM', 0);
			this.isDaylightSavingTimezone = BX.prop.getString(props, 'IS_DAYLIGHT_SAVING_TZ', '');
			this.sectionId = BX.prop.getNumber(props, 'SECTION_ID', 0);
			this.name = BX.prop.getString(props, 'NAME', '').replaceAll('\r\n', ' ');
			this.description = BX.prop.getString(props, 'DESCRIPTION', '');
			this.location = BX.prop.getString(props, 'LOCATION', '');
			this.color = BX.prop.getString(props, 'COLOR', '');
			this.eventType = BX.prop.getString(props, 'EVENT_TYPE', '');
			this.meetingStatus = BX.prop.getString(props, 'MEETING_STATUS', '');
			this.recurrenceRule = BX.prop.getObject(props, 'RRULE', null);
			this.eventLength = BX.prop.getNumber(props, 'DT_LENGTH', null) ?? BX.prop.getNumber(props, 'DURATION', 0);
			this.calType = BX.prop.getString(props, 'CAL_TYPE', '');
			this.ownerId = BX.prop.getNumber(props, 'OWNER_ID', 0);
			this.meetingHost = BX.prop.getNumber(props, 'MEETING_HOST', 0);
			this.reminders = BX.prop.getArray(props, 'REMIND', []);
			this.collabId = BX.prop.getNumber(props, 'COLLAB_ID', 0);
			this.accessibility = BX.prop.getString(props, 'ACCESSIBILITY', 'busy');
			this.importance = BX.prop.getString(props, 'IMPORTANCE', 'normal');
			this.privateEvent = BX.prop.getNumber(props, 'PRIVATE_EVENT', 0);
			this.longWithTime = false;

			this.prepareDate(props);
		}

		prepareDate(props)
		{
			const dateFormatted = BX.prop.getString(props, 'DATE_FROM_FORMATTED', '');
			this.dateFrom = dateFormatted ? new Date(dateFormatted) : new Date();
			if (this.isFullDay())
			{
				this.eventLength ||= 86400;
				this.dateFrom.setHours(0, 0, 0, 0);
			}
			else
			{
				const userTimezoneOffsetFrom = this.timezoneOffset - (this.dateFrom.getTimezoneOffset() * -60);
				this.dateFrom.setTime(this.dateFrom.getTime() - userTimezoneOffsetFrom * 1000);
			}
			this.dateTo = new Date(this.dateFrom.getTime() + this.eventLength * 1000);
		}

		getId()
		{
			return this.id;
		}

		getParentId()
		{
			return this.parentId;
		}

		getName()
		{
			return this.name;
		}

		getDescription()
		{
			return this.description;
		}

		setColor(color)
		{
			this.color = color;
		}

		getColor()
		{
			return this.color;
		}

		setLocation(location)
		{
			this.location = location;
		}

		getLocation()
		{
			return this.location;
		}

		isFullDay()
		{
			return this.fullDay;
		}

		getTimezone()
		{
			return this.timezone;
		}

		getDateFrom()
		{
			return this.dateFrom;
		}

		getDateTo()
		{
			return this.dateTo;
		}

		getRecurrenceRule()
		{
			return this.recurrenceRule;
		}

		getMeetingStatus()
		{
			return this.meetingStatus;
		}

		getEventType()
		{
			return this.eventType;
		}

		getSectionId()
		{
			return this.sectionId;
		}

		getAccessibility()
		{
			return this.accessibility;
		}

		getImportance()
		{
			return this.importance;
		}

		getPrivateEvent()
		{
			return this.privateEvent ? 'Y' : 'N';
		}

		getUniqueId()
		{
			const id = this.parentId || this.id;

			if (this.isRecurrence())
			{
				return `${id}|${DateHelper.getDayCode(this.dateFrom)}`;
			}

			return id;
		}

		isInvited()
		{
			return this.getMeetingStatus() === EventMeetingStatus.QUESTIONED && this.getOwnerId() === Number(env.userId);
		}

		isDeclined()
		{
			return this.getMeetingStatus() === EventMeetingStatus.DECLINED;
		}

		isSharingEvent()
		{
			return this.getEventType()
				&& [EventTypes.SHARED, EventTypes.SHARED_CRM, EventTypes.SHARED_COLLAB].includes(this.getEventType())
			;
		}

		isBookingEvent()
		{
			return this.getEventType() === EventTypes.BOOKING;
		}

		isRecurrence()
		{
			return Boolean(this.getRecurrenceRule());
		}

		isPrivate()
		{
			return Boolean(this.getPrivateEvent());
		}

		hasPassed(dayCode)
		{
			if (this.isRecurrence())
			{
				const selectedDate = DateHelper.getDateFromDayCode(dayCode);
				selectedDate.setHours(this.dateTo.getHours(), this.dateTo.getMinutes());

				if (this.isFullDay())
				{
					selectedDate.setTime(selectedDate.getTime() + DateHelper.dayLength);
				}

				return new Moment(selectedDate).hasPassed;
			}

			return this.getMomentDateTo().hasPassed;
		}

		getMomentDateFrom()
		{
			return new Moment(this.dateFrom);
		}

		getMomentDateTo()
		{
			return new Moment(this.dateTo);
		}

		getOwnerId()
		{
			return this.ownerId;
		}

		getCalType()
		{
			return this.calType;
		}

		isGroupCalendar()
		{
			return this.calType === CalendarType.GROUP;
		}

		getDuration()
		{
			return this.dateTo.getTime() - this.dateFrom.getTime();
		}

		getReminders()
		{
			return this.reminders;
		}

		isLongWithTime()
		{
			return this.longWithTime;
		}

		getCollabId()
		{
			return this.collabId;
		}

		isDaylightSavingTz()
		{
			return this.isDaylightSavingTimezone;
		}
	}

	module.exports = { EventModel };
});
