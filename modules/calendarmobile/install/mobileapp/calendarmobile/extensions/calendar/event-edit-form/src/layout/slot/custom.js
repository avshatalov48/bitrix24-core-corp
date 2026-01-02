/**
 * @module calendar/event-edit-form/layout/slot/custom
 */
jn.define('calendar/event-edit-form/layout/slot/custom', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { Text3 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	/**
	 * @class SlotCustom
	 */
	const SlotCustom = ({ selected, onSlotSelected, onEditClick, text }) => {
		const selectedStyles = {
			borderWidth: 2,
			borderColor: Color.accentMainPrimary.toHex(),
		};

		return View(
			{
				style: {
					justifyContent: 'center',
				},
			},
			Card(
				{
					testId: 'calendar-event-edit-form-slot-custom',
					design: CardDesign.PRIMARY,
					style: {
						alignItems: 'center',
						marginBottom: Indent.XS.toNumber(),
						borderWidth: 1,
						borderColor: Color.bgSeparatorPrimary.toHex(),
						...(selected ? selectedStyles : {}),
					},
					onClick: onSlotSelected,
				},
				Text3({
					text,
					color: Color.base1,
					testId: 'calendar-event-edit-form-slot-custom-text',
				}),
				View(
					{
						style: {
							position: 'absolute',
							top: 10,
							right: 10,
						},
						onClick: onEditClick,
					},
					IconView({
						icon: Icon.EDIT,
						size: 24,
						color: Color.base4,
					}),
				),
			),
		);
	};

	module.exports = { SlotCustom };
});
