/**
 * @module toast
 */
jn.define('toast', (require, exports, module) => {
	const { showToast, showSafeToast, Position } = require('toast/base');
	const { showErrorToast } = require('toast/error');
	const { showOfflineToast } = require('toast/offline');
	const { showRemoveToast } = require('toast/remove');

	module.exports = {
		showToast,
		showSafeToast,
		showErrorToast,
		showOfflineToast,
		showRemoveToast,
		Position,
	};
});
