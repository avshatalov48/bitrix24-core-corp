/**
 * @module crm/receive-payment/steps/send-message/sender-codes
 */
jn.define('crm/receive-payment/steps/send-message/sender-codes', (require, exports, module) => {
	const SenderCodes = {
		BITRIX24: 'bitrix24',
		SMS_PROVIDER: 'sms_provider',
	};

	module.exports = { SenderCodes };
});
