/**
 * @module im/messenger/lib/counters/counter-manager/navigation
 */
jn.define('im/messenger/lib/counters/counter-manager/navigation', (require, exports, module) => {
	const { Event } = require('im/messenger/lib/counters/counter-manager/const');
	const { getLogger } = require('im/messenger/lib/logger');
	const { Feature } = require('im/messenger/lib/feature');
	const { CounterStorageWriter } = require('im/messenger/lib/counters/counter-manager/storage/writer');
	const { CounterPushMessageHandler } = require('im/messenger/provider/push/message-handler/counter');
	const { SyncFillerCounter } = require('im/messenger/provider/services/sync/fillers/counter');
	const { CounterPullHandler } = require('im/messenger/provider/pull/counter');
	const { ReadMessageService } = require('im/messenger/provider/services/read');

	const logger = getLogger('counters--navigation-handler');

	/**
	 * @class NavigationCounterHandler
	 */
	class NavigationCounterHandler
	{
		static #instance;

		/**
		 * @return {NavigationCounterHandler}
		 */
		static getInstance()
		{
			if (!this.#instance)
			{
				this.#instance = new this();
			}

			return this.#instance;
		}

		constructor()
		{
			this.pushHandler = new CounterPushMessageHandler(CounterStorageWriter.getInstance());
			this.syncHandler = new SyncFillerCounter(CounterStorageWriter.getInstance());
			this.readMessageService = new ReadMessageService();
			this.writer = CounterStorageWriter.getInstance();
			this.pullHandler = new CounterPullHandler(CounterStorageWriter.getInstance());
			this.#subscribeEvents();
			this.#subscribePullEvents();
		}

		get className()
		{
			return this.constructor.name;
		}

		#subscribeEvents()
		{
			BX.addCustomEvent(Event.initialCountersLoaded, this.#initialCountersLoadedHandler);
			BX.addCustomEvent(Event.recentPageLoaded, this.#recentPageLoadedHandler);
			BX.addCustomEvent(Event.readAll, this.#readAllHandler);
			BX.addCustomEvent(Event.readChannelComments, this.#readChannelCommentsHandler);
			BX.addCustomEvent(Event.deleteChats, this.#deleteChatsHandler);
		}

		#subscribePullEvents()
		{
			BX.PULL.subscribe(this.pullHandler);
		}

		/**
		 * @param {Array<CounterState>} counterStateList
		 */
		#initialCountersLoadedHandler = (counterStateList) => {
			logger.log(`${this.className}.initialCountersLoadedHandler`, counterStateList);
			const counterCollection = this.#convertCounterStateListToCollection(counterStateList);

			this.writer.setCollection(counterCollection)
				.catch((error) => {
					logger.error(error);
				})
			;
		};

		/**
		 * @param {Array<CounterState>} counterStateList
		 */
		#recentPageLoadedHandler = (counterStateList) => {
			logger.log(`${this.className}.recentPageLoadedHandler`, counterStateList);
			const counterCollection = this.#convertCounterStateListToCollection(counterStateList);

			this.writer.setCollection(counterCollection)
				.catch((error) => {
					logger.error(error);
				})
			;
		};

		#readAllHandler = () => {
			logger.log(`${this.className}.readAllHandler`);
			void this.writer.clearCounters();
		};

		#readChannelCommentsHandler = (channelChatId) => {
			logger.log(`${this.className}.readChannelCommentsHandler`, channelChatId);
			void this.writer.clearChannelCommentsCounter(channelChatId);
		};

		#deleteChatsHandler = (chatIdList) => {
			logger.log(`${this.className}.deleteChatsHandler`, chatIdList);
			void this.writer.deleteFromCollection(chatIdList);
		};

		/**
		 * @param {Array<CounterState>} counterStateList
		 * @return {CounterStateCollection}
		 */
		#convertCounterStateListToCollection(counterStateList)
		{
			const collection = {};

			for (const counterState of counterStateList)
			{
				collection[counterState.chatId] = counterState;
			}

			return collection;
		}
	}

	module.exports = { NavigationCounterHandler };
});
