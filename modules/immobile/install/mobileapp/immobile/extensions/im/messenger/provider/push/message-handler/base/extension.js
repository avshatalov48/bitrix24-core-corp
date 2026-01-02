/**
 * @module im/messenger/provider/push/message-handler/base
 */

jn.define('im/messenger/provider/push/message-handler/base', (require, exports, module) => {
	const { Type } = require('type');
	const {
		DialogType,
		EventType,
		MessageStatus,
	} = require('im/messenger/const');
	const { RecentDataConverter } = require('im/messenger/lib/converter/data/recent');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { ComponentRequestBroadcaster } = require('im/messenger/lib/component-request-broadcaster');
	const { Feature } = require('im/messenger/lib/feature');

	const { PushHelper } = require('im/messenger/provider/push/message-handler/lib/helper');
	const { getLogger } = require('im/messenger/lib/logger');

	const logger = getLogger('push-handler');

	/**
	 * @class BasePushMessageHandler
	 */
	class BasePushMessageHandler
	{
		constructor()
		{
			this.bindMethods();

			if (!Feature.isMessengerV2Enabled)
			{
				this.subscribeEvents();
			}
		}

		/**
		 * @return {MessengerCoreStore}
		 */
		get store()
		{
			return serviceLocator.get('core').getStore();
		}

		/**
		 * @deprecated
		 * @abstract
		 * @return {string}
		 */
		getHandlerId()
		{
			throw new Error('should implements this method');
		}

		/**
		 * @abstract
		 * @param {Array<MessengerPushEvent>} eventList
		 * @return {Array<MessengerPushEvent>}
		 */
		filterMessageEvents(eventList)
		{
			throw new Error('should implements this method');
		}

		destructor()
		{
			this.unsubscribeEvents();
		}

		/**
		 * @deprecated
		 */
		bindMethods()
		{
			this.handleMessageBatch = this.handleMessageBatch.bind(this);
		}

		/**
		 * @deprecated
		 */
		subscribeEvents()
		{
			ComponentRequestBroadcaster.getInstance().registerHandler(
				EventType.push.messageBatch,
				this.getHandlerId(),
				this.handleMessageBatch,
			);
		}

		/**
		 * @deprecated
		 */
		unsubscribeEvents()
		{
			ComponentRequestBroadcaster.getInstance().unregisterHandler(
				EventType.push.messageBatch,
				this.getHandlerId(),
			);
		}

		async handleMessageBatch(eventList)
		{
			const componentEventList = this.filterMessageEvents(eventList);
			if (componentEventList.length === 0)
			{
				return;
			}
			logger.log(`${this.className}.handleMessageBatch`, eventList);
			const modelData = this.prepareData(eventList);
			try
			{
				await this.setData(modelData);
			}
			catch (error)
			{
				logger.error(`${this.className}.handleMessageBatch error`, error);
			}
			logger.warn(`${this.className} handleMessageBatch completed`);
		}

		async setData(modelData)
		{
			await this.setUsers(modelData.users);
			await this.setDialogs(modelData.dialogs);
			await this.setFiles(modelData.files);
			await this.setMessages(modelData.messages);
			await this.setRecent(modelData.recent);
		}

		async setDialogs(dialogs = [])
		{
			if (!Type.isArrayFilled(dialogs))
			{
				return;
			}

			await this.store.dispatch('dialoguesModel/setFromPush', dialogs);
		}

		async setUsers(users = [])
		{
			if (!Type.isArrayFilled(users))
			{
				return;
			}

			await this.store.dispatch('usersModel/setFromPush', users);
		}

		async setFiles(files = [])
		{
			if (!Type.isArrayFilled(files))
			{
				return;
			}

			await this.store.dispatch('filesModel/setFromPush', files);
		}

		async setMessages(messages = [])
		{
			if (!Type.isArrayFilled(messages))
			{
				return;
			}

			await this.store.dispatch('messagesModel/setFromPush', messages);
		}

		async setRecent(recentItems = [])
		{
			if (!Type.isArrayFilled(recentItems))
			{
				return;
			}

			await this.store.dispatch('recentModel/setFromPush', recentItems);
		}

		prepareData(componentEventList)
		{
			return {
				dialogs: this.prepareDialogs(componentEventList),
				users: this.prepareUsers(componentEventList),
				files: this.prepareFiles(componentEventList),
				messages: this.prepareMessages(componentEventList),
				recent: this.prepareRecentItems(componentEventList),
			};
		}

		/**
		 * @param {Array<MessengerPushEvent>} eventList
		 * @return {Array<RawChat>}
		 */
		prepareDialogs(eventList)
		{
			const dialogCollection = {};

			for (const event of eventList)
			{
				let dialog = null;
				const helper = this.getHelper(event);

				if (helper.isChatExist())
				{
					dialog = this.prepareGroupChat(event);
				}
				else
				{
					dialog = this.prepareUserChat(event);
				}

				dialogCollection[dialog.dialogId] = dialog;
			}

			return Object.values(dialogCollection);
		}

		/**
		 * @param {Array<MessengerPushEvent>} eventList
		 * @return {Array<RawUser>}
		 */
		prepareUsers(eventList)
		{
			const userCollection = {};

			for (const event of eventList)
			{
				const helper = this.getHelper(event);

				helper.getUsers().forEach((user) => {
					userCollection[user.id] = user;
				});
			}

			return Object.values(userCollection);
		}

		/**
		 * @param {Array<MessengerPushEvent>} eventList
		 * @return {Array<RawFile>}
		 */
		prepareFiles(eventList)
		{
			const filesCollection = {};

			for (const event of eventList)
			{
				const helper = this.getHelper(event);

				helper.getFiles().forEach((user) => {
					filesCollection[user.id] = user;
				});
			}

			return Object.values(filesCollection);
		}

		/**
		 * @param {Array<MessengerPushEvent>} eventList
		 * @return {Array<RawMessage>}
		 */
		prepareMessages(eventList)
		{
			const messages = [];

			for (const event of eventList)
			{
				const message = this.getHelper(event).getMessage();

				// if params are empty then they have an array type.
				if (Type.isPlainObject(message.params) && Type.isArrayFilled(message.params?.FILE_ID))
				{
					message.params.FILE_ID = message.params.FILE_ID.map((fileId) => Number(fileId));
				}

				messages.push({
					...message,
				});
			}

			return messages;
		}

		/**
		 * @param {Array<MessengerPushEvent>} eventList
		 * @return {Array<object>}
		 */
		prepareRecentItems(eventList)
		{
			const recentCollection = {};

			for (const event of eventList)
			{
				const helper = this.getHelper(event);
				const message = this.prepareRecentMessage(event);

				const recentItem = RecentDataConverter.fromPushToModel({
					id: helper.getDialogId(),
					chat: helper.getChat(),
					user: helper.getSender(),
					lines: event.params.lines, // undefined it's OK
					counter: event.params.counter,
					liked: false,
					lastActivityDate: event.params.message.date,
					dateMessage: event.params.message.date,
					message,
				});

				recentCollection[recentItem.id] = recentItem;
			}

			return Object.values(recentCollection);
		}

		/**
		 *
		 * @param {MessengerPushEvent} event
		 * @return {PushRawMessage & {status: string}}
		 */
		prepareRecentMessage(event)
		{
			const helper = this.getHelper(event);
			const messageStatus = helper.getSenderId() === helper.getCurrentUserId()
				? MessageStatus.received
				: ''
			;

			return {
				...helper.getMessage(),
				status: messageStatus,
			};
		}

		get className()
		{
			return this.constructor.name;
		}

		/**
		 * @param {MessengerPushEvent} event
		 * @return {PushHelper}
		 */
		getHelper(event)
		{
			return new PushHelper(event);
		}

		/**
		 * @param {MessengerPushEvent} event
		 */
		prepareGroupChat(event)
		{
			const { params } = event;

			return {
				...this.getHelper(event).getChat(),
				dialogId: params.dialogId,
				counter: params.counter,
			};
		}

		/**
		 * @param {MessengerPushEvent} event
		 */
		prepareUserChat(event)
		{
			const { params } = event;

			const sender = this.getHelper(event).getSender();

			return {
				dialogId: params.dialogId,
				counter: params.counter,
				type: DialogType.private,
				name: sender.name,
				avatar: sender.avatar,
				color: sender.color,
				chatId: params.chatId,
			};
		}
	}

	module.exports = { BasePushMessageHandler };
});
