/**
 * @module im/messenger/const/message-menu-action-type
 */
jn.define('im/messenger/const/message-menu-action-type', (require, exports, module) => {
	const MessageMenuActionType = Object.freeze({
		reaction: 'reaction',
		reply: 'reply',
		copy: 'copy',
		copyLink: 'copy-link',
		pin: 'pin',
		unpin: 'unpin',
		forward: 'forward',
		profile: 'profile',
		edit: 'edit',
		delete: 'delete',
		downloadToDisk: 'download-to-disk',
		downloadToDevice: 'download-to-device',
		create: 'create',
		feedback: 'feedback',
		subscribe: 'subscribe',
		unsubscribe: 'unsubscribe',
		multiselect: 'multiselect',
		resend: 'resend',
		finishVote: 'finish-vote',
		revote: 'revote',
		openVoteResult: 'open-vote-result',
		goToMessage: 'go-to-message',
	});

	module.exports = { MessageMenuActionType };
});
