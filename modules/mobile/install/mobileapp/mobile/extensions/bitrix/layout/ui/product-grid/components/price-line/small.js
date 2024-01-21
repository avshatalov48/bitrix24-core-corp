/**
 * @module layout/ui/product-grid/components/price-line/small
 */
jn.define('layout/ui/product-grid/components/price-line/small', (require, exports, module) => {
	const { merge } = require('utils/object');
	const { PriceLine } = require('layout/ui/product-grid/components/price-line/default');
	const { Styles } = require('layout/ui/product-grid/components/price-line/styles');
	const AppTheme = require('apptheme');

	class SmallPriceLine extends PriceLine
	{
		styles()
		{
			return merge({}, Styles, {
				titleText: {
					fontSize: 14,
					color: AppTheme.colors.base5,
				},
				amount: {
					fontSize: 14,
					color: AppTheme.colors.base4,
				},
				currency: {
					fontSize: 14,
					color: AppTheme.colors.base5,
				},
			});
		}
	}

	module.exports = { SmallPriceLine };
});
