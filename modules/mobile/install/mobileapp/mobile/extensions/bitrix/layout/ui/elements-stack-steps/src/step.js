/**
 * @module layout/ui/elements-stack-steps/step
 */
jn.define('layout/ui/elements-stack-steps/step', (require, exports, module) => {
	const { merge } = require('utils/object');

	/**
	 * @function Step
	 * @param {Object} props
	 * @param {string} [props.testId]
	 * @param {Object} [props.style]
	 * @param {Array<View>} children
	 * @return View
	 */
	function Step(props = {}, ...children)
	{
		const content = Array.isArray(children) ? children : [];

		const defaultStyle = {
			flexDirection: 'column',
			alignItems: 'center',
			flexShrink: 1,
			width: '100%',
		};

		const {
			testId = 'elements-stack-steps-step',
			style = {},
		} = props;

		return View(
			{
				testId,
				style: merge(defaultStyle, style),
			},
			...content,
		);
	}

	module.exports = { Step };
});
