/**
 * @module layout/ui/product-grid/components/summary
 */
jn.define('layout/ui/product-grid/components/summary', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { clone, isEqual, get } = require('utils/object');
	const { DiscountPrice } = require('layout/ui/product-grid/components/discount-price');
	const { animate, transition } = require('animation');

	/**
	 * @class ProductGridSummary
	 */
	class ProductGridSummary extends LayoutComponent
	{
		/**
		 * @param {{
		 *     currency: String,
		 *     totalRows: Number,
		 *     totalCost: Number,
		 *     totalDelivery: (Number|null),
		 *     totalDiscount: (Number|null),
		 *     totalTax: (Number|null),
		 *     totalWithoutDiscount: (Number|null),
		 *     totalWithoutTax: (Number|null),
		 *     countCaption: (String|null),
		 *     taxIncluded: (Boolean|null),
		 *     taxPartlyIncluded: (Boolean|null),
		 *     componentsForDisplay: Object,
		 *     additionalSummary: (LayoutComponent),
		 *     additionalSummaryBottom: (LayoutComponent),
		 *     discountCaption: (String|null),
		 *     totalSumCaption: (String|null),
		 * }} props
		 * @param props
		 */
		constructor(props)
		{
			super(props);

			this.state = clone(props);

			this.containerRef = null;
		}

		getComponentsForDisplay()
		{
			return {
				summary: true,
				amount: true,
				discount: true,
				taxes: true,
				...this.state.componentsForDisplay,
			};
		}

		componentWillReceiveProps(props)
		{
			if (!isEqual(this.state, props))
			{
				this.state = clone(props);
			}
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
						borderRadius: 12,
						padding: 12,
						marginBottom: 90,
						opacity: this.state.loading ? 0.4 : 1,
						...BX.prop.getObject(this.styles, 'container', {}),
					},
					ref: (ref) => {
						this.containerRef = ref;
					},
				},
				this.state.additionalSummary,
				(get(this.getComponentsForDisplay(), 'summary', true)) && Summary(
					(get(this.getComponentsForDisplay(), 'amount', true)) ? ItemsCount(this.state) : View(),
					Sum(this.state),
				),
				(get(this.getComponentsForDisplay(), 'discount', true)) && Discount(this.state),
				(get(this.getComponentsForDisplay(), 'taxes', true)) && Taxes(this.state),
				this.state.additionalSummaryBottom,
			);
		}

		fadeOut()
		{
			return animate(this.containerRef, {
				opacity: 0.4,
				duration: 300,
			});
		}

		fadeIn(nextState)
		{
			const setState = (state) => new Promise((resolve) => this.setState(state, resolve));
			const fadeIn = transition(this.containerRef, {
				opacity: 1,
				duration: 300,
			});

			return setState({ ...nextState, loading: true })
				.then(fadeIn)
				.then(() => setState({ loading: false }));
		}

		get styles()
		{
			return BX.prop.getObject(this.props, 'styles', {});
		}
	}

	function Discount({ totalDiscount, totalWithoutDiscount, currency, discountCaption })
	{
		if (totalDiscount === 0)
		{
			return null;
		}

		const discount = new Money({ amount: totalDiscount, currency });
		const oldPrice = new Money({ amount: totalWithoutDiscount, currency });

		return View(
			{
				style: {
					flexDirection: 'row',
					justifyContent: 'flex-end',
					marginTop: 2,
					marginBottom: 2,
				},
			},
			discountCaption && Text({
				style: {
					color: AppTheme.colors.base4,
					fontSize: 12,
				},
				text: discountCaption,
			}),
			DiscountPrice({
				discount,
				oldPrice,
			}),
		);
	}

	function Summary(...children)
	{
		return View(
			{
				style: {
					flexDirection: 'row',
					justifyContent: 'space-between',
				},
			},
			...children,
		);
	}

	function Taxes({ totalTax, taxIncluded, taxPartlyIncluded, currency })
	{
		const normal = {
			fontSize: 12,
			color: AppTheme.colors.base4,
		};
		const bold = {
			...normal,
			fontWeight: 'bold',
		};
		const alignRight = {
			flexDirection: 'row',
			justifyContent: 'flex-end',
		};
		let taxMessageCode;
		if (taxPartlyIncluded)
		{
			taxMessageCode = 'PRODUCT_GRID_SUMMARY_TAX_PARTLY_INCLUDED';
		}
		else
		{
			taxMessageCode = taxIncluded ? 'PRODUCT_GRID_SUMMARY_TAX_INCLUDED' : 'PRODUCT_GRID_SUMMARY_TAX_NOT_INCLUDED_MSGVER_1';
		}

		return View(
			{},
			View(
				{
					style: alignRight,
				},
				Text({
					style: { ...normal, marginRight: 2 },
					text: Loc.getMessage('PRODUCT_GRID_SUMMARY_TOTAL_TAX'),
				}),
				MoneyView({
					money: Money.create({ amount: totalTax, currency }),
					renderAmount: (text) => Text({
						testId: 'product-grid-summary-tax-total-amount',
						text,
						style: bold,
					}),
					renderCurrency: (text) => Text({
						testId: 'product-grid-summary-tax-total-currency',
						text,
						style: bold,
					}),
				}),
			),
			(totalTax > 0) && View(
				{
					style: alignRight,
				},
				Text({
					style: normal,
					text: Loc.getMessage(taxMessageCode),
				}),
			),
		);
	}

	function ItemsCount({ totalRows, countCaption })
	{
		countCaption = countCaption || Loc.getMessage('PRODUCT_GRID_SUMMARY_ITEMS_COUNT_MSGVER_1');
		countCaption = countCaption.replace('#NUM#', totalRows);

		return View(
			{
				style: {
					flexDirection: 'row',
					justifyContent: 'flex-start',
					alignItems: 'center',
				},
			},
			Text({
				testId: 'product-grid-summary-items-count',
				text: countCaption,
				style: {
					fontSize: 18,
					color: AppTheme.colors.base2,
					opacity: 0.4,
				},
			}),
		);
	}

	function Sum({ totalCost, currency, totalSumCaption })
	{
		const totalCaption = totalSumCaption || Loc.getMessage('PRODUCT_GRID_SUMMARY_TOTAL');

		return View(
			{
				style: {
					flexDirection: 'row',
					justifyContent: 'flex-end',
					alignItems: 'center',
				},
			},
			View(
				{},
				Text({
					text: totalCaption,
					style: {
						color: AppTheme.colors.base2,
						fontSize: 18,
						fontWeight: 'bold',
						marginRight: 6,
					},
				}),
			),
			MoneyView({
				money: Money.create({ amount: totalCost, currency }),
				renderAmount: (formattedAmount) => Text({
					testId: 'product-grid-summary-total-amount',
					text: formattedAmount,
					style: {
						fontSize: 20,
						color: AppTheme.colors.base1,
						fontWeight: 'bold',
					},
				}),
				renderCurrency: (formattedCurrency) => Text({
					testId: 'product-grid-summary-total-currency',
					text: formattedCurrency,
					style: {
						fontSize: 20,
						color: AppTheme.colors.base3,
						fontWeight: 'bold',
					},
				}),
			}),
		);
	}

	module.exports = { ProductGridSummary, Discount, ItemsCount, Taxes, Sum };
});
