/**
 * @module catalog/store/sku-selector
 */
jn.define('catalog/store/sku-selector', (require, exports, module) => {
	const { clone } = require('utils/object');
	const { SkuSelector } = require('layout/ui/product-grid/components/sku-selector');

	/**
	 * @class StoreSkuSelector
	 */
	class StoreSkuSelector extends SkuSelector
	{
		constructor(props)
		{
			super(props);
		}

		/**
		 * @returns {Promise}
		 */
		preloadSkuCollection()
		{
			return new Promise((resolve, reject) => {
				if (this.productVariations === null)
				{
					const variationId = this.props.selectedVariationId;
					const action = 'catalogmobile.StoreDocumentProduct.loadSkuCollection';
					const queryConfig = {
						data: { variationId }
					};

					// @todo cache this query on client
					BX.ajax.runAction(action, queryConfig)
						.then(response => {
							this.productVariations = response.data.variations;
							resolve(this.productVariations);
						})
						.catch(err => {
							void ErrorNotifier.showError(Loc.getMessage('PRODUCT_GRID_CONTROL_SKU_SELECTOR_LOADING_ERROR'));
							console.error(err);
							reject(err)
						});
				}
				else
				{
					resolve(this.productVariations);
				}
			});
		}

		save()
		{
			const skuTree = clone(this.skuTree);
			skuTree.SELECTED_VALUES = clone(this.selectedPropertyValues);
			if (this.props.onSave)
			{
				this.props.onSave({
					skuTree,
					variationId: this.state.selectedVariationId,
					variationData: this.selectedVariation,
				});
			}
			this.layout.close(() => {
				if (this.props.onWidgetClosed)
				{
					this.props.onWidgetClosed({
						skuTree,
						variationId: this.state.selectedVariationId,
						variationData: this.selectedVariation,
					});
				}
			});
		}
	}

	module.exports = { StoreSkuSelector };
});
