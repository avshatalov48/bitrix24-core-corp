/**
 * @module im/messenger/lib/converter
 */
jn.define('im/messenger/lib/converter', (require, exports, module) => {

	const { RecentConverter } = require('im/messenger/lib/converter/recent');
	const { DialogConverter } = require('im/messenger/lib/converter/dialog');
	const { SearchConverter } = require('im/messenger/lib/converter/search');

	module.exports = {
		RecentConverter,
		DialogConverter,
		SearchConverter,
	};
});
