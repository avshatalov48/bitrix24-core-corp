/**
 * @module crm/salescenter/products-step/product-grid
 */
jn.define('crm/salescenter/products-step/product-grid', (require, exports, module) => {
	const { Loc } = require('loc');
	const { CrmProductGrid } = require('crm/product-grid');
	const { SalescenterProductModelLoader } = require('crm/salescenter/products-step/product-model-loader');
	const { EmptyScreen } = require('layout/ui/empty-screen');

	/**
	 * @class ProductGrid
	 *
	 * Product grid implementation for salescenter scenarios
	 */
	class ProductGrid extends CrmProductGrid
	{
		initServices()
		{
			super.initServices();

			const { entity } = this.getProps();

			this.productModelLoader = new SalescenterProductModelLoader({
				entityId: entity.id,
				entityTypeName: entity.typeName,
				categoryId: entity.categoryId,
				ajaxErrorHandler: this.props.ajaxErrorHandler,
			});

			if (this.props.menuAnalyticsPrefix)
			{
				this.menu.analytics.entityTypeName = `${this.props.menuAnalyticsPrefix}_${this.menu.analytics.entityTypeName}`;
			}
		}

		getSummaryComponents()
		{
			return {
				summary: true,
				amount: true,
				discount: true,
				taxes: false,
			};
		}

		getEmptyScreenImage()
		{
			return {
				svg: {
					uri: EmptyScreen.makeLibraryImagePath('products-no-clouds.svg'),
				},
				style: {
					width: 142,
					height: 146,
				},
			};
		}

		getEmptyScreenTitle()
		{
			return Loc.getMessage('M_CRM_RECEIVE_PAYMENT_PRODUCT_GRID_PICK_UP_PRODUCTS');
		}

		getEmptyScreenDescription()
		{
			return Loc.getMessage('M_CRM_RECEIVE_PAYMENT_PRODUCT_GRID_PICK_UP_PRODUCTS_DESCRIPTION');
		}

		getEmptyScreenStyles()
		{
			return {
				container: {
					justifyContent: 'flex-start',
				},
				icon: {
					marginTop: 52,
				},
			};
		}

		getEmptyScreenBackgroundColor()
		{
			return 'transparent';
		}

		getFetchTotalsEndpoint()
		{
			return 'crmmobile.Salescenter.ProductGrid.loadProductGridSummary';
		}

		notifyGridChanged()
		{
			super.notifyGridChanged();

			this.customEventEmitter.emit('SalescenterProductGrid::onUpdate', [this.state.products]);
		}

		onAfterSummaryUpdate(responseData)
		{
			responseData.items.forEach((responseItem) => {
				/** @type ProductRow */
				const productRow = this.getItemByXmlId(responseItem.innerId);
				if (productRow && responseItem.code)
				{
					productRow.setField('BASKET_ITEM_FIELDS.BASKET_CODE', responseItem.code);
				}
			});

			this.notifyGridChanged();
		}

		getItemByXmlId(xmlId)
		{
			return this.getItems().find((item) => item.getField('BASKET_ITEM_FIELDS.XML_ID') === xmlId);
		}

		onRemoveItemConfirm(productRow)
		{
			super.onRemoveItemConfirm(productRow);

			if (this.props.onRemoveItemConfirm)
			{
				this.props.onRemoveItemConfirm();
			}
		}

		showTaxInProductCard()
		{
			return false;
		}
	}

	module.exports = { ProductGrid };
});
