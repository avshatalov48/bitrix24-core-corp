/**
 * @module im/messenger/db/repository/message
 */
jn.define('im/messenger/db/repository/message', (require, exports, module) => {
	const { Type } = require('type');

	const { Settings } = require('im/messenger/lib/settings');
	const {
		FileTable,
		UserTable,
		ReactionTable,
		MessageTable,
	} = require('im/messenger/db/table');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { ObjectUtils } = require('im/messenger/lib/utils');
	const { clone } = require('utils/object');
	const { LoggerManager } = require('im/messenger/lib/logger');
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
		 * @return {Promise<{messageList: [], userList: [], fileList: [], reactionList: []}>}
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
			if (!Settings.isLocalStorageEnabled)
			{
				return {
					messageList: [],
					userList: [],
					fileList: [],
					reactionList: [],
				};
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

			const modelMessageList = [];
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
					this.validateRestMessage(message),
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

		validateRestMessage(message)
		{
			const result = {};

			if (Type.isNumber(message.id))
			{
				result.id = message.id;
			}

			if (!Type.isUndefined(message.chat_id))
			{
				// eslint-disable-next-line no-param-reassign
				message.chatId = message.chat_id;
			}

			if (Type.isNumber(message.chatId) || Type.isStringFilled(message.chatId))
			{
				result.chatId = Number.parseInt(message.chatId, 10);
			}

			if (Type.isStringFilled(message.date))
			{
				result.date = DateHelper.cast(message.date);
			}
			else if (Type.isDate(message.date))
			{
				result.date = message.date;
			}

			if (Type.isNumber(message.text) || Type.isStringFilled(message.text))
			{
				result.text = message.text.toString();
			}

			if (!Type.isUndefined(message.senderId))
			{
				// eslint-disable-next-line no-param-reassign
				message.authorId = message.senderId;
			}
			else if (!Type.isUndefined(message.author_id))
			{
				// eslint-disable-next-line no-param-reassign
				message.authorId = message.author_id;
			}

			if (Type.isNumber(message.authorId) || Type.isStringFilled(message.authorId))
			{
				if (
					message.system === true
					|| message.system === 'Y'
					|| message.isSystem === true
				)
				{
					result.authorId = 0;
				}
				else
				{
					result.authorId = Number.parseInt(message.authorId, 10);
				}
			}

			if (Type.isBoolean(message.sending))
			{
				result.sending = message.sending;
			}

			if (Type.isBoolean(message.unread))
			{
				result.unread = message.unread;
			}

			if (Type.isBoolean(message.viewed))
			{
				result.viewed = message.viewed;
			}

			if (Type.isBoolean(message.viewedByOthers))
			{
				result.viewedByOthers = message.viewedByOthers;
			}

			if (Type.isBoolean(message.error))
			{
				result.error = message.error;
			}

			if (Type.isBoolean(message.retry))
			{
				result.retry = message.retry;
			}

			if (Type.isArray(message.attach))
			{
				result.attach = message.attach;
			}

			if (Type.isNumber(message.richLinkId) || Type.isNull(message.richLinkId))
			{
				result.richLinkId = message.richLinkId;
			}

			if (Type.isPlainObject(message.params))
			{
				const { params, fileIds, attach, richLinkId } = this.validateParams(message.params);
				result.params = params;
				result.files = fileIds;

				if (Type.isUndefined(result.attach))
				{
					result.attach = attach;
				}

				if (Type.isUndefined(result.richLinkId))
				{
					result.richLinkId = richLinkId;
				}
			}

			result.forward = {};
			if (Type.isPlainObject(message.forward))
			{
				result.forward = message.forward;
			}

			return result;
		}

		validateParams(rawParams)
		{
			const params = {};
			let fileIds = [];
			let attach = [];
			let richLinkId = null;

			Object.entries(rawParams).forEach(([key, value]) => {
				if (key === 'COMPONENT_ID' && Type.isStringFilled(value))
				{
					params.componentId = value;
				}
				else if (key === 'FILE_ID' && Type.isArray(value))
				{
					fileIds = value;
				}
				else if (key === 'ATTACH')
				{
					attach = ObjectUtils.convertKeysToCamelCase(clone(value), true);
					params.ATTACH = value;
				}
				else if (key === 'URL_ID')
				{
					richLinkId = value[0] ? Number(value[0]) : null;
					params.URL_ID = value;
				}
				else
				{
					params[key] = value;
				}
			});

			return { params, fileIds, attach, richLinkId };
		}
	}

	module.exports = {
		MessageRepository,
	};
});
