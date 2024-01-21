/**
 * @module layout/ui/product-grid/components/price-line/styles
 */
jn.define('layout/ui/product-grid/components/price-line/styles', (require, exports, module) => {
	const AppTheme = require('apptheme');

	const Styles = {
		wrapper: {
			flexDirection: 'row',
		},
		titleContainer: {
			width: '50%',
			flexDirection: 'row',
			justifyContent: 'flex-end',
			paddingRight: 4,
			alignItems: 'center',
		},
		valueContainer: {
			width: '50%',
			flexDirection: 'row',
			justifyContent: 'flex-end',
		},
		titleText: {
			fontSize: 16,
			color: AppTheme.colors.base3,
			textAlign: 'right',
		},
		amount: {
			fontSize: 18,
			color: AppTheme.colors.base1,
			fontWeight: 'bold',
		},
		currency: {
			fontSize: 18,
			color: AppTheme.colors.base3,
			fontWeight: 'bold',
		},
	};

	module.exports = { Styles };
});
