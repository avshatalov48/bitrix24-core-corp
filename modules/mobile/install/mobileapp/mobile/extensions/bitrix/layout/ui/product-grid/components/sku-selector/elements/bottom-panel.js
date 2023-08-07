/**
 * @module layout/ui/product-grid/components/sku-selector/elements/bottom-panel
 */
jn.define('layout/ui/product-grid/components/sku-selector/elements/bottom-panel', (require, exports, module) => {

	function BottomPanel(props)
	{
		return new UI.BottomToolbar({
			style: {
				paddingTop: 8,
				paddingLeft: 20,
				paddingRight: 20,
				paddingBottom: 16,
			},
			renderContent: () => View(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
					}
				},
				View(
					{
						style: {
							paddingLeft: 40,
							paddingRight: 40,
						}
					},
					new PrimaryButton({
						text: props.saveButtonCaption,
						rounded: true,
						style: {},
						onClick: () => props.onSave(),
					})
				),
			),
		});
	}

	module.exports = { BottomPanel };

});
