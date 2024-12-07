/**
 * @module im/messenger/provider/data
 */
jn.define('im/messenger/provider/data', (require, exports, module) => {
	const { ChatDataProvider } = require('im/messenger/provider/data/chat');
	const { RecentDataProvider } = require('im/messenger/provider/data/recent');
	const { DataProviderResult } = require('im/messenger/provider/data/result');

	module.exports = {
		ChatDataProvider,
		RecentDataProvider,

		DataProviderResult,
	};
});
