/**
 * @module im/messenger-v2/provider/pull/counter
 */
jn.define('im/messenger-v2/provider/pull/counter', (require, exports, module) => {
	const { Type } = require('type');
	const { CounterType } = require('im/messenger/const');
	const { UuidManager } = require('im/messenger/lib/uuid-manager');
	const { BasePullHandler } = require('im/messenger/provider/pull/base');
	const { UserHelper, CounterHelper } = require('im/messenger/lib/helper');

	const { getLoggerWithContext } = require('im/messenger/lib/logger');

	const logger = getLoggerWithContext('pull-handler--counters', 'CounterPullHandler');

	/**
	 * @class CounterPullHandler
	 */
	class CounterPullHandler extends BasePullHandler
	{
		/** @type {UuidManager} */
		#uuidManager;

		/**
		 * @param {CounterStorageWriter} storageWriter
		 */
		constructor(storageWriter)
		{
			super({});

			this.#uuidManager = UuidManager.getInstance();
		}

		/**
		 * @param {MessageAddParams} params
		 * @param {PullExtraParams} extra
		 */
		handleMessage(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			logger.log('handleMessage', params, extra, command);

			const {
				chatId,
				counter,
				type = CounterType.chat,
				parentChatId = 0,
				message,
			} = params;

			const templateMessageId = message.templateId;

			if (this.#uuidManager.hasActionUuid(templateMessageId))
			{
				return;
			}

			this.#setCounter({
				chatId,
				counter,
				type,
				parentChatId,
			})
				.catch((error) => {
					logger.error('handleMessage error', error);
				});
		}

		/**
		 * @param {MessageAddParams} params
		 * @param {PullExtraParams} extra
		 */
		handleMessageChat(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			logger.log('handleMessageChat', params, extra, command);

			if (this.#isSharedEvent(extra))
			{
				logger.log('event is shared. skip event');

				return;
			}

			const chat = params.chat[params.chatId];

			/** @type {CounterModelState} */
			const counterState = {
				chatId: params.chatId,
				counter: params.counter,
				type: params.counterType,
				parentChatId: chat.parent_chat_id,
				disabled: CounterHelper.getDisabledByMuteList(chat.mute_list),
			};

			this.#setCounter(counterState)
				.catch((error) => {
					logger.error('handleMessageChat error', error);
				});
		}

		/**
		 * @param {MessagePullHandlerMessageDeleteV2Params} params
		 */
		handleMessageDeleteV2(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			logger.log('handleMessageDeleteV2', params, extra, command);

			if (this.#isSharedEvent(extra))
			{
				return;
			}

			const {
				chatId,
				counter,
				counterType,
			} = params;

			let {
				parentChatId = 0,
			} = params;

			if (counterType === CounterType.comment) // in this event for comment chat property "type" is equal 'chat'
			{
				const commentCounterState = this.#getStoredState(chatId);

				parentChatId = commentCounterState?.parentChatId ?? 0;
			}

			this.#setCounter({
				chatId,
				counter,
				type: counterType,
				parentChatId,
			})
				.catch((error) => {
					logger.error('handleMessageDeleteV2 error', error);
				});
		}

		async handleReadMessage(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			if (this.#isSharedEvent(extra))
			{
				return;
			}
			logger.log('handleReadMessage', params, extra, command);

			if (this.#isLocalActionUuid(extra))
			{
				logger.log('handleReadMessage. This event sent by current device. Skip event');

				return;
			}

			const {
				chatId,
				counter,
			} = params;

			/** @type {CounterState} */
			const counterState = {
				chatId,
				counter,
				type: CounterType.chat,
				parentChatId: 0,
			};

			this.#setCounter(counterState)
				.catch((error) => {
					logger.error('handleReadMessage error', error);
				});
		}

		handleReadAllChats(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			logger.info('handleReadAllChats', params);
			this.#clearCounters()
				.catch((error) => {
					logger.error('handleReadAllChats error', error);
				});
		}

		handleReadMessageChat(params, extra, command)
		{
			logger.log(params, extra, command);

			if (this.interceptEvent(extra))
			{
				return;
			}

			if (this.#isSharedEvent(extra))
			{
				return;
			}
			logger.log('handleReadMessageChat', params, extra, command);

			if (this.#isLocalActionUuid(extra))
			{
				logger.log('handleReadMessageChat. This event sent by current device. Skip event');

				return;
			}

			const {
				chatId,
				counter,
				counterType,
				parentChatId,
			} = params;

			/** @type {CounterState} */
			const counterState = {
				chatId,
				counter,
				parentChatId,
				type: counterType,
			};

			this.#setCounter(counterState)
				.catch((error) => {
					logger.error('handleReadMessageChat error', error);
				});
		}

		handleReadAllChannelComments(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			if (this.#isSharedEvent(extra))
			{
				return;
			}

			logger.log(`${this.constructor.name}.handleReadAllChannelComments`, params, extra, command);

			const { chatId } = params;
			this.store.dispatch('counterModel/readChildChatsCounters', { parentChatId: chatId })
				.catch((error) => {
					logger.error('handleReadAllChannelComments error', error);
				});
		}

		handleUnreadMessage(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			logger.log(`${this.constructor.name}.handleUnreadMessage`, params, extra, command);

			if (this.#isLocalActionUuid(extra))
			{
				logger.log('handleUnreadMessage. This event sent by current device. Skip event');

				return;
			}

			const {
				chatId,
				counter,
				type = CounterType.chat,
				parentChatId = 0,
			} = params;

			this.#setCounter({
				chatId,
				counter,
				type,
				parentChatId,
			})
				.catch((error) => {
					logger.error('handleUnreadMessage error', error);
				});
		}

		handleUnreadMessageChat(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			logger.log(`${this.constructor.name}.handleUnreadMessageChat`, params, extra, command);

			if (this.#isLocalActionUuid(extra))
			{
				logger.log('handleUnreadMessageChat. This event sent by current device. Skip event');

				return;
			}

			const {
				chatId,
				counter,
				counterType,
			} = params;

			const storedEvent = this.#getStoredState(chatId);

			this.#setCounter({
				chatId,
				counter,
				type: counterType,
				parentChatId: storedEvent?.parentChatId ?? 0,
			})
				.catch((error) => {
					logger.error('handleUnreadMessageChat error', error);
				});
		}

		/**
		 * @param {ChatUnreadPullHandlerParams} params
		 * @param {PullExtraData} extra
		 */
		handleChatUnread(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			if (this.#isSharedEvent(extra))
			{
				return;
			}
			logger.log(`${this.constructor.name}.handleChatUnread`, params, extra);

			const {
				chatId,
				counter,
				counterType,
			} = params;

			const storedEvent = this.#getStoredState(chatId);

			this.#setCounter({
				chatId,
				counter,
				type: counterType,
				parentChatId: storedEvent?.parentChatId ?? 0,
			})
				.catch((error) => {
					logger.error('handleChatUnread error', error);
				});
		}

		handleChatDelete(params, extra, command)
		{
			logger.log(`${this.constructor.name}.handleChatDelete`, params, extra, command);
			/*
			 intercept event doesn't need
			 the event contains the deletion of the comment chat, which is not included in the synchronization.
			 */

			if (this.#isSharedEvent(extra))
			{
				return;
			}

			const { chatId } = params;

			this.#deleteCounter(chatId)
				.catch((error) => {
					logger.error('handleChatDelete error', error);
				});
		}

		handleChatHide(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			logger.log(`${this.constructor.name}.handleChatHide`, params, extra, command);

			if (this.#isSharedEvent(extra))
			{
				return;
			}

			const { chatId } = params;

			this.#deleteCounter(chatId)
				.catch((error) => {
					logger.error('handleChatHide error', error);
				});
		}

		handleChatUserLeave(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			logger.log(`${this.constructor.name}.handleChatUserLeave`, params, extra, command);

			if (this.#isSharedEvent(extra))
			{
				return;
			}

			const {
				chatId,
				userId,
			} = params;

			if (!UserHelper.isCurrentUser(userId))
			{
				return;
			}

			this.#deleteCounter(chatId)
				.catch((error) => {
					logger.error('handleChatUserLeave error', error);
				});
		}

		handleChatMuteNotify(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			logger.log(`${this.constructor.name}.handleChatMuteNotify`, params, extra, command);

			const {
				chatId,
				counter,
				counterType,
				muted: disabled,
				parentChatId = 0,
			} = params;

			this.#setCounter({
				chatId,
				counter,
				parentChatId,
				disabled,
				type: counterType,
			})
				.catch((error) => {
					logger.error('handleChatMuteNotify error', error);
				});
		}

		/**
		 * @param {RecentUpdateParams} params
		 * @param {PullExtraParams} extra
		 */
		handleRecentUpdate(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			logger.log(`${this.constructor.name}.handleRecentUpdate`, params, extra, command);

			const {
				counter,
				counterType,
				chat,
			} = params;

			const {
				id: chatId,
				parent_chat_Id: parentChatId = 0,
				mute_list: muteList,
			} = chat;

			const disabled = CounterHelper.getDisabledByMuteList(muteList);

			this.#setCounter({
				chatId,
				counter,
				parentChatId,
				disabled,
				type: counterType,
			});
		}

		/**
		 * @param chatId
		 * @return {?CounterModelState}
		 */
		#getStoredState(chatId)
		{
			return this.store.getters['counterModel/getByChatId'](chatId);
		}

		/**
		 * @param {CounterModelState} counterState
		 */
		async #setCounter(counterState)
		{
			logger.log(`${this.constructor.name}.setCounter`, counterState);

			return this.store.dispatch('counterModel/setList', { counterList: [counterState] });
		}

		async #deleteCounter(chatId)
		{
			return this.store.dispatch('counterModel/delete', { chatIdList: [chatId] });
		}

		async #clearCounters()
		{
			return this.store.dispatch('counterModel/clear');
		}

		/**
		 * @param {PullExtraParams} extra
		 * @return {boolean}
		 */
		#isSharedEvent(extra)
		{
			return extra.is_shared_event === true;
		}

		/**
		 * @param {PullExtraParams} extra
		 * @return {boolean}
		 */
		#isLocalActionUuid(extra)
		{
			if (Type.isNil(extra.action_uuid))
			{
				return false;
			}

			return this.#uuidManager.hasActionUuid(extra.action_uuid);
		}
	}

	module.exports = { CounterPullHandler };
});
