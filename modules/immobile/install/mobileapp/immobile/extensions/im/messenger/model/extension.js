/**
 * @module im/messenger/model
 */
jn.define('im/messenger/model', (require, exports, module) => {

	const { applicationModel } = jn.require('im/messenger/model/application');
	const { recentModel } = jn.require('im/messenger/model/recent');
	const { messagesModel } = jn.require('im/messenger/model/messages');
	const { usersModel } = jn.require('im/messenger/model/users');
	const { dialoguesModel } = jn.require('im/messenger/model/dialogues');
	const { filesModel } = jn.require('im/messenger/model/files');

	module.exports = {
		applicationModel,
		recentModel,
		messagesModel,
		usersModel,
		dialoguesModel,
		filesModel,
	};
});
