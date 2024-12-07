/**
 * @module im/messenger/lib/element/dialog/message/call/const/type
 */
jn.define('im/messenger/lib/element/dialog/message/call/const/type', (require, exports, module) => {
	const CallMessageType = Object.freeze({
		START: 'START',
		FINISH: 'FINISH',
		BUSY: 'BUSY',
		DECLINED: 'DECLINED',
		MISSED: 'MISSED',
	});

	module.exports = {
		CallMessageType,
	};
});
