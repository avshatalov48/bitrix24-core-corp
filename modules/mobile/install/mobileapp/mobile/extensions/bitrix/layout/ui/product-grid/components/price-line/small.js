jn.define('layout/ui/product-grid/components/price-line/small', (require, exports, module) => {

	const { merge } = require('utils/object');
	const { PriceLine } = require('layout/ui/product-grid/components/price-line/default');
	const { Styles } = require('layout/ui/product-grid/components/price-line/styles');

	class SmallPriceLine extends PriceLine
	{
		styles()
		{
			return merge({}, Styles, {
				titleText: {
					fontSize: 14,
					color: '#bdc1c6',
				},
				amount: {
					fontSize: 14,
					color: '#A8ADB4',
				},
				currency: {
					fontSize: 14,
					color: '#bdc1c6',
				},
			});
		}
	}

	module.exports = { SmallPriceLine };

});