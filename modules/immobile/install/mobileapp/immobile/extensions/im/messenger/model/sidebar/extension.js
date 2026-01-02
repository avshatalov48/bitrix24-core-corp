/**
 * @module im/messenger/model/sidebar
 */
jn.define('im/messenger/model/sidebar', (require, exports, module) => {
	const { sidebarModel } = require('im/messenger/model/sidebar/src/model');
	const { sidebarDefaultElement } = require('im/messenger/model/sidebar/src/default-element');

	module.exports = {
		sidebarModel,
		sidebarDefaultElement,
	};
});
