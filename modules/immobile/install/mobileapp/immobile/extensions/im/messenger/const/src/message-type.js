/**
 * @module im/messenger/const/message-type
 */
jn.define('im/messenger/const/message-type', (require, exports, module) => {

	const MessageType = Object.freeze({
		text: 'text',
		audio: 'audio',
		image: 'image',
	});

	module.exports = { MessageType };
});
