jn.define('layout/ui/product-grid/components/price-line/styles', (require, exports, module) => {

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
			color: '#828B95',
			textAlign: 'right',
		},
		amount: {
			fontSize: 18,
			color: '#333333',
			fontWeight: 'bold',
		},
		currency: {
			fontSize: 18,
			color: '#828B95',
			fontWeight: 'bold',
		},
	};

	module.exports = { Styles };
});