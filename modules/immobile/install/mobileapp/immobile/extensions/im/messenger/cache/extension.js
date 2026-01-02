/**
 * @module im/messenger/cache
 */
jn.define('im/messenger/cache', (require, exports, module) => {
	const { ShareDialogCache } = require('im/messenger/cache/share-dialog');

	const { MapCache } = require('im/messenger/cache/simple-wrapper/map-cache');

	const { RecentViewCache } = require('im/messenger/cache/shared-storage/recent');

	module.exports = {
		ShareDialogCache,
		MapCache,
		RecentViewCache,
	};
});
