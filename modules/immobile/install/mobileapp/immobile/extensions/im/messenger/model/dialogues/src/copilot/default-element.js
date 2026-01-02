/**
 * @module im/messenger/model/dialogues/copilot/default-element
 */

jn.define('im/messenger/model/dialogues/copilot/default-element', (require, exports, module) => {
	const copilotDefaultElement = Object.freeze({
		dialogId: 'chat0',
		roles: {},
		aiProvider: '',
		chats: [],
		messages: [],
	});

	module.exports = {
		copilotDefaultElement,
	};
});
