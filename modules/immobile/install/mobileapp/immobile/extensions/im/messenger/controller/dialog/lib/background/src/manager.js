/**
 * @module im/messenger/controller/dialog/lib/background/manager
 */
jn.define('im/messenger/controller/dialog/lib/background/manager', (require, exports, module) => {
	const { Theme } = require('im/lib/theme');

	const { DialogBackgroundId } = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');
	const { getLogger } = require('im/messenger/lib/logger');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { BackgroundConfiguration } = require('im/messenger/controller/dialog/lib/background/configuration');

	const logger = getLogger('dialog--dialog');

	/**
	 * @class BackgroundManager
	 */
	class BackgroundManager
	{
		/**
		 * @constructor
		 * @param {DialogId} dialogId
		 * @param {DialogLocator} dialogLocator
		 */
		constructor({ dialogId, dialogLocator })
		{
			/** @type {DialogLocator} */
			this.dialogLocator = dialogLocator;
			/** @type {DialogId} */
			this.dialogId = dialogId;
			/** @type {MessengerCoreStore} */
			this.store = this.dialogLocator.get('store');
			/** @type {string} */
			this.themeId = Theme.getInstance().getId();
		}

		/**
		 * @return {BackgroundConfiguration}
		 */
		getConfiguration()
		{
			if (!Feature.isDialogBackgroundAvailable || !this.isAvailableByDialogType())
			{
				return {};
			}

			const isAiAssistant = DialogHelper.createByDialogId(this.dialogId)?.isAiAssistant;
			if (isAiAssistant)
			{
				return BackgroundConfiguration[this.getThemeId()][DialogBackgroundId.aiAssistant];
			}

			const dialogBackgroundId = this.store.getters['dialoguesModel/getBackgroundId'](this.dialogId);
			if (BackgroundConfiguration[this.getThemeId()][dialogBackgroundId])
			{
				return BackgroundConfiguration[this.getThemeId()][dialogBackgroundId];
			}

			const userBackgroundId = this.store.getters['usersModel/getBotBackgroundId'](this.dialogId);
			if (BackgroundConfiguration[this.getThemeId()][userBackgroundId])
			{
				return BackgroundConfiguration[this.getThemeId()][userBackgroundId];
			}

			return {};
		}

		/**
		 * @return {boolean}
		 */
		isAvailableByDialogType()
		{
			const dialogHelper = DialogHelper.createByDialogId(this.dialogId);
			if (!dialogHelper)
			{
				return false;
			}

			return !(dialogHelper.isCollab || dialogHelper.isCopilot);
		}

		/**
		 * @return {string}
		 */
		getThemeId()
		{
			return this.themeId;
		}

		/**
		 * @void
		 */
		update()
		{
			if (!Feature.isDialogBackgroundAvailable || !this.isAvailableByDialogType())
			{
				return;
			}

			try
			{
				const newConfiguration = this.getConfiguration();
				const view = this.dialogLocator.get('view');
				view.setBackground(newConfiguration);
			}
			catch (error)
			{
				logger.warn(`${this.constructor.name}.update.catch:`, error);
			}
		}
	}

	module.exports = {
		BackgroundManager,
	};
});
