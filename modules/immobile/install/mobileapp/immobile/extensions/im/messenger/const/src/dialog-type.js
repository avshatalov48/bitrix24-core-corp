/**
 * @module im/messenger/const/dialog-type
 */
jn.define('im/messenger/const/dialog-type', (require, exports, module) => {

	const DialogType = Object.freeze({
		private: 'private',
		chat: 'chat',
		open: 'open',
		call: 'call',
		crm: 'crm',
	});

	module.exports = { DialogType };
});
