/**
 * @module crm/entity-chat-opener
 */
jn.define('crm/entity-chat-opener', (require, exports, module) => {
	let DialogOpener = null;

	try
	{
		DialogOpener = require('im/messenger/api/dialog-opener').DialogOpener;
	}
	catch (err)
	{
		console.warn('Cannot get DialogOpener module', err);
	}
	const { NotifyManager } = require('notify-manager');
	const { Type: CrmType } = require('crm/type');
	const { Type: CoreType } = require('type');

	/**
	 * @class EntityChatOpener
	 */
	class EntityChatOpener
	{
		/**
		 * @public
		 * @param {number} entityTypeId
		 * @param {number} entityId
		 * @param {bool} [useLoadingIndicator=true]
		 * @returns {Promise|null}
		 */
		static getChatId(entityTypeId, entityId, useLoadingIndicator = true)
		{
			if (CoreType.isNil(entityId))
			{
				console.error('entityId is not defined');

				return null;
			}

			if (!CrmType.existsById(entityTypeId))
			{
				console.error('Such entityTypeId does not exist');

				return null;
			}

			return new Promise((resolve, reject) => {
				const data = {
					data: {
						entityId,
						entityTypeId,
					},
				};

				const successCallback = (response) => {
					if (useLoadingIndicator)
					{
						NotifyManager.hideLoadingIndicatorWithoutFallback();
					}
					const chatId = parseInt(response.data.chatId, 10);
					resolve(chatId);
				};

				const errorCallback = (error) => {
					if (useLoadingIndicator)
					{
						NotifyManager.hideLoadingIndicatorWithoutFallback();
					}
					NotifyManager.showDefaultError();
					console.error(error);
					reject(error);
				};

				if (useLoadingIndicator)
				{
					NotifyManager.showLoadingIndicator();
				}
				BX.ajax.runAction('crm.timeline.chat.get', data)
					.then(successCallback, errorCallback)
					.catch(({ errors }) => {
						console.error(errors);
						reject(errors);
					});
			});
		}

		/**
		 * @public
		 * @param {number} chatId
		 */
		static openById(chatId)
		{
			if (CoreType.isNil(chatId))
			{
				console.error('chatId not defined');

				return;
			}
			const dialogParams = {
				dialogId: `chat${chatId}`,
			};
			if (DialogOpener !== null)
			{
				DialogOpener.open(dialogParams);
			}
		}

		/**
		 * @public
		 * @param {number} entityTypeId
		 * @param {number} entityId
		 * @param {bool} [useLoadingIndicator=true]
		 * @returns {Promise|null}
		 */
		static openChatByEntity(entityTypeId, entityId, useLoadingIndicator = true)
		{
			if (CoreType.isNil(entityId))
			{
				console.error('entityId is not defined');

				return null;
			}

			if (!CrmType.existsById(entityTypeId))
			{
				console.error('Such entityTypeId does not exist');

				return null;
			}

			return new Promise((resolve, reject) => {
				EntityChatOpener.getChatId(entityTypeId, entityId, useLoadingIndicator)
					.then(
						(chatId) => {
							EntityChatOpener.openById(chatId);
							resolve();
						},
						(error) => {
							console.error(error);
							reject(error);
						},
					)
					.catch(({ errors }) => {
						console.error(errors);
						reject(errors);
					});
			});
		}
	}

	module.exports = { EntityChatOpener };
});
