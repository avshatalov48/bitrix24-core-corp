/**
 * @module im/messenger/model/anchor/default-element
 */

jn.define('im/messenger/model/anchor/default-element', (require, exports, module) => {
	const anchorDefaultElement = Object.freeze({
		chatId: 0,
		dialogId: 0,
		fromUserId: 0,
		messageId: 0,
		parentChatId: 0,
		parentMessageId: 0,
		subType: null,
		type: null,
		userId: 0,
	});

	module.exports = {
		anchorDefaultElement,
	};
});
