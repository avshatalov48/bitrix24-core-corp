/**
 * @module calendar/event-list-view/section-list/header
 */
jn.define('calendar/event-list-view/section-list/header', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');

	const SectionListHeader = (text) => View(
		{
			style: {
				paddingVertical: Indent.M.toNumber(),
			},
		},
		Text4({
			text,
			color: Color.base4,
			numberOfLines: 1,
			ellipsize: 'end',
		}),
	);

	module.exports = { SectionListHeader };
});
