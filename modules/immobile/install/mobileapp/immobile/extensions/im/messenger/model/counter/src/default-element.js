/**
 * @module im/messenger/model/counter/default-element
 */
jn.define('im/messenger/model/counter/default-element', (require, exports, module) => {
	const { CounterType } = require('im/messenger/const');

	const counterDefaultElement = Object.freeze({
		chatId: 0,
		parentChatId: 0,
		type: CounterType.chat,
		counter: 0,
		locked: false,
		disabled: false,
	});

	module.exports = {
		counterDefaultElement,
	};
});
