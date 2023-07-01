/**
 * @module crm/entity-document/payment-document
 */
jn.define('crm/entity-document/payment-document', (require, exports, module) => {
	const { BaseDocument } = require('crm/entity-document/base-document');
	const { Loc } = require('loc');
	const { TypeId } = require('crm/type');

	/**
	 * @class PaymentDocument
	 */
	class PaymentDocument extends BaseDocument
	{
		constructor(props)
		{
			super(props);
			this.isAvailableReceivePayment = props.isAvailableReceivePayment;
		}

		componentDidMount()
		{
			this.layoutWidget.setTitle({
				text: this.getDocumentTitle(),
			});
			if (this.isAvailableReceivePayment)
			{
				this.layoutWidget.setRightButtons([
					{
						name: Loc.getMessage('M_CRM_ENTITY_DOCUMENT_SEND'),
						type: 'text',
						color: '#2066b0',
						callback: () => this.openSendMessageStep(),
					},
				]);
			}
		}

		getDocumentTitle()
		{
			return Loc.getMessage('M_CRM_ENTITY_DOCUMENT_PAYMENT_TITLE', {
				'#DATE#': this.document.FORMATTED_DATE,
				'#ACCOUNT_NUMBER#': this.document.ACCOUNT_NUMBER,
			});
		}

		getEntityTypeId()
		{
			return TypeId.OrderPayment;
		}

		getSummaryPaymentData()
		{
			const document = this.payment;
			let productsPrice = 0;
			let productsCurrency = document.CURRENCY;
			if (this.grid)
			{
				productsPrice = this.grid.summary.totalProductCost;
				productsCurrency = this.grid.summary.currency;
			}
			const money = Money.create({
				amount: productsPrice,
				currency: productsCurrency,
			});
			const title = Loc.getMessage('M_CRM_ENTITY_DOCUMENT_SUMMARY_PRODUCTS_TITLE', {
				'#AMOUNT#': this.grid.products.length,
			});

			let subtitle = '';
			if (document.PAID === 'Y')
			{
				subtitle = `${document.PAY_SYSTEM_NAME} ${document.FORMATTED_DATE_PAID}`;
			}

			let badgeText;
			let bagdeColor;
			let bagdeTextColor;
			if (document.PAID === 'Y')
			{
				bagdeColor = '#eaf6c3';
				bagdeTextColor = '#688800';
				badgeText = Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_STAGE_PAID');
			}
			else
			{
				bagdeColor = '#dfe0e3';
				bagdeTextColor = '#828b95';
				badgeText = Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_STAGE_NOT_PAID');
			}

			return {
				title,
				subtitle,
				badge: {
					text: badgeText,
					color: bagdeTextColor,
					backgroundColor: bagdeColor,
				},
				money: money.formatted,
			};
		}

		getSummaryDeliveryData()
		{
			const shipment = this.shipment;
			if (!shipment)
			{
				return null;
			}
			const shipmentMoney = Money.create({
				amount: shipment.BASE_PRICE_DELIVERY,
				currency: shipment.CURRENCY,
			});

			let bagdeColor;
			let bagdeTextColor;
			let badgeText;
			if (shipment.DEDUCTED === 'Y')
			{
				bagdeColor = '#eaf6c3';
				bagdeTextColor = '#688800';
				badgeText = Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_SHIPMENT_DONE');
			}
			else
			{
				badgeText = Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_SHIPMENT_WAITING');
				bagdeColor = '#dfe0e3';
				bagdeTextColor = '#828b95';
			}

			return {
				title: Loc.getMessage('M_CRM_ENTITY_DOCUMENT_SUMMARY_DELIVERY_TITLE'),
				subtitle: shipment.DELIVERY_NAME,
				badge: {
					text: badgeText,
					color: bagdeTextColor,
					backgroundColor: bagdeColor,
				},
				money: shipmentMoney.formatted,
			};
		}
	}

	module.exports = {
		PaymentDocument,
	};
});
