/**
 * @module calendar/event-edit-form/layout/reminder-selector
 */
jn.define('calendar/event-edit-form/layout/reminder-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');
	const { Duration } = require('utils/date');
	const { Button, Icon, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');

	const { State, observeState } = require('calendar/event-edit-form/state');
	const { ReminderMenu } = require('calendar/event-edit-form/menu/reminder');

	/**
	 * @class ReminderSelector
	 */
	class ReminderSelector extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.refs = {
				button: null,
			};

			this.menu = null;
		}

		render()
		{
			return Button({
				testId: 'calendar-event-edit-form-reminder-button',
				text: this.getTitle(),
				design: ButtonDesign.PLAIN,
				color: Color.base4,
				size: ButtonSize.XS,
				leftIcon: Icon.NOTIFICATION,
				rightIcon: Icon.CHEVRON_DOWN,
				style: {
					marginBottom: Indent.XL2.toNumber(),
				},
				stretched: true,
				onClick: this.onClickHandler,
				forwardRef: this.#bindButtonRef,
			});
		}

		#bindButtonRef = (ref) => {
			this.refs.button = ref;
		};

		getTitle()
		{
			if (this.props.reminder === 0)
			{
				return Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_NO_REMINDER');
			}

			return Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_REMIND_BEFORE_TIME', {
				'#TIME#': this.formatReminder(this.props.reminder),
			});
		}

		onClickHandler = () => {
			this.menu = new ReminderMenu({
				reminder: this.props.reminder,
				targetElementRef: this.refs.button,
				onItemSelected: this.onReminderSelectedHandler,
			});

			this.menu.show();
		};

		onReminderSelectedHandler = (item) => {
			const reminder = parseInt(item.id, 10);
			State.setReminder(reminder);
		};

		formatReminder(reminder)
		{
			return Duration.createFromMinutes(reminder).format();
		}
	}

	const mapStateToProps = (state) => ({
		reminder: state.reminder,
	});

	module.exports = { ReminderSelector: observeState(ReminderSelector, mapStateToProps) };
});
