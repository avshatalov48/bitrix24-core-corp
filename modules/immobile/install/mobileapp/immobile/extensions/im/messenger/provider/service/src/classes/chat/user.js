/**
 * @module im/messenger/provider/service/classes/chat/user
 */
jn.define('im/messenger/provider/service/classes/chat/user', (require, exports, module) => {
	const { Type } = require('type');
	const {
		RestMethod,
		UserRole,
		DialogType,
	} = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { runAction } = require('im/messenger/lib/rest');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('dialog--chat-service');

	/**
	 * @class UserService
	 */
	class UserService
	{
		/** @type {MessengerCoreStore} */
		#store;

		constructor()
		{
			this.#store = serviceLocator.get('core').getStore();
		}

		async joinChat(dialogId)
		{
			logger.warn(`UserService: join chat ${dialogId}`);

			if (!Type.isStringFilled(dialogId))
			{
				return Promise.reject(new Error('UserService.joinChat: dialogId is not provided'));
			}

			this.#store.dispatch('dialoguesModel/update', {
				dialogId,
				fields: {
					role: UserRole.member,
				},
			});

			return runAction(RestMethod.imV2ChatJoin, {
				data: {
					dialogId,
				},
			}).catch((error) => {
				// eslint-disable-next-line no-console
				logger.error('UserService.joinChat: error', error);

				this.#store.dispatch('dialoguesModel/update', {
					dialogId,
					fields: {
						role: UserRole.guest,
					},
				});
			});
		}

		/**
		 * @param {number} chatId
		 * @param {Array<number>} members
		 * @param {boolean} showHistory
		 *
		 * @return {Promise<*|T>}
		 */
		async addToChat(chatId, members, showHistory = true)
		{
			const dialog = this.#store.getters['dialoguesModel/getByChatId'](chatId);
			if (!dialog)
			{
				return Promise.reject(new Error('ChatService.addToChat: unknown dialog'));
			}

			if (dialog.type === DialogType.collab)
			{
				const collabUserAddQueryParams = {
					dialogId: dialog.dialogId,
					members: members.map((userId) => `U${userId}`),
				};

				return runAction(RestMethod.socialnetworkCollabMemberAdd, {
					data: collabUserAddQueryParams,
				});
			}

			const chatUsersAddQueryParams = {
				id: chatId,
				userIds: members,
				hideHistory: showHistory ? 'N' : 'Y',
			};

			return runAction(RestMethod.imV2ChatAddUsers, {
				data: chatUsersAddQueryParams,
			});
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} userId
		 *
		 * @return {Promise<*|T>}
		 */
		async kickUserFromChat(dialogId, userId)
		{
			const dialog = this.#store.getters['dialoguesModel/getById'](dialogId);
			if (!dialog)
			{
				return Promise.reject(new Error('ChatService.kickUserFromChat: unknown dialog'));
			}

			if (dialog.type === DialogType.collab)
			{
				const collabUserDeleteQueryParams = {
					dialogId,
					members: [['user', String(userId)]],
				};

				return runAction(RestMethod.socialnetworkCollabMemberDelete, {
					data: collabUserDeleteQueryParams,
				});
			}

			const chatUserDeleteQueryParams = {
				dialogId,
				userId,
			};

			return runAction(RestMethod.imV2ChatDeleteUser, {
				data: chatUserDeleteQueryParams,
			});
		}

		/**
		 * @desc Rest leave user from chat
		 * @param {DialogId} dialogId
		 * @return {Promise<string>}
		 */
		leaveFromChat(dialogId)
		{
			const dialog = this.#store.getters['dialoguesModel/getById'](dialogId);
			if (!dialog)
			{
				return Promise.reject(new Error('ChatService.leaveChat: unknown dialog'));
			}

			if (dialog.type === DialogType.collab)
			{
				return runAction(RestMethod.socialnetworkCollabMemberLeave, {
					data: {
						dialogId,
					},
				});
			}

			return new Promise((resolve, reject) => {
				BX.rest.callMethod(
					RestMethod.imChatLeave,
					{
						DIALOG_ID: dialogId,
					},
					(result) => {
						if (result.error() || result.status !== 200)
						{
							reject(result.error());

							return;
						}

						resolve(result.data());
					},
				);
			});
		}

		/**
		 * @desc Rest leave user from chat
		 * @param {DialogId} dialogId
		 * @return {Promise<string>}
		 */
		deleteChat(dialogId)
		{
			const dialog = this.#store.getters['dialoguesModel/getById'](dialogId);
			if (!dialog)
			{
				return Promise.reject(new Error('ChatService.leaveChat: unknown dialog'));
			}

			if (dialog.type === DialogType.collab)
			{
				return runAction(RestMethod.socialnetworkCollabDelete, {
					data: {
						dialogId,
					},
				});
			}

			return runAction(RestMethod.imV2ChatDelete, {
				data: {
					dialogId,
				},
			});
		}
	}

	module.exports = { UserService };
});
