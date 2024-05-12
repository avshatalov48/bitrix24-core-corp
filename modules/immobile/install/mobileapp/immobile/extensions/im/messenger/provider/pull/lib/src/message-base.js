/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/lib/message-base
 */
jn.define('im/messenger/provider/pull/lib/message-base', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { clone } = require('utils/object');

	const { BasePullHandler } = require('im/messenger/provider/pull/lib/pull-handler-base');
	const { ChatTitle, ChatAvatar } = require('im/messenger/lib/element');
	const { DialogConverter } = require('im/messenger/lib/converter');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { Counters } = require('im/messenger/lib/counters');
	const { RecentConverter } = require('im/messenger/lib/converter');
	const { Notifier } = require('im/messenger/lib/notifier');
	const { ShareDialogCache } = require('im/messenger/cache/share-dialog');
	const { UuidManager } = require('im/messenger/lib/uuid-manager');
	const {
		DialogType,
		EventType,
	} = require('im/messenger/const');

	/**
	 * @class MessageBasePullHandler
	 */
	class MessageBasePullHandler extends BasePullHandler
	{
		constructor(options)
		{
			super(options);

			this.writingTimer = 25000;
		}

		handleMessage(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}
			this.logger.log(`${this.getClassName()}.handleMessage `, params, extra);

			const dialogId = params.message.recipientId;
			const userId = MessengerParams.getUserId();

			const recipientId = params.message.senderId === userId
				? params.message.recipientId
				: params.message.senderId
			;

			const recentParams = clone(params);
			const userData = recentParams.users[recipientId];
			const recentItem = RecentConverter.fromPushToModel({
				id: recipientId,
				user: userData,
				message: recentParams.message,
				counter: recentParams.counter,
			});

			recentItem.message.status = recentParams.message.senderId === userId ? 'received' : '';

			const userPromise = this.setUsers(params);

			if (!recentItem)
			{
				return;
			}
			this.updateDialog(params)
				.then(() => this.store.dispatch('recentModel/set', [recentItem]))
				.then(() => {
					if (extra && extra.server_time_ago <= 5 && params.message.senderId !== userId)
					{
						const userName = ChatTitle.createFromDialogId(params.message.senderId).getTitle();
						const userAvatar = ChatAvatar.createFromDialogId(params.message.senderId).getAvatarUrl();

						Notifier.notify({
							dialogId: recipientId,
							title: userName,
							text: recentItem.message.text,
							avatar: userAvatar,
						});
					}

					Counters.updateDelayed();

					this.saveShareDialogCache();
				})
			;

			const dialog = this.getDialog(dialogId);
			if (!dialog || dialog.hasNextPage)
			{
				return;
			}

			const hasUnloadMessages = dialog.hasNextPage;
			if (hasUnloadMessages)
			{
				return;
			}

			userPromise.then(() => {
				this.setFiles(params).then(() => {
					this.setMessage(params);
					this.checkWritingTimer(params.dialogId, userData);
				});
			});
		}

		handleMessageChat(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.log(`${this.getClassName()}.handleMessageChat`, params, extra);

			const dialogId = params.message.recipientId;
			const userId = MessengerParams.getUserId();

			if (params.lines)
			{
				if (MessengerParams.isOpenlinesOperator())
				{
					Counters.openlinesCounter.detail[params.dialogId] = params.counter;
					Counters.update();
				}

				return;
			}

			const recentParams = clone(params);
			recentParams.message.text = ChatMessengerCommon.purifyText(
				recentParams.message.text,
				recentParams.message.params,
			);
			recentParams.message.status = recentParams.message.senderId === userId ? 'received' : '';
			const userData = recentParams.message.senderId > 0
				? recentParams.users[recentParams.message.senderId]
				: { id: 0 };
			const recentItem = RecentConverter.fromPushToModel({
				id: dialogId,
				chat: recentParams.chat[recentParams.chatId],
				user: userData,
				lines: recentParams.lines,
				message: recentParams.message,
				counter: recentParams.counter,
				liked: false,
			});

			this.updateDialog(params)
				.then(() => this.store.dispatch('recentModel/set', [recentItem]))
				.then(() => {
					const dialog = this.getDialog(dialogId);
					if (
						extra && extra.server_time_ago <= 5
						&& params.message.senderId !== userId
						&& dialog && !dialog.muteList.includes(userId)
					)
					{
						const dialogTitle = ChatTitle.createFromDialogId(dialogId).getTitle();
						const userName = ChatTitle.createFromDialogId(userData.id).getTitle();
						const avatar = ChatAvatar.createFromDialogId(dialogId).getAvatarUrl();

						Notifier.notify({
							dialogId: dialog.dialogId,
							title: dialogTitle,
							text: (userName ? `${userName}: ` : '') + recentItem.message.text,
							avatar,
						});
					}

					Counters.updateDelayed();

					this.saveShareDialogCache();
				})
			;

			const dialog = this.getDialog(dialogId);
			if (!dialog || dialog.hasNextPage)
			{
				return;
			}

			const hasUnloadMessages = dialog.hasNextPage;
			if (hasUnloadMessages)
			{
				return;
			}

			this.setUsers(params).then(() => {
				this.setFiles(params).then(() => {
					this.setMessage(params);
					this.checkWritingTimer(dialogId, userData);
				});
			});
		}

		handleMessageUpdate(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleMessageUpdate:`, params);

			this.updateMessage(params);
		}

		handleMessageParamsUpdate(params, extra, command)
		{
			this.logger.info(`${this.getClassName()}.handleMessageParamsUpdate:`, params);

			this.store.dispatch('messagesModel/update', {
				id: params.id,
				chatId: params.chatId,
				fields: { params: params.params },
			}).catch((err) => this.logger.error(`${this.getClassName()}.handleMessageParamsUpdate.messagesModel/update.catch:`, err));
		}

		handleMessageDelete(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleMessageDelete:`, params, extra);

			// eslint-disable-next-line no-param-reassign
			params.text = Loc.getMessage('IMMOBILE_PULL_HANDLER_MESSAGE_DELETED');

			this.updateMessage(params);
		}

		/**
		 * @param {MessagePullHandlerMessageDeleteCompleteParams} params
		 * @param {object} extra
		 * @param {object} command
		 */
		handleMessageDeleteComplete(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleMessageDeleteComplete: `, params, extra);

			this.fullDeleteMessage(params)
				.catch((err) => this.logger.error(`${this.getClassName()}.handleMessageDeleteComplete.catch err: `, err));
		}

		/**
		 * @param {AddReactionParams} params
		 * @param {object} extra
		 * @param {object} command
		 */
		handleAddReaction(params, extra, command)
		{
			this.logger.info(`${this.getClassName()}.handleAddReaction: `, params);
			if (UuidManager.getInstance().hasActionUuid(extra.action_uuid))
			{
				this.logger.info(`${this.getClassName()}handleAddReaction: we already locally processed this action`);
				UuidManager.getInstance().removeActionUuid(extra.action_uuid);

				return;
			}
			const {
				actualReactions: { reaction: actualReactionsState, usersShort },
				userId,
				reaction,
				dialogId,
			} = params;
			const message = this.store.getters['messagesModel/getById'](actualReactionsState.messageId);
			if (!message)
			{
				return;
			}

			if (MessengerParams.getUserId().toString() === userId.toString())
			{
				actualReactionsState.ownReactions = [reaction];
			}

			this.store.dispatch('usersModel/addShort', usersShort)
				.then(() => {
					this.store.dispatch('messagesModel/reactionsModel/setFromPullEvent', {
						usersShort,
						reactions: [actualReactionsState],
					});
				})
				.catch((err) => this.logger.error(`${this.getClassName()}.handleAddReaction.usersModel/addShort.catch err:`, err));

			if (this.store.getters['applicationModel/getOpenDialogs']().includes(dialogId))
			{
				return;
			}

			const recentItem = this.store.getters['recentModel/getById'](dialogId);
			const isOwnLike = MessengerParams.getUserId() === userId;
			const isOwnLastMessage = MessengerParams.getUserId() === recentItem.message.senderId;
			if (isOwnLike || !isOwnLastMessage)
			{
				return;
			}

			this.store.dispatch('recentModel/like', {
				messageId: actualReactionsState.messageId,
				id: dialogId,
				liked: true,
			});
		}

		/**
		 * @param {DeleteReactionParams} params
		 * @param {object} extra
		 * @param {object} command
		 */
		handleDeleteReaction(params, extra, command)
		{
			this.logger.info(`${this.getClassName()}.handleDeleteReaction: `, params);
			if (UuidManager.getInstance().hasActionUuid(extra.action_uuid))
			{
				this.logger.info(`${this.getClassName()}.handleDeleteReaction: we already locally processed this action`);
				UuidManager.getInstance().removeActionUuid(extra.action_uuid);

				return;
			}
			const { actualReactions: { reaction: actualReactionsState, usersShort } } = params;

			const message = this.store.getters['messagesModel/getById'](actualReactionsState.messageId);
			if (!message)
			{
				return;
			}

			this.store.dispatch('messagesModel/reactionsModel/setFromPullEvent', {
				usersShort,
				reactions: [actualReactionsState],
			}).catch((err) => this.logger.error(`${this.getClassName()}.handleDeleteReaction.messagesModel.catch err:`, err));
		}

		async handleStartWriting(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleStartWriting:`, params);

			const {
				dialogId,
				userId,
				userName,
			} = params;

			this.updateUserOnline(userId);

			const isHasDialog = await this.setDialogItemWriting(params, true);
			if (isHasDialog)
			{
				ChatTimer.start('writing', `${dialogId} ${userName}`, this.writingTimer, () => {
					this.setDialogItemWriting(params, false);
				}, params);
			}
		}

		handleReadMessage(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleReadMessage:`, params);

			if (UuidManager.getInstance().hasActionUuid(extra.action_uuid))
			{
				this.logger.info(`${this.getClassName()}.handleReadMessage: we already locally processed this action`);
				UuidManager.getInstance().removeActionUuid(extra.action_uuid);

				return;
			}

			this.readMessage(params);
			this.updateCounters(params);
		}

		handleReadMessageChat(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleReadMessageChat:`, params);

			if (UuidManager.getInstance().hasActionUuid(extra.action_uuid))
			{
				this.logger.info(`${this.getClassName()}.handleReadMessageChat: we already locally processed this action`);
				UuidManager.getInstance().removeActionUuid(extra.action_uuid);

				return;
			}

			this.readMessage(params);
			this.updateCounters(params);
		}

		handleUnreadMessage(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleUnreadMessage:`, params);

			this.unreadMessage(params);
			this.updateCounters(params);
		}

		handleUnreadMessageChat(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleUnreadMessageChat:`, params);

			this.unreadMessage(params);
			this.updateCounters(params);
		}

		readMessage(params)
		{
			this.store.dispatch('messagesModel/readMessages', {
				chatId: params.chatId,
				messageIds: params.viewedMessages,
			});
		}

		unreadMessage(params)
		{}

		handleReadMessageOpponent(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleReadMessageOpponent:`, params);

			this.readMessageChatOpponent(params);
		}

		// eslint-disable-next-line sonarjs/no-identical-functions
		handleReadMessageChatOpponent(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleReadMessageChatOpponent:`, params);

			this.readMessageChatOpponent(params);
		}

		handleUnreadMessageOpponent(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleUnreadMessageOpponent:`, params);

			this.updateMessageStatus(params);
		}

		// eslint-disable-next-line sonarjs/no-identical-functions
		handleUnreadMessageChatOpponent(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleUnreadMessageChatOpponent:`, params);

			this.updateMessageStatus(params);
		}

		readMessageChatOpponent(params)
		{
			this.updateMessageViewedByOthers(params);
			this.updateMessageStatus(params);
			this.updateChatLastMessageViews(params);
		}

		updateMessage(params)
		{
			const dialogId = params.dialogId;
			const messageId = params.id;
			const messageParams = params.params;

			const message = clone(this.store.getters['messagesModel/getById'](messageId));
			if (!message)
			{
				return;
			}

			if (message.params && message.params.replyId)
			{
				// this copyrighting params need for update quote - not deleting
				messageParams.replyId = message.params.replyId;
			}

			this.store.dispatch('messagesModel/update', {
				id: params.id,
				fields: {
					text: params.text,
					params: messageParams,
				},
			});

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			const recentParams = clone(params);
			if (recentItem.message.id === message.id)
			{
				message.text = ChatMessengerCommon.purifyText(recentParams.text, recentParams.params);
				message.params = recentParams.params;
				message.file = recentParams.params && recentParams.params.FILE_ID
					? recentParams.params.FILE_ID.length > 0
					: false
				;
				message.attach = recentParams.params && recentParams.params.ATTACH
					? recentParams.params.ATTACH.length > 0
					: false
				;

				recentItem.message = {
					...recentItem.message,
					...message,
				};
			}

			this.store.dispatch('recentModel/set', [recentItem])
				.catch((err) => this.logger.error(`${this.getClassName()}.updateMessage.recentModel/set.catch:`, err));
		}

		/**
		 * @param {MessagePullHandlerMessageDeleteCompleteParams} params
		 */
		async fullDeleteMessage(params)
		{
			const dialogId = params.dialogId;
			const messageId = params.id;

			this.store.dispatch('messagesModel/delete', { id: messageId })
				.catch((err) => this.logger.error(`${this.getClassName()}.fullDeleteMessage.messagesModel/delete.catch:`, err));

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			const dialogItem = this.store.getters['dialoguesModel/getById'](dialogId);
			if (!recentItem && !dialogItem)
			{
				return;
			}


			const fieldsCount = {
				counter: params.counter,
			};
			let isNeedUpdateRecentItem = false;
			if (params.lastMessageViews?.countOfViewers
				&& (params.lastMessageViews.countOfViewers !== dialogItem.lastMessageViews.countOfViewers))
			{
				fieldsCount.lastMessageId = params.newLastMessage.id;
				fieldsCount.lastId = dialogItem.lastReadId === dialogItem.lastMessageId
					? params.newLastMessage.id : dialogItem.lastReadId;

				const fieldsViews = {
					...params.lastMessageViews.firstViewers[0],
					messageId: params.lastMessageViews.messageId,
					countOfViewers: params.lastMessageViews.countOfViewers,
				};

				await this.store.dispatch('dialoguesModel/setLastMessageViews', {
					dialogId,
					fields: fieldsViews,
				});
			}

			await this.store.dispatch('dialoguesModel/update', {
				dialogId,
				fields: fieldsCount,
			});

			const newLastMessage = params.newLastMessage;
			if (newLastMessage)
			{
				recentItem.message = {
					text: newLastMessage.file ? `[${BX.message('IM_F_FILE')}]` : newLastMessage.text,
					date: newLastMessage.date,
					author_id: newLastMessage.author_id,
					id: newLastMessage.id,
					file: newLastMessage.file ?? false,
				};

				isNeedUpdateRecentItem = true;
			}

			if (isNeedUpdateRecentItem)
			{
				this.store.dispatch('recentModel/set', [recentItem])
					.then(() => {
						Counters.update();

						this.saveShareDialogCache();
					})
					.catch((err) => this.logger.error(`${this.getClassName()}.fullDeleteMessage.recentModel/set.catch:`, err))
				;
			}
		}

		updateMessageStatus(params)
		{
			const dialogId = params.dialogId;
			const userId = params.userId;

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			if (params.chatMessageStatus && params.chatMessageStatus !== recentItem.message.status)
			{
				recentItem.message.status = params.chatMessageStatus;

				this.store.dispatch('recentModel/set', [recentItem])
					.catch((err) => this.logger.error(`${this.getClassName()}.updateMessageStatus.recentModel/set.catch:`, err));
			}

			const user = clone(this.store.getters['usersModel/getById'](userId));
			if (!user)
			{
				return;
			}

			this.store.dispatch('usersModel/update', [{
				id: userId,
				idle: false,
				lastActivityDate: new Date(params.date),
			}])
				.catch((err) => this.logger.error(`${this.getClassName()}.updateMessageStatus.usersModel/update.catch:`, err));
		}

		/**
		 * @desc Update views a message in dialog model store
		 * @param {Object} params - pull event
		 */
		updateChatLastMessageViews(params)
		{
			const dialogModelState = this.getDialog(params.dialogId);
			if (!dialogModelState)
			{
				return;
			}

			const isLastMessage = params.viewedMessages.includes(dialogModelState.lastMessageId);
			if (!isLastMessage)
			{
				return;
			}

			const hasFirstViewer = Boolean(dialogModelState.lastMessageViews.firstViewer);
			if (hasFirstViewer)
			{
				// FIXME this case occurs when the user is using 2 or more devices at the same time ( work only first user )
				//  need wait update while this bug in backend will be fix
				const isDoubleFirstViewer = params.userId === dialogModelState.lastMessageViews.firstViewer.userId;
				if (DialogHelper.isDialogId(params.dialogId) && !isDoubleFirstViewer)
				{
					this.store.dispatch('dialoguesModel/incrementLastMessageViews', {
						dialogId: params.dialogId,
					}).catch(
						(err) => this.logger.error(`${this.getClassName()}.updateChatLastMessageViews.dialoguesModel/incrementLastMessageViews.catch:`, err),
					);
				}

				return;
			}

			if (params.userId)
			{
				this.store.dispatch('dialoguesModel/setLastMessageViews', {
					dialogId: params.dialogId,
					fields: {
						userId: params.userId,
						userName: params.userName,
						date: params.date,
						messageId: dialogModelState.lastMessageId,
					},
				}).catch(
					(err) => this.logger.error(`${this.getClassName()}.updateChatLastMessageViews.dialoguesModel/setLastMessageViews.catch:`, err),
				);
			}
		}

		updateMessageViewedByOthers(params)
		{
			this.store.dispatch('messagesModel/setViewedByOthers', { messageIds: params.viewedMessages })
				.catch(
					(err) => this.logger.error(`${this.getClassName()}.updateMessageViewedByOthers.messagesModel/setViewedByOthers.catch:`, err),
				);
		}

		updateCounters(params)
		{
			const dialogId = params.dialogId;

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			recentItem.counter = params.counter;

			const dialog = clone(this.store.getters['dialoguesModel/getById'](dialogId));

			if (!dialog)
			{
				return;
			}

			this.store.dispatch('dialoguesModel/update', {
				dialogId,
				fields: {
					counter: params.counter,
					lastId: params.lastId,
				},
			})
				.then(() => this.store.dispatch('recentModel/set', [recentItem]))
				.then(() => Counters.update())
				.catch(
					(err) => this.logger.error(`${this.getClassName()}.updateCounters.dialoguesModel/update.catch:`, err),
				);
		}

		/**
		 * @desc set writing list data to a dialog model
		 * @param {object} params
		 * @param {string} params.dialogId
		 * @param {number} params.userId
		 * @param {string} params.userName
		 * @param {boolean} isWriting
		 * @return {Promise|boolean}
		 */
		setDialogItemWriting(params, isWriting)
		{
			const { dialogId, userId } = params;
			const dialog = this.getDialog(dialogId);
			const user = this.store.getters['usersModel/getById'](userId);
			if (!dialog || !user)
			{
				return false;
			}

			this.logger.info(`${this.getClassName()}.setDialogItemWriting`, params);

			return this.store.dispatch('dialoguesModel/updateWritingList', {
				dialogId,
				fields: {
					writingList: [{ ...params, isWriting }],
				},
			})
				.then(() => true)
				.catch((err) => this.logger.error(`${this.getClassName()}.handleStartWriting.catch:`, err));
		}

		saveShareDialogCache()
		{
			const firstPage = this.store.getters['recentModel/getRecentPage'](1, 50);
			ShareDialogCache.saveRecentItemList(firstPage).catch(
				(err) => this.logger.error(`${this.getClassName()}.saveShareDialogCache.catch:`, err),
			);
		}

		/**
		 * @param {Array<RawUser>} params.users
		 * @return {Promise<any>|Promise<Awaited<boolean>>}
		 */
		setUsers(params)
		{
			if (!params.users)
			{
				return Promise.resolve(false);
			}

			return this.store.dispatch('usersModel/set', Object.values(params.users));
		}

		/**
		 *
		 * @param params.files
		 * @return {Promise<Awaited<unknown>[]>|Promise<void>}
		 */
		setFiles(params)
		{
			if (!params.files)
			{
				return Promise.resolve();
			}

			const promises = [];
			const files = Object.values(params.files);
			files.forEach((file) => {
				const templateFileIdExists = this.store.getters['filesModel/isInCollection']({
					fileId: params.message?.templateFileId,
				});

				if (templateFileIdExists)
				{
					const updateFileWithIdPromise = this.store.dispatch('filesModel/updateWithId', {
						id: params.message?.templateFileId,
						fields: file,
					});

					promises.push(updateFileWithIdPromise);
				}
				else
				{
					const setFilePromise = this.store.dispatch('filesModel/set', file);
					promises.push(setFilePromise);
				}
			});

			return Promise.all(promises);
		}

		async setMessage(params)
		{
			const message = DialogConverter.fromPushToMessage(params);

			/**
			 * @type {MessagePullHandlerAdditionalEntities || un}
			 */
			const { additionalEntities } = params.message;

			if (Type.isObject(additionalEntities))
			{
				if (Type.isArrayFilled(additionalEntities?.users))
				{
					await this.setUsers(additionalEntities);
				}

				if (Type.isArrayFilled(additionalEntities?.files))
				{
					await this.setFiles(additionalEntities);
				}
			}

			const messageWithTemplateId = this.store.getters['messagesModel/isInChatCollection']({
				messageId: params.message.templateId,
			});

			const messageWithRealId = this.store.getters['messagesModel/isInChatCollection']({
				messageId: params.message.id,
			});

			if (messageWithRealId)
			{
				this.logger.warn(`${this.getClassName()}.New message pull handler: we already have this message`, params.message);
				this.store.dispatch('messagesModel/update', {
					id: params.message.id,
					fields: params.message,
				})
					.catch((err) => this.logger.error(`${this.getClassName()}.setMessage.messagesModel/update.catch:`, err));
			}
			else if (!messageWithRealId && messageWithTemplateId)
			{
				this.logger.warn(`${this.getClassName()}.New message pull handler: we already have the TEMPORARY message`, params.message);
				this.store.dispatch('messagesModel/updateWithId', {
					id: params.message.templateId,
					fields: params.message,
				})
					.catch((err) => this.logger.error(`${this.getClassName()}.setMessage.messagesModel/updateWithId.catch:`, err));
			}
			// it's an opponent message or our own message from somewhere else
			else if (!messageWithRealId && !messageWithTemplateId)
			{
				this.logger.warn(`${this.getClassName()}.New message pull handler: we dont have this message`, params.message);

				const prevMessageId = this.store.getters['messagesModel/getLastId'](message.chatId);

				this.store.dispatch('messagesModel/add', message).then(() => {
					/** @type {ScrollToBottomEvent} */
					const scrollToBottomEventData = {
						dialogId: message.dialogId,
						messageId: message.id,
						withAnimation: true,
						prevMessageId,
					};

					BX.postComponentEvent(EventType.dialog.external.scrollToBottom, [scrollToBottomEventData]);
				});
			}
		}

		updateUserOnline(userId)
		{
			return this.store.dispatch('usersModel/update', [{
				id: userId,
				fields: {
					id: userId,
					lastActivityDate: new Date(),
				},
			}]);
		}

		/**
		 * @desc Check is having writing timer by user and stop it
		 * @param {string} dialogId
		 * @param {object} userData
		 * @void
		 */
		checkWritingTimer(dialogId, userData) {
			let userModel = userData;

			if (!userModel.name)
			{
				userModel = this.store.getters['usersModel/getById'](userData.id);
			}
			const timerId = `${dialogId} ${userModel.name}`;
			if (this.isHasTimerWriting(timerId))
			{
				this.stopTimerWriting(timerId);
			}
		}

		/**
		 * @desc Returns check is having timer with 'writing' type by id
		 * @param {string|number} timerId
		 * @return {boolean}
		 */
		isHasTimerWriting(timerId)
		{
			return ChatTimer.isHasTimer('writing', timerId);
		}

		/**
		 * @desc Stop timer with 'writing' type by id
		 * @param {string|number} timerId
		 * @return {boolean}
		 */
		stopTimerWriting(timerId)
		{
			/** @type {object} */
			return ChatTimer.stop('writing', timerId);
		}

		/**
		 * @return {?DialoguesModelState}
		 */
		getDialog(dialogId)
		{
			return this.store.getters['dialoguesModel/getById'](dialogId);
		}

		/**
		 *
		 * @param {MessagePullHandlerUpdateDialogParams}params
		 * @return {Promise<any>}
		 */
		async updateDialog(params)
		{
			const dialog = this.store.getters['dialoguesModel/getById'](params.dialogId);
			if (!dialog)
			{
				return this.addDialog(params);
			}

			const dialogFieldsToUpdate = {};
			if (params.message.id > dialog.lastMessageId)
			{
				dialogFieldsToUpdate.lastMessageId = params.message.id;
				dialogFieldsToUpdate.counter = params.counter;
			}

			if (params.message.senderId === MessengerParams.getUserId() && params.message.id > dialog.lastReadId)
			{
				dialogFieldsToUpdate.lastId = params.message.id;
			}

			if (Object.keys(dialogFieldsToUpdate).length > 0)
			{
				return this.store.dispatch('dialoguesModel/update', {
					dialogId: params.dialogId,
					fields: dialogFieldsToUpdate,
				}).then(() => this.store.dispatch('dialoguesModel/clearLastMessageViews', {
					dialogId: params.dialogId,
				})).catch((err) => this.logger.error(`${this.getClassName()}.updateDialog.catch:`, err));
			}

			return Promise.resolve(false);
		}

		/**
		 *
		 * @param {MessagePullHandlerUpdateDialogParams}params
		 * @return {Promise<any>}
		 */
		async addDialog(params)
		{
			if (DialogHelper.isChatId(params.dialogId))
			{
				if (!params.users)
				{
					return false;
				}
				/** @type {UsersModelState} */
				const opponent = params.users[params.dialogId];

				return this.store.dispatch('dialoguesModel/set', {
					dialogId: params.dialogId,
					counter: params.counter,
					type: DialogType.user,
					name: opponent.name,
					avatar: opponent.avatar,
					color: opponent.color,
					chatId: params.chatId,
				});
			}

			if (!params.chat)
			{
				return false;
			}

			return this.store.dispatch('dialoguesModel/set', {
				...params.chat[params.chatId],
				dialogId: params.dialogId,
				counter: params.counter,
				chatId: params.chatId,
			});
		}

		/**
		 * @desc get class name for logger
		 * @return {string}
		 */
		getClassName()
		{
			return this.constructor.name;
		}
	}

	module.exports = {
		MessageBasePullHandler,
	};
});
