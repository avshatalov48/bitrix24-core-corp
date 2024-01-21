/**
 * @module im/messenger/model
 */
jn.define('im/messenger/model', (require, exports, module) => {
	const { applicationModel } = require('im/messenger/model/application');
	const { recentModel } = require('im/messenger/model/recent');
	const { messagesModel } = require('im/messenger/model/messages');
	const { usersModel } = require('im/messenger/model/users');
	const { dialoguesModel } = require('im/messenger/model/dialogues');
	const { filesModel } = require('im/messenger/model/files');
	const { sidebarModel } = require('im/messenger/model/sidebar');
	const { draftModel } = require('im/messenger/model/draft');
	const { queueModel } = require('im/messenger/model/queue');

	module.exports = {
		applicationModel,
		recentModel,
		messagesModel,
		usersModel,
		dialoguesModel,
		filesModel,
		sidebarModel,
		draftModel,
		queueModel,
	};
});
