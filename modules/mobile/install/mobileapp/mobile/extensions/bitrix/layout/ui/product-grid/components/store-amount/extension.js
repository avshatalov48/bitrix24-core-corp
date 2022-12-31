jn.define('layout/ui/product-grid/components/store-amount', (require, exports, module) => {

	const { get } = require('utils/object');
	const { hint } = require('layout/ui/product-grid/components/hint');

	class StoreAmount extends LayoutComponent
	{
		/**
		 * @param {{
		 *     title,
		 *     amount,
		 *     measure,
		 * }} props
		 */
		constructor(props)
		{
			super(props);
		}

		render()
		{
			let amount = Number(get(this.props, 'amount', 0));
			if (isNaN(amount))
			{
				amount = 0;
			}

			const measure = get(this.props, 'measure', '');

			return View(
				{
					style: Styles.wrapper
				},
				View(
					{
						style: Styles.titleContainer,
						onClick: () => hint(this.props.title)
					},
					Text({
						text: this.props.title,
						style: Styles.titleText,
						ellipsize: 'end',
						numberOfLines: 1,
					}),
				),
				View(
					{
						style: Styles.valueContainer
					},
					Text({
						text: `${amount} `,
						style: Styles.valueText,
						numberOfLines: 1,
					}),
					Text({
						text: `${measure}`,
						style: {...Styles.valueText, color: '#828B95'},
						numberOfLines: 1,
					})
				)
			);
		}
	}

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
		valueText: {
			fontSize: 18,
			fontWeight: 'bold',
			textAlign: 'right',
			color: '#333333'
		},
	};

	module.exports = { StoreAmount };

});