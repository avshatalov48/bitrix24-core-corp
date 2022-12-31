/**
 * @module layout/ui/product-grid/components/string-field
 */
jn.define('layout/ui/product-grid/components/string-field', (require, exports, module) => {

	const { ProductGridStringField } = require('layout/ui/product-grid/components/string-field/string');
	const { ProductGridMoneyField } = require('layout/ui/product-grid/components/string-field/money');
	const { ProductGridNumberField } = require('layout/ui/product-grid/components/string-field/number');

	module.exports = {
		ProductGridStringField,
		ProductGridMoneyField,
		ProductGridNumberField,
	};

});