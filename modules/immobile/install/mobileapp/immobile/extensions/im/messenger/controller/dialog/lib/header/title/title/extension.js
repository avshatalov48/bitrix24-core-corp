/**
 * @module im/messenger/controller/dialog/lib/header/title/title
 */
jn.define('im/messenger/controller/dialog/lib/header/title/title', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');

	const { AppStatus, DialogType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { UserUtils } = require('im/messenger/lib/utils');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');
	const { ChatAvatar } = require('im/messenger/lib/element/chat-avatar');
	const {
		DialogHelper,
		UserHelper,
	} = require('im/messenger/lib/helper');

	/**
	 * @class HeaderTitle
	 * @implements IDialogHeaderTitle
	 */
	class HeaderTitle
	{
		/**
		 * @param {() => DialoguesModelState} getDialog
		 * @param {RelatedEntityData} relatedEntity
		 */
		constructor({ getDialog, relatedEntity })
		{
			/**
			 * @protected
			 * @type {() => DialoguesModelState} */
			this.getDialog = getDialog;

			/**
			 * @protected
			 * @type {RelatedEntityData}
			 * */
			this.relatedEntity = relatedEntity;
		}

		/**
		 * @return JNWidgetTitleParams
		 */
		createTitleParams()
		{
			const dialogId = this.getDialog()?.dialogId;

			if (!dialogId)
			{
				return {};
			}

			const avatar = ChatAvatar.createFromDialogId(dialogId);
			const title = ChatTitle.createFromDialogId(dialogId);
			const result = {
				...avatar.getTitleParams(),
				...title.getTitleParams({
					useNotes: true,
				}),
			};

			if (avatar.type !== DialogType.comment)
			{
				result.avatar = avatar.getDialogHeaderAvatarProps();
			}

			if (UserHelper.isCurrentUser(dialogId))
			{
				result.detailText = '';

				return result;
			}

			let status = '';
			if (DialogHelper.isChatId(dialogId) && !result.hasInputActions)
			{
				const userData = serviceLocator.get('core').getStore().getters['usersModel/getById'](dialogId);
				status = userData?.status ? (new UserUtils()).getLastDateText(userData) : result.detailText;
			}

			let detailLottie = null;
			const appStatus = serviceLocator.get('core').getAppStatus();
			switch (appStatus)
			{
				case AppStatus.networkWaiting:
					status = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_HEADER_TITLE_NETWORK_WAITING');
					break;

				case AppStatus.connection:
					status = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_HEADER_TITLE_CONNECTION');
					break;

				case AppStatus.sync:
					status = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_HEADER_TITLE_SYNC');
					break;

				default:
					detailLottie = result.detailLottie;
					break;
			}

			if (status)
			{
				result.detailLottie = detailLottie;
				result.detailText = status;
			}

			return result;
		}
	}

	module.exports = {
		HeaderTitle,
	};
});
