/**
 * @module im/messenger-v2/provider/pull/dialog
 */
jn.define('im/messenger-v2/provider/pull/dialog', (require, exports, module) => {
	/* global ChatDataConverter */
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { unique } = require('utils/array');

	const {
		UserRole,
		EventType,
		DialogType,
		MessagesAutoDeleteDelay,
	} = require('im/messenger/const');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { Feature } = require('im/messenger/lib/feature');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { ChatDataProvider } = require('im/messenger/provider/data');
	const { BasePullHandler } = require('im/messenger/provider/pull/base');

	const { InputActionListener } = require('im/messenger-v2/provider/pull/lib/input-action-listener');

	/**
	 * @class DialogPullHandler
	 */
	class DialogPullHandler extends BasePullHandler
	{
		constructor()
		{
			super({ logger: getLoggerWithContext('pull-handler--dialog-v2', DialogPullHandler) });
		}

		/**
		 * @param {ChatUpdatePullHandlerParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleChatUpdate(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleChatUpdate:', params, extra);

			await this.store.dispatch('dialoguesModel/update', {
				dialogId: params.chat.dialogId,
				fields: params.chat,
			});
		}

		/**
		 * @desc yep use for backgroundId and textFieldEnabled fields
		 * @param {ChatUpdateFieldsPullHandlerParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleChatFieldsUpdate(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleChatFieldsUpdate:', params, extra);

			await this.store.dispatch('dialoguesModel/update', {
				dialogId: params.dialogId,
				fields: params,
			});
		}

		/**
		 * @param {object} params
		 * @param {PullExtraParams} extra
		 */
		async handleChatCopilotRoleUpdate(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleChatCopilotRoleUpdate:', params);

			await this.store.dispatch(
				'dialoguesModel/copilotModel/updateRole',
				{
					dialogId: params.dialogId,
					fields: {
						chats: params.copilotRole.chats,
						roles: params.copilotRole.roles,
					},
				},
			);
		}

		/**
		 * @param {ChatMuteNotifyPullHandlerParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleChatMuteNotify(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleChatMuteNotify:', params);

			if (params.lines)
			{
				this.logger.info('handleChatMuteNotify skip openline mute:', params);

				return;
			}
			const { dialogId, muted } = params;

			const dialog = clone(this.#getDialogModel(dialogId));
			if (Type.isUndefined(dialog))
			{
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

			await this.store.dispatch('dialoguesModel/set', [
				{
					dialogId,
					muteList: [...muteList],
				},
			]);

			await this.store.dispatch('sidebarModel/changeMute', {
				dialogId,
				isMute: muted,
			});
		}

		/**
		 * @param {ChatRenamePullHandlerParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleChatRename(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleChatRename:', params);

			const dialogId = `chat${params.chatId}`;
			const name = params.name;

			await this.store.dispatch('dialoguesModel/update', {
				dialogId,
				fields: { name },
			});
		}

		/**
		 * @desc maybe it is legacy and not used
		 * @param {GeneralChatIdPullHandlerParams} params
		 * @param {PullExtraParams} extra
		 */
		handleGeneralChatId(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleGeneralChatId:', params);

			if (ChatDataConverter)
			{
				ChatDataConverter.generalChatId = params.id;
			}

			MessengerParams.setGeneralChatId(params.id);
		}

		/**
		 * @param {ChatUserAddPullHandlerParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleChatUserAdd(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleChatUserAdd:', params, extra);
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
				await this.store.dispatch('dialoguesModel/update', {
					dialogId,
					fields: dialogUpdatingFields,
				});
			}

			await this.store.dispatch('usersModel/set', Object.values(users));
			await this.store.dispatch('dialoguesModel/addParticipants', {
				dialogId,
				participants: newUsers,
				userCounter: userCount,
			});
		}

		/**
		 * @param {ChatUserLeavePullHandlerParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleChatUserLeave(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleChatUserLeave:', params);

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
				chatId: userDialogModel?.chatId,
				id: chatId,
			});

			if (Number(userId) === MessengerParams.getUserId())
			{
				const chatProvider = new ChatDataProvider();
				const chatDataResult = await chatProvider.get({ dialogId });

				if (!chatDataResult.hasData())
				{
					this.logger.info('handleChatUserLeave not have chatData:', dialogId);

					return;
				}
				const chatData = chatDataResult.getData();
				const chatHelper = DialogHelper.createByModel(chatData);
				if (chatHelper?.isChannel)
				{
					await this.#userLeaveFromChannel(chatHelper, chatProvider, chatData);
				}
				else
				{
					await this.#userLeaveFromChat(chatProvider, chatData);
				}
			}

			await this.store.dispatch('dialoguesModel/removeParticipants', {
				dialogId,
				participants: [userId],
				userCounter: userCount,
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
				await this.store.dispatch('dialoguesModel/update', {
					dialogId,
					fields: dialogUpdatingFields,
				});
			}
		}

		/**
		 * @param {ChatAvatarPullHandlerParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleChatAvatar(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleChatAvatar:', params);

			const dialogId = `chat${params.chatId}`;

			await this.store.dispatch('dialoguesModel/update', { dialogId, fields: { avatar: params.avatar } });
		}

		/**
		 * @param {CommentSubscribePullHandlerParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleCommentSubscribe(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleCommentSubscribe:', params);
			if (params.subscribe)
			{
				await this.store.dispatch('commentModel/subscribe', { messageId: params.messageId });
				await this.store.dispatch('dialoguesModel/unmute', {
					dialogId: params.dialogId,
				});

				return;
			}

			await this.store.dispatch('commentModel/unsubscribe', { messageId: params.messageId });
			await this.store.dispatch('dialoguesModel/mute', {
				dialogId: params.dialogId,
			});
		}

		/**
		 * @param {ChatManagersPullHandlerParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleChatManagers(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleChatManagers:', params);
			await this.store.dispatch('dialoguesModel/updateManagerList', {
				dialogId: params.dialogId,
				managerList: params.list,
			});
		}

		/**
		 * @param {ChatDeletePullHandlerParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleChatDelete(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			this.logger.info('handleChatDelete:', params, extra);

			if (params.userId === MessengerParams.getUserId())
			{
				return;
			}

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
				this.logger.log(`handleChatDelete dialog with chat id=${params.chatId} not found`);

				return;
			}

			const chatData = chatDataResult.getData();

			const openedDialogStack = this.store.getters['applicationModel/getOpenDialogs']();
			for (const dialogId of openedDialogStack)
			{
				const dialog = this.#getDialogModel(dialogId);

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

			try
			{
				await chatProvider.delete({ dialogId: chatData.dialogId });
			}
			catch (error)
			{
				this.logger.error('handleChatDelete delete chat error', error);
			}

			MessengerEmitter.emit(EventType.dialog.external.delete, {
				dialogId: chatData.dialogId,
				shouldShowAlert: true,
				chatType: chatData.type,
				shouldSendDeleteAnalytics: true,
			});
		}

		/**
		 * @param {InputActionNotifyPullHandlerParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleInputActionNotify(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			const { dialogId, userId } = params;
			if (!this.#getDialogModel(dialogId))
			{
				return;
			}

			this.logger.info('handleInputActionNotify:', params);

			await this.store.dispatch('usersModel/update', [
				{
					id: userId,
					fields: {
						id: userId,
						lastActivityDate: new Date(),
					},
				},
			]);
			await this.store.dispatch('dialoguesModel/setInputAction', { ...params });

			InputActionListener.getInstance().startInputAction({ ...params });
		}

		/**
		 * @param {MessagesAutoDeleteDelayParams} params
		 * @param {PullExtraParams} extra
		 */
		handleMessagesAutoDeleteDelayChanged(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			this.logger.info('handleMessagesAutoDeleteDelayChanged params:', params, extra);

			if (params.delay !== MessagesAutoDeleteDelay.off)
			{
				Feature.updateExistingImFeatures({ messagesAutoDeleteEnabled: true });
			}

			this.store.dispatch('dialoguesModel/update', {
				dialogId: String(params.dialogId),
				fields: {
					messagesAutoDeleteDelay: params.delay,
				},
			});
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
		 * @param {DialogHelper} chatHelper
		 * @param {ChatDataProvider} chatProvider
		 * @param {DialoguesModelState} chatData
		 */
		async #userLeaveFromChannel(chatHelper, chatProvider, chatData)
		{
			if (chatHelper?.isOpenChannel)
			{
				await this.store.dispatch('dialoguesModel/update', {
					dialogId: chatHelper.dialogId,
					fields: {
						role: UserRole.guest,
					},
				});
			}

			void this.store.dispatch('commentModel/deleteChannelCounters', {
				channelId: chatHelper.chatId,
			});

			const commentChatData = this.store.getters['dialoguesModel/getByParentChatId'](chatHelper.chatId);
			if (
				Type.isPlainObject(commentChatData)
				&& this.store.getters['applicationModel/isDialogOpen'](commentChatData.dialogId)
			)
			{
				await chatProvider.delete({ dialogId: commentChatData.dialogId });
				MessengerEmitter.emit(EventType.dialog.external.delete, {
					dialogId: commentChatData.dialogId,
					shouldShowAlert: false,
					shouldSendDeleteAnalytics: false,
					chatType: chatData.type,
				});
			}

			await chatProvider.deleteFromSource(ChatDataProvider.source.database, {
				dialogId: chatHelper.dialogId,
			});

			MessengerEmitter.emit(EventType.dialog.external.delete, {
				dialogId: chatHelper.dialogId,
				shouldShowAlert: true,
				shouldSendDeleteAnalytics: false,
				chatType: chatData.type,
			});
		}

		/**
		 * @param {ChatDataProvider} chatProvider
		 * @param {DialoguesModelState} chatData
		 */
		async #userLeaveFromChat(chatProvider, chatData)
		{
			try
			{
				await chatProvider.delete({ dialogId: chatData.dialogId });
			}
			catch (error)
			{
				this.logger.error('handleChatUserLeave.userLeaveFromChat chatProvider.delete catch:', error);
			}

			MessengerEmitter.emit(EventType.dialog.external.delete, {
				dialogId: chatData.dialogId,
				shouldShowAlert: true,
				chatType: chatData.type,
				shouldSendDeleteAnalytics: false,
			});
		}
	}

	module.exports = {
		DialogPullHandler,
	};
});
