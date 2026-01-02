/**
 * @module layout/ui/elements-stack-steps/block/duration
 */
jn.define('layout/ui/elements-stack-steps/block/duration', (require, exports, module) => {
	const { Duration: DurationClass } = require('utils/date/duration');
	const { Text } = require('layout/ui/elements-stack-steps/block/text');

	/**
	 * @function Duration
	 * @param {Object} props
	 * @param {number} props.duration
	 * @param {string} [props.format]
	 * @param {Color} [props.textColor]
	 * @param {number} [props.wrapperHeight]
	 * @param {Indent} [props.wrapperVerticalMargin]
	 * @return View
	 */
	function Duration(props = {})
	{
		const {
			duration = 0,
			format = '',
			textColor,
			wrapperHeight,
			wrapperVerticalMargin,
		} = props;

		return Text({
			text: DurationClass.createFromSeconds(duration).format(format),
			textColor,
			wrapperHeight,
			wrapperVerticalMargin,
		});
	}

	module.exports = { Duration };
});
