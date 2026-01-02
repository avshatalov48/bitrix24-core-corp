/**
 * @module calendar/event-list-view/section-list/item
 */
jn.define('calendar/event-list-view/section-list/item', (require, exports, module) => {
	const { Color, Indent, Corner } = require('tokens');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Text2 } = require('ui-system/typography/text');
	const { Checkbox } = require('ui-system/form/checkbox');

	const SectionListItem = (section, checked) => View(
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
					backgroundColor: section.color,
				},
			},
			IconView({
				icon: Icon.CALENDAR,
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
					text: section.name,
					ellipsize: 'end',
					numberOfLines: 1,
					color: Color.base1,
				}),
			),
			new Checkbox({
				checked,
				size: 24,
				useState: false,
				testId: `calendar-section-list-item-${section.id}-checkbox`,
			}),
		),
	);

	module.exports = { SectionListItem };
});
