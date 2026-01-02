/**
 * @module layout/ui/elements-stack-steps/stick
 */
jn.define('layout/ui/elements-stack-steps/stick', (require, exports, module) => {
	const { Color } = require('tokens');
	const { merge } = require('utils/object');

	/**
	 * @function Stick
	 * @param {Object} props
	 * @param {Color} [props.color]
	 * @param {number | string} [props.leftOffset]
	 * @param {number | string } [props.rightOffset]
	 * @param {Object} [props.style]
	 * @return View
	 */
	function Stick(props = {})
	{
		const {
			color = Color.base7,
			leftOffset: left = 30,
			rightOffset: right = 30,
			style = {},
		} = props;

		const defaultStyle = {
			height: 1,
			position: 'absolute',
			left,
			right,
			top: '50%',
			backgroundColor: color.toHex(),
		};

		return View({
			style: merge(defaultStyle, style),
		});
	}

	module.exports = { Stick };
});
