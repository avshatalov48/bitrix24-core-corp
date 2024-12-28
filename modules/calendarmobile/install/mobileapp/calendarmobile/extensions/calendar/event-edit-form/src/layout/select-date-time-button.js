/**
 * @module calendar/event-edit-form/layout/select-date-time-button
 */
jn.define('calendar/event-edit-form/layout/select-date-time-button', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { Button, ButtonSize } = require('ui-system/form/buttons/button');
	const { Icon } = require('assets/icons');
	const { Type } = require('type');

	const { DateTimePage } = require('calendar/event-edit-form/pages/date-time-page');
	const { observeState } = require('calendar/event-edit-form/state');

	/**
	 * @class SelectDateTimeButton
	 */
	class SelectDateTimeButton extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: {
						display: this.props.keyboardShown ? 'none' : 'flex',
					},
				},
				Button({
					testId: 'calendar-event-edit-form-select-date-time-button',
					text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_SELECT_DATE_TIME'),
					backgroundColor: Color.accentMainPrimary,
					size: ButtonSize.L,
					stretched: true,
					onClick: this.onClickHandler,
				}),
			);
		}

		onClickHandler = () => {
			const text = Type.isString(this.props.eventName) && this.props.eventName.trim()
				? this.props.eventName
				: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_DEFAULT_EVENT_NAME')
			;

			void this.props.layout.openWidget('layout', {
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
						parentLayout: this.props.layout,
						layout,
					});

					layout.showComponent(component);
				},
			});
		};
	}

	const mapStateToProps = (state) => ({
		keyboardShown: state.keyboardShown,
		eventName: state.eventName,
	});

	module.exports = { SelectDateTimeButton: observeState(SelectDateTimeButton, mapStateToProps) };
});
