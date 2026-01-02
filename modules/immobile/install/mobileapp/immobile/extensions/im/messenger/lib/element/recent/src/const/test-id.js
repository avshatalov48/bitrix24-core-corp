/**
 * @module im/messenger/lib/element/recent/const/test-id
 */
jn.define('im/messenger/lib/element/recent/const/test-id', (require, exports, module) => {
	const CounterPrefix = Object.freeze({
		counter: 'counter',
	});

	const CounterValue = Object.freeze({
		unread: 'unread',
	});

	const CounterPostfix = Object.freeze({
		unmuted: 'unmuted',
		muted: 'muted',
		watch: 'watch',
	});

	module.exports = {
		CounterPrefix,
		CounterValue,
		CounterPostfix,
	};
});
