/**
 * @module crm/product-grid/components/sku-selector/elements
 */
jn.define('crm/product-grid/components/sku-selector/elements', (require, exports, module) => {
	const { BottomPanel } = require('crm/product-grid/components/sku-selector/elements/bottom-panel');
	const { Price } = require('crm/product-grid/components/sku-selector/elements/price');
	const { ProductInfo } = require('crm/product-grid/components/sku-selector/elements/product-info');
	const { Scrollable } = require('crm/product-grid/components/sku-selector/elements/scrollable');
	const { SkuTreeContainer } = require('crm/product-grid/components/sku-selector/elements/sku-tree-container');
	const { SkuTreeProperty } = require('crm/product-grid/components/sku-selector/elements/sku-tree-property');
	const { SkuTreePropertyValue } = require('crm/product-grid/components/sku-selector/elements/sku-tree-property-value');

	module.exports = {
		BottomPanel,
		Price,
		ProductInfo,
		Scrollable,
		SkuTreeContainer,
		SkuTreeProperty,
		SkuTreePropertyValue,
	};
});
