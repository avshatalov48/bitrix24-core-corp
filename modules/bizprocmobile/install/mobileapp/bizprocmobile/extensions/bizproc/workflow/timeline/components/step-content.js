/**
 * @module bizproc/workflow/timeline/components/step-content
 * */

jn.define('bizproc/workflow/timeline/components/step-content', (require, exports, module) => {
	const { merge } = require('utils/object');

	/**
	 * @param {?Partial<ViewProps>} props
	 * @param {View[]} children
	 * @return {View}
	 */
	function StepContent(props = {}, ...children)
	{
		const defaultProps = {
			style: {
				flexDirection: 'column',
				flex: 1,
				flexGrow: 1,
				flexShrink: '0%',
				overflow: 'hidden',
				marginTop: 12,
				marginRight: 12,
				marginBottom: 12,
			},
		};

		return View(
			merge(defaultProps, props),
			...children,
		);
	}

	module.exports = {
		StepContent,
	};
});
