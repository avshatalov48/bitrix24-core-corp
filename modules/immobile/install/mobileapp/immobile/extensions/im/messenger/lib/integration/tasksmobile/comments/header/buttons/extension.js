/**
 * @module im/messenger/lib/integration/tasksmobile/comments/header/buttons
 */
jn.define('im/messenger/lib/integration/tasksmobile/comments/header/buttons', (require, exports, module) => {
	const { isOnline } = require('device/connection');
	const { DialogHeaderButtons } = require('im/messenger/api/dialog-integration/header/buttons');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');
	const {
		CallAudioButton,
		CallVideoButton,
	} = require('im/messenger/controller/dialog/lib/header/buttons/buttons/button-configuration');
	const { CallManager } = require('im/messenger/lib/integration/callmobile/call-manager');
	// const { Analytics } = require('im/messenger/const');

	/**
	 * @class CommentsHeaderButtons
	 */
	class CommentsHeaderButtons extends DialogHeaderButtons
	{
		/**
		 * @param {() => DialoguesModelState} getDialog
		 * @param {RelatedEntityData} relatedEntity
		 */
		constructor({ getDialog, relatedEntity })
		{
			super({ getDialog, relatedEntity });

			/** @private */
			this.store = serviceLocator.get('core').getStore();
		}

		/**
		 * @return {DialogId}
		 */
		get dialogId()
		{
			return this.getDialog().dialogId;
		}

		/**
		 * @protected
		 * @return {Array<DialogHeaderButton>}
		 */
		getButtons()
		{
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (!dialogData || !ChatPermission.canCall(dialogData))
			{
				return [];
			}

			return [CallVideoButton, CallAudioButton];
		}

		/**
		 * @protected
		 * @param {string} buttonId
		 * @return void
		 */
		tapHandler(buttonId)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			switch (buttonId)
			{
				case CallVideoButton.id:
					// CallManager.getInstance().sendAnalyticsEvent(
					// 	this.dialogId,
					// 	Analytics.Element.videocall,
					// 	Analytics.Section.chatWindow,
					// );
					CallManager.getInstance().createVideoCall(this.dialogId);
					break;

				case CallAudioButton.id:
					// CallManager.getInstance().sendAnalyticsEvent(
					// 	this.dialogId,
					// 	Analytics.Element.audiocall,
					// 	Analytics.Section.chatWindow,
					// );
					CallManager.getInstance().createAudioCall(this.dialogId);
					break;

				default:
					break;
			}
		}
	}

	module.exports = {
		CommentsHeaderButtons,
	};
});
