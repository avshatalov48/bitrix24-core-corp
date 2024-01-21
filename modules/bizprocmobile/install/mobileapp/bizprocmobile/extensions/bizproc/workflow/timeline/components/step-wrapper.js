/**
 * @module bizproc/workflow/timeline/components/step-wrapper
 * */

jn.define('bizproc/workflow/timeline/components/step-wrapper', (require, exports, module) => {
	const AppTheme = require('apptheme');

	/**
	 *
	 * @param {?{
	 * 	   showBorders: ?boolean,
	 *     borderOptions: ?{
	 *         style: ?string,
	 *         width: ?number,
	 *         color: ?string,
	 *         radius: ?number,
	 *     },
	 *     backgroundColor: ?string,
	 * }}
	 * @param {View[]} children
	 * @return {object}
	 */
	function StepWrapper({
		showBorders = false,
		borderOptions = {},
		backgroundColor,
	} = {}, ...children)
	{
		return View(
			{
				style: {
					position: 'relative',
				},
			},
			showBorders && View({
				style: {
					position: 'absolute',
					borderStyle: borderOptions.style || 'solid',
					borderWidth: borderOptions.width || 1,
					borderColor: borderOptions.color || AppTheme.colors.bgSeparatorPrimary,
					borderRadius: borderOptions.radius || 18,
					backgroundColor,
					left: 0,
					top: 0,
					right: 0,
					bottom: 8,
				},
			}),
			View(
				{
					style: {
						alignItems: 'stretch',
						flexDirection: 'row',
						position: 'relative',
						paddingBottom: 8,
					},
				},
				...children,
			),
		);
	}

	module.exports = {
		StepWrapper,
	};
});
