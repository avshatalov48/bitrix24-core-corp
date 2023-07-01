/**
 * @module crm/product-grid/components/sku-selector/elements/sku-tree-container
 */
jn.define('crm/product-grid/components/sku-selector/elements/sku-tree-container', (require, exports, module) => {
	function SkuTreeContainer(...children)
	{
		return View(
			{
				style: {
					borderRadius: 10,
					borderColor: '#d5d7db',
					borderWidth: 2,
					padding: 16,
				},
			},
			...children,
		);
	}

	module.exports = { SkuTreeContainer };
});
