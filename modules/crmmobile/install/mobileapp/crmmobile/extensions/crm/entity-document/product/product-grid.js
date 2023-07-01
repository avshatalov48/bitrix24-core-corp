/**
 * @module crm/entity-document/product/product-grid
 */
jn.define('crm/entity-document/product/product-grid', (require, exports, module) => {
	const { ProductGrid } = require('layout/ui/product-grid');
	const { EntityDocumentProductCard } = require('crm/entity-document/product/product-card');
	const { ProductRow } = require('crm/product-grid/model');
	const { clone } = require('utils/object');

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
			this.additionalSummary = props.additionalSummary;
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
			const { catalog, measures, inventoryControl, entity, taxes, permissions } = this.getProps();

			return View(
				{
					style: {
						backgroundColor: '#eef2f4',
					},
				},
				new EntityDocumentProductCard({
					ref: (ref) => this.productCardRef = ref,
					productRow,
					name: productRow.getProductName(),
					gallery: productRow.getPhotos(),
					index,
					measures,
					permissions,
					editable: false,
					vatRates: taxes.vatRates,
					iblockId: catalog.id,
					inventoryControlEnabled: inventoryControl.enabled,
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

	module.exports = { EntityDocumentProductGrid };
});
