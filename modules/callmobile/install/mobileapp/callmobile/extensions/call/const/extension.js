/**
 * @module call/const
 */
jn.define('call/const', (require, exports, module) => {
	const { Analytics } = require('call/const/analytics');
	const { DialogType } = require('call/const/dialog-type');

	module.exports = {
		Analytics,
		DialogType,
	};
});
