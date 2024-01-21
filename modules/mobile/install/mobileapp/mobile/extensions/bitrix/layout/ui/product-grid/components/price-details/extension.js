/**
 * @module layout/ui/product-grid/components/price-details
 */
jn.define('layout/ui/product-grid/components/price-details', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');

	class PriceDetails extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layout = props.layout;
			this.initLayout();
		}

		initLayout()
		{
			this.layout.setTitle({ text: this.props.title || Loc.getMessage('PRODUCT_GRID_CONTROL_PRICE_DETAILS') });
			this.layout.enableNavigationBarBorder(false);
		}

		render()
		{
			return Container(
				this.renderPriceBeforeTax(),
				this.renderTaxValue(),
				this.renderTotalPrice(),
			);
		}

		renderPriceBeforeTax()
		{
			const title = Loc.getMessage('PRODUCT_GRID_CONTROL_PRICE_DETAILS_BEFORE_TAX');
			const amount = this.props.priceBeforeTax;
			const currency = this.props.currency;

			return MoneyRow({ title, amount, currency });
		}

		renderTaxValue()
		{
			const title = this.props.taxName
				? Loc.getMessage('PRODUCT_GRID_CONTROL_PRICE_DETAILS_TAX', { '#TAX_NAME#': this.props.taxName })
				: Loc.getMessage('PRODUCT_GRID_CONTROL_PRICE_DETAILS_TAX_EMPTY');

			const amount = this.props.taxValue;
			const currency = this.props.currency;

			return MoneyRow({ title, amount, currency });
		}

		renderTotalPrice()
		{
			const title = Loc.getMessage('PRODUCT_GRID_CONTROL_PRICE_DETAILS_TOTAL');
			const amount = this.props.finalPrice;
			const currency = this.props.currency;

			return TotalRow({ title, amount, currency });
		}
	}

	function Container(...children)
	{
		return View(
			{
				style: {
					backgroundColor: AppTheme.colors.bgContentPrimary,
				},
			},
			View(
				{
					style: {
						borderRadius: 12,
						paddingLeft: 20,
						paddingRight: 20,
					},
				},
				...children,
			),
		);
	}

	function MoneyRow({ title, amount, currency })
	{
		const style = {
			borderBottomColor: AppTheme.colors.bgSeparatorPrimary,
			borderBottomWidth: 1,
		};

		return RowWrap(
			style,
			Title(title),
			View(
				{},
				MoneyView({
					money: Money.create({ amount, currency }),
					renderAmount: (formattedAmount) => Text({
						text: formattedAmount,
						style: {
							fontSize: 18,
							color: AppTheme.colors.base2,
						},
					}),
					renderCurrency: (formattedCurrency) => Text({
						text: formattedCurrency,
						style: {
							fontSize: 18,
							color: AppTheme.colors.base3,
						},
					}),
				}),
			),
		);
	}

	function TotalRow({ title, amount, currency })
	{
		return RowWrap(
			{},
			Title(title, { color: AppTheme.colors.base2, fontSize: 20 }),
			View(
				{},
				MoneyView({
					money: Money.create({ amount, currency }),
					renderAmount: (formattedAmount) => Text({
						text: formattedAmount,
						style: {
							fontSize: 20,
							color: AppTheme.colors.base1,
							fontWeight: 'bold',
						},
					}),
					renderCurrency: (formattedCurrency) => Text({
						text: formattedCurrency,
						style: {
							fontSize: 20,
							color: AppTheme.colors.base3,
							fontWeight: 'bold',
						},
					}),
				}),
			),
		);
	}

	function Title(text, style = {})
	{
		style = {
			color: AppTheme.colors.base4,
			fontSize: 18,
			...style,
		};

		return View(
			{},
			Text({ text, style }),
		);
	}

	function RowWrap(style = {}, ...children)
	{
		style = {
			flexDirection: 'row',
			justifyContent: 'space-between',
			paddingTop: 16,
			paddingBottom: 16,
			...style,
		};

		return View({ style }, ...children);
	}

	module.exports = { PriceDetails };
});
