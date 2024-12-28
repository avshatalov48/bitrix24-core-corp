/**
 * @module calendar/event-view-form/fields/date-time
 */

jn.define('calendar/event-view-form/fields/date-time', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Loc } = require('loc');
	const { Moment } = require('utils/date');
	const { dayMonth, shortTime, longDate } = require('utils/date/formats');
	const { IconWithText, Icon } = require('calendar/event-view-form/layout/icon-with-text');

	const { DateHelper } = require('calendar/date-helper');

	class DateTimeField extends PureComponent
	{
		getId()
		{
			return this.props.id;
		}

		isReadOnly()
		{
			return this.props.readOnly;
		}

		render()
		{
			return IconWithText(Icon.CALENDAR_WITH_SLOTS, this.formattedDateTime, 'calendar-event-view-form-date-time');
		}

		getDisplayedValue()
		{
			return this.formattedDateTime;
		}

		get formattedDateTime()
		{
			const minusSecond = this.props.isFullDay ? 1000 : 0;
			const eventFrom = new Date(this.props.dateFromTs);
			const eventTo = new Date(this.props.dateToTs - minusSecond);
			const isSameDate = DateHelper.getDayCode(eventFrom) === DateHelper.getDayCode(eventTo);
			const startsInCurrentYear = eventFrom.getFullYear() === new Date().getFullYear();
			const endsInCurrentYear = eventTo.getFullYear() === new Date().getFullYear();

			if (isSameDate)
			{
				const dateFormat = startsInCurrentYear ? dayMonth() : longDate();
				const date = this.formatTimestamp(eventFrom.getTime(), dateFormat);

				if (this.props.isFullDay)
				{
					return Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_FORMAT_FULL_DAY', {
						'#DATE#': date,
					});
				}

				const time = Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_FORMAT_TIME_RANGE', {
					'#FROM#': this.formatTimestamp(eventFrom.getTime(), shortTime()),
					'#TO#': this.formatTimestamp(eventTo.getTime(), shortTime()),
				});

				return Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_FORMAT_DATE_TIME', {
					'#DATE#': date,
					'#TIME#': time,
				});
			}

			const dateFromFormat = startsInCurrentYear ? dayMonth() : longDate();
			const dateToFormat = endsInCurrentYear ? dayMonth() : longDate();

			if (this.props.isFullDay)
			{
				return Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_FORMAT_TIME_RANGE_FULL_DAY', {
					'#FROM#': this.formatTimestamp(eventFrom.getTime(), dateFromFormat),
					'#TO#': this.formatTimestamp(eventTo.getTime(), dateToFormat),
				});
			}

			return Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_FORMAT_DATE_TIME_RANGE', {
				'#FROM_DATE#': this.formatTimestamp(eventFrom.getTime(), dateFromFormat),
				'#FROM_TIME#': this.formatTimestamp(eventFrom.getTime(), shortTime()),
				'#TO_DATE#': this.formatTimestamp(eventTo.getTime(), dateToFormat),
				'#TO_TIME#': this.formatTimestamp(eventTo.getTime(), shortTime()),
			});
		}

		formatTimestamp(timestamp, format)
		{
			return new Moment(timestamp).format(format);
		}
	}

	module.exports = {
		DateTimeField: (props) => new DateTimeField(props),
	};
});
