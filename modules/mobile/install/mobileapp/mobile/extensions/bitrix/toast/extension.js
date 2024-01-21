/**
 * @module toast
 */
jn.define('toast', (require, exports, module) => {
	const { showToast } = require('toast/base');
	const { showOfflineToast } = require('toast/offline');
	const { showRemoveToast } = require('toast/remove');

	module.exports = {
		showToast,
		showOfflineToast,
		showRemoveToast,
	};
});
