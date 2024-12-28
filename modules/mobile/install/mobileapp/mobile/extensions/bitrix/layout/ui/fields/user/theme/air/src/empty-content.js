/**
 * @module layout/ui/fields/user/theme/air/src/empty-content
 */
jn.define('layout/ui/fields/user/theme/air/src/empty-content', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { IconView } = require('ui-system/blocks/icon');
	const IMAGE_SIZE = 32;
	const { Text4 } = require('ui-system/typography/text');

	/**
	 * @param {string} icon
	 * @param {string} text
	 * @param {string} testId
	 */
	const EmptyContent = ({
		icon,
		text,
		testId,
	}) => {
		return View(
			{
				testId,
				style: {
					flexDirection: 'row',
				},
			},
			IconView({
				testId: `${testId}_ICON`,
				icon,
				size: IMAGE_SIZE,
				color: Color.accentMainPrimaryalt,
			}),
			Text4({
				testId: `${testId}_TEXT`,
				text,
				style: {
					color: Color.base2.toHex(),
					marginLeft: Indent.M.toNumber(),
					flexShrink: 2,
				},
				numberOfLines: 1,
				ellipsize: 'end',
			}),
		);
	};

	module.exports = {
		EmptyContent,
	};
});
