/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/provider/service/classes/rest-data-extractor
 */
jn.define('im/messenger/provider/service/classes/rest-data-extractor', (require, exports, module) => {

	const { RestMethod } = require('im/messenger/const/rest');
	const { UserManager } = require('im/messenger/lib/user-manager');

	/**
	 * @class RestDataExtractor
	 */
	class RestDataExtractor
	{
		constructor(response)
		{
			this.response = {};
			this.chatId = 0;
			this.dialogId = '';

			this.rawUsers = [];

			this.users = {};
			this.dialogues = {};
			this.files = {};
			this.messages = {};
			this.messagesToStore = {};
			this.pinnedMessageIds = [];

			Object.keys(response).forEach(restManagerResponseKey => {
				const restMethod = restManagerResponseKey.split('|')[0];
				const ajaxResult = response[restManagerResponseKey];

				delete response[restManagerResponseKey];
				response[restMethod] = ajaxResult.data();
			});

			this.response = response;
		}

		extractData()
		{
			this.extractChatResult();
			this.extractUserResult();
			this.extractMessageListResult();
			this.extractContextResult();
			this.extractPinnedMessagesResult();

			this.fillChatsForUsers();
		}

		getChatId()
		{
			return this.chatId;
		}

		getUsers()
		{
			return this.rawUsers;
		}

		getDialogues()
		{
			return Object.values(this.dialogues);
		}

		getMessages()
		{
			return Object.values(this.messages);
		}

		getMessagesToStore()
		{
			return Object.values(this.messagesToStore);
		}

		getFiles()
		{
			return Object.values(this.files);
		}

		getPinnedMessages()
		{
			return this.pinnedMessageIds;
		}

		extractChatResult()
		{
			const chat = this.response[RestMethod.imChatGet];
			this.chatId = chat.id;
			this.dialogId = chat.dialog_id;
			if (!this.dialogues[chat.dialog_id])
			{
				this.dialogues[chat.dialog_id] = chat;
			}
		}

		extractUserResult()
		{
			// solo user for group chats
			const soloUser = this.response[RestMethod.imUserGet];
			if (soloUser)
			{
				this.rawUsers = [soloUser];
				return;
			}

			// two users for 1v1
			const userList = this.response[RestMethod.imUserListGet];
			if (userList)
			{
				this.rawUsers = Object.values(userList);
			}
		}

		extractMessageListResult()
		{
			const messageList = this.response[RestMethod.imV2ChatMessageList];
			if (!messageList)
			{
				return;
			}

			this.extractPaginationFlags(messageList);
			this.extractMessages(messageList);
		}

		extractPaginationFlags(data)
		{
			const {
				hasPrevPage,
				hasNextPage,
			} = data;

			this.dialogues[this.dialogId] = {
				...this.dialogues[this.dialogId],
				hasPrevPage,
				hasNextPage
			};
		}

		extractContextResult()
		{
			const contextMessageList = this.response[RestMethod.imDialogContextGet];
			if (!contextMessageList)
			{
				return;
			}

			this.extractMessages(contextMessageList);
		}

		extractPinnedMessagesResult()
		{
			const pinMessageList = this.response[RestMethod.imChatPinGet];
			if (!pinMessageList)
			{
				return;
			}

			const {list = [], users = [], files: pinnedFiles = []} = pinMessageList;
			this.rawUsers = [...this.rawUsers, ...users];
			pinnedFiles.forEach(file => {
				this.files[file.id] = file;
			});
			list.forEach(pinnedItem => {
				this.pinnedMessageIds.push(pinnedItem.messageId);
				this.messagesToStore[pinnedItem.message.id] = pinnedItem.message;
			});
		}

		extractMessages(data)
		{
			const {messages, users, files} = data;
			files.forEach(file => {
				this.files[file.id] = file;
			});
			messages.forEach(message => {
				this.messages[message.id] = message;
			});

			this.rawUsers = [...this.rawUsers, ...users];
		}

		fillChatsForUsers()
		{
			this.rawUsers.forEach(user => {
				if (!this.dialogues[user.id])
				{
					this.dialogues[user.id] = UserManager.getDialogForUser(user);
				}
				else
				{
					this.dialogues[user.id] = {...this.dialogues[user.id], ...UserManager.getDialogForUser(user)};
				}
			});
		}
	}

	module.exports = {
		RestDataExtractor,
	};
});
