/**
 * @module layout/ui/product-grid/components/discount-price
 */
jn.define('layout/ui/product-grid/components/discount-price', (require, exports, module) => {

	/**
	 * @param {{
	 *     oldPrice: Money,
	 *     discount: Money,
	 *     style: object,
	 * }} props
	 * @returns {object}
	 */
	function DiscountPrice(props)
	{
		const { oldPrice, discount } = props;
		const customStyle = props.style || {};
		const sign = discount.amount > 0 ? '−' : '+';
		const discountAbs = new Money({
			amount: Math.abs(discount.amount),
			currency: discount.currency,
		});

		return View(
			{
				style: Styles.container,
			},
			Text({
				testId: 'product-grid-summary-discount-old-price',
				text: oldPrice.formatted,
				style: Styles.oldPrice(customStyle),
			}),
			View(
				{
					style: Styles.discountContainer(discount.amount, customStyle),
				},
				Text({
					testId: 'product-grid-summary-discount-price',
					text: `${sign}${discountAbs.formatted}`,
					style: Styles.discountText(customStyle),
				})
			)
		);
	}

	const Styles = {
		container: {
			flexDirection: 'row',
		},
		oldPrice: (props) => ({
			color: BX.prop.getString(props, 'color', '#A8ADB4'),
			fontSize: BX.prop.getInteger(props, 'fontSize', 12),
			fontWeight: BX.prop.getString(props, 'fontWeight', 'normal'),
			textDecorationLine: 'line-through',
			marginRight: 5,
		}),
		discountContainer: (amount, props) => ({
			backgroundColor: BX.prop.getString(props, 'backgroundColor', amount > 0 ? '#9DCF00' : '#FF5752'),
			borderRadius: 4,
			paddingLeft: 3,
			paddingRight: 3,
			paddingBottom: 1,
			paddingTop: 1,
		}),
		discountText: (props) => ({
			color: '#ffffff',
			fontWeight: BX.prop.getString(props, 'fontWeight', 'bold'),
			fontSize: BX.prop.getInteger(props, 'fontSize', 12),
		})
	};

	module.exports = { DiscountPrice };

});