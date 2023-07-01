/**
 * @module crm/product-grid/components/sku-selector/elements/sku-tree-property
 */
jn.define('crm/product-grid/components/sku-selector/elements/sku-tree-property', (require, exports, module) => {
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
						color: '#525C69',
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
