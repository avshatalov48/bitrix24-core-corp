/**
 * @module layout/ui/fields/theme/air/elements/title
 */
jn.define('layout/ui/fields/theme/air/elements/title', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Text5 } = require('ui-system/typography/text');

	/**
	 * @param {string} text
	 * @param {number} count
	 * @param {string} testId
	 * @param {string} [textMultiple='']
	 */
	const Title = ({ text, count, testId, textMultiple }) => Text5({
		testId: `${testId}_TITLE`,
		style: {
			color: Color.base4.toHex(),
		},
		numberOfLines: 1,
		ellipsize: 'end',
		text: (
			textMultiple && count > 0
				? textMultiple.replace('#COUNT#', String(count))
				: String(text)
		),
	});

	module.exports = {
		Title,
	};
});
