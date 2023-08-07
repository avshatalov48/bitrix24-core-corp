/**
 * @module layout/ui/product-grid/components/sku-selector/elements/scrollable
 */
jn.define('layout/ui/product-grid/components/sku-selector/elements/scrollable', (require, exports, module) => {
	function Scrollable(...children)
	{
		return ScrollView(
			{
				style: {
					backgroundColor: '#EEF2F4',
					flexDirection: 'column',
					flexGrow: 1,
				},
			},
			View(
				{
					style: {
						backgroundColor: '#ffffff',
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
