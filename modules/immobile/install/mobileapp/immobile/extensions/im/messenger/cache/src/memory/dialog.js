/* eslint-disable bitrix-rules/no-pseudo-private */
/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/cache/memory/dialog
 */
jn.define('im/messenger/cache/memory/dialog', (require, exports, module) => {

	const { MessengerParams } = jn.require('im/messenger/lib/params');

	/**
	 * @class DialogMemory
	 */
	class DialogMemory
	{
		constructor()
		{
			this.state = {
				currentDialogId: null,
				currentUserId: MessengerParams.getUserId(),
				quoteMessage: null,
				messageList: [],
				saveMessageList: {},
			};
		}

		getMessageState()
		{
			return {
				author_id: 0,
				dialogId: 0,
				date: new Date(),
				id: 0,
				params: {},
				text: '',
				unread: false,
			};
		}

		set currentDialogId(id)
		{
			this.state.currentDialogId = id;
		}

		get currentDialogId()
		{
			return this.state.currentDialogId;
		}

		get currentUserId()
		{
			return this.state.currentUserId;
		}

		getMessagePage(pageNumber, itemsPerPage)
		{
			const messageList = [...this.state.messageList];

			return messageList.splice((pageNumber - 1) * itemsPerPage, itemsPerPage);
		}

		getMessageByIndex(index)
		{
			return this.state.messageList[index] || {}
		}

		getMessageIdByIndex(index)
		{
			const message = this.getMessageByIndex(index);
			if (message.id)
			{
				return message.id;
			}

			return -1;
		}

		getMessageIndexById(id)
		{
			return this.state.messageList.findIndex((message) => message.id === id);
		}

		addMessages(payload)
		{
			payload.messages.forEach((newMessage) => {
				if (this.state.saveMessageList[newMessage.id])
				{
					const index = this.state.messageList.findIndex(message => message.id === newMessage.id);
					this.state.messageList[index] = newMessage;

					return;
				}

				this.state.messageList.push(newMessage);
				this.state.saveMessageList[newMessage.id] = true;
			});

			this.state.messageList.sort((a, b) => b.id - a.id);
		}

		pushMessage(payload)
		{
			const message = {
				...this.getMessageState(),
				...payload.message,
			};

			if (!message.id && message.templateId)
			{
				message.id = message.templateId;
			}

			if (this.state.saveMessageList[message.templateId])
			{
				const index = this.state.messageList.findIndex(message => message.id === message.templateId);
				this.state.messageList[index] = message;

				delete this.state.saveMessageList[message.templateId];
				this.state.saveMessageList[message.id] = true;

				this.state.messageList.sort((a, b) => b.id - a.id);

				return;
			}
			else if (this.state.saveMessageList[message.id])
			{
				const index = this.state.messageList.findIndex(message => message.id === message.id);
				this.state.messageList[index] = message;

				this.state.messageList.sort((a, b) => b.id - a.id);

				return;
			}

			this.state.messageList.push(message);
			this.state.saveMessageList[message.id] = true;

			this.state.messageList.sort((a, b) => b.id - a.id);
		}

		likeMessage(index)
		{
			const message = this.getMessageByIndex(index);
			if (!message.params)
			{
				message.params = {};
			}

			if (!message.params.LIKE)
			{
				message.params.LIKE = [];
			}

			const currentUserId = MessengerParams.getUserId();
			if (message.params.LIKE.includes(currentUserId))
			{
				message.params.LIKE = message.params.LIKE.filter(userId => userId !== currentUserId);
			}
			else
			{
				message.params.LIKE.push(currentUserId);
			}

			return message.params.LIKE;
		}
	}

	module.exports = {
		DialogMemory,
	};
});
