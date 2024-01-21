/**
 * @module crm/entity-document
 */
jn.define('crm/entity-document', (require, exports, module) => {
	const { PaymentDocument } = require('crm/entity-document/payment-document');

	module.exports = {
		PaymentDocument,
	};
});
