/**
 * @module im/messenger/controller/dialog/lib/header/title
 */
jn.define('im/messenger/controller/dialog/lib/header/title', (require, exports, module) => {
	const { Loc } = require('loc');
	const { isEqual } = require('utils/object');

	const { AppStatus } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { UserUtils } = require('im/messenger/lib/utils');
	const { ChatAvatar, ChatTitle } = require('im/messenger/lib/element');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { Logger } = require('im/messenger/lib/logger');

	/**
	 * @class HeaderTitle
	 */
	class HeaderTitle
	{
		/**
		 *
		 * @param {MessengerCoreStore} store
		 * @param {DialogView} view
		 * @param {number|string} dialogId
		 */
		constructor({ store, view, dialogId })
		{
			/** @private */
			this.store = store;

			/** @private */
			this.view = view;

			/** @private */
			this.dialogId = dialogId;

			/** @private */
			this.timerId = null;

			/** @private */
			this.titleParams = null;
		}

		/**
		 * @param {string|number} dialogId
		 * @param {MessengerCoreStore} store
		 * @return {DialogHeaderTitleParams}
		 */
		static createTitleParams(dialogId, store)
		{
			const avatar = ChatAvatar.createFromDialogId(dialogId);
			const title = ChatTitle.createFromDialogId(dialogId);
			const result = {
				...avatar.getTitleParams(),
				avatar: avatar.getDialogHeaderAvatarProps(),
				...title.getTitleParams(),
			};

			let status = '';
			if (DialogHelper.isChatId(dialogId) && !result.isWriting)
			{
				status = (new UserUtils()).getLastDateText(store.getters['usersModel/getById'](dialogId));
			}

			const appStatus = serviceLocator.get('core').getAppStatus();
			switch (appStatus)
			{
				case AppStatus.networkWaiting:
					status = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_HEADER_NETWORK_WAITING');
					break;

				case AppStatus.connection:
					status = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_HEADER_CONNECTION');
					break;

				case AppStatus.sync:
					status = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_HEADER_SYNC');
					break;

				default:
					break;
			}

			if (status)
			{
				result.detailText = status;
			}

			return result;
		}

		startRender()
		{
			this.renderTitle();

			this.timerId = setInterval(this.renderTitle.bind(this), 60000);

			return this;
		}

		stopRender()
		{
			clearInterval(this.timerId);

			return this;
		}

		renderTitle()
		{
			const titleParams = HeaderTitle.createTitleParams(this.dialogId, this.store);
			if (!isEqual(this.titleParams, titleParams))
			{
				Logger.info('Dialog._redrawHeader: before: ', this.titleParams, ' after: ', titleParams);

				this.view.setTitle(titleParams);
				this.titleParams = titleParams;

				return this;
			}
			Logger.info('Dialog._redrawHeader: header is up-to-date, redrawing is cancelled.');

			return this;
		}
	}

	module.exports = { HeaderTitle };
});
