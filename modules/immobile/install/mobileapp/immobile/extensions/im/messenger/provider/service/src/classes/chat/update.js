/**
 * @module im/messenger/provider/service/classes/chat/update
 */
jn.define('im/messenger/provider/service/classes/chat/update', (require, exports, module) => {
	const { RestMethod } = require('im/messenger/const');
	const { runAction } = require('im/messenger/lib/rest');
	const { LoggerManager } = require('im/messenger/lib/logger');

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
			return runAction(RestMethod.imV2ChatSetTitle, {
				data: {
					dialogId,
					title,
				},
			})
				.then(
					(response) => {
						if (response !== true)
						{
							logger.error(`${this.constructor.name}.restUpdateTitle.error:`, response);

							return response;
						}
						logger.log(`${this.constructor.name}.restUpdateTitle.result:`, response);

						return response;
					},
				);
		}
	}

	module.exports = { UpdateService };
});
