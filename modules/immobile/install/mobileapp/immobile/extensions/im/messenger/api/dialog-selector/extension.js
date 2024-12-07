/**
 * @module im/messenger/api/dialog-selector
 */
jn.define('im/messenger/api/dialog-selector', (require, exports, module) => {

	const { buildApplication } = require('im/messenger/core/embedded');

	const { DialogSelector } = require('im/messenger/api/dialog-selector/controller');

	module.exports = buildApplication({
		exports: {
			DialogSelector,
		},
		appConfig: {
			localStorage: {
				enable: false,
			},
		},
	});
});
