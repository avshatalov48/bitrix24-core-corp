/**
 * @module im/messenger/provider/push/message-handler/messenger
 */
jn.define('im/messenger/provider/push/message-handler/messenger', (require, exports, module) => {
	const { Type } = require('type');
	const { WaitingEntity, RecentTab, CounterType } = require('im/messenger/const');
	const { RecentDataConverter } = require('im/messenger/lib/converter/data/recent');
	const { BasePushMessageHandler } = require('im/messenger/provider/push/message-handler/base');

	/**
	 * @class MessengerPushMessageHandler
	 */
	class MessengerPushMessageHandler extends BasePushMessageHandler
	{
		/**
		 * @deprecated
		 * @return {string}
		 */
		getHandlerId()
		{
			return WaitingEntity.push.messageHandler.chat;
		}

		prepareData(componentEventList)
		{
			const modelData = super.prepareData(componentEventList);

			return {
				...modelData,
				counters: this.prepareCounters(componentEventList),
			};
		}

		async setData(modelData)
		{
			await this.setCounters(modelData.counters);
			await super.setData(modelData);
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

		/**
		 * @param {Array<MessengerPushEvent>} eventList
		 * @return {Record<string, Array<object>>}
		 */
		prepareRecentItems(eventList)
		{
			const groups = {
				[RecentTab.chat]: {},
				[RecentTab.copilot]: {},
				[RecentTab.collab]: {},
				[RecentTab.tasksTask]: {},
			};

			for (const event of eventList)
			{
				const helper = this.getHelper(event);
				const message = this.prepareRecentMessage(event);

				const recentItem = RecentDataConverter.fromPushToModel({
					id: String(helper.getDialogId()),
					chat: helper.getChat(),
					user: helper.getSender(),
					lines: event.params.lines, // undefined it's OK
					counter: event.params.counter,
					liked: false,
					lastActivityDate: event.params.message.date,
					dateMessage: event.params.message.date,
					message,
				});

				if (helper.isTaskChat)
				{
					groups[RecentTab.tasksTask][recentItem.id] = recentItem;

					continue;
				}

				groups[RecentTab.chat][recentItem.id] = recentItem;

				if (helper.isCopilotChat())
				{
					groups[RecentTab.copilot][recentItem.id] = recentItem;
				}

				if (helper.isCollabChat())
				{
					groups[RecentTab.collab][recentItem.id] = recentItem;
				}
			}

			groups[RecentTab.chat] = Object.values(groups[RecentTab.chat]);
			groups[RecentTab.copilot] = Object.values(groups[RecentTab.copilot]);
			groups[RecentTab.collab] = Object.values(groups[RecentTab.collab]);
			groups[RecentTab.tasksTask] = Object.values(groups[RecentTab.tasksTask]);

			return groups;
		}

		/**
		 * @param {Array<MessengerPushEvent>} eventList
		 * @return {Array<CounterModelState>}
		 */
		prepareCounters(eventList)
		{
			/**
			 * @type {Record<number, CounterModelState>}
			 */
			const counterCollection = {};

			for (const event of eventList)
			{
				const { params } = event;
				const helper = this.getHelper(event);
				const chatId = helper.getChatId();

				if (helper.isCollabChat())
				{
					counterCollection[chatId] = {
						counter: params.counter,
						chatId,
						type: CounterType.collab,
					};

					continue;
				}

				if (helper.isCopilotChat())
				{
					counterCollection[chatId] = {
						counter: params.counter,
						chatId,
						type: CounterType.copilot,
					};

					continue;
				}

				counterCollection[helper.getChatId()] = {
					counter: params.counter,
					chatId,
					type: CounterType.chat,
				};
			}

			return Object.values(counterCollection);
		}

		async setRecent(groups = {})
		{
			await this.store.dispatch('recentModel/setGroupCollection', {
				groups,
			});
		}

		async setCounters(counterList = [])
		{
			if (!Type.isArrayFilled(counterList))
			{
				return;
			}

			await this.store.dispatch('counterModel/setList', { counterList });
		}
	}

	module.exports = { MessengerPushMessageHandler };
});
