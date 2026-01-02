/**
 * @module im/messenger/lib/counters/counter-manager/messenger/sender
 */
jn.define('im/messenger/lib/counters/counter-manager/messenger/sender', (require, exports, module) => {
	const {
		CounterType,
		ComponentCode,
	} = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { Event } = require('im/messenger/lib/counters/counter-manager/const');

	/**
	 * @class MessengerCounterSender
	 */
	class MessengerCounterSender
	{
		static #instance;

		/**
		 * @return {MessengerCounterSender}
		 */
		static getInstance()
		{
			if (!this.#instance)
			{
				this.#instance = new this();
			}

			return this.#instance;
		}

		/**
		 * @param {immobileTabsLoadCommonResult['imCounters']} counters
		 */
		sendInitialCounters(counters)
		{
			const counterStateList = this.#prepareInitialCounters(counters);

			MessengerEmitter.emit(Event.initialCountersLoaded, counterStateList, ComponentCode.imNavigation);
		}

		/**
		 * @param {Array<CounterState>} counterStateList
		 */
		sendRecentPageLoaded(counterStateList)
		{
			MessengerEmitter.emit(Event.recentPageLoaded, counterStateList, ComponentCode.imNavigation);
		}

		sendReadAll()
		{
			MessengerEmitter.emit(Event.readAll, {}, ComponentCode.imNavigation);
		}

		/**
		 * @param {Array<number>} chatIdList
		 */
		sendDeleteChats(chatIdList)
		{
			MessengerEmitter.emit(Event.deleteChats, chatIdList, ComponentCode.imNavigation);
		}

		/**
		 * @param {number} channelId
		 */
		sendReadChannelComments(channelId)
		{
			MessengerEmitter.emit(Event.readChannelComments, channelId, ComponentCode.imNavigation);
		}

		/**
		 * @param {immobileTabsLoadCommonResult['imCounters']} counters
		 * @return {Array<CounterState>}
		 */
		#prepareInitialCounters(counters)
		{
			const chatCounters = Object.entries(counters.chat).map(([chatId, counter]) => {
				return {
					chatId: Number(chatId),
					parentChatId: 0,
					counter,
					type: CounterType.chat,
				};
			});

			const mutedCounters = Object.entries(counters.chatMuted).map(([chatId, counter]) => {
				return {
					chatId: Number(chatId),
					parentChatId: 0,
					counter,
					type: CounterType.chat,
				};
			});

			const collabCounters = Object.entries(counters.collab).map(([chatId, counter]) => {
				return {
					chatId: Number(chatId),
					parentChatId: 0,
					counter,
					type: CounterType.collab,
				};
			});

			const copilotCounters = Object.entries(counters.copilot).map(([chatId, counter]) => {
				return {
					chatId: Number(chatId),
					parentChatId: 0,
					counter,
					type: CounterType.copilot,
				};
			});

			const linesCounters = Object.entries(counters.lines).map(([chatId, counter]) => {
				return {
					chatId: Number(chatId),
					parentChatId: 0,
					counter,
					type: CounterType.openline,
				};
			});

			const commentCounters = Object.entries(counters.channelComment)
				.flatMap(([channelChatId, counterMap]) => {
					return Object.entries(counterMap).map(([commentChatId, counter]) => {
						return {
							chatId: Number(commentChatId),
							parentChatId: Number(channelChatId),
							counter,
							type: CounterType.comment,
						};
					});
				})
			;

			return [
				...chatCounters,
				...mutedCounters,
				...collabCounters,
				...copilotCounters,
				...linesCounters,
				...commentCounters,
			];
		}
	}

	module.exports = { MessengerCounterSender };
});
