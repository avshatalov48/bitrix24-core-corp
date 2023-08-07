/**
 * @module layout/ui/product-grid/components/sku-selector/elements/sku-tree-container
 */
jn.define('layout/ui/product-grid/components/sku-selector/elements/sku-tree-container', (require, exports, module) => {
	function SkuTreeContainer(...children)
	{
		return View(
			{
				style: {
					borderRadius: 10,
					borderColor: '#D4DCE0',
					borderWidth: 2,
					padding: 16,
				},
			},
			...children,
		);
	}

	module.exports = { SkuTreeContainer };
});
