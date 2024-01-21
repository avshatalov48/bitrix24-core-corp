/**
 * @module crm/terminal/services/field-manager/fields
 */
jn.define('crm/terminal/services/field-manager/fields', (require, exports, module) => {
	const { StatusFields } = require('crm/terminal/services/field-manager/fields/status');

	const FIELD_NAMES = {
		FieldNameSum: 'SUM',
		FieldNamePhone: 'PHONE',
		FieldNameClient: 'CLIENT',
		FieldNameClientName: 'CLIENT_NAME',
		FieldNameDatePaid: 'DATE_PAID',
		FieldNameStatus: 'STATUS',
		FieldNamePaymentSystem: 'PAYMENT_SYSTEM',
		FieldNameSlipLink: 'SLIP_LINK',
		FieldNameResponsible: 'RESPONSIBLE_ID',
	};

	const FIELDS = {
		[FIELD_NAMES.FieldNameStatus]: StatusFields,
	};

	const getTerminalField = (fieldName) => FIELDS[fieldName];

	module.exports = { FIELDS, FIELD_NAMES, getTerminalField };
});
