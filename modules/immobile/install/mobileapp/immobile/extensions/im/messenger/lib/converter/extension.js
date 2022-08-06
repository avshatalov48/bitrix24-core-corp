/**
 * @module im/messenger/lib/converter
 */
jn.define('im/messenger/lib/converter', (require, exports, module) => {

	const { RecentConverter } = jn.require('im/messenger/lib/converter/recent');
	const { DialogConverter } = jn.require('im/messenger/lib/converter/dialog');
	const { SearchConverter } = jn.require('im/messenger/lib/converter/search');

	module.exports = {
		RecentConverter,
		DialogConverter,
		SearchConverter,
	};
});
