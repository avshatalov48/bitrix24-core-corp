/**
 * @module layout/ui/product-grid/components/sku-selector/elements/product-info
 */
jn.define('layout/ui/product-grid/components/sku-selector/elements/product-info', (require, exports, module) => {
	const AppTheme = require('apptheme');

	function ProductInfo({ name, images })
	{
		return View(
			{
				style: {
					flexDirection: 'row',
					marginBottom: 16,
				},
			},
			View(
				{
					style: {
						width: 62,
						height: 62,
						marginRight: 11,
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				new ImageStack({
					images,
					style: {
						width: 62,
						height: 62,
					},
				}),
			),
			View(
				{
					style: {
						marginBottom: 16,
						marginTop: 4,
						flexShrink: 1,
					},
				},
				Text({
					text: name,
					style: {
						fontSize: 18,
						color: AppTheme.colors.base1,
					},
				}),
			),
		);
	}

	module.exports = { ProductInfo };
});
