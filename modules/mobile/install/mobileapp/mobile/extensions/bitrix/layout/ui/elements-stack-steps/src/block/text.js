/**
 * @module layout/ui/elements-stack-steps/block/text
 */
jn.define('layout/ui/elements-stack-steps/block/text', (require, exports, module) => {
	const { Indent } = require('tokens');
	const { Text6 } = require('ui-system/typography/text');
	const { DEFAULT_TEXT_COLOR, DEFAULT_TEXT_HEIGHT } = require('layout/ui/elements-stack-steps/constants');

	/**
	 * @function Text
	 * @param {Object} props
	 * @param {string} [props.text]
	 * @param {Color} [props.textColor]
	 * @param {number} [props.wrapperHeight]
	 * @param {Indent} [props.wrapperVerticalMargin]
	 * @return View
	 */
	function Text(props)
	{
		const {
			wrapperHeight: height = DEFAULT_TEXT_HEIGHT,
			wrapperVerticalMargin: margin = Indent.XS,
			text = '',
			textColor = DEFAULT_TEXT_COLOR,
		} = props;

		return View(
			{
				style: {
					height,
					justifyContent: 'center',
					marginVertical: margin.toNumber(),
				},
			},
			Text6({
				text,
				style: { color: textColor.toHex() },
				numberOfLines: 1,
				ellipsize: 'end',
			}),
		);
	}

	module.exports = { Text };
});
