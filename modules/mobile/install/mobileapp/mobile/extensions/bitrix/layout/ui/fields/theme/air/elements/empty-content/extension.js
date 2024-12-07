/**
 * @module layout/ui/fields/theme/air/elements/empty-content
 */
jn.define('layout/ui/fields/theme/air/elements/empty-content', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');
	const { IconView } = require('ui-system/blocks/icon');

	/**
	 * @param {string} testId
	 * @param {string} text
	 * @param {string} icon
	 */
	const EmptyContent = ({ testId, text, icon }) => View(
		{
			testId: `${testId}_EMPTY_VIEW_CONTENT`,
			style: {
				flexDirection: 'row',
				flexShrink: 2,
				paddingVertical: 6,
			},
		},
		IconView({
			testId: `${testId}_EMPTY_VIEW_ICON`,
			icon,
			iconSize: {
				width: 24,
				height: 24,
			},
			color: Color.accentMainPrimaryalt,
		}),
		Text4({
			testId: `${testId}_EMPTY_VIEW_TEXT`,
			style: {
				color: Color.base2.toHex(),
				marginLeft: Indent.L.toNumber(),
				flexShrink: 2,
			},
			text,
			numberOfLines: 1,
			ellipsize: 'end',
		}),
	);

	module.exports = { EmptyContent };
});
