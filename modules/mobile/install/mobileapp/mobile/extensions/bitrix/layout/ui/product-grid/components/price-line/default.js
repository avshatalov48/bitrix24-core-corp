jn.define('layout/ui/product-grid/components/price-line/default', (require, exports, module) => {

	const { Styles } = require('layout/ui/product-grid/components/price-line/styles');

	class PriceLine extends LayoutComponent
	{
		/**
		 * @param {{
		 *     title,
		 *     value: {amount, currency}
		 * }} props
		 */
		constructor(props)
		{
			super(props);
		}

		render()
		{
			return View(
				{
					style: this.styles().wrapper,
				},
				this.renderTitle(),
				this.renderValue(),
			);
		}

		renderTitle()
		{
			return View(
				{
					style: this.styles().titleContainer
				},
				Text({
					text: this.props.title,
					style: this.styles().titleText
				}),
			);
		}

		renderValue()
		{
			let {amount, currency} = this.props.value;

			amount = parseFloat(amount);

			if (!isFinite(amount))
			{
				amount = 0;
			}

			return View(
				{
					style: this.styles().valueContainer
				},
				MoneyView({
					money: Money.create({amount, currency}),
					renderAmount: (formattedAmount) => Text({
						text: formattedAmount,
						style: this.styles().amount
					}),
					renderCurrency: (formattedCurrency) => Text({
						text: formattedCurrency,
						style: this.styles().currency,
					}),
				})
			);
		}

		styles()
		{
			return Styles;
		}
	}

	module.exports = { PriceLine };

});