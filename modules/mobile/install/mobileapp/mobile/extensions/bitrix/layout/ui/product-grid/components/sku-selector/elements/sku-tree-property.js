/**
 * @module layout/ui/product-grid/components/sku-selector/elements/sku-tree-property
 */
jn.define('layout/ui/product-grid/components/sku-selector/elements/sku-tree-property', (require, exports, module) => {
	const AppTheme = require('apptheme');
	function SkuTreeProperty(name, ...children)
	{
		return View(
			{
				style: {
					marginBottom: 12,
				},
			},
			View(
				{
					style: {
						marginBottom: 2,
					},
				},
				Text({
					text: name,
					style: {
						color: AppTheme.colors.base2,
						fontSize: 16,
					},
				}),
			),
			View(
				{
					style: {
						flexDirection: 'row',
						flexWrap: 'wrap',
					},
				},
				...children,
			),
		);
	}

	module.exports = { SkuTreeProperty };
});
