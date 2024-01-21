/**
 * @module bizproc/fields/types/crm
 */
jn.define('bizproc/fields/types/crm', (require, exports, module) => {

	const CrmType = {
		EMAIL: 'email',
		PHONE: 'phone',
		WEB: 'web',
		IM: 'im',
		DEAL_CATEGORY: 'deal_category',
		DEAL_STAGE: 'deal_stage',
		LEAD_STATUS: 'lead_status',
		SMS_SENDER: 'sms_sender',
		MAIL_SENDER: 'mail_sender',
	};

	module.exports = { CrmType };
});
