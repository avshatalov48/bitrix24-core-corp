/**
 * @module im/messenger/provider/pull/lib/recent/chat/update-manager/update-manager
 */
jn.define('im/messenger/provider/pull/lib/recent/chat/update-manager/update-manager', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { RecentDataConverter } = require('im/messenger/lib/converter/data/recent');

	/**
	 * @class ChatRecentUpdateManager
	 */
	class ChatRecentUpdateManager
	{
		/** @type {RecentUpdateParams} */
		#params;
		/** @type {MessengerCoreStore} */
		#store;

		/**
		 * @param {RecentUpdateParams} params
		 */
		constructor(params)
		{
			this.#params = params;
			this.#store = serviceLocator.get('core').getStore();
		}

		setLastMessageInfo()
		{
			this.#setMessageChat();
			this.#setUsers();
			this.#setFiles();
			this.#setMessage();
		}

		getDialogId()
		{
			return this.#params.chat.dialogId;
		}

		getLastMessageId()
		{
			return this.getLastMessage().id;
		}

		/**
		 * @return {RawMessage} // todo check types
		 */
		getLastMessage()
		{
			const [lastMessage] = this.#params.messages;

			return { ...lastMessage };
		}

		/**
		 * @return {Partial<RawMessage>} // todo check types
		 */
		getPreparedLastMessage()
		{
			const message = this.getLastMessage();
			const currentUserId = serviceLocator.get('core').getUserId();
			message.status = message.author_id === currentUserId ? 'received' : '';
			message.senderId = message.author_id;

			return message;
		}

		/**
		 * @return {RecentModelState}
		 */
		getPreparedRecentItem()
		{
			const message = this.getPreparedLastMessage();

			const userData = message.author_id > 0
				? this.#params.users[message.author_id]
				: { id: 0 };

			return RecentDataConverter.fromPullToModel({
				id: this.getDialogId(),
				chat: this.#params.chat,
				user: userData,
				counter: this.#params.counter,
				lastActivityDate: this.#params.lastActivityDate,
				message,
			});
		}

		#setUsers()
		{
			this.#store.dispatch('usersModel/set', this.#params.users);
		}

		#setFiles()
		{
			this.#store.dispatch('filesModel/set', this.#params.files);
		}

		#setMessageChat()
		{
			const chat = {
				...this.#params.chat,
				counter: this.#params.counter,
				dialogId: this.getDialogId(),
			};

			this.#store.dispatch('dialoguesModel/set', chat);
		}

		#setMessage()
		{
			const lastChannelPost = this.getLastMessage();
			this.#store.dispatch('messagesModel/store', lastChannelPost);
		}
	}

	module.exports = { ChatRecentUpdateManager };
});
