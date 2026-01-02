/**
 * @module calendar/event-edit-form/layout/slot/list-empty
 */
jn.define('calendar/event-edit-form/layout/slot/list-empty', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { StatusBlock } = require('ui-system/blocks/status-block');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');
	const { Icons } = require('calendar/layout/icons');

	const showDescriptionDeviceHeight = Application.getPlatform() === 'android' ? 800 : 700;

	const SlotListEmpty = ({ showButton = true, onButtonClick = null }) => View(
		{
			style: {
				opacity: 0,
			},
			ref: (ref) => ref?.animate({ duration: 200, opacity: 1 }),
		},
		StatusBlock({
			testId: 'calendar-event-edit-form-empty-day',
			image: Image({
				svg: {
					content: AppTheme.id === 'dark' ? Icons.eventEditSlotEmptyDark : Icons.eventEditSlotEmptyLight,
				},
				style: {
					width: device.screen.width * 0.25,
					height: device.screen.width * 0.28,
				},
			}),
			title: showButton
				? Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_EMPTY_STATE_TITLE_V2')
				: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_EMPTY_STATE_TITLE'),
			description: (showButton && device.screen.height >= showDescriptionDeviceHeight)
				? Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_EMPTY_STATE_DESCRIPTION_V2')
				: '',
			buttons: showButton ? [
				Button({
					testId: 'calendar-event-edit-form-empty-day-button',
					size: ButtonSize.M,
					text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_EMPTY_STATE_BUTTON'),
					design: ButtonDesign.OUTLINE_ACCENT_2,
					onClick: onButtonClick,
				}),
			] : [],
		}),
	);

	module.exports = { SlotListEmpty };
});
