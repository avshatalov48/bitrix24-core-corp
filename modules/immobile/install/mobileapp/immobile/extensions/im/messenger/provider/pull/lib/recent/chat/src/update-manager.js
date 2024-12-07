/**
 * @module im/messenger/provider/pull/lib/recent/chat/update-manager
 */
jn.define('im/messenger/provider/pull/lib/recent/chat/update-manager', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
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
		 * @return {RawMessage}
		 */
		getLastMessage()
		{
			const [lastMessage] = this.#params.messages;

			return lastMessage;
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
