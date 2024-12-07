/**
 * @module layout/ui/fields/user/theme/air/src/counter
 */
jn.define('layout/ui/fields/user/theme/air/src/counter', (require, exports, module) => {
	const { Text4 } = require('ui-system/typography/text');
	const { Color } = require('tokens');

	/**
	 * @param {number} count
	 * @param {number} size
	 * @param {function} onClick
	 */
	const Counter = ({ count, size, onClick }) => View(
		{
			style: {
				width: size,
				height: size,
				borderColor: Color.bgSeparatorPrimary.toHex(),
				borderWidth: 1,
				backgroundColor: Color.bgContentTertiary.toHex(),
				alignItems: 'center',
				justifyContent: 'center',
			},
			onClick,
		},
		View(
			{
				style: {
					flexShrink: 2,
				},
			},
			Text4({
				text: `+${count}`,
				style: {
					color: Color.base4.toHex(),
					flexShrink: 2,
				},
				ellipsize: 'end',
				numberOfLines: 1,
			}),
		),
	);

	module.exports = {
		Counter,
	};
});
