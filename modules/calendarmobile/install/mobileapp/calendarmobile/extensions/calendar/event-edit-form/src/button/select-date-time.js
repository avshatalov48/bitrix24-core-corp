/**
 * @module calendar/event-edit-form/button/select-date-time
 */
jn.define('calendar/event-edit-form/button/select-date-time', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');
	const { Icon } = require('assets/icons');
	const { Type } = require('type');

	const { DateTimePage } = require('calendar/event-edit-form/pages/date-time-page');
	const { State } = require('calendar/event-edit-form/state');

	/**
	 * @class SelectDateTimeButton
	 */
	class SelectDateTimeButton extends LayoutComponent
	{
		get layout()
		{
			return this.props.layout;
		}

		render()
		{
			return Button({
				testId: 'calendar-event-edit-form-select-date-time-button',
				text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_SELECT_DATE_TIME'),
				size: ButtonSize.L,
				stretched: true,
				onClick: this.onClickHandler,
				design: this.isNameFilled()
					? ButtonDesign.FILLED
					: ButtonDesign.TINTED
				,
			});
		}

		isNameFilled()
		{
			return Type.isStringFilled(State.eventNameValue) && State.eventNameValue.trim();
		}

		onClickHandler = () => {
			const text = this.isNameFilled()
				? State.eventNameValue
				: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_DEFAULT_EVENT_NAME')
			;

			void this.layout.openWidget('layout', {
				titleParams: {
					text,
					type: 'wizard',
				},
				onReady: (layout) => {
					layout.setRightButtons([
						{
							type: Icon.CROSS.getIconName(),
							callback: () => layout.close(),
						},
					]);

					const component = new DateTimePage({
						parentLayout: this.layout,
						layout,
					});

					layout.showComponent(component);
				},
			});
		};
	}

	module.exports = { SelectDateTimeButton };
});
