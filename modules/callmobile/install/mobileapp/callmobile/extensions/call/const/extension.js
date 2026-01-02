/**
 * @module call/const
 */
jn.define('call/const', (require, exports, module) => {
	const { Analytics } = require('call/const/analytics');
	const { EventType } = require('call/const/event-type');
	const { DialogType } = require('call/const/dialog-type');

	module.exports = {
		Analytics,
		EventType,
		DialogType,
	};
});
