/**
 * @module im/messenger/const/recent
 */
jn.define('im/messenger/const/recent', (require, exports, module) => {
	const ChatTypes = Object.freeze({
		chat: 'chat',
		open: 'open',
		user: 'user',
		notification: 'notification',
	});

	const RecentTab = Object.freeze({
		chat: 'default',
		copilot: 'copilot',
		openChannel: 'openChannel',
		collab: 'collab',
		tasksTask: 'tasksTask',
	});

	const MessageStatus = Object.freeze({
		received: 'received',
		delivered: 'delivered',
		error: 'error',
	});

	const SubTitleIconType = Object.freeze({
		reply: 'reply',
		wait: 'wait',
		error: 'error',
	});

	module.exports = {
		ChatTypes,
		RecentTab,
		MessageStatus,
		SubTitleIconType,
	};
});
