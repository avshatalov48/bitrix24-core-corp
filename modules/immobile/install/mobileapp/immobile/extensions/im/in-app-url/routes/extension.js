/**
 * @module im/in-app-url/routes
 */
jn.define('im/in-app-url/routes', (require, exports, module) => {
	const { Loc } = require('loc');

	const {
		EventType,
	} = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');

	const openDialog = (dialogId) => {
		MessengerEmitter.emit(EventType.messenger.openDialog, { dialogId });
	};

	const openMessageAttach = (messageId) => {
		const realUrl = `${currentDomain}/mobile/im/attach.php?messageId=${messageId}`;

		PageManager.openPage({
			url: realUrl,
			titleParams: {
				text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_ATTACH_TITLE'),
			},
			backdrop: {
				horizontalSwipeAllowed: false,
			},
		});
	};

	/**
	 * @param {InAppUrl} inAppUrl
	 */
	module.exports = (inAppUrl) => {
		inAppUrl.register(
			'/online/\\?IM_DIALOG=:dialogId',
			({ dialogId }) => openDialog(dialogId),
		).name('im:dialog:open');

		inAppUrl.register(
			'/immobile/in-app/message-attach/:messageId',
			({ messageId }) => openMessageAttach(messageId),
		).name('im:message:attach:open');
	};
});
