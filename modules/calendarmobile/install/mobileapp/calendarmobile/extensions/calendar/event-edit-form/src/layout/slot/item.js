/**
 * @module calendar/event-edit-form/layout/slot/item
 */
jn.define('calendar/event-edit-form/layout/slot/item', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Component } = require('tokens');
	const { Card, CardDesign, BadgeStatusMode } = require('ui-system/layout/card');
	const { Text3 } = require('ui-system/typography/text');

	const { DateHelper } = require('calendar/date-helper');

	const slotItemHeight = 58;

	const SlotItem = ({ slot, selectedSlot, lastSlot, onSlotSelected, onLayout }) => {
		const selected = slot.id === selectedSlot?.id;
		const selectedProps = {
			badgeMode: BadgeStatusMode.SUCCESS,
			design: CardDesign.PRIMARY,
		};
		const selectedStyles = {
			borderWidth: 2,
			borderColor: Color.accentMainPrimary.toHex(),
		};
		const onClickHandler = () => onSlotSelected(selected ? null : slot);
		const onLayoutHandler = () => {
			if (slot.from === lastSlot.from)
			{
				onLayout();
			}
		};

		return View(
			{
				style: {
					height: slotItemHeight,
					justifyContent: 'center',
				},
			},
			Card(
				{
					testId: `calendar-event-edit-form-slot-${slot.id}-card`,
					design: CardDesign.ACCENT,
					...(selected ? selectedProps : {}),
					style: {
						alignItems: 'center',
						marginBottom: Component.cardListGap.toNumber(),
						...(selected ? selectedStyles : {}),
					},
					onClick: onClickHandler,
				},
				Text3({
					testId: `calendar-event-edit-form-slot-${slot.id}-text`,
					text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_FROM_TO', {
						'#FROM#': DateHelper.formatTime(new Date(slot.from)).toLocaleUpperCase(env.languageId),
						'#TO#': DateHelper.formatTime(new Date(slot.to)).toLocaleUpperCase(env.languageId),
					}),
					onLayout: onLayoutHandler,
				}),
			),
		);
	};

	module.exports = { SlotItem, slotItemHeight };
});
