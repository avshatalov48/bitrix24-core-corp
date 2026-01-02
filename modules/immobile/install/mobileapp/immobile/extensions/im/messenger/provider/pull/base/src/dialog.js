/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/base/dialog
 */
jn.define('im/messenger/provider/pull/base/dialog', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/base/pull-handler');
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { unique } = require('utils/array');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { EventType, DialogType, UserRole } = require('im/messenger/const');
	const { ChatDataProvider, RecentDataProvider } = require('im/messenger/provider/data');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class BaseDialogPullHandler
	 */
	class BaseDialogPullHandler extends BasePullHandler
	{
		handleChatUpdate(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleChatUpdate:`, params, extra, command);

			this.store.dispatch('dialoguesModel/update', {
				dialogId: params.chat.dialogId,
				fields: params.chat,
			}).catch((err) => this.logger.error(`${this.getClassName()}.handleChatUpdate.dialoguesModel/update.catch:`, err));
		}

		handleChatFieldsUpdate(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleChatFieldsUpdate:`, params, extra, command);

			this.store.dispatch('dialoguesModel/update', {
				dialogId: params.dialogId,
				fields: params,
			}).catch((err) => this.logger.error(`${this.getClassName()}.handleChatFieldsUpdate.dialoguesModel/update.catch:`, err));
		}

		handleChatPin(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleChatPin`, params);

			this.store.dispatch('recentModel/update', [{
				id: params.dialogId,
				pinned: params.active,
			}]).catch((err) => this.logger.error(`${this.getClassName()}.handleChatPin.recentModel/update.catch:`, err));
		}

		handleChatMuteNotify(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleChatMuteNotify`, params);

			if (params.lines)
			{
				this.logger.info(`${this.getClassName()}.handleChatMuteNotify skip openline mute`, params);

				return;
			}
			const {
				dialogId,
				chatId,
				muted,
				counter,
				counterType,
				parentChatId = 0,
			} = params;

			/** @type {CounterState} */
			const counterState = {
				chatId,
				counter,
				type: counterType,
				parentChatId,
			};

			const dialog = clone(this.store.getters['dialoguesModel/getById'](dialogId));
			if (Type.isUndefined(dialog))
			{
				this.updateCounterChatMutedCollection(counterState, muted);
				serviceLocator.get('tab-counters').update();

				return;
			}

			const muteList = new Set(dialog.muteList);
			if (muted)
			{
				muteList.add(MessengerParams.getUserId());
			}
			else
			{
				muteList.delete(MessengerParams.getUserId());
			}

			this.store.dispatch('dialoguesModel/set', [{
				dialogId,
				muteList: [...muteList],
			}]).catch((err) => this.logger.error(`${this.getClassName()}.handleChatMuteNotify.dialoguesModel/set.catch:`, err));

			this.store.dispatch('sidebarModel/changeMute', {
				dialogId,
				isMute: muted,
			}).catch((err) => this.logger.error(`${this.getClassName()}.handleChatMuteNotify.sidebarModel/changeMute.catch:`, err));

			this.updateCounterChatMutedCollection(counterState, muted);
			serviceLocator.get('tab-counters').update();
		}

		handleChatHide(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			if (params.lines)
			{
				if (MessengerParams.isOpenlinesOperator())
				{
					const tabCounters = serviceLocator.get('tab-counters');
					tabCounters.deleteCounterByChatId(params.chatId);
					tabCounters.update();
				}

				return;
			}

			this.logger.info(`${this.getClassName()}.handleChatHide`, params);

			this.store.dispatch('recentModel/delete', { id: params.dialogId })
				.then(() => serviceLocator.get('tab-counters').updateDelayed())
				.catch((err) => this.logger.error(`${this.getClassName()}.handleChatHide.updateDelayed().catch:`, err))
			;
		}

		handleChatRename(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleChatRename`, params);

			const dialogId = `chat${params.chatId}`;
			const name = params.name;

			this.store.dispatch('dialoguesModel/update', {
				dialogId,
				fields: { name },
			}).catch((err) => this.logger.error(`${this.getClassName()}.handleChatRename.dialoguesModel/update.catch:`, err));
		}

		handleGeneralChatId(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleGeneralChatId`, params);

			// TODO: Remove after converter implementation
			if (ChatDataConverter)
			{
				ChatDataConverter.generalChatId = params.id;
			}

			MessengerParams.setGeneralChatId(params.id);
		}

		handleChatUnread(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleChatUnread`, params);

			const dialogId = params.dialogId;

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			let counter = 0;
			if (!params.muted && params.counter)
			{
				counter = params.counter;
			}

			this.store.dispatch('recentModel/set', [{
				id: dialogId,
				unread: params.active,
				counter,
			}]).then(() => serviceLocator.get('tab-counters').update());
		}

		handleChatUserAdd(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleChatUserAdd`, params, extra);
			const {
				dialogId,
				newUsers,
				userCount,
				users,
				chatExtranet = false,
				containsCollaber = false,
			} = params || {};

			const dialogModel = this.#getDialogModel(dialogId);

			unique(newUsers).forEach((userId) => {
				const userDialogModel = this.#getDialogModel(userId);
				if (userDialogModel)
				{
					void this.store.dispatch('sidebarModel/sidebarCommonChatsModel/set', {
						chatId: userDialogModel.chatId,
						chats: [dialogModel],
					});
				}
			});

			/** @type {Partial<DialoguesModelState>} */
			const dialogUpdatingFields = {};

			if (Boolean(dialogModel?.extranet) !== chatExtranet)
			{
				dialogUpdatingFields.extranet = chatExtranet;
			}

			if (Boolean(dialogModel?.containsCollaber) !== containsCollaber)
			{
				dialogUpdatingFields.containsCollaber = containsCollaber;
			}

			if (newUsers.includes(MessengerParams.getUserId()))
			{
				dialogUpdatingFields.role = UserRole.member;
			}

			if (Object.keys(dialogUpdatingFields).length > 0)
			{
				this.store.dispatch('dialoguesModel/update', {
					dialogId,
					fields: dialogUpdatingFields,
				});
			}

			this.store.dispatch('usersModel/set', Object.values(users)).catch((err) => {
				this.logger.error(`${this.getClassName()}.handleChatUserAdd.usersModel/set.catch:`, err);
			});
			this.store.dispatch('dialoguesModel/addParticipants', {
				dialogId,
				participants: newUsers,
				userCounter: userCount,
			}).catch((err) => {
				this.logger.error(
					`${this.getClassName()}.handleChatUserAdd.dialoguesModel/addParticipants.catch:`,
					err,
				);
			});
		}

		/**
		 * @param {Object} params
		 * @param {DialogId} params.dialogId
		 * @param {string} params.chatId
		 * @param {number} params.userId
		 * @param {number} params.userCount
		 * @param {boolean} params.chatExtranet
		 * @param {Object} extra
		 * @param {Object} command
		 * @returns {Promise<void>}
		 */
		async handleChatUserLeave(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleChatUserLeave`, params);

			const {
				userId,
				chatId,
				dialogId,
				userCount,
				chatExtranet,
				containsCollaber,
			} = params;

			const userDialogModel = this.#getDialogModel(userId);

			void this.store.dispatch('sidebarModel/sidebarCommonChatsModel/delete', {
				chatId: userDialogModel.chatId,
				id: chatId,
			});

			this.deleteCounters(chatId);

			if (Number(userId) === MessengerParams.getUserId())
			{
				const chatProvider = new ChatDataProvider();
				const chatDataResult = await chatProvider.get({ dialogId });

				if (!chatDataResult.hasData())
				{
					return;
				}
				const chatData = chatDataResult.getData();
				const chatHelper = DialogHelper.createByModel(chatData);

				// close and delete the comment chat linked to this channel
				if (chatHelper?.isChannel)
				{
					void this.store.dispatch('commentModel/deleteChannelCounters', {
						channelId: chatId,
					});

					const commentChatData = this.store.getters['dialoguesModel/getByParentChatId'](chatData.chatId);

					if (
						Type.isPlainObject(commentChatData)
						&& this.store.getters['applicationModel/isDialogOpen'](commentChatData.dialogId)
					)
					{
						chatProvider.delete({ dialogId: commentChatData.dialogId });
						MessengerEmitter.emit(EventType.dialog.external.delete, {
							dialogId: commentChatData.dialogId,
							shouldShowAlert: false,
							shouldSendDeleteAnalytics: false,
							chatType: chatDataResult.getData().type,
						});
					}
				}

				const recentProvider = new RecentDataProvider();
				recentProvider.delete({ dialogId })
					.then(() => chatProvider.delete({ dialogId }))
					.catch((error) => {
						this.logger.error(`${this.constructor.name}.handleChatUserLeave delete chat error`, error);
					})
				;

				MessengerEmitter.emit(EventType.dialog.external.delete, {
					dialogId,
					shouldShowAlert: true,
					chatType: chatDataResult.getData().type,
					shouldSendDeleteAnalytics: false,
				});

				serviceLocator.get('tab-counters').update();
			}

			this.store.dispatch('dialoguesModel/removeParticipants', {
				dialogId,
				participants: [userId],
				userCounter: userCount,
			}).catch((error) => {
				this.logger.error(
					`${this.getClassName()}.handleChatUserLeave.dialoguesModel/removeParticipants.catch:`,
					error,
				);
			});

			const dialogModel = this.#getDialogModel(dialogId);
			/** @type {Partial<DialoguesModelState>} */
			const dialogUpdatingFields = {};

			if (Boolean(dialogModel?.extranet) !== chatExtranet)
			{
				dialogUpdatingFields.extranet = chatExtranet;
			}

			if (Boolean(dialogModel?.containsCollaber) !== containsCollaber)
			{
				dialogUpdatingFields.containsCollaber = containsCollaber;
			}

			if (Object.keys(dialogUpdatingFields).length > 0)
			{
				this.store.dispatch('dialoguesModel/update', {
					dialogId,
					fields: dialogUpdatingFields,
				});
			}
		}

		/**
		 * @param {number} chatId
		 * @void
		 */
		deleteCounters(chatId)
		{
			serviceLocator.get('tab-counters').deleteCounterByChatId(chatId);
		}

		handleChatAvatar(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleChatAvatar`, params);

			const dialogId = `chat${params.chatId}`;

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			recentItem.avatar = params.avatar;

			this.store.dispatch('recentModel/set', [recentItem])
				.catch((err) => this.logger.error(`${this.getClassName()}.handleChatAvatar.recentModel/set.catch:`, err));
			this.store.dispatch('dialoguesModel/update', { dialogId, fields: { avatar: recentItem.avatar } })
				.catch((err) => this.logger.error(`${this.getClassName()}.handleChatAvatar.dialoguesModel/update.catch:`, err));
		}

		handleChatChangeColor(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleChatChangeColor`, params);

			const dialogId = `chat${params.chatId}`;

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			recentItem.color = params.color;

			this.store.dispatch('recentModel/set', [recentItem])
				.catch((err) => this.logger.error(`${this.getClassName()}.handleChatChangeColor.recentModel/set.catch:`, err));
		}

		handleCommentSubscribe(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleCommentSubscribe`, params);
			if (params.subscribe)
			{
				this.store.dispatch('commentModel/subscribe', { messageId: params.messageId });
				this.store.dispatch('dialoguesModel/unmute', {
					dialogId: params.dialogId,
				});

				return;
			}

			this.store.dispatch('commentModel/unsubscribe', { messageId: params.messageId });
			this.store.dispatch('dialoguesModel/mute', {
				dialogId: params.dialogId,
			});
		}

		handleChatManagers(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleChatManagers`, params);
			this.store.dispatch('dialoguesModel/updateManagerList', {
				dialogId: params.dialogId,
				managerList: params.list,
			});
		}

		/**
		 * @param {{chatId: number, userId: number, dialogId: DialogId, type: string, parentChatId?: number}} params
		 * @param extra
		 * @param command
		 */
		async handleChatDelete(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			this.logger.warn(`${this.getClassName()}.handleChatDelete`, params, extra, command);

			if ([DialogType.openChannel, DialogType.channel].includes(params.type))
			{
				void this.store.dispatch('commentModel/deleteChannelCounters', {
					channelId: params.chatId,
				});
			}

			if (params.type === DialogType.comment)
			{
				void this.store.dispatch('commentModel/setCounters', {
					[params.parentChatId]: {
						[params.chatId]: 0,
					},
				});
			}

			const chatProvider = new ChatDataProvider();
			const chatDataResult = await chatProvider.get({ chatId: params.chatId });

			if (!chatDataResult.hasData())
			{
				this.logger.log(`${this.getClassName()}.handleChatDelete dialog with chat id=${params.chatId} not found`);

				return;
			}

			const chatData = chatDataResult.getData();

			if (!this.shouldDeleteChat(chatData))
			{
				this.logger.log(`${this.getClassName()}.handleChatDelete does not need to be deleted from this component`);

				return;
			}

			const openedDialogStack = this.store.getters['applicationModel/getOpenDialogs']();
			for (const dialogId of openedDialogStack)
			{
				const dialog = this.store.getters['dialoguesModel/getById'](dialogId);

				if (dialog?.parentChatId === params.chatId)
				{
					MessengerEmitter.emit(EventType.dialog.external.delete, {
						dialogId,
						shouldShowAlert: false,
						chatType: dialog.type,
						shouldSendDeleteAnalytics: false,
					});
				}
			}

			const recentProvider = new RecentDataProvider();
			recentProvider.delete({ dialogId: chatData.dialogId })
				.then(() => chatProvider.delete({ dialogId: chatData.dialogId }))
				.catch((error) => {
					this.logger.error(`${this.constructor.name}.handleChatDelete delete chat error`, error);
				})
			;

			MessengerEmitter.emit(EventType.dialog.external.delete, {
				dialogId: chatData.dialogId,
				shouldShowAlert: true,
				chatType: chatData.type,
				shouldSendDeleteAnalytics: true,
			});
		}

		/**
		 * @abstract
		 * @desc Method should return true if this chat needs to be deleted in this component
		 * @param {DialoguesModelState} chatData
		 * @return {boolean}
		 */
		shouldDeleteChat(chatData)
		{
			return true;
		}

		/**
		 * @desc get class name for logger
		 * @return {string}1
		 */
		getClassName()
		{
			return this.constructor.name;
		}

		/**
		 * @param {DialogId} dialogId
		 * @returns {?DialoguesModelState}
		 */
		#getDialogModel(dialogId)
		{
			return this.store.getters['dialoguesModel/getById'](dialogId);
		}

		/**
		 * @param {CounterState} counterState
		 * @param {boolean} isMuted
		 */
		updateCounterChatMutedCollection(counterState, isMuted)
		{
			const tabCounters = serviceLocator.get('tab-counters');
			if (isMuted)
			{
				tabCounters.addChatToMutedCollection(counterState.chatId);
				tabCounters.deleteCounterByChatId(counterState.chatId);
			}
			else
			{
				tabCounters.deleteChatFromMutedCollection(counterState.chatId);
				tabCounters.updateCounterDetailByCounterState(counterState);
			}
		}
	}

	module.exports = {
		BaseDialogPullHandler,
	};
});
