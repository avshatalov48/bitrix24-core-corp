/**
 * @module im/messenger/provider/services/chat/update
 */
jn.define('im/messenger/provider/services/chat/update', (require, exports, module) => {
	const { RestMethod, MessagesAutoDeleteDelay } = require('im/messenger/const');
	const { runAction, callMethod } = require('im/messenger/lib/rest');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { Feature } = require('im/messenger/lib/feature');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	const logger = LoggerManager.getInstance().getLogger('update-service--chat');

	/**
	 * @class UpdateService
	 */
	class UpdateService
	{
		/**
		 * @desc rest update chat
		 * @param {DialogId} dialogId
		 * @param {object} options
		 * @return {Promise<{result:boolean}>}
		 */
		updateChat(dialogId, options)
		{
			const chatSettings = Application.storage.getObject('settings.chat', {
				historyShow: true,
			});

			const hideHistory = chatSettings.historyShow ? 'N' : 'Y';
			const fields = { hideHistory, ...options };

			return runAction(RestMethod.imV2ChatUpdate, {
				data: {
					dialogId,
					fields,
				},
			}).then(
				(response) => {
					if (response.result !== true)
					{
						logger.error(`${this.constructor.name}.restChatUpdate.error:`, response.result);

						return response.result;
					}
					logger.log(`${this.constructor.name}.restChatUpdate.result:`, response.result);

					return response.result;
				},
			);
		}

		/**
		 * @desc rest update avatar
		 * @param {DialogId} dialogId
		 * @param {string} avatarBase64
		 * @return {Promise<{result:boolean}>}
		 */
		updateAvatar(dialogId, avatarBase64)
		{
			return runAction(RestMethod.imV2ChatUpdateAvatar, {
				data: {
					dialogId,
					avatar: avatarBase64,
				},
			})
				.then(
					(response) => {
						if (response.result !== true)
						{
							logger.error(`${this.constructor.name}.restUpdateAvatar.error:`, response.result);

							return response.result;
						}
						logger.log(`${this.constructor.name}.restUpdateAvatar.result:`, response.result);

						return response.result;
					},
				);
		}

		/**
		 * @desc rest update title
		 * @param {DialogId} dialogId
		 * @param {string} title
		 * @return {Promise<{result:boolean}>}
		 */
		updateTitle(dialogId, title)
		{
			const params = {
				dialog_id: dialogId,
				title,
			};

			return callMethod(RestMethod.imChatUpdateTitle, params)
				.then(
					(result) => {
						if (result.error())
						{
							logger.error(`${this.constructor.name}.restUpdateTitle.error:`, result.error());

							return result.error();
						}
						logger.log(`${this.constructor.name}.restUpdateTitle.result:`, result.data());

						return result.data();
					},
				);
		}

		/**
		 * @desc rest update messages auto delete delay
		 * @param {Number} delay
		 * @param {DialogId} dialogId
		 * @returns {Promise|*}
		 */
		updateMessagesAutoDeleteDelay({ delay, dialogId })
		{
			return runAction(RestMethod.imv2ChatSetMessagesAutoDeleteDelay, {
				data: {
					dialogId,
					hours: delay,
				},
			})
				.then((data) => {
					const newDelay = data.messagesAutoDeleteConfigs?.[0]?.delay;
					if (newDelay !== delay && newDelay === MessagesAutoDeleteDelay.off)
					{
						Feature.updateExistingImFeatures({ messagesAutoDeleteEnabled: false });
						serviceLocator.get('core').getStore().dispatch('dialoguesModel/update', {
							dialogId,
							fields: { messagesAutoDeleteDelay: MessagesAutoDeleteDelay.off },
						});
					}

					return newDelay;
				})
			;
		}
	}

	module.exports = { UpdateService };
});
