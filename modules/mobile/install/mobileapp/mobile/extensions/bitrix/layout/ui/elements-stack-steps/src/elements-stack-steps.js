/**
 * @module layout/ui/elements-stack-steps/elements-stack-steps
 */
jn.define('layout/ui/elements-stack-steps/elements-stack-steps', (require, exports, module) => {
	const { merge } = require('utils/object');

	/**
	 * @function ElementsStackSteps
	 * @param {Object} props
	 * @param {string} [props.testId]
	 * @param {Object} [props.style]
	 * @param {Array<View>} children
	 * @return View
	 */
	function ElementsStackSteps(props = {}, ...children)
	{
		const steps = Array.isArray(children) ? children : [];
		if (steps.length === 0)
		{
			return null;
		}

		const {
			style = {},
			testId = 'elements-stack-steps',
		} = props;

		const defaultStyle = {
			flexDirection: 'row',
			alignItems: 'flex-start',
			justifyContent: 'space-between',
			width: '100%',
		};

		return View(
			{
				testId,
				style: merge(defaultStyle, style),
			},
			...steps,
		);
	}

	module.exports = { ElementsStackSteps };
});
