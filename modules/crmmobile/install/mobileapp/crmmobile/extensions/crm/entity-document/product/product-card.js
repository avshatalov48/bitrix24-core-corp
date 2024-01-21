/**
 * @module crm/entity-document/product/product-card
 */
jn.define('crm/entity-document/product/product-card', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { InlineSkuTree } = require('layout/ui/product-grid/components/inline-sku-tree');
	const { ProductCard } = require('layout/ui/product-grid/components/product-card');
	const { DiscountPrice } = require('layout/ui/product-grid/components/discount-price');
	const { isEmpty } = require('utils/object');

	/**
	 * @class EntityDocumentProductCard
	 */
	class EntityDocumentProductCard extends ProductCard
	{
		constructor(props)
		{
			super(props);
			this.state = {
				productRow: this.props.productRow,
			};
		}

		render()
		{
			const { productRow } = this.state;

			return View(
				{
					style: styles.productCard,
				},
				new ProductCard({
					ref: (ref) => this.productCardRef = ref,
					productRow,
					id: productRow.getProductId(),
					name: productRow.getProductName(),
					gallery: productRow.getPhotos(),
					index: this.props.index + 1,
					editable: false,
					renderInnerContent: () => this.renderInnerContent(),
				}),
			);
		}

		renderInnerContent()
		{
			return View(
				{
					style: styles.innerContent.wrapper,
				},
				this.renderProperties(),
				View(
					{
						style: styles.innerContent.totalsWrapper,
					},
					this.renderQuantity(),
					this.renderPrices(),
				),
			);
		}

		renderQuantity()
		{
			/** @var {ProductRow} productRow */
			const { productRow } = this.state;

			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				Text({
					text: productRow.getQuantity().toString(),
					style: {
						fontSize: 16,
						color: AppTheme.colors.base1,
						fontWeight: '700',
						marginRight: 5,
					},
				}),
				Text({
					text: productRow.getMeasureName(),
					style: {
						fontSize: 16,
						color: AppTheme.colors.base3,
						fontWeight: '700',
					},
				}),
			);
		}

		/**
		 * Product price and discounts
		 * @returns {View}
		 */
		renderPrices()
		{
			/** @var {ProductRow} productRow */
			const { productRow } = this.state;
			const currency = productRow.getCurrency();
			let price = productRow.getPrice();
			if (!productRow.isTaxIncluded())
			{
				price = productRow.getPrice() + productRow.getTaxSum();
			}
			const discount = new Money({ amount: productRow.getMaxPrice() - price, currency });
			const oldPrice = new Money({ amount: productRow.getMaxPrice(), currency });

			return View(
				{
					style: {
						alignItems: 'flex-end',
					},
				},
				MoneyView({
					money: Money.create({
						amount: price,
						currency,
					}),
					renderAmount: (formattedAmount) => Text({
						text: formattedAmount,
						style: {
							fontSize: 16,
							color: AppTheme.colors.base1,
							fontWeight: '700',
						},
					}),
					renderCurrency: (formattedCurrency) => Text({
						text: formattedCurrency,
						style: {
							fontSize: 16,
							color: AppTheme.colors.base3,
							fontWeight: '700',
						},
					}),
				}),
				productRow.getDiscountSum() > 0 && View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					Text({
						style: styles.innerContent.totalText,
						text: Loc.getMessage('M_CRM_ENTITY_DOCUMENT_SUMMARY_TOTAL_DISCOUNT'),
					}),
					DiscountPrice({
						discount,
						oldPrice,
					}),
				),
			);
		}

		renderProperties()
		{
			if (!this.hasVariations())
			{
				return null;
			}
			const { productRow } = this.state;
			const skuTree = productRow.getSkuTree();

			return View(
				{},
				new InlineSkuTree({
					...skuTree,
					editable: false,
					onChangeSku: () => {},
				}),
			);
		}

		hasVariations()
		{
			const { productRow } = this.state;
			const skuTree = productRow.getSkuTree();
			if (skuTree && skuTree.OFFERS_PROP && !isEmpty(skuTree.OFFERS_PROP))
			{
				return true;
			}

			return false;
		}
	}

	const styles = {
		productCard: {
			backgroundColor: AppTheme.colors.bgSecondary,
			borderRadius: 12,
		},
		innerContent: {
			wrapper: {
				flexDirection: 'column',
			},
			totalsWrapper: {
				flexDirection: 'row',
				justifyContent: 'space-between',
				alignItems: 'flex-start',
			},
			totalText: {
				flexDirection: 'row',
				color: AppTheme.colors.base4,
				fontSize: 12,
			},
			total: {
				quantity: {
					fontSize: 22,
					fontWeight: '700',
					color: AppTheme.colors.base1,
					marginRight: 5,
				},
				measure: {
					fontSize: 22,
					fontWeight: '700',
					color: AppTheme.colors.base4,
				},
			},
			prices: {
				flexDirection: 'column',
				alignItems: 'flex-end',
			},
		},
	};

	module.exports = { EntityDocumentProductCard };
});
