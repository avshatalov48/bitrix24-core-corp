/**
 * @module calendar/event-edit-form/menu/reminder
 */
jn.define('calendar/event-edit-form/menu/reminder', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BaseMenu, baseSectionType } = require('calendar/base-menu');
	const { Duration } = require('utils/date');

	class ReminderMenu extends BaseMenu
	{
		getItems()
		{
			const reminders = [0, 5, 15, 30, 60, 120, 1440, 2880];

			return reminders.map((reminder) => ({
				id: String(reminder),
				testId: `calendar-event-edit-form-reminder-menu-${reminder}`,
				sectionCode: baseSectionType,
				title: this.formatReminder(reminder),
				checked: reminder === this.props.reminder,
			}));
		}

		formatReminder(reminder)
		{
			if (reminder === 0)
			{
				return Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_BOOLEAN_N');
			}

			return Duration.createFromMinutes(reminder).format();
		}
	}

	module.exports = { ReminderMenu };
});
