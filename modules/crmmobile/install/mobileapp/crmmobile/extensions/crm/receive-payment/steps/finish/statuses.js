/**
 * @module crm/receive-payment/steps/finish/statuses
 */
jn.define('crm/receive-payment/steps/finish/statuses', (require, exports, module) => {
	const Statuses = {
		NONE: 0,
		STARTED: 1,
		FINISHING: 2,
		FINISHED: 3,
		ERROR: 4,
	};

	module.exports = { Statuses };
});
