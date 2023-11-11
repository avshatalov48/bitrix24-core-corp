/**
 * @module catalog/product-type
 */
jn.define('catalog/product-type', (require, exports, module) => {
	/*
	 * @class ProductType
	 */
	const ProductType = {
		Product: 1,
		Set: 2,
		Sku: 3,
		Offer: 4,
		FreeOffer: 5,
		EmptySku: 6,
		Service: 7,
	};

	module.exports = { ProductType };
});
