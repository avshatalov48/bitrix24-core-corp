/**
 * @module layout/ui/fields/theme/air/elements/title
 */
jn.define('layout/ui/fields/theme/air/elements/title', (require, exports, module) => {
	const { Color, Indent } = require('tokens');

	/**
	 * @param {string} text
	 * @param {string} testId
	 */
	const Title = ({ text, testId }) => {
		return Text({
			style: {
				testId: `${testId}_NAME`,
				fontSize: 12,
				color: Color.base4,
				paddingBottom: Indent.XS,
				marginBottom: Indent.M,
			},
			numberOfLines: 1,
			ellipsize: 'end',
			text,
		});
	};

	module.exports = {
		Title,
	};
});
