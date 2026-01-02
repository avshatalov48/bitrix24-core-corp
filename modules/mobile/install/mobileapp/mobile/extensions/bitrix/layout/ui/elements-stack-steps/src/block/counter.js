/**
 * @module layout/ui/elements-stack-steps/block/counter
 */
jn.define('layout/ui/elements-stack-steps/block/counter', (require, exports, module) => {
	const { Color, Indent, Component } = require('tokens');
	const { Text6 } = require('ui-system/typography/text');
	const { ELEMENT_SIZE, ELEMENT_BORDER, DEFAULT_TEXT_COLOR } = require('layout/ui/elements-stack-steps/constants');

	/**
	 * @function Counter
	 * @param {Object} props
	 * @param {string} props.text
	 * @param {number} [props.size]
	 * @param {Color} [props.backgroundColor]
	 * @param {Color} [props.borderColor]
	 * @param {Color} [props.textColor]
	 * @return View
	 */
	function Counter(props = {})
	{
		const {
			size = ELEMENT_SIZE,
			text = '',
			borderColor = Color.bgContentPrimary,
			backgroundColor = Color.base7,
			textColor = DEFAULT_TEXT_COLOR,
		} = props;

		const outline = ELEMENT_BORDER.toNumber();

		return View(
			{
				style: {
					backgroundColor: borderColor.toHex(),
					width: size + outline,
					height: size + outline,
					alignItems: 'center',
					justifyContent: 'center',
					borderRadius: Component.elementAccentCorner.toNumber(),
				},
			},
			View(
				{
					style: {
						width: size,
						height: size,
						alignItems: 'center',
						justifyContent: 'center',
						paddingHorizontal: Indent.XS2.toNumber(),
						borderRadius: Component.elementAccentCorner.toNumber(),
						backgroundColor: backgroundColor.toHex(),
					},
				},
				Text6({
					text,
					style: { color: textColor.toHex() },
					numberOfLines: 1,
					ellipsize: 'end',
				}),
			),
		);
	}

	module.exports = { Counter };
});
