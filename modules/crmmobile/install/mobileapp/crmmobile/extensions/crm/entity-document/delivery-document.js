/**
 * @module crm/entity-document/delivery-document
 */
jn.define('crm/entity-document/delivery-document', (require, exports, module) => {
	const { BaseDocument } = require('crm/entity-document/base-document');

	/**
	 * @class DeliveryDocument
	 */
	class DeliveryDocument extends BaseDocument
	{
		constructor(props)
		{
			super(props);
		}

		getEntityTypeId()
		{
			return TypeId.OrderShipment;
		}

		getLoadDocumentDataAction()
		{
			return 'crmmobile.Document.Delivery.getDocumentData';
		}

		getSummaryDeliveryData()
		{
			const { document } = this.props;
			let badgeText = '';
			let bagdeColor = '';
			let bagdeTextColor = '';
			const title = Loc.getMessage('M_CRM_ENTITY_DOCUMENT_SUMMARY_DELIVERY_TITLE');
			const subtitle = document.DELIVERY_NAME;

			if (document.DEDUCTED === 'Y')
			{
				bagdeColor = '#eaf6c3';
				bagdeTextColor = '#688800';
				badgeText = Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_SHIPMENT_DONE');
			}
			else
			{
				bagdeColor = '#dfe0e3';
				bagdeTextColor = '#828b95';
				badgeText = Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_SHIPMENT_WAITING');
			}

			return {
				title,
				subtitle,
				badge: {
					text: badgeText,
					color: bagdeColor,
					backgroundColor: bagdeTextColor,
				},
			};
		}
	}

	module.exports = {
		DeliveryDocument,
	};
});
