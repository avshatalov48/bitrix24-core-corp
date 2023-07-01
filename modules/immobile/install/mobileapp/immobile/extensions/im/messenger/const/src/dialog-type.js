/**
 * @module im/messenger/const/dialog-type
 */
jn.define('im/messenger/const/dialog-type', (require, exports, module) => {

	const DialogType = Object.freeze({
		user: 'user',
		chat: 'chat',
		open: 'open',
		general: 'general',
		videoconf: 'videoconf',
		announcement: 'announcement',
		call: 'call',
		support24Notifier: 'support24Notifier',
		support24Question: 'support24Question',
		crm: 'crm',
		sonetGroup: 'sonetGroup',
		calendar: 'calendar',
		tasks: 'tasks',
		thread: 'thread',
		mail: 'mail',
	});

	module.exports = { DialogType };
});
