/* eslint-disable consistent-return */
/**
 * @module tasks/layout/online
 */
jn.define('tasks/layout/online', (require, exports, module) => {
	const { isOnline } = require('device/connection');
	const { showOfflineToast } = require('toast');

	/**
	 * Executes a callback if we are online, otherwise shows a popup message saying we are offline.
	 * @param {Function} callback
	 * @param {Object} [layoutWidget]
	 * @param {Object} [toastParams]
	 */
	const executeIfOnline = (callback, layoutWidget = layout, toastParams = {}) => {
		if (isOnline())
		{
			return callback();
		}

		showOfflineToast(toastParams, layoutWidget);

		return Promise.reject(new Error('Offline mode is active. Cannot execute the action.'));
	};

	module.exports = {
		executeIfOnline,
	};
});
