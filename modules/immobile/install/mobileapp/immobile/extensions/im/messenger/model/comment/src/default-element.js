/**
 * @module im/messenger/model/comment/default-element
 */

jn.define('im/messenger/model/comment/default-element', (require, exports, module) => {
	const commentDefaultElement = Object.freeze({
		chatId: 0,
		dialogId: 0,
		lastUserIds: [],
		messageCount: 0,
		messageId: 0,
		isUserSubscribed: false,
		showLoader: false,
	});

	module.exports = {
		commentDefaultElement,
	};
});
