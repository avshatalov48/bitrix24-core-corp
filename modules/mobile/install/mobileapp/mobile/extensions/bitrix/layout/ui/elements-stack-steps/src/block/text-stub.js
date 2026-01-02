/**
 * @module layout/ui/elements-stack-steps/block/text-stub
 */
jn.define('layout/ui/elements-stack-steps/block/text-stub', (require, exports, module) => {
	const { Color, Indent, Corner } = require('tokens');
	const { DEFAULT_TEXT_HEIGHT, ELEMENT_SIZE } = require('layout/ui/elements-stack-steps/constants');

	/**
	 * @function TextStub
	 * @param {Object} props
	 * @param {number} [props.wrapperHeight]
	 * @param {Indent} [props.wrapperVerticalMargin]
	 * @param {number} [props.height]
	 * @param {number} [props.width]
	 * @param {Color} [props.color]
	 * @param {Corner} [props.radius]
	 * @return View
	 */
	function TextStub(props = {})
	{
		const {
			wrapperHeight: headerHeight = DEFAULT_TEXT_HEIGHT,
			wrapperVerticalMargin: headerMargin = Indent.XS,
			color: backgroundColor = Color.bgContentTertiary,
			height = 4,
			width = ELEMENT_SIZE,
			radius = Corner.XS,
		} = props;

		return View(
			{
				style: {
					height: headerHeight,
					justifyContent: 'space-between',
					alignItems: 'center',
					marginVertical: headerMargin.toNumber(),
					width,
					flexDirection: 'row',
				},
			},
			View({
				style: {
					borderRadius: radius.toNumber(),
					backgroundColor: backgroundColor.toHex(),
					width: '20%',
					height,
				},
			}),
			View({
				style: {
					borderRadius: radius.toNumber(),
					backgroundColor: backgroundColor.toHex(),
					width: '70%',
					height,
				},
			}),
		);
	}

	module.exports = { TextStub };
});
