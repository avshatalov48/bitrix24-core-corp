/**
 * @module im/messenger/const/dialog-type
 */
jn.define('im/messenger/const/dialog-type', (require, exports, module) => {
	const DialogType = Object.freeze({
		user: 'user',
		chat: 'chat',
		open: 'open',
		lines: 'lines',
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
		private: 'private',
		copilot: 'copilot',
		default: 'default',
		comment: 'comment',
		channel: 'channel',
		openChannel: 'openChannel',
		generalChannel: 'generalChannel',
		collab: 'collab',
	});

	const DialogWidgetType = Object.freeze({
		chat: 'messenger',
		copilot: 'copilot',
		collab: 'collab',
	});

	module.exports = {
		DialogType,
		DialogWidgetType,
	};
});
