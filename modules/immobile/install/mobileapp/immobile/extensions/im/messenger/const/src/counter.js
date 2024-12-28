/**
 * @module im/messenger/const/counter
 */
jn.define('im/messenger/const/counter', (require, exports, module) => {
	const CounterType = Object.freeze({
		chat: 'chat',
		comment: 'comment',
		copilot: 'copilot',
		openline: 'openline',
		collab: 'collab',
	});

	module.exports = {
		CounterType,
	};
});
