/**
 * @module layout/ui/product-grid/components/sku-selector/elements/scrollable
 */
jn.define('layout/ui/product-grid/components/sku-selector/elements/scrollable', (require, exports, module) => {
	const AppTheme = require('apptheme');
	function Scrollable(...children)
	{
		return ScrollView(
			{
				style: {
					backgroundColor: AppTheme.colors.bgPrimary,
					flexDirection: 'column',
					flexGrow: 1,
				},
			},
			View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
						borderRadius: 12,
						padding: 16,
						paddingBottom: 195,
						flexDirection: 'column',
						flexGrow: 1,
					},
				},
				...children,
			),
		);
	}

	module.exports = { Scrollable };
});
