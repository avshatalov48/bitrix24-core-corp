/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/controller/recent/lib
 */
jn.define('im/messenger/controller/recent/lib', (require, exports, module) => {
	const { BaseRecent } = require('im/messenger/controller/recent/lib/recent-base');
	const { RecentRenderer } = require('im/messenger/controller/recent/lib/renderer');
	const { ItemAction } = require('im/messenger/controller/recent/lib/item-action');

	module.exports = {
		BaseRecent,
		RecentRenderer,
		ItemAction,
	};
});
