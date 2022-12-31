jn.define('layout/ui/product-grid/components/price-line', (require, exports, module) => {

	const { PriceLine } = require('layout/ui/product-grid/components/price-line/default');
	const { SmallPriceLine } = require('layout/ui/product-grid/components/price-line/small');

	module.exports = { PriceLine, SmallPriceLine };

});