/**
 * @module im/messenger/db/table/message-push
 */
jn.define('im/messenger/db/table/message-push', (require, exports, module) => {
	const { Feature } = require('im/messenger/lib/feature');

	const {
		Table,
		FieldType,
	} = require('im/messenger/db/table/table');

	/**
	 * @extends {Table<MessagePushStoredData>}
	 */
	class MessagePushTable extends Table
	{
		getName()
		{
			return 'b_im_message_push';
		}

		getPrimaryKey()
		{
			return 'id';
		}

		getFields()
		{
			return [
				{ name: 'id', type: FieldType.integer, defaultValue: 0, unique: true, index: true },
				{ name: 'chatId', type: FieldType.integer, defaultValue: 0, index: true },
				{ name: 'authorId', type: FieldType.integer, defaultValue: 0 }, // senderId from push notification
				{ name: 'date', type: FieldType.date, defaultValue: 0 },
				{ name: 'text', type: FieldType.text, defaultValue: '' },
				{ name: 'params', type: FieldType.json, defaultValue: {} },
				{ name: 'previousId', type: FieldType.integer, defaultValue: 0 },

				// the following fields are not used in the b_im_message table
				{ name: 'dialogId', type: FieldType.text, defaultValue: '0', index: true }, // recipientId from push notification
				{ name: 'textOriginal', type: FieldType.text, defaultValue: '' },
				{ name: 'system', type: FieldType.boolean, defaultValue: false },
				{ name: 'push', type: FieldType.boolean, defaultValue: false },
			];
		}

		/**
		 *
		 * @param {{chatId: number, fromMessageId: number}} options
		 * @return {{items: Array<MessagePushStoredData>}}
		 */
		async getPage(options)
		{
			if (!this.isSupported || !Feature.isLocalStorageEnabled)
			{
				return Promise.resolve({
					items: [],
				});
			}

			const fromMessageId = options.fromMessageId;

			const result = await this.getList({
				filter: {
					chatId: options.chatId,
				},
				order: {
					id: 'desc',
				},
			});

			const isPageWithMessage = result.items.some((message) => {
				return Number(message.id) === Number(fromMessageId)
					|| Number(message.previousId) === Number(fromMessageId)
				;
			});

			if (!isPageWithMessage)
			{
				return {
					items: [],
				};
			}

			return {
				items: result.items.map((item) => {
					return {
						...item,
						unread: true,
						viewed: false,
					};
				}),
			};
		}
	}

	module.exports = {
		MessagePushTable,
	};
});
