/**
 * @module im/messenger/provider/push/message-handler/database
 */
jn.define('im/messenger/provider/push/message-handler/database', (require, exports, module) => {
	const { Type } = require('type');
	const { WaitingEntity } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { BasePushMessageHandler } = require('im/messenger/provider/push/message-handler/base');

	/**
	 * @class DatabasePushMessageHandler
	 */
	class DatabasePushMessageHandler extends BasePushMessageHandler
	{
		constructor()
		{
			super();
			/**
			 * @type {DialogRepository}
			 */
			this.dialogRepository = serviceLocator.get('core').getRepository().dialog;
			/**
			 * @type {UserRepository}
			 */
			this.userRepository = serviceLocator.get('core').getRepository().user;
			/**
			 * @type {FileRepository}
			 */
			this.fileRepository = serviceLocator.get('core').getRepository().file;
			/**
			 * @type {MessageRepository}
			 */
			this.messageRepository = serviceLocator.get('core').getRepository().message;
			/**
			 * @type {RecentRepository}
			 */
			this.recentRepository = serviceLocator.get('core').getRepository().recent;
		}

		getHandlerId()
		{
			return WaitingEntity.push.messageHandler.database;
		}

		/**
		 * @param {Array<MessengerPushEvent>} eventList
		 * @return {Array<MessengerPushEvent>}
		 */
		filterMessageEvents(eventList)
		{
			const verifiedEvents = [];
			for (const event of eventList)
			{
				if (event.command === 'message')
				{
					verifiedEvents.push(event);

					continue;
				}

				const helper = this.getHelper(event);

				if (helper.isLines())
				{
					continue;
				}

				if (!helper.isChatExist())
				{
					continue;
				}

				if (helper.isOpenChannelChat && !helper.isUserInChat())
				{
					continue;
				}

				verifiedEvents.push(event);
			}

			return verifiedEvents;
		}

		async setDialogs(dialogs = [])
		{
			if (!Type.isArrayFilled(dialogs))
			{
				return Promise.resolve();
			}

			return this.dialogRepository.saveFromPush(dialogs);
		}

		async setUsers(users = [])
		{
			if (!Type.isArrayFilled(users))
			{
				return Promise.resolve();
			}

			return this.userRepository.saveFromPush(users);
		}

		async setFiles(files = [])
		{
			if (!Type.isArrayFilled(files))
			{
				return Promise.resolve();
			}

			return this.fileRepository.saveFromPush(files);
		}

		async setMessages(messages = [])
		{
			if (!Type.isArrayFilled(messages))
			{
				return Promise.resolve();
			}

			return this.messageRepository.saveFromPush(messages);
		}

		async setRecent(recentItems = [])
		{
			if (!Type.isArrayFilled(recentItems))
			{
				return Promise.resolve();
			}

			return this.recentRepository.saveFromPush(recentItems);
		}
	}

	module.exports = { DatabasePushMessageHandler };
});
