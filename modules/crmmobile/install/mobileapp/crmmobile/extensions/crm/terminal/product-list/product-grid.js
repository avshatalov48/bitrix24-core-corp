/**
 * @module crm/terminal/product-list/product-grid
 */
jn.define('crm/terminal/product-list/product-grid', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { ProductGrid } = require('layout/ui/product-grid');
	const { PaymentProductCard } = require('crm/terminal/product-list/product-card');

	const { ProductRow } = require('crm/product-grid/model');
	const { clone } = require('utils/object');

	/**
	 * @class PaymentProductGrid
	 */
	class PaymentProductGrid extends ProductGrid
	{
		constructor(props)
		{
			super(props);

			this.productCardRef = null;
			this.state = this.buildState(this.getProps());
		}

		getSummaryComponents()
		{
			return BX.prop.getObject(this.props, 'summaryComponents', {});
		}

		initServices()
		{
			super.initServices();
		}

		getProps()
		{
			return this.props;
		}

		renderSingleItem(productRow, index)
		{
			const { catalog, measures, entity, taxes, permissions } = this.getProps();

			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgPrimary,
					},
				},
				new PaymentProductCard({
					ref: (ref) => {
						this.productCardRef = ref;
					},
					productRow,
					name: productRow.getProductName(),
					gallery: productRow.getPhotos(),
					index,
					measures,
					permissions,
					editable: false,
					vatRates: taxes.vatRates,
					iblockId: catalog.id,
					entityDetailPageUrl: entity.detailPageUrl,
					entityTypeId: entity.typeId,
				}),
			);
		}

		addItem(productRow)
		{}

		buildState(props)
		{
			return {
				items: clone(props.products).map((row) => new ProductRow(row)),
				summary: clone(props.summary),
			};
		}

		getItems()
		{
			return this.state.items;
		}

		getSummary()
		{
			return this.state.summary;
		}

		isEditable()
		{
			return false;
		}

		onAddItemButtonClick()
		{}

		onAddItemButtonLongClick()
		{}

		removeItem(productRow)
		{}
	}

	module.exports = { PaymentProductGrid };
});
