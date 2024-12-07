/**
 * @module im/messenger/model
 */
jn.define('im/messenger/model', (require, exports, module) => {
	const { applicationModel } = require('im/messenger/model/application');
	const { recentModel, recentDefaultElement } = require('im/messenger/model/recent');
	const { counterModel } = require('im/messenger/model/counter');
	const { messagesModel, messageDefaultElement } = require('im/messenger/model/messages');
	const { usersModel, userDefaultElement } = require('im/messenger/model/users');
	const { dialoguesModel, dialogDefaultElement } = require('im/messenger/model/dialogues');
	const { filesModel, fileDefaultElement } = require('im/messenger/model/files');
	const { sidebarModel, sidebarDefaultElement } = require('im/messenger/model/sidebar');
	const { draftModel, draftDefaultElement } = require('im/messenger/model/draft');
	const { queueModel, queueDefaultElement } = require('im/messenger/model/queue');
	const { commentModel, commentDefaultElement } = require('im/messenger/model/comment');

	module.exports = {
		applicationModel,
		recentModel,
		counterModel,
		messagesModel,
		usersModel,
		dialoguesModel,
		filesModel,
		sidebarModel,
		draftModel,
		queueModel,
		commentModel,

		recentDefaultElement,
		messageDefaultElement,
		userDefaultElement,
		dialogDefaultElement,
		fileDefaultElement,
		sidebarDefaultElement,
		draftDefaultElement,
		queueDefaultElement,
		commentDefaultElement,
	};
});
