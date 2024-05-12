/**
 * @module im/messenger/db/repository/pin-message
 */
jn.define('im/messenger/db/repository/pin-message', (require, exports, module) => {
	const { Type } = require('type');
	const { LinkPinTable, LinkPinMessageTable, FileTable, UserTable } = require('im/messenger/db/table');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { validate: validateMessage } = require('im/messenger/db/repository/validators/message');
	const { validate: validatePin } = require('im/messenger/db/repository/validators/pin');
	const { merge } = require('utils/object');

	const logger = LoggerManager.getInstance().getLogger('repository--pin');
	/**
	 * @class PinMessageRepository
	 */
	class PinMessageRepository
	{
		constructor()
		{
			this.pinTable = new LinkPinTable();
			this.pinMessageTable = new LinkPinMessageTable();
			this.fileTable = new FileTable();
			this.userTable = new UserTable();
		}

		/**
		 *
		 * @param chatId
		 * @return {Promise<{
		 * messages: Array<PinMessageRaw> | null,
		 * pins: Array<RawPin> | null,
		 * files: Array<FilesModelState> | null,
		 * users: Array<UsersModelState> | null,
		 * }>}
		 */
		async getByChatId(chatId)
		{
			const pinRows = await this.pinTable.getList({
				filter: {
					chatId,
				},
				limit: 50,
			});

			if (pinRows.items.length === 0)
			{
				return {
					pins: null,
					messages: null,
					files: null,
					users: null,
				};
			}

			const pins = pinRows.items;
			const pinMessageIdList = pins.map((pin) => pin.messageId);

			const pinnedMessageRows = await this.pinMessageTable.getListByIds(pinMessageIdList);

			if (pinnedMessageRows.items.length === 0)
			{
				return {
					pins: null,
					messages: null,
					files: null,
					users: null,
				};
			}

			const pinnedMessageList = pinnedMessageRows.items;

			const result = {
				pins: [],
				messages: [],
				files: [],
				users: [],
			};

			const userIds = new Set();
			const fileIds = new Set();

			for (const pin of pins)
			{
				/** @type {PinMessageRaw} */
				const pinnedMessage = pinnedMessageList.find((message) => message.id === pin.messageId);

				if (!pinnedMessage)
				{
					continue;
				}

				result.pins.push(pin);
				result.messages.push(pinnedMessage);
				if (Type.isArrayFilled(pinnedMessage.files))
				{
					pinnedMessage.files.forEach((fileId) => {
						fileIds.add(fileId);
					});
				}

				if (pinnedMessage.authorId !== 0)
				{
					userIds.add(pinnedMessage.authorId);
				}
			}

			const userRows = await this.userTable.getListByIds([...userIds]);
			const fileRows = await this.fileTable.getListByIds([...fileIds]);

			result.files = fileRows.items;
			result.users = userRows.items;

			return result;
		}

		/**
		 *
		 * @param {Array<Pin>} pins
		 * @param {Array<MessagesModelState>} messages
		 * @return {Promise<void>}
		 */
		async saveFromModel(pins, messages)
		{
			const messageListToAdd = [];
			const pinListToAdd = [];

			messages.forEach((message) => {
				const messageToAdd = this.pinMessageTable.validate(message);

				messageListToAdd.push(messageToAdd);
			});
			pins.forEach((pin) => {
				const pinToAdd = this.pinTable.validate(pin);

				pinListToAdd.push(pinToAdd);
			});

			this.pinTable.add(pinListToAdd, true);
			this.pinMessageTable.add(messageListToAdd, true);
		}

		/**
		 *
		 * @param {Array<RawPin>} pins
		 * @param {Array<RawMessage>} messages
		 * @return {Promise<void>}
		 */
		async saveFromRest(pins, messages)
		{
			const pinsToAdd = pins
				.map((pin) => this.pinTable.validate(validatePin(pin)))
			;
			const messageToAdd = messages
				.map((message) => this.pinMessageTable.validate(validateMessage(message)))
			;

			this.pinTable.add(pinsToAdd, true);
			this.pinMessageTable.add(messageToAdd, true);
		}

		/**
		 *
		 * @param {MessagesModelState} modelMessage
		 * @return {Promise<void>}
		 */
		async updateMessage(modelMessage)
		{
			const messageRow = await this.pinMessageTable.getById(modelMessage.id);

			if (!messageRow)
			{
				return;
			}

			const messageToAdd = this.pinMessageTable.validate(merge(messageRow, modelMessage));

			this.pinMessageTable.add([messageToAdd], true);
		}

		async deleteByMessageIdList(messageIdList)
		{
			try
			{
				await this.pinTable.deleteByMessageIdList(messageIdList);
			}
			catch (error)
			{
				logger.error(`${this.constructor.name}: pinTable.deleteByIdList error: `, error);
			}

			try
			{
				await this.pinMessageTable.deleteByIdList(messageIdList);
			}
			catch (error)
			{
				logger.error(`${this.constructor.name}: pinMessageTable.deleteByIdList error: `, error);
			}
		}

		async deletePinsByIdList(idList)
		{
			const result = await this.pinTable.getListByIds(idList);

			if (!Type.isArrayFilled(result.items))
			{
				return;
			}

			const messageIdList = result.items.map((pinData) => pinData.messageId);

			try
			{
				await this.pinMessageTable.deleteByIdList(messageIdList);
			}
			catch (error)
			{
				logger.error(`${this.constructor.name}: pinMessageTable.deleteByIdList error: `, error);
			}
		}

		async deleteByChatId(chatId)
		{
			try
			{
				await this.pinTable.delete({ chatId });
			}
			catch (error)
			{
				logger.error(`${this.constructor.name}: pinTable.deleteByChatId error: `, error);
			}

			try
			{
				await this.pinMessageTable.delete({ chatId });
			}
			catch (error)
			{
				logger.error(`${this.constructor.name}: pinMessageTable.deleteByChatId error: `, error);
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
	}

	module.exports = { PinMessageRepository };
});
