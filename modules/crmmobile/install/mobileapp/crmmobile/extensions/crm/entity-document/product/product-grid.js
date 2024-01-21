/**
 * @module crm/entity-document/product/product-grid
 */
jn.define('crm/entity-document/product/product-grid', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { clone } = require('utils/object');
	const { ProductGrid } = require('layout/ui/product-grid');
	const { ProductRow } = require('crm/product-grid/model');
	const { EntityDocumentProductCard } = require('crm/entity-document/product/product-card');

	/**
	 * @class EntityDocumentProductGrid
	 */
	class EntityDocumentProductGrid extends ProductGrid
	{
		constructor(props)
		{
			super(props);

			this.productCardRef = null;
			this.state = this.buildState(this.getProps());

			this.additionalTopContent = props.additionalTopContent;
			this.additionalBottomContent = props.additionalBottomContent;
			this.additionalSummary = props.additionalSummary;
			this.additionalSummaryBottom = props.additionalSummaryBottom;
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
				new EntityDocumentProductCard({
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
			return {
				...this.state.summary,
				styles: {
					container: {
						marginBottom: 12,
					},
				},
			};
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

	module.exports = { EntityDocumentProductGrid };
});
