/**
 * @module im/messenger/provider/service/classes/message/load
 */
jn.define('im/messenger/provider/service/classes/message/load', (require, exports, module) => {
	const { Type } = require('type');

	const { DialogType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Feature } = require('im/messenger/lib/feature');
	const { UserManager } = require('im/messenger/lib/user-manager');
	const { RestManager } = require('im/messenger/lib/rest-manager');
	const { RestMethod } = require('im/messenger/const/rest');
	const { runAction } = require('im/messenger/lib/rest');
	const { MessageContextCreator } = require('im/messenger/provider/service/classes/message-context-creator');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { DialogHelper, DateHelper } = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');
	const logger = LoggerManager.getInstance().getLogger('message-service--load');

	const DAY_MILLISECONDS = 24 * 60 * 60 * 1000;

	/**
	 * @class LoadService
	 */
	class LoadService
	{
		static getMessageRequestLimit()
		{
			return 50;
		}

		constructor({ chatId })
		{
			this.store = serviceLocator.get('core').getStore();
			this.chatId = chatId;
			/**
			 * @type {MessageRepository}
			 */
			this.messageRepository = serviceLocator.get('core').getRepository().message;
			/**
			 * @type {TempMessageRepository}
			 */
			this.tempMessageRepository = serviceLocator.get('core').getRepository().tempMessage;
			this.contextCreator = new MessageContextCreator();

			this.preparedHistoryMessages = [];
			this.preparedUnreadMessages = [];
			this.isUnreadLoading = false;
			this.isUnreadLoadingFromDb = false;

			this.isHistoryLoading = false;
			this.isHistoryLoadingFromDb = false;

			this.userManager = new UserManager(this.store);
			this.reactions = null;
		}

		async loadUnread()
		{
			if (Feature.isLocalStorageEnabled && this.isUnreadLoadingFromDb === false)
			{
				this.isUnreadLoadingFromDb = true;

				try
				{
					await this.loadUnreadMessagesFromDb();
				}
				catch (error)
				{
					logger.error('LoadService.loadUnreadMessagesFromDb error: ', error);
				}
				finally
				{
					this.isUnreadLoadingFromDb = false;
				}
			}

			if (this.isUnreadLoading || !this.getDialog().hasNextPage)
			{
				return Promise.resolve(false);
			}

			logger.warn('LoadService: loadUnread');
			const lastUnreadMessageId = this.store.getters['messagesModel/getLastId'](this.chatId);
			if (!lastUnreadMessageId)
			{
				logger.warn('LoadService: no lastUnreadMessageId, cant load unread');

				return Promise.resolve(false);
			}

			this.isUnreadLoading = true;

			const query = {
				chatId: this.chatId,
				filter: {
					lastId: lastUnreadMessageId,
				},
				order: {
					id: 'ASC',
				},
			};

			return runAction(RestMethod.imV2ChatMessageTail, { data: query }).then(async (result) => {
				logger.warn('LoadService: loadUnread result', result);
				this.preparedUnreadMessages = result.messages.sort((a, b) => a.id - b.id);
				this.preparedUnreadMessages = await this.contextCreator
					.createMessageDoublyLinkedListForDialog(this.getDialog(), this.preparedUnreadMessages)
				;
				this.preparedUnreadMessages = this.addUploadingMessagesToMessageList(this.preparedUnreadMessages);

				this.reactions = {
					reactions: result.reactions,
					usersShort: result.usersShort,
				};

				return this.updateModels(result);
			}).then(() => {
				this.drawPreparedUnreadMessages();
				this.isUnreadLoading = false;

				return true;
			}).catch((error) => {
				logger.error('LoadService: loadUnread error:', error);
				this.isUnreadLoading = false;
			});
		}

		async loadHistory()
		{
			const dialog = this.store.getters['dialoguesModel/getByChatId'](this.chatId);

			if (
				Feature.isLocalStorageEnabled
				&& this.isHistoryLoadingFromDb === false
				&& ![DialogType.openChannel, DialogType.channel, DialogType.comment, DialogType.generalChannel].includes(dialog?.type)
			)
			{
				this.isHistoryLoadingFromDb = true;

				try
				{
					await this.loadHistoryMessagesFromDb();
				}
				catch (error)
				{
					logger.error('LoadService.loadHistoryMessagesFromDb error: ', error);
				}
				finally
				{
					this.isHistoryLoadingFromDb = false;
				}
			}

			if (this.isHistoryLoading || !this.getDialog().hasPrevPage)
			{
				return Promise.resolve(false);
			}

			logger.warn('LoadService: loadHistory');
			const lastHistoryMessageId = this.store.getters['messagesModel/getFirstId'](this.chatId);
			if (!lastHistoryMessageId)
			{
				logger.warn('LoadService: no lastHistoryMessageId, cant load unread');

				return Promise.resolve();
			}

			this.isHistoryLoading = true;

			const query = {
				chatId: this.chatId,
				filter: {
					lastId: lastHistoryMessageId,
				},
				order: {
					id: 'DESC',
				},
			};

			return runAction(RestMethod.imV2ChatMessageTail, { data: query }).then(async (result) => {
				logger.warn('LoadService: loadHistory result', result);
				const hasPrevPage = result.hasNextPage; // FIXME convert key name when back and web switch to two keys
				this.preparedHistoryMessages = result.messages.sort((a, b) => a.id - b.id);
				this.preparedHistoryMessages = await this.contextCreator
					.createMessageDoublyLinkedListForDialog({ ...this.getDialog(), hasPrevPage }, this.preparedHistoryMessages)
				;
				this.preparedHistoryMessages = this.addUploadingMessagesToMessageList(this.preparedHistoryMessages);
				this.reactions = {
					reactions: result.reactions,
					usersShort: result.usersShort,
				};

				const rawData = { ...result, hasPrevPage, hasNextPage: null };
				await this.updateModels(rawData);

				return result;
			}).then(async (result) => {
				await this.drawPreparedHistoryMessages();

				if (this.preparedHistoryMessages.length === 0 && result.tariffRestrictions.isHistoryLimitExceeded === true)
				{
					await this.updateForceTariffRestrictions(result.tariffRestrictions);
				}
				this.isHistoryLoading = false;

				return true;
			}).catch((error) => {
				logger.error('LoadService: loadHistory error:', error);
				this.isHistoryLoading = false;
			});
		}

		async loadHistoryMessagesFromDb()
		{
			if (!this.#checkShouldLoadFromDb())
			{
				return;
			}

			const lastHistoryMessageId = this.store.getters['messagesModel/getFirstId'](this.chatId);

			const options = {
				chatId: this.chatId,
				fromMessageId: lastHistoryMessageId,
				limit: LoadService.getMessageRequestLimit(),
			};
			logger.log('LoadService: loadHistoryMessagesFromDb', options);
			const result = await this.messageRepository.getTopPage(options);
			const resultWithValidPlan = this.checkPlanLimits(result);
			await this.updateModelsByDbResult(resultWithValidPlan);

			const resultTemp = await this.tempMessageRepository.getList();
			if (Type.isArrayFilled(resultTemp.messageList))
			{
				await this.store.dispatch('messagesModel/setTemporaryMessages', {
					messages: resultTemp.messageList,
				});
			}
		}

		async loadUnreadMessagesFromDb()
		{
			if (!this.#checkShouldLoadFromDb())
			{
				return;
			}

			const lastUnreadMessageId = this.store.getters['messagesModel/getLastId'](this.chatId);

			const options = {
				chatId: this.chatId,
				fromMessageId: lastUnreadMessageId,
				limit: LoadService.getMessageRequestLimit(),
			};
			logger.log('LoadService: loadUnreadMessagesFromDb', options);
			const result = await this.messageRepository.getBottomPage(options);
			const resultWithValidPlan = this.checkPlanLimits(result);
			await this.updateModelsByDbResult(resultWithValidPlan);
		}

		async loadFirstPage()
		{
			logger.log('MessageService: loadFirstPage for: ', this.chatId);
			this.isHistoryLoading = true;

			const messageTailParams = {
				data: {
					chatId: this.chatId,
					limit: LoadService.getMessageRequestLimit(),
					order: { id: 'ASC' },
				},
			};
			const result = await runAction(RestMethod.imV2ChatMessageTail, messageTailParams)
				.catch((error) => {
					logger.error('MessageService: loadFirstPage error:', error);

					this.isHistoryLoading = false;
				});
			// because imV2ChatMessageTail does not return this field for the first page
			result.hasPrevPage = false;

			logger.log('MessageService: loadFirstPage result', result);
			this.isHistoryLoading = false;

			return result;
		}

		async loadContext(messageId)
		{
			logger.log('MessageService: loadContext for: ', messageId);
			this.isHistoryLoading = true;
			this.isUnreadLoading = true;

			const restManager = new RestManager();
			restManager
				.once(RestMethod.imV2ChatMessageGetContext, {
					id: messageId,
					range: LoadService.getMessageRequestLimit(),
				})
				.once(RestMethod.imV2ChatMessageRead, {
					chatId: this.chatId,
					ids: [messageId],
				})
			;

			try
			{
				const response = await restManager.callBatch({
					shouldExtractResponseByMethod: true,
				});

				logger.log('MessageService: loadContext result ', response);
				this.isHistoryLoading = false;
				this.isUnreadLoading = false;

				return response[RestMethod.imV2ChatMessageGetContext];
			}
			catch (error)
			{
				logger.log('MessageService: loadContext error ', error);
				this.isHistoryLoading = false;
				this.isUnreadLoading = false;

				const errorKey = Object.keys(error).filter((key) => key.startsWith(RestMethod.imV2ChatMessageGetContext));

				return error[errorKey] || error;
			}
		}

		/**
		 * @param {number} commentChatId
		 * @return {Promise<{result: object, contextMessageId: number}>}
		 */
		async loadContextByCommentChatId(commentChatId)
		{
			logger.log('MessageService: loadContextByChatId for: ', commentChatId);
			this.isHistoryLoading = true;
			this.isUnreadLoading = true;

			const queryParams = {
				data: { commentChatId },
			};
			const result = await runAction(RestMethod.imV2ChatMessageGetContext, queryParams)
				.catch((error) => {
					logger.error('MessageService: loadContextByChatId error ', error);
					this.isHistoryLoading = false;
					this.isUnreadLoading = false;
				});
			logger.log('MessageService: loadContextByChatId result ', result);
			this.isHistoryLoading = false;
			this.isUnreadLoading = false;

			const commentInfo = result.commentInfo;
			const targetCommentInfo = commentInfo.find((item) => {
				return item.chatId === commentChatId;
			});
			const contextMessageId = targetCommentInfo?.messageId;

			return {
				result,
				contextMessageId,
			};
		}

		async updateModelByContextResult(result)
		{
			logger.log('MessageService.updateModelByContextResult: ', result);

			await this.updateModels(result);
			await this.drawContextMessages(result);
		}

		/**
		 * @param {number} messageId
		 * @return {Promise<{result: MessageRepositoryContext, isCompleteContext: boolean}>}
		 */
		async loadLocalStorageContext(messageId)
		{
			if (!this.#checkShouldLoadFromDb())
			{
				return {
					isCompleteContext: false,
					result: {},
				};
			}

			const result = await this.messageRepository.getContext(
				this.chatId,
				messageId,
				LoadService.getMessageRequestLimit(),
			);
			this.checkPlanLimits(result, true);
			if (result.dialogFields)
			{
				await this.updateDialogFields(result.dialogFields);
			}

			const response = {
				isCompleteContext: true,
				result,
			};

			logger.log('MessageService.loadLocalStorageContext result: ', result);
			if (!result.hasContextMessage)
			{
				response.isCompleteContext = false;

				logger.log('MessageService.loadLocalStorageContext: no message with ID = ', messageId);

				return response;
			}

			const expectedMessagesCount = (LoadService.getMessageRequestLimit() * 2) + 1;
			if (result.messageList.length < expectedMessagesCount)
			{
				// TODO: handle scenario
				response.isCompleteContext = true;

				return response;
			}

			return response;
		}

		async updateModelByLocalStorageContextResult(result)
		{
			logger.log('MessageService.updateModelByLocalStorageContextResult: ', result);

			if (Type.isArrayFilled(result.userList))
			{
				await this.store.dispatch('usersModel/setFromLocalDatabase', result.userList);
			}

			if (Type.isArrayFilled(result.fileList))
			{
				await this.store.dispatch('filesModel/set', result.fileList);
			}

			if (Type.isArrayFilled(result.reactionList))
			{
				await this.store.dispatch('messagesModel/reactionsModel/set', {
					reactions: result.reactionList,
				});
			}

			if (Type.isArrayFilled(result.messageList))
			{
				// eslint-disable-next-line no-param-reassign
				result.messageList = this.addUploadingMessagesToMessageList(result.messageList);

				await this.store.dispatch('messagesModel/setChatCollection', {
					messages: result.messageList,
					clearCollection: true,
				});
			}
		}

		hasPreparedUnreadMessages()
		{
			return this.preparedUnreadMessages.length > 0;
		}

		hasPreparedHistoryMessages()
		{
			return this.preparedHistoryMessages.length > 0;
		}

		drawPreparedHistoryMessages()
		{
			if (!this.hasPreparedHistoryMessages())
			{
				return Promise.resolve();
			}

			return this.store.dispatch('messagesModel/reactionsModel/set', this.reactions)
				.then(() => this.store.dispatch('messagesModel/setChatCollection', {
					messages: this.preparedHistoryMessages,
				}))
				.then(() => {
					this.preparedUnreadMessages = [];
					this.reactions = null;

					return true;
				})
			;
		}

		drawPreparedUnreadMessages()
		{
			if (!this.hasPreparedUnreadMessages())
			{
				return Promise.resolve();
			}

			return this.store.dispatch('messagesModel/reactionsModel/set', this.reactions)
				.then(() => this.store.dispatch('messagesModel/setChatCollection', {
					messages: this.preparedUnreadMessages,
				}))
				.then(() => {
					this.preparedUnreadMessages = [];
					this.reactions = null;

					return true;
				})
			;
		}

		async drawContextMessages(result)
		{
			if (!Type.isArrayFilled(result.messages))
			{
				return Promise.resolve();
			}

			let messages = await this.contextCreator
				.createMessageDoublyLinkedListForDialog(this.getDialog(), result.messages)
			;
			messages = this.addUploadingMessagesToMessageList(messages);

			const reactions = {
				reactions: result.reactions,
				usersShort: result.usersShort,
			};

			await this.store.dispatch('messagesModel/reactionsModel/set', reactions);
			await this.store.dispatch('messagesModel/setChatCollection', {
				messages,
				clearCollection: true,
			});
		}

		/**
		 * @param {MessageRepositoryPage|*} result
		 * @private
		 */
		async updateModelsByDbResult(result)
		{
			await this.updateDialogFields(result.dialogFields);

			if (Type.isArrayFilled(result.userList))
			{
				await this.store.dispatch('usersModel/setFromLocalDatabase', result.userList);
			}

			if (Type.isArrayFilled(result.fileList))
			{
				await this.store.dispatch('filesModel/setFromLocalDatabase', result.fileList);
			}

			if (Type.isArrayFilled(result.reactionList))
			{
				await this.store.dispatch('messagesModel/reactionsModel/setFromLocalDatabase', {
					reactions: result.reactionList,
				});
			}

			if (Type.isArrayFilled(result.additionalMessageList))
			{
				await this.store.dispatch('messagesModel/store', result.additionalMessageList);
			}

			if (Type.isArrayFilled(result.messageList))
			{
				await this.store.dispatch('messagesModel/setFromLocalDatabase', {
					messages: result.messageList,
				});
			}
		}

		/**
		 * @param {object} dialogFields
		 * @return void
		 */
		async updateDialogFields(dialogFields)
		{
			if (!Type.isUndefined(dialogFields))
			{
				await this.store.dispatch('dialoguesModel/update', {
					dialogId: this.getDialog().dialogId,
					fields: dialogFields,
				});
			}
		}

		/**
		 * @param {boolean} hasPrevPage
		 * @param {boolean} hasNextPage
		 * @param {TariffRestrictions} tariffRestrictions
		 * @return {Promise<*>}
		 */
		async updatePageNavigationFields({ hasPrevPage, hasNextPage, tariffRestrictions = {} })
		{
			const fields = {};
			if (Type.isBoolean(hasPrevPage))
			{
				fields.hasPrevPage = hasPrevPage;
			}

			if (Type.isBoolean(hasNextPage))
			{
				fields.hasNextPage = hasNextPage;
			}

			if (Type.isBoolean(tariffRestrictions?.isHistoryLimitExceeded))
			{
				fields.tariffRestrictions = tariffRestrictions;
			}

			return this.store.dispatch('dialoguesModel/update', {
				dialogId: this.getDialog().dialogId,
				fields,
			});
		}

		/**
		 * @private
		 */
		updateModels(rawData)
		{
			const {
				files,
				users,
				usersShort,
				hasPrevPage,
				hasNextPage,
				tariffRestrictions = {},
				additionalMessages,
				commentInfo,
				copilot,
			} = rawData;

			const dialogPromise = this.updatePageNavigationFields({
				hasPrevPage,
				hasNextPage,
				tariffRestrictions,
			});
			const usersPromise = [
				this.userManager.setUsersToModel(users),
				this.userManager.addShortUsersToModel(usersShort),
			];
			const filesPromise = this.store.dispatch('filesModel/set', files);
			const additionalMessagesPromise = this.store.dispatch('messagesModel/store', additionalMessages.sort((a, b) => a.id - b.id));

			let commentPromise = Promise.resolve();
			if (Type.isArrayFilled(commentInfo))
			{
				commentPromise = this.store.dispatch('commentModel/setComments', commentInfo);
			}

			let copilotPromise = Promise.resolve();
			if (copilot)
			{
				copilotPromise = this.store.dispatch(
					'dialoguesModel/copilotModel/setCollection',
					{ dialogId: `chat${this.chatId}`, ...copilot },
				);
			}

			return Promise.all([
				dialogPromise,
				Promise.all(usersPromise),
				filesPromise,
				additionalMessagesPromise,
				commentPromise,
				copilotPromise,
			]);
		}

		/**
		 * @private
		 */
		getDialog()
		{
			return this.store.getters['dialoguesModel/getByChatId'](this.chatId);
		}

		#isChannel()
		{
			const dialog = this.getDialog();

			return [DialogType.openChannel, DialogType.channel, DialogType.generalChannel].includes(dialog.type);
		}

		#isComment()
		{
			const dialog = this.getDialog();

			return dialog.type === DialogType.comment;
		}

		#isCurrentUserGuest()
		{
			const helper = DialogHelper.createByChatId(this.chatId);

			return Boolean(helper?.isCurrentUserGuest);
		}

		#checkShouldLoadFromDb()
		{
			if (this.#isComment())
			{
				return false;
			}

			if (this.#isChannel() && this.#isCurrentUserGuest())
			{
				return false;
			}

			return true;
		}

		/**
		 * @param {MessageRepositoryPage} requestPageResult
		 * @param {boolean} [isContextLoad=false]
		 * @return {MessageRepositoryPage|*}
		 */
		checkPlanLimits(requestPageResult, isContextLoad = false)
		{
			if (DialogHelper.createByChatId(this.chatId)?.isChannelOrComment)
			{
				return true;
			}

			const planLimits = MessengerParams.getPlanLimits();
			if (planLimits?.fullChatHistory?.isAvailable === true)
			{
				return requestPageResult;
			}

			logger.log(`${this.constructor.name}.checkPlanLimits got messages: ${requestPageResult.messageList.length}`);
			const filteredMessageList = this.filterMessagesByPlanLimitDay(
				requestPageResult.messageList,
				planLimits.fullChatHistory?.limitDays,
			);

			if (filteredMessageList.length < requestPageResult.messageList.length)
			{
				// eslint-disable-n ext-line no-param-reassign
				requestPageResult.dialogFields = {
					tariffRestrictions: {
						isHistoryLimitExceeded: true,
					},
				};
			}

			if (filteredMessageList.length > 0 && filteredMessageList.length === requestPageResult.messageList.length)
			{
				const dialog = this.store.getters['dialoguesModel/getByChatId'](this.chatId);
				if (!isContextLoad && dialog?.inited === false && dialog?.tariffRestrictions?.isHistoryLimitExceeded === true)
				{
					// eslint-disable-next-line no-param-reassign
					requestPageResult.dialogFields = {
						tariffRestrictions: {
							isHistoryLimitExceeded: false,
						},
					};
				}

				if (filteredMessageList.length >= 10)
				{
					// eslint-disable-next-line no-param-reassign
					requestPageResult.dialogFields = {
						tariffRestrictions: {
							isHistoryLimitExceeded: false,
						},
					};
				}
			}
			logger.log(`${this.constructor.name}.checkPlanLimits filtered messages: ${requestPageResult.messageList.length - filteredMessageList.length}`);

			// eslint-disable-next-line no-param-reassign
			requestPageResult.messageList = filteredMessageList;

			return requestPageResult;
		}

		/**
		 * @param {Array<MessagesModelState>} messageList
		 * @param {number} [limitDay=30]
		 * @return {Array<MessagesModelState>}
		 */
		filterMessagesByPlanLimitDay(messageList, limitDay = 30)
		{
			const currentEndData = Date.now() - this.getTimeFromDays(limitDay);

			return messageList.filter((message) => {
				const mesTime = DateHelper.cast(message.date)?.getTime();

				return currentEndData <= mesTime;
			});
		}

		/**
		 * @param {number} limitDay
		 * @return {number}
		 */
		getTimeFromDays(limitDay)
		{
			return limitDay * DAY_MILLISECONDS;
		}

		/**
		 * @param {TariffRestrictions} tariffRestrictions
		 */
		updateForceTariffRestrictions(tariffRestrictions)
		{
			return this.store.dispatch(
				'dialoguesModel/updateTariffRestrictions',
				{
					dialogId: `chat${this.chatId}`,
					tariffRestrictions,
					isForceUpdate: true,
				},
			);
		}

		/**
		 * @param {Array<MessagesModelState | RawMessage>} messageList
		 * @returns {Array<MessagesModelState | RawMessage>}
		 */
		addUploadingMessagesToMessageList(messageList)
		{
			/** @type {Map<number, Array<MessagesModelState>>} */
			const uploadingCollection = new Map();
			const uploadingMessageList = this.store.getters['messagesModel/getUploadingMessages'](this.chatId);
			if (!Type.isArrayFilled(uploadingMessageList))
			{
				return messageList;
			}

			for (const uploadingMessage of uploadingMessageList)
			{
				if (!uploadingCollection.has(uploadingMessage.previousId))
				{
					uploadingCollection.set(uploadingMessage.previousId, []);
				}
				uploadingCollection.get(uploadingMessage.previousId).push(uploadingMessage);
			}

			for (const [messageId, uploadingMessages] of uploadingCollection.entries())
			{
				const messageIndex = messageList.findIndex((message) => message.id === messageId);
				if (messageIndex === -1)
				{
					continue;
				}

				if (messageIndex === messageList.length - 1)
				{
					messageList.push(...uploadingMessages);
				}
				else
				{
					messageList.splice(messageIndex + 1, 0, ...uploadingMessages);
				}
			}

			return messageList;
		}
	}

	module.exports = {
		LoadService,
	};
});
