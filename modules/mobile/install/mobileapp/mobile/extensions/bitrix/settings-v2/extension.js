/**
 * @module settings-v2
 */
jn.define('settings-v2', (require, exports, module) => {
	const { SettingsPageId } = require('settings-v2/const');
	const { openSettings } = require('settings-v2/manager');

	module.exports = {
		openSettings,
		SettingsPageId,
	};
});
