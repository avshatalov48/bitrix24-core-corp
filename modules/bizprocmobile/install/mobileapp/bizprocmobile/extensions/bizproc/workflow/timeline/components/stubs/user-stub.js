/**
 * @module bizproc/workflow/timeline/components/stubs/user-stub
 * */

jn.define('bizproc/workflow/timeline/components/stubs/user-stub', (require, exports, module) => {
	const AppTheme = require('apptheme');

	/**
	 * @param {string} color
	 * @returns {View}
	 */
	function UserStub({ color = AppTheme.colors.base7 } = {})
	{
		return View(
			{
				style: {
					marginTop: 8,
					flexDirection: 'row',
				},
			},
			// user avatar stub
			View(
				{
					style: {
						marginRight: 6,
					},
				},
				View({
					style: {
						width: 24,
						height: 24,
						backgroundColor: color,
						borderRadius: 100,
					},
				}),
			),
			View(
				{
					style: {
						justifyContent: 'space-between',
						flexDirection: 'column',
						maxWidth: 147,
					},
				},
				View({
					style: {
						width: 65,
						height: 10,
						borderRadius: 3,
						backgroundColor: color,
					},
				}),
				View({
					style: {
						width: 147,
						height: 10,
						borderRadius: 3,
						backgroundColor: color,
					},
				}),
			),
		);
	}

	module.exports = {
		UserStub,
	};
});
