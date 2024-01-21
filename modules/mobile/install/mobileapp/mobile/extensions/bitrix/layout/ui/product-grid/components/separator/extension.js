/**
 * @module layout/ui/product-grid/components/separator
 */
jn.define('layout/ui/product-grid/components/separator', (require, exports, module) => {
	const AppTheme = require('apptheme');

	class Separator extends LayoutComponent
	{
		render()
		{
			return View({
				style: {
					height: 1,
					width: '100%',
					backgroundColor: AppTheme.colors.bgContentPrimary,
					marginTop: 4,
					marginBottom: 6,
				},
			});
		}
	}

	module.exports = { Separator };

});
