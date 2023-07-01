/**
 * @module crm/receive-payment/steps/products/product-model-loader
 */
jn.define('crm/receive-payment/steps/products/product-model-loader', (require, exports, module) => {
	const { ProductModelLoader } = require('crm/product-grid/services/product-model-loader');

	/**
	 * @class ReceivePaymentProductModelLoader
	 */
	class ReceivePaymentProductModelLoader extends ProductModelLoader
	{
		getLoadEndpoint()
		{
			return 'crmmobile.ReceivePayment.ProductStep.loadProductModel';
		}
	}

	module.exports = { ReceivePaymentProductModelLoader };
});
