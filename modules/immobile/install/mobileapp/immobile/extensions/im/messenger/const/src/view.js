/**
 * @module im/messenger/const/view
 */
jn.define('im/messenger/const/view', (require, exports, module) => {

	const ViewName = Object.freeze({
		dialog: 'dialog',
		dialogTextField: 'dialogTextField',
		dialogMentionPanel: 'dialogMentionPanel',
		dialogPinPanel: 'dialogPinPanel',
		dialogCommentsButton: 'dialogCommentsButton',
		dialogActionPanel: 'dialogActionPanel',
		dialogStatusField: 'dialogStatusField',
		dialogChatJoinButton: 'dialogChatJoinButton',
		dialogSelector: 'dialogSelector',
		dialogRestrictions: 'dialogRestrictions',

		recent: 'recent',
	});

	module.exports = {
		ViewName,
	};
});
