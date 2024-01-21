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
	 */
	const executeIfOnline = (callback, layoutWidget = layout) => {
		if (isOnline())
		{
			return callback();
		}

		showOfflineToast({}, layoutWidget);
	};

	module.exports = {
		executeIfOnline,
	};
});
