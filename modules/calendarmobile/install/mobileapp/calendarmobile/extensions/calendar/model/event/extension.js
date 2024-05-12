/**
 * @module calendar/model/event
 */
jn.define('calendar/model/event', (require, exports, module) => {
	const { DateHelper, Moment } = require('calendar/date-helper');

	/**
	 * @class EventModel
	 */
	class EventModel
	{
		constructor(props)
		{
			this.id = BX.prop.getNumber(props, 'ID', 0);
			this.parentId = BX.prop.getNumber(props, 'PARENT_ID', 0);
			this.fullDay = BX.prop.getString(props, 'DT_SKIP_TIME', 'N') === 'Y';
			this.sectionId = BX.prop.getNumber(props, 'SECT_ID', 0);
			this.name = BX.prop.getString(props, 'NAME', '').replaceAll('\r\n', ' ');
			this.location = BX.prop.getString(props, 'LOCATION', '');
			this.color = BX.prop.getString(props, 'COLOR', '');
			this.eventType = BX.prop.getString(props, 'EVENT_TYPE', '');
			this.meetingStatus = BX.prop.getString(props, 'MEETING_STATUS', '');
			this.recurrenceRule = BX.prop.getObject(props, 'RRULE', null);
			this.eventLenght = BX.prop.getNumber(props, 'DT_LENGTH', 0);

			this.userTimezoneOffsetFrom = BX.prop.getNumber(props, '~USER_OFFSET_FROM', 0);
			this.userTimezoneOffsetTo = BX.prop.getNumber(props, '~USER_OFFSET_TO', 0);

			this.prepareDate(props);
		}

		prepareDate(props)
		{
			if (!this.eventLenght)
			{
				this.eventLenght = BX.prop.getNumber(props, 'DURATION', 0);
			}

			if (this.isFullDay() && !this.eventLenght)
			{
				this.eventLenght = 86400;
			}

			const dateFormatted = BX.prop.getString(props, 'DATE_FROM_FORMATTED', '');
			this.dateFrom = dateFormatted ? new Date(dateFormatted) : new Date();

			if (this.isFullDay())
			{
				this.dateFrom.setHours(0, 0, 0, 0);
			}
			else
			{
				this.dateFrom = new Date(this.dateFrom.getTime() - this.userTimezoneOffsetFrom * 1000);
			}

			if (this.isFullDay())
			{
				this.dateTo = new Date(this.dateFrom.getTime() + (this.eventLenght - 3601) * 1000);
				this.dateTo.setHours(0, 0, 0, 0);
			}
			else
			{
				this.dateTo = new Date(this.dateFrom.getTime() + this.eventLenght * 1000);
			}
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
			return this.getMeetingStatus() === 'Q';
		}

		isDeclined()
		{
			return this.getMeetingStatus() === 'N';
		}

		isSharingEvent()
		{
			return this.getEventType() === '#shared#' || this.getEventType() === '#shared_crm#';
		}

		isRecurrence()
		{
			return Boolean(this.getRecurrenceRule());
		}

		hasPassed(isFullDayParam = null)
		{
			const isFullDay = isFullDayParam ?? this.isFullDay();
			const dateToMoment = isFullDay
				? new Moment(this.dateTo).add(86_000_000)
				: new Moment(this.dateTo)
			;

			return dateToMoment.hasPassed;
		}

		getMomentDateFrom()
		{
			return new Moment(this.dateFrom);
		}

		getMomentDateTo()
		{
			return new Moment(this.dateTo);
		}
	}

	module.exports = { EventModel };
});
