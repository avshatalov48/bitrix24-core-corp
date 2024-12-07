/**
 * @module layout/ui/fields/theme/air/elements/counter
 */
jn.define('layout/ui/fields/theme/air/elements/counter', (require, exports, module) => {
	const { Color, Corner } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');

	/**
	 * @param {number} count
	 * @param {string} testId
	 * @param {boolean} [isLoading=false]
	 * @param {boolean} [hasErrors=false]
	 */
	const Counter = ({ count, testId, isLoading = false, hasErrors = false }) => View(
		{
			style: {
				borderColor: hasErrors ? Color.accentSoftRed1.toHex() : Color.bgSeparatorPrimary.toHex(),
				borderWidth: 1,
				width: 34,
				height: 34,
				borderRadius: Corner.XL.toNumber(),
				justifyContent: 'center',
				alignItems: 'center',
			},
		},
		isLoading && Loader({
			style: {
				width: 18,
				height: 18,
			},
			tintColor: Color.base6.toHex(),
			animating: true,
			size: 'small',
		}),
		!isLoading && Text4({
			testId: `${testId}_REST_COUNT`,
			style: {
				color: hasErrors ? Color.accentMainAlert.toHex() : Color.base3.toHex(),
			},
			text: `+${count}`,
		}),
	);

	module.exports = { Counter };
});
