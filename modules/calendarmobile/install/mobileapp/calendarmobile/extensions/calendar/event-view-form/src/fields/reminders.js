/**
 * @module calendar/event-view-form/fields/reminders
 */
jn.define('calendar/event-view-form/fields/reminders', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Color } = require('tokens');
	const { Text5 } = require('ui-system/typography/text');
	const { Duration } = require('utils/date');
	const { Loc } = require('loc');

	const { DateHelper } = require('calendar/date-helper');
	const { IconWithText, Icon } = require('calendar/event-view-form/layout/icon-with-text');

	class RemindersField extends PureComponent
	{
		getId()
		{
			return this.props.id;
		}

		isReadOnly()
		{
			return this.props.readOnly;
		}

		isRequired()
		{
			return false;
		}

		isEmpty()
		{
			return this.props.value.length === 0;
		}

		render()
		{
			const reminders = this.props.value;
			const remindersFormatted = reminders
				.map((reminder) => this.formatReminder(reminder))
				.filter((reminder) => reminder?.value)
			;

			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				Text5({
					testId: 'calendar-event-view-form-reminders_TITLE',
					text: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_REMINDERS_NUM', {
						'#NUM#': reminders.length,
					}),
					color: Color.base4,
				}),
				...remindersFormatted.map((reminder) => IconWithText(
					Icon.NOTIFICATION,
					reminder.value,
					`calendar-event-view-form-reminder-${reminder.testId}`,
					false,
				)),
			);
		}

		formatReminder(reminder)
		{
			switch (reminder.type)
			{
				case 'min':
					if (reminder.count === 0)
					{
						return {
							testId: 'at-time',
							value: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_REMIND_AT_THE_EVENT_TIME')
						};
					}

					return {
						testId: `min-${String(reminder.count)}`,
						value: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_REMIND_BEFORE_TIME', {
							'#TIME#': Duration.createFromMinutes(reminder.count).format(),
						}),
					};
				case 'daybefore':
					// eslint-disable-next-line no-case-declarations
					const dateFromMinutes = new Date(new Date().setHours(0, reminder.time, 0, 0));
					// eslint-disable-next-line no-case-declarations
					const time = DateHelper.formatTime(dateFromMinutes);

					if (reminder.before === 0)
					{
						return {
							testId: `at-day-${String(time)}`,
							value: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_REMIND_IN_EVENT_DATE_AT_TIME', {
								'#TIME#': time,
							}),
						};
					}

					return {
						testId: `daybefore-${String(reminder.before)}-time-${String(time)}`,
						value: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_REMIND_BEFORE_N_DAYS_AT_TIME', {
							'#N_DAYS#': new Duration(1000 * 60 * 60 * 24 * reminder.before).format('d'),
							'#TIME#': time,
						}),
					};
				case 'date':
					return {
						testId: `date-${String(reminder.value)}`,
						value: reminder.value,
					};
				default:
					return null;
			}
		}
	}

	module.exports = {
		RemindersField: (props) => new RemindersField(props),
	};
});
