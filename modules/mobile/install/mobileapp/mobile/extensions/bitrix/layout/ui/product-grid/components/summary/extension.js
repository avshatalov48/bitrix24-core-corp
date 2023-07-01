/**
 * @module layout/ui/product-grid/components/summary
 */
jn.define('layout/ui/product-grid/components/summary', (require, exports, module) => {

	const { Loc } = require('loc');
	const { clone, isEqual } = require('utils/object');
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
		 *     additionalSummary: (LayoutComponent),
		 *     showSummaryAmount: (Boolean|null),
		 *     showSummaryTax: (Boolean|null),
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
						backgroundColor: '#eef2f4',
					},
				},
				View(
					{
						style: {
							backgroundColor: '#ffffff',
							borderRadius: 12,
							padding: 12,
							marginBottom: 90,
							opacity: this.state.loading ? 0.4 : 1,
						},
						ref: (ref) => this.containerRef = ref,
					},
					this.state.additionalSummary,
					Summary(
						this.state.showSummaryAmount ? ItemsCount(this.state) : View(),
						Sum(this.state),
					),
					Discount(this.state),
					this.state.showSummaryTax && Taxes(this.state),
				)
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
			const setState = (state) => new Promise(resolve => this.setState(state, resolve));
			const fadeIn = transition(this.containerRef, {
				opacity: 1,
				duration: 300,
			})

			return setState({ ...nextState, loading: true })
				.then(fadeIn)
				.then(() => setState({ loading: false }));
		}
	}

	function Discount({totalDiscount, totalWithoutDiscount, currency, discountCaption})
	{
		if (totalDiscount === 0)
		{
			return null;
		}

		const discount = new Money({amount: totalDiscount, currency});
		const oldPrice = new Money({amount: totalWithoutDiscount, currency});

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
					color: '#A8ADB4',
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
				}
			},
			...children
		);
	}

	function Taxes({totalTax, taxIncluded, taxPartlyIncluded, currency})
	{
		const normal = {
			fontSize: 12,
			color: '#A8ADB4',
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
					style: alignRight
				},
				Text({
					style: {...normal, marginRight: 2},
					text: Loc.getMessage('PRODUCT_GRID_SUMMARY_TOTAL_TAX'),
				}),
				MoneyView({
					money: Money.create({ amount: totalTax, currency }),
					renderAmount: text => Text({
						testId: 'product-grid-summary-tax-total-amount',
						text,
						style: bold,
					}),
					renderCurrency: text => Text({
						testId: 'product-grid-summary-tax-total-currency',
						text,
						style: bold,
					}),
				}),
			),
			(totalTax > 0) && View(
				{
					style: alignRight
				},
				Text({
					style: normal,
					text: Loc.getMessage(taxMessageCode)
				}),
			),
		);
	}

	function ItemsCount({totalRows, countCaption})
	{
		countCaption = countCaption || Loc.getMessage('PRODUCT_GRID_SUMMARY_ITEMS_COUNT');
		countCaption = countCaption.replace('#NUM#', totalRows);

		return View(
			{
				style: {
					flexDirection: 'row',
					justifyContent: 'flex-start',
					alignItems: 'center',
				}
			},
			Text({
				testId: 'product-grid-summary-items-count',
				text: countCaption,
				style: {
					fontSize: 18,
					color: '#525C69',
					opacity: 0.4,
				}
			})
		);
	}

	function Sum({totalCost, currency, totalSumCaption})
	{
		const totalCaption = totalSumCaption || Loc.getMessage('PRODUCT_GRID_SUMMARY_TOTAL');

		return View(
			{
				style: {
					flexDirection: 'row',
					justifyContent: 'flex-end',
					alignItems: 'center',
				}
			},
			View(
				{},
				Text({
					text: totalCaption,
					style: {
						color: '#525C69',
						fontSize: 18,
						fontWeight: 'bold',
						marginRight: 6,
					}
				})
			),
			MoneyView({
				money: Money.create({amount: totalCost, currency}),
				renderAmount: (formattedAmount) => Text({
					testId: 'product-grid-summary-total-amount',
					text: formattedAmount,
					style: {
						fontSize: 20,
						color: '#333333',
						fontWeight: 'bold',
					}
				}),
				renderCurrency: (formattedCurrency) => Text({
					testId: 'product-grid-summary-total-currency',
					text: formattedCurrency,
					style: {
						fontSize: 20,
						color: '#828B95',
						fontWeight: 'bold',
					}
				}),
			}),
		);
	}


	module.exports = { ProductGridSummary, Discount, ItemsCount, Taxes, Sum };

});