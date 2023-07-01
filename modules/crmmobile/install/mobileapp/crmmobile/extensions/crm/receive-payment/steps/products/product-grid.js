/**
 * @module crm/receive-payment/steps/products/product-grid
 */
jn.define('crm/receive-payment/steps/products/product-grid', (require, exports, module) => {
	const { Loc } = require('loc');
	const { CrmProductGrid } = require('crm/product-grid');
	const { ReceivePaymentProductModelLoader } = require('crm/receive-payment/steps/products/product-model-loader');
	const { AnalyticsLabel } = require('analytics-label');

	/**
	 * @class ReceivePaymentProductGrid
	 *
	 * Product grid implementation for receive payment scenario.
	 */
	class ReceivePaymentProductGrid extends CrmProductGrid
	{
		initServices()
		{
			super.initServices();

			const { entity } = this.getProps();

			this.productModelLoader = new ReceivePaymentProductModelLoader({
				entityId: entity.id,
				entityTypeName: entity.typeName,
				categoryId: entity.categoryId,
				ajaxErrorHandler: this.props.ajaxErrorHandler,
			});

			this.menu.analytics.entityTypeName = `receive_payment_${this.menu.analytics.entityTypeName}`;
		}

		getEmptyScreenTitle()
		{
			return Loc.getMessage('M_CRM_RECEIVE_PAYMENT_PRODUCT_GRID_PICK_UP_PRODUCTS');
		}

		getEmptyScreenDescription()
		{
			return Loc.getMessage('M_CRM_RECEIVE_PAYMENT_PRODUCT_GRID_PICK_UP_PRODUCTS_DESCRIPTION');
		}

		getFetchTotalsEndpoint()
		{
			return 'crmmobile.ReceivePayment.ProductStep.loadProductGridSummary';
		}

		notifyGridChanged()
		{
			super.notifyGridChanged();

			this.customEventEmitter.emit('ReceivePaymentProductGrid::onUpdate', [this.state.products]);
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
			AnalyticsLabel.send({
				event: 'onReceivePaymentProductRemoved',
			});
		}

		showTaxInProductCard()
		{
			return false;
		}
	}

	module.exports = { ReceivePaymentProductGrid };
});
