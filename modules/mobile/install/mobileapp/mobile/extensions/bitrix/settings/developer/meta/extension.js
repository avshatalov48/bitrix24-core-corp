/**
 * @module settings/developer/meta
 */
jn.define('settings/developer/meta', (require, exports, module) => {
	const DEV_SETTINGS = {
		enableReduxLogger: {
			id: 'enableReduxLogger',
			name: 'Enable Redux Logger',
			defaultValue: true,
		},
	};

	const STORAGE_PREFIX = 'settings.developer';

	module.exports = {
		DEV_SETTINGS,
		STORAGE_PREFIX,
	};
});
