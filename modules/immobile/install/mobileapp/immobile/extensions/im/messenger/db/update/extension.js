/**
 * @module im/messenger/db/update
 */
jn.define('im/messenger/db/update', (require, exports, module) => {
	const { Updater } = require('im/messenger/db/update/updater');
	const { Version } = require('im/messenger/db/update/version');
	const { updateDatabase } = require('im/messenger/db/update/update');

	module.exports = {
		Updater,
		Version,
		updateDatabase,
	};
});
