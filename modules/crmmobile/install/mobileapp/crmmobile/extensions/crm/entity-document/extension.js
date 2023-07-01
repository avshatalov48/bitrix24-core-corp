/**
 * @module crm/entity-document
 */
jn.define('crm/entity-document', (require, exports, module) => {
	const { BaseDocument } = require('crm/entity-document/base-document');
	const { PaymentDocument } = require('crm/entity-document/payment-document');
	const { DeliveryDocument } = require('crm/entity-document/delivery-document');

	module.exports = {
		BaseDocument,
		PaymentDocument,
		DeliveryDocument,
	};
});
