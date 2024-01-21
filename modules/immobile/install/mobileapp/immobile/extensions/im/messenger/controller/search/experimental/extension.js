/**
 * @module im/messenger/controller/search/experimental
 */
jn.define('im/messenger/controller/search/experimental', (require, exports, module) => {
	const { RecentSelector } = require('im/messenger/controller/search/experimental/selector');
	const { RecentProvider } = require('im/messenger/controller/search/experimental/provider');
	const { RecentConfig } = require('im/messenger/controller/search/experimental/config');

	module.exports = { RecentSelector, RecentProvider, RecentConfig };
});