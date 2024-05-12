/**
 * @module im/messenger/controller/dialog/lib/message-menu
 */
jn.define('im/messenger/controller/dialog/lib/message-menu', (require, exports, module) => {
	const { MessageMenu } = require('im/messenger/controller/dialog/lib/message-menu/message-menu');
	const { ActionType } = require('im/messenger/controller/dialog/lib/message-menu/action-type');

	module.exports = { MessageMenu, ActionType };
});
