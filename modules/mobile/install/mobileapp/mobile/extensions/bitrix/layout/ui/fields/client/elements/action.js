/**
 * @module layout/ui/fields/client/elements/action
 */
jn.define('layout/ui/fields/client/elements/action', (require, exports, module) => {
	const { AppTheme } = require('apptheme/extended');
	const { pen } = require('assets/common');

	/**
	 * @function ClientItemAction
	 */
	function ClientItemAction(props)
	{
		const { readOnly } = props;

		return View(
			{
				style: {
					width: readOnly ? 0 : 38,
					alignItems: 'flex-end',
				},
			},
			!readOnly && View(
				{
					style: {
						width: 28,
						height: 28,
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				Image({
					style: {
						height: 15,
						width: 15,
					},
					svg: {
						content: pen(),
					},
				}),
			),
		);
	}

	module.exports = { ClientItemAction };
});
