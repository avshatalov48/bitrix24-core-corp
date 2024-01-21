/**
 * @module crm/timeline/item/ui/header/vertical-separator
 */
jn.define('crm/timeline/item/ui/header/vertical-separator', (require, exports, module) => {
	const AppTheme = require('apptheme');

	function VerticalSeparator()
	{
		return View(
			{
				style: {
					width: 1,
					paddingTop: 10,
					paddingBottom: 10,
				},
			},
			View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgSeparatorPrimary,
						flex: 1,
					},
				},
			),
		);
	}

	module.exports = { VerticalSeparator };
});
