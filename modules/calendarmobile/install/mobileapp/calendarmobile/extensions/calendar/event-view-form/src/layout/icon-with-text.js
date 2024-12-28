/**
 * @module calendar/event-view-form/layout/icon-with-text
 */
jn.define('calendar/event-view-form/layout/icon-with-text', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	/**
	 * @param {Icon} icon
	 * @param {string} text
	 * @param {string} testId
	 * @param {boolean} isPrimary
	 * @return {BaseMethods}
	 */
	const IconWithText = (icon, text, testId, isPrimary = true) => View(
		{
			testId: `${testId}_FIELD`,
			style: {
				flexDirection: 'row',
				alignItems: 'center',
				marginTop: isPrimary ? Indent.S.toNumber() : Indent.L.toNumber(),
			},
		},
		IconView({
			color: Color.accentMainPrimaryalt,
			icon,
			size: 32,
			style: {
				marginRight: Indent.M.toNumber(),
			},
		}),
		Text4({
			testId: `${testId}_CONTENT`,
			text,
			color: Color.base2,
			style: {
				flex: 1,
			},
		}),
	);

	module.exports = { IconWithText, Icon };
});
