/**
 * @module layout/ui/product-grid/components/sku-selector/elements/sku-tree-container
 */
jn.define('layout/ui/product-grid/components/sku-selector/elements/sku-tree-container', (require, exports, module) => {
	const AppTheme = require('apptheme');
	function SkuTreeContainer(...children)
	{
		return View(
			{
				style: {
					borderRadius: 10,
					borderColor: AppTheme.colors.bgSeparatorPrimary,
					borderWidth: 2,
					padding: 16,
				},
			},
			...children,
		);
	}

	module.exports = { SkuTreeContainer };
});
