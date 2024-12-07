/* eslint-disable no-await-in-loop */

/**
 * @module im/messenger/db/table/message
 */
jn.define('im/messenger/db/table/message', (require, exports, module) => {
	const { Type } = require('type');

	const { Feature } = require('im/messenger/lib/feature');
	const {
		Table,
		FieldType,
		FieldDefaultValue,
	} = require('im/messenger/db/table/table');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('database-table--message');

	const MessageTableGetLinkedListDirection = Object.freeze({
		top: 'top',
		bottom: 'bottom',
	});

	class MessageTable extends Table
	{
		getName()
		{
			return 'b_im_message';
		}

		getPrimaryKey()
		{
			return 'id';
		}

		getFields()
		{
			// null is temporary here for json fields, in the future we need an empty object by default {}

			return [
				{ name: 'id', type: FieldType.integer, unique: true, index: true },
				{ name: 'chatId', type: FieldType.integer },
				{ name: 'authorId', type: FieldType.integer },
				{ name: 'date', type: FieldType.date },
				{ name: 'text', type: FieldType.text },
				{ name: 'params', type: FieldType.json, defaultValue: FieldDefaultValue.null },
				{ name: 'files', type: FieldType.json, defaultValue: FieldDefaultValue.null },
				{ name: 'unread', type: FieldType.boolean },
				{ name: 'viewed', type: FieldType.boolean },
				{ name: 'viewedByOthers', type: FieldType.boolean },
				{ name: 'sending', type: FieldType.boolean },
				{ name: 'error', type: FieldType.boolean },
				{ name: 'retry', type: FieldType.boolean },
				{ name: 'attach', type: FieldType.json, defaultValue: FieldDefaultValue.null },
				{ name: 'keyboard', type: FieldType.json, defaultValue: FieldDefaultValue.emptyArray },
				{ name: 'forward', type: FieldType.json, defaultValue: FieldDefaultValue.null },
				{ name: 'richLinkId', type: FieldType.integer },
				{ name: 'previousId', type: FieldType.integer },
				{ name: 'nextId', type: FieldType.integer },
			];
		}

		/**
		 * @param {number} options.chatId
		 * @param {number} [options.fromMessageId]
		 * @param {number} options.limit
		 * @param {'top'|'bottom'} options.direction
		 * @param {boolean} options.includeFromMessageId
		 *
		 * @return {Promise<{items: Array}>}
		 */
		async getLinkedList(options)
		{
			if (!this.isSupported || !Feature.isLocalStorageEnabled)
			{
				return Promise.resolve({
					items: [],
				});
			}

			const fromMessageId = options.fromMessageId;
			let fromMessageIdQueryString;
			if (Type.isNumber(fromMessageId) && fromMessageId > 0)
			{
				fromMessageIdQueryString = `id = ${fromMessageId} AND chatId = ${options.chatId}`;
			}
			else
			{
				fromMessageIdQueryString = `
					id = (
						SELECT id
						FROM ${this.getName()}
						WHERE chatId = ${options.chatId}
						ORDER BY id DESC
						LIMIT 1
					)
				`;
			}

			const direction = [
				MessageTableGetLinkedListDirection.top,
				MessageTableGetLinkedListDirection.bottom,
			].includes(options.direction) ? options.direction : MessageTableGetLinkedListDirection.top;
			const directionFieldName = direction === MessageTableGetLinkedListDirection.top ? 'previousId' : 'nextId';

			const includeFromMessageId = Type.isBoolean(options.includeFromMessageId) ? options.includeFromMessageId : true;

			const query = `
				WITH RECURSIVE linked_messages AS (
					SELECT *
					FROM ${this.getName()}
					WHERE ${fromMessageIdQueryString}
					UNION ALL
						SELECT m.*
						FROM ${this.getName()} m
						INNER JOIN linked_messages lm ON lm.${directionFieldName} = m.id
						LIMIT ${options.limit}
				)
				SELECT * FROM linked_messages
				ORDER BY id DESC;
			`;

			const selectResult = await this.executeSql({
				query,
			});

			const getListResult = this.convertSelectResultToGetListResult(selectResult, true);
			const linkedList = {
				items: [],
			};
			if (includeFromMessageId)
			{
				linkedList.items = getListResult.items;
			}
			else
			{
				linkedList.items = getListResult.items.filter((message) => message.id !== fromMessageId);
			}

			return linkedList;
		}

		async add(messageList, replace = true)
		{
			const messageIdCollection = {};
			messageList.forEach((message) => {
				messageIdCollection[message.id] = true;
			});

			const messagesToUpdatePrevious = [];
			messageList.forEach((message) => {
				if (messageIdCollection[message.previousId])
				{
					return;
				}

				messagesToUpdatePrevious.push(message);
			});

			const updatePromiseList = [];
			messagesToUpdatePrevious.forEach((message) => {
				const updatePromise = this.updatePreviousMessageByNextMessage(message);
				updatePromiseList.push(updatePromise);
			});

			try
			{
				await Promise.all(updatePromiseList);
			}
			catch (error)
			{
				logger.error(
					'MessageTable.updatePreviousMessageByNextMessage: error: ',
					messagesToUpdatePrevious,
					updatePromiseList,
					error,
				);
			}

			return super.add(messageList, replace);
		}

		/**
		 * Provides recording of a doubly linked list of messages.
		 * Example we add a message with id === 3 and previousId === 2,
		 * this means that we need to update the message with id === 2 and set it to nextId = 3.
		 *
		 * @param message
		 * @return {Promise<*>}
		 */
		async updatePreviousMessageByNextMessage(message)
		{
			logger.log('MessageTable.updatePreviousMessageByNextMessage: ', message);

			return this.updateNextId(message.previousId, message.id);
		}

		async updatePreviousId(messageId, newPreviousId)
		{
			logger.log('MessageTable.updatePreviousId: ', messageId, newPreviousId);

			const updateOptions = {
				filter: {
					id: messageId,
				},
				fields: {
					previousId: newPreviousId,
				},
			};

			return this.update(updateOptions);
		}

		async updateNextId(messageId, nextPreviousId)
		{
			logger.log('MessageTable.updateNextId: ', messageId, nextPreviousId);

			const updateOptions = {
				filter: {
					id: messageId,
				},
				fields: {
					nextId: nextPreviousId,
				},
			};

			return this.update(updateOptions);
		}

		async deleteByIdList(idList)
		{
			if (
				!this.isSupported
				|| this.readOnly
				|| !Feature.isLocalStorageEnabled
				|| !Type.isArrayFilled(idList)
			)
			{
				return Promise.resolve({});
			}

			for (const id of idList)
			{
				const messageToBeDeleted = await this.getById(id);
				if (!messageToBeDeleted)
				{
					continue;
				}

				const messageBeforeBeingDeleted = await this.getById(messageToBeDeleted.previousId);
				const messageAfterBeingDeleted = await this.getById(messageToBeDeleted.nextId);
				if (messageBeforeBeingDeleted && messageAfterBeingDeleted)
				{
					await this.updateNextId(messageBeforeBeingDeleted.id, messageAfterBeingDeleted.id);
					await this.updatePreviousId(messageAfterBeingDeleted.id, messageBeforeBeingDeleted.id);
				}

				if (!messageBeforeBeingDeleted && messageAfterBeingDeleted)
				{
					await this.updatePreviousId(messageAfterBeingDeleted.id, 0);
				}

				if (!messageAfterBeingDeleted && messageBeforeBeingDeleted)
				{
					await this.updateNextId(messageBeforeBeingDeleted.id, 0);
				}

				await this.delete({ id });
			}

			logger.log(`MessageTable.deleteByIdList complete: ${this.getName()}`, idList);

			return Promise.resolve({});
		}
	}

	module.exports = {
		MessageTable,
		MessageTableGetLinkedListDirection,
	};
});
