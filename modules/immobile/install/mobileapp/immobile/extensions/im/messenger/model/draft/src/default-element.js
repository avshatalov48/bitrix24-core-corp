/**
 * @module im/messenger/model/draft/src/default-element
 */

jn.define('im/messenger/model/draft/src/default-element', (require, exports, module) => {
	const { DraftType, MessageType } = require('im/messenger/const');

	const draftDefaultElement = Object.freeze({
		dialogId: 0,
		lastActivityDate: new Date(),
		messageId: 0,
		messageType: MessageType.text,
		type: DraftType.text,
		text: '',
		message: [],
		userName: '',
		image: null,
		video: null,
	});

	module.exports = {
		draftDefaultElement,
	};
});
