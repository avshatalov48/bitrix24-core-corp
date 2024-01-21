/**
 * @module crm/salescenter/products-step/product-model-loader
 */
jn.define('crm/salescenter/products-step/product-model-loader', (require, exports, module) => {
	const { ProductModelLoader } = require('crm/product-grid/services/product-model-loader');

	/**
	 * @class SalescenterProductModelLoader
	 */
	class SalescenterProductModelLoader extends ProductModelLoader
	{
		getLoadEndpoint()
		{
			return 'crmmobile.Salescenter.ProductGrid.loadProductModel';
		}
	}

	module.exports = { SalescenterProductModelLoader };
});
