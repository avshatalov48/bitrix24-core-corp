/**
 * @module im/messenger/lib/element/dialog/message/check-in/const/type
 */
jn.define('im/messenger/lib/element/dialog/message/check-in/const/type', (require, exports, module) => {
	const CheckInType = Object.freeze({
		withLocation: 'withLocation',
		withoutLocation: 'withoutLocation',
	});

	module.exports = {
		CheckInType,
	};
});
