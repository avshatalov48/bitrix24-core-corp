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
			this.errors = {};
			this.chatId = 0;
			this.dialogId = '';

			this.rawUsers = [];

			this.users = {};
			this.dialogues = {};
			this.files = {};
			this.messages = {};
			this.messagesToStore = {};
			this.pinnedMessageIds = [];
			this.reactions = [];
			this.usersShort = [];

			Object.keys(response).forEach((restManagerResponseKey) => {
				const restMethod = restManagerResponseKey.split('|')[0];
				const ajaxResult = response[restManagerResponseKey];

				// eslint-disable-next-line no-param-reassign
				delete response[restManagerResponseKey];
				// eslint-disable-next-line no-param-reassign
				response[restMethod] = ajaxResult.data();
				this.errors[restMethod] = ajaxResult.error();
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

		getUsersShort()
		{
			return this.usersShort;
		}

		getDialogues()
		{
			return Object.values(this.dialogues);
		}

		getMessages()
		{
			return Object.values(this.messages).sort((a, b) => a.id - b.id);
		}

		getMessagesToStore()
		{
			return Object.values(this.messagesToStore).sort((a, b) => a.id - b.id);
		}

		getFiles()
		{
			return Object.values(this.files);
		}

		getPinnedMessages()
		{
			return this.pinnedMessageIds;
		}

		getReactions()
		{
			return {
				reactions: this.reactions,
				usersShort: this.usersShort,
			};
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
			this.extractReactions(messageList);
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
				hasNextPage,
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

			const { list = [], users = [], files: pinnedFiles = [] } = pinMessageList;
			this.rawUsers = [...this.rawUsers, ...users];
			pinnedFiles.forEach((file) => {
				this.files[file.id] = file;
			});
			list.forEach((pinnedItem) => {
				this.pinnedMessageIds.push(pinnedItem.messageId);
				this.messagesToStore[pinnedItem.message.id] = pinnedItem.message;
			});
		}

		extractMessages(data)
		{
			const { messages, users, files, additionalMessages } = data;
			files.forEach((file) => {
				this.files[file.id] = file;
			});
			messages.forEach((message) => {
				this.messages[message.id] = message;
			});

			additionalMessages.forEach((message) => {
				this.messagesToStore[message.id] = message;
			});

			this.rawUsers = [...this.rawUsers, ...users];
		}

		extractReactions(data)
		{
			const { reactions, usersShort } = data;

			this.reactions = reactions;
			this.usersShort = usersShort;
		}

		fillChatsForUsers()
		{
			this.rawUsers.forEach((user) => {
				if (this.dialogues[user.id])
				{
					this.dialogues[user.id] = {
						...this.dialogues[user.id],
						...UserManager.getDialogForUser(user),
					};
				}
				else
				{
					this.dialogues[user.id] = UserManager.getDialogForUser(user);
				}
			});
		}
	}

	module.exports = {
		RestDataExtractor,
	};
});
