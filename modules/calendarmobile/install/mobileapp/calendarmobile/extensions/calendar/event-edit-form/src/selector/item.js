/**
 * @module calendar/event-edit-form/selector/item
 */
jn.define('calendar/event-edit-form/selector/item', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Indent, Color, Corner } = require('tokens');
	const { Text2 } = require('ui-system/typography/text');
	const { IconView } = require('ui-system/blocks/icon');
	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');

	/**
	 * @param item {SectionModel | LocationModel}
	 * @param icon {Icon}
	 * @param isSelected {Boolean}
	 * @param isReserved {Boolean}
	 * @returns {BaseMethods}
	 * @constructor
	 */
	const SelectorItem = ({ item, icon, isSelected, isReserved }) => View(
		{
			style: {
				flexDirection: 'row',
				alignItems: 'center',
			},
		},
		View(
			{
				style: {
					justifyContent: 'center',
					alignItems: 'center',
					marginVertical: Indent.XL.toNumber(),
					width: 40,
					height: 40,
					borderRadius: Corner.S.toNumber(),
					backgroundColor: item.color,
				},
			},
			IconView({
				icon,
				size: 32,
				color: Color.baseWhiteFixed,
			}),
		),
		View(
			{
				style: {
					marginLeft: Indent.XL.toNumber(),
					height: 70,
					flex: 1,
					flexDirection: 'row',
					alignItems: 'center',
					borderBottomWidth: 1,
					borderBottomColor: Color.bgSeparatorSecondary.toHex(),
				},
			},
			View(
				{
					style: {
						marginRight: Indent.XL.toNumber(),
						flex: 1,
					},
				},
				Text2({
					text: item.name,
					ellipsize: 'end',
					numberOfLines: 1,
					color: isSelected ? Color.accentMainPrimary : Color.base0,
					style: {
						fontWeight: isSelected ? '500' : '400',
					},
				}),
			),
			isReserved && BadgeCounter({
				testId: `calendar-event-edit-form-selector-item-${item.id}-badge-reserved`,
				value: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_RESERVED'),
				showRawValue: true,
				design: BadgeCounterDesign.ALERT,
			}),
			!isReserved && item.capacity && BadgeCounter({
				testId: `calendar-event-edit-form-selector-item-${item.id}-badge-capacity`,
				value: Loc.getMessagePlural('M_CALENDAR_EVENT_EDIT_FORM_CAPACITY', item.capacity, {
					'#NUM#': item.capacity,
				}),
				showRawValue: true,
				design: BadgeCounterDesign.LIGHT_GREY,
			}),
		),
	);

	module.exports = { SelectorItem };
});
