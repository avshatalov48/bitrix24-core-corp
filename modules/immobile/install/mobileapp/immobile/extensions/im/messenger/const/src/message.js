/**
 * @module im/messenger/const/message
 */
jn.define('im/messenger/const/message', (require, exports, module) => {
	const MessageType = Object.freeze({
		text: 'text',
		audio: 'audio',
		image: 'image',
		status: 'status',
		systemText: 'system-text',
	});

	const MessageIdType = {
		statusMessage: 'status-message',
		templateSeparatorUnread: 'template-separator-unread',
		templateSeparatorDate: 'template-separator',
	};

	const OwnMessageStatus = Object.freeze({
		sending: 'sending',
		sent: 'sent',
		viewed: 'viewed',
	});

	module.exports = {
		MessageType,
		MessageIdType,
		OwnMessageStatus,
	};
});
