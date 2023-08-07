/**
 * @module crm/product-grid/components/sku-selector/elements
 */
jn.define('crm/product-grid/components/sku-selector/elements', (require, exports, module) => {
	const { BottomPanel } = require('crm/product-grid/components/sku-selector/elements/bottom-panel');
	const { Price } = require('crm/product-grid/components/sku-selector/elements/price');

	module.exports = {
		BottomPanel,
		Price,
	};
});
