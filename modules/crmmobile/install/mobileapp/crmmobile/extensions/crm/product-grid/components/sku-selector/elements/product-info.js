/**
 * @module crm/product-grid/components/sku-selector/elements/product-info
 */
jn.define('crm/product-grid/components/sku-selector/elements/product-info', (require, exports, module) => {
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
					},
				}),
			),
		);
	}

	module.exports = { ProductInfo };
});
