/**
 * @module im/messenger/db/repository/message
 */
jn.define('im/messenger/db/repository/message', (require, exports, module) => {
	const { Type } = require('type');

	const { Feature } = require('im/messenger/lib/feature');
	const {
		FileTable,
		UserTable,
		ReactionTable,
		MessageTable,
		MessageTableGetLinkedListDirection,
	} = require('im/messenger/db/table');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const { validate } = require('im/messenger/db/repository/validators/message');

	const logger = LoggerManager.getInstance().getLogger('repository--message');

	/**
	 * @class MessageRepository
	 */
	class MessageRepository
	{
		constructor()
		{
			this.fileTable = new FileTable();
			this.userTable = new UserTable();
			this.reactionTable = new ReactionTable();
			this.messageTable = new MessageTable();
		}

		/**
		 * @param {number} chatId
		 * @param {number} limit
		 * @param {number} offset
		 * @param {number} lastId
		 * @param {'top'|'bottom'} direction
		 * @param {'asc'|'desc'} order
		 *
		 * @return {Promise<MessageRepositoryPage>}
		 */
		async getList({
			chatId,
			limit,
			offset,
			lastId,
			direction,
			order,
		})
		{
			if (!Feature.isLocalStorageEnabled)
			{
				return this.#getDefaultPageResult();
			}

			const options = {
				filter: {
					chatId,
				},
				limit,
				order: {},
			};

			if (order === 'asc')
			{
				options.order.id = 'asc';
			}
			else
			{
				options.order.id = 'desc';
			}

			if (Type.isNumber(lastId))
			{
				if (direction === 'top')
				{
					options.filter['<id'] = lastId;
				}
				else if (direction === 'bottom')
				{
					options.filter['>id'] = lastId;
				}
				else
				{
					options.filter['=id'] = lastId;
				}
			}
			else if (Type.isNumber(offset))
			{
				options.offset = offset;
			}

			const messageList = await this.messageTable.getList(options);

			return this.#getMessagesWithAdditionalData(messageList);
		}

		/**
		 * @param {number} chatId
		 * @param {number} messageId
		 *
		 * @return {Promise<MessageRepositoryPage>}
		 */
		async get(chatId, messageId)
		{
			logger.log('MessageRepository.get request: ', chatId, messageId);

			const messageList = await this.getList({
				chatId,
				lastId: messageId,
				limit: 1,
			});

			logger.log('MessageRepository.get result: ', messageList);

			return messageList;
		}

		/**
		 * @param {number} options.chatId
		 * @param {number} [options.fromMessageId]
		 * @param {number} options.limit
		 *
		 * @return {Promise<MessageRepositoryPage>}
		 */
		async getTopPage(options)
		{
			if (!Feature.isLocalStorageEnabled)
			{
				return this.#getDefaultPageResult();
			}

			const dateStart = Date.now();

			const getListOptions = {
				...options,
				direction: MessageTableGetLinkedListDirection.top,
				includeFromMessageId: false,
			};

			logger.log('MessageRepository.getTopPage request: ', getListOptions);

			const getListResult = await this.messageTable.getLinkedList(getListOptions);
			const messageList = await this.#getMessagesWithAdditionalData(getListResult);

			const dateFinish = Date.now();
			logger.log('MessageRepository.getTopPage result: ', messageList, `${dateFinish - dateStart} ms.`);

			return messageList;
		}

		/**
		 * @param {number} options.chatId
		 * @param {number} [options.fromMessageId]
		 * @param {number} options.limit
		 *
		 * @return {Promise<MessageRepositoryPage>}
		 */
		async getBottomPage(options)
		{
			if (!Feature.isLocalStorageEnabled)
			{
				return this.#getDefaultPageResult();
			}

			const dateStart = Date.now();

			const getListOptions = {
				...options,
				direction: MessageTableGetLinkedListDirection.bottom,
				includeFromMessageId: false,
			};

			logger.log('MessageRepository.getBottomPage request: ', getListOptions);

			const getListResult = await this.messageTable.getLinkedList(getListOptions);
			const messageList = await this.#getMessagesWithAdditionalData(getListResult);

			const dateFinish = Date.now();
			logger.log('MessageRepository.getBottomPage result: ', messageList, `${dateFinish - dateStart} ms.`);

			return messageList;
		}

		/**
		 * @param chatId
		 * @param contextMessageId
		 * @param limit
		 *
		 * @return {Promise<MessageRepositoryContext>}
		 */
		async getContext(chatId, contextMessageId, limit)
		{
			if (!Feature.isLocalStorageEnabled)
			{
				return {
					...this.#getDefaultPageResult(),
					hasContextMessage: false,
				};
			}

			logger.info('MessageRepository.getContext request: ', chatId, contextMessageId, limit);

			const topPage = await this.getTopPage({
				chatId,
				fromMessageId: contextMessageId,
				limit,
			});
			const contextMessage = await this.get(chatId, contextMessageId);
			const bottomPage = await this.getBottomPage({
				chatId,
				fromMessageId: contextMessageId,
				limit,
			});

			const context = {
				messageList: [
					...topPage.messageList,
					...contextMessage.messageList,
					...bottomPage.messageList,
				],
				additionalMessageList: [
					...topPage.additionalMessageList,
					...contextMessage.additionalMessageList,
					...bottomPage.additionalMessageList,
				],
				userList: [
					...topPage.userList,
					...contextMessage.userList,
					...bottomPage.userList,
				],
				fileList: [
					...topPage.fileList,
					...contextMessage.fileList,
					...bottomPage.fileList,
				],
				reactionList: [
					...topPage.reactionList,
					...contextMessage.reactionList,
					...bottomPage.reactionList,
				],
				hasContextMessage: Type.isArrayFilled(contextMessage.messageList),
			};

			logger.info('MessageRepository.getContext result: ', context);

			return context;
		}

		/**
		 * @private
		 * Supplement the list of messages with additional data from other tables
		 */
		async #getMessagesWithAdditionalData(messageList)
		{
			const modelMessageList = [];
			const additionalMessageIdList = new Set();
			const fileIdList = new Set();
			const authorIdList = [];
			const messageIdList = new Set();
			messageList.items.forEach((message) => {
				const modelMessage = message;

				modelMessageList.push(modelMessage);
				messageIdList.add(modelMessage.id);

				if (Type.isArrayFilled(modelMessage.files))
				{
					modelMessage.files.forEach((fileId) => {
						fileIdList.add(fileId);
					});
				}

				if (Type.isInteger(modelMessage.authorId))
				{
					authorIdList.push(modelMessage.authorId);
				}

				if (Type.isObject(modelMessage.params) && Type.isNumber(modelMessage.params.replyId))
				{
					additionalMessageIdList.add(modelMessage.params.replyId);
				}
			});

			const additionalMessageList = await this.messageTable.getListByIds([...additionalMessageIdList]);
			additionalMessageList.items.forEach((message) => {
				const modelMessage = message;

				if (Type.isArrayFilled(modelMessage.files))
				{
					modelMessage.files.forEach((fileId) => {
						fileIdList.add(fileId);
					});
				}

				if (Type.isInteger(modelMessage.authorId))
				{
					authorIdList.push(modelMessage.authorId);
				}
			});

			const fileList = await this.fileTable.getListByIds([...fileIdList]);

			const reactionList = await this.reactionTable.getListByMessageIds([...messageIdList]);
			const reactionUserIdList = [];
			reactionList.items.forEach((reaction) => {
				Object.values(Object.fromEntries(reaction.reactionUsers)).forEach((reactionIdList) => {
					reactionUserIdList.push(...reactionIdList);
				});
			});

			const userIdList = new Set([...authorIdList, ...reactionUserIdList]);
			const userList = await this.userTable.getListByIds([...userIdList]);

			return {
				messageList: modelMessageList.reverse(),
				additionalMessageList: additionalMessageList.items,
				userList: userList.items,
				fileList: fileList.items,
				reactionList: reactionList.items,
			};
		}

		async saveFromModel(messageList)
		{
			const messageListToAdd = [];

			messageList.forEach((message) => {
				const messageToAdd = this.messageTable.validate(message);

				messageListToAdd.push(messageToAdd);
			});

			return this.messageTable.add(messageListToAdd, true);
		}

		async saveFromRest(messageList)
		{
			const messageListToAdd = [];

			messageList.forEach((message) => {
				const messageToAdd = this.messageTable.validate(
					validate(message),
				);

				messageListToAdd.push(messageToAdd);
			});

			return this.messageTable.add(messageListToAdd, true);
		}

		async deleteByIdList(idList)
		{
			const messageList = await this.messageTable.getListByIds(idList, true);
			const fileIdList = [];
			messageList.items.forEach(/** @param {MessagesModelState} message */(message) => {
				if (Type.isArrayFilled(message.files))
				{
					fileIdList.push(...message.files);
				}
			});

			try
			{
				await this.messageTable.deleteByIdList(idList);
			}
			catch (error)
			{
				logger.error('MessageRepository: messageTable.deleteByIdList error: ', error);
			}

			try
			{
				await this.fileTable.deleteByIdList(fileIdList);
			}
			catch (error)
			{
				logger.error('MessageRepository: fileTable.deleteByIdList error: ', error);
			}
		}

		async deleteByChatId(chatId)
		{
			try
			{
				await this.messageTable.delete({ chatId });
			}
			catch (error)
			{
				logger.error('MessageRepository: messageTable.deleteByChatId error: ', error);
			}

			try
			{
				await this.fileTable.delete({ chatId });
			}
			catch (error)
			{
				logger.error('MessageRepository: fileTable.deleteByIdList error: ', error);
			}
		}

		async deleteByChatIdList(chatIdList)
		{
			const deletePromiseList = [];
			chatIdList.forEach((chatId) => {
				const deletePromise = this.deleteByChatId(chatId);
				deletePromiseList.push(deletePromise);
			});

			return Promise.all(deletePromiseList);
		}

		/**
		 * @return {MessageRepositoryPage}
		 */
		#getDefaultPageResult()
		{
			return {
				messageList: [],
				additionalMessageList: [],
				userList: [],
				fileList: [],
				reactionList: [],
			};
		}
	}

	module.exports = {
		MessageRepository,
	};
});
