/**
 * @module im/messenger/provider/push/message-handler/lib/helper
 */
jn.define('im/messenger/provider/push/message-handler/lib/helper', (require, exports, module) => {
	const { Type } = require('type');
	const { DialogType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class PushHelper
	 */
	class PushHelper
	{
		/** @type {MessengerPushEvent} */
		#event;

		/**
		 * @param {MessengerPushEvent} event
		 */
		constructor(event)
		{
			this.#event = event;
		}

		/**
		 *
		 * @return {"message" | "messageChat"}
		 */
		getEventName()
		{
			return this.#event.command;
		}

		getCurrentUserId()
		{
			return serviceLocator.get('core').getUserId();
		}

		/**
		 * @return {PushRawMessage}
		 */
		getMessage()
		{
			return this.#event.params.message;
		}

		getUsers()
		{
			return Object.values(this.#event.params.users);
		}

		getFiles()
		{
			return Object.values(this.#event.params.files);
		}

		getChatId()
		{
			return this.#event.params.chatId;
		}

		isChatExist()
		{
			return Boolean(this.getChat());
		}

		/**
		 * @return {PushRawChat|null}
		 */
		getChat()
		{
			const chatId = this.getChatId();

			return this.#event.params.chat?.[chatId] ?? null;
		}

		getChatType()
		{
			const chat = this.getChat();

			return chat?.type ?? '';
		}

		getDialogId()
		{
			return this.#event.params.dialogId;
		}

		/**
		 * @return {number}
		 */
		getSenderId()
		{
			return this.getMessage().senderId;
		}

		getSender()
		{
			const senderId = this.getSenderId();

			return this.#event.params.users?.[senderId] ?? { id: 0 };
		}

		isChannelChat()
		{
			return [DialogType.channel, DialogType.openChannel, DialogType.generalChannel].includes(this.getChatType());
		}

		isOpenChannelChat()
		{
			return this.getChatType() === DialogType.openChannel;
		}

		isCopilotChat()
		{
			return this.getChatType() === DialogType.copilot;
		}

		isCollabChat()
		{
			return this.getChatType() === DialogType.collab;
		}

		isTaskChat()
		{
			return this.getChatType() === DialogType.tasksTask;
		}

		isLines()
		{
			return !Type.isNil(this.#event.params.lines);
		}

		isUserInChat()
		{
			if (Type.isArray(this.#event.params.userInChat))
			{
				return true;
			}

			const chatUsers = this.#event.params.userInChat[this.getChatId()];

			return chatUsers.includes(this.getCurrentUserId());
		}
	}

	module.exports = { PushHelper };
});
