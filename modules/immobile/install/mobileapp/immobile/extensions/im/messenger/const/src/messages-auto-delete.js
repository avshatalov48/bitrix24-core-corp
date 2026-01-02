/**
 * @module im/messenger/const/messages-auto-delete
 */
jn.define('im/messenger/const/messages-auto-delete', (require, exports, module) => {

	const MessagesAutoDeleteDelay = {
		off: 0,
		hour: 1,
		day: 24,
		week: 168,
		month: 720,
	};

	const MessagesAutoDeleteMenuIds = {
		off: 'off',
		hour: 'hour',
		day: 'day',
		week: 'week',
		month: 'month',
		notEnoughRights: 'not-enough-rights',
		helpdesk: 'helpdesk',
	};

	module.exports = { MessagesAutoDeleteDelay, MessagesAutoDeleteMenuIds };
});
