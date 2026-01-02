/**
 * @module im/messenger/provider/pull/counter
 */
jn.define('im/messenger/provider/pull/counter', (require, exports, module) => {
	const { Type } = require('type');
	const {
		CounterType,
		DialogType,
	} = require('im/messenger/const');
	const { UuidManager } = require('im/messenger/lib/uuid-manager');
	const { CounterStorageWriter } = require('im/messenger/lib/counters/counter-manager/storage/writer');
	const { BasePullHandler } = require('im/messenger/provider/pull/base');
	const { UserHelper } = require('im/messenger/lib/helper');

	const { getLogger } = require('im/messenger/lib/logger');

	const logger = getLogger('pull-handler--counters');

	/**
	 * @class CounterPullHandler
	 */
	class CounterPullHandler extends BasePullHandler
	{
		/** @type {CounterStorageWriter} */
		#storageWriter;
		/** @type {UuidManager} */
		#uuidManager;

		/**
		 * @param {CounterStorageWriter} storageWriter
		 */
		constructor(storageWriter)
		{
			super({});

			this.#storageWriter = storageWriter ?? CounterStorageWriter.getInstance();
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
			logger.log(`${this.getClassName()}.handleMessage`, params, extra, command);

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

			this.setCounter({
				chatId,
				counter,
				type,
				parentChatId,
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
			logger.log(`${this.getClassName()}.handleMessageChat`, params, extra, command);

			if (this.#isSharedEvent(extra))
			{
				logger.log('event is shared. skip event');

				return;
			}

			const {
				chatId,
				counter,
				counterType,
			} = params;

			const chat = params.chat[chatId];

			if (chat.type === DialogType.tasksTask || counterType === CounterType.tasksTask)
			{
				return;
			}

			/** @type {CounterState} */
			const counterState = {
				chatId,
				counter,
				type: counterType,
				parentChatId: chat.parent_chat_id,
			};

			this.setCounter(counterState);
		}

		/**
		 * @param {MessagePullHandlerMessageDeleteV2Params} params
		 */
		async handleMessageDeleteV2(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			logger.log(`${this.getClassName()}.handleMessageDeleteV2`, params, extra, command);

			if (this.#isSharedEvent(extra))
			{
				return;
			}

			const {
				chatId,
				counter,
				counterType,
			} = params;

			if (counterType === CounterType.tasksTask)
			{
				return;
			}

			let {
				parentChatId = 0,
			} = params;

			if (counterType === CounterType.comment) // in this event for comment chat property "type" is equal 'chat'
			{
				const commentCounterState = await this.#getStoredState(chatId);

				parentChatId = commentCounterState?.parentChatId ?? 0;
			}

			this.setCounter({
				chatId,
				counter,
				type: counterType,
				parentChatId,
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
			logger.log(`${this.getClassName()}.handleReadMessage`, params, extra, command);

			if (this.#isLocalActionUuid(extra))
			{
				logger.log(`${this.getClassName()}.handleReadMessage. This event sent by current device. Skip event`);

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

			this.setCounter(counterState);
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
			logger.log(`${this.getClassName()}.handleReadMessageChat`, params, extra, command);

			if (this.#isLocalActionUuid(extra))
			{
				logger.log(`${this.getClassName()}.handleReadMessageChat. This event sent by current device. Skip event`);

				return;
			}

			const {
				chatId,
				counter,
				counterType,
				parentChatId,
			} = params;

			if (counterType === CounterType.tasksTask)
			{
				return;
			}

			/** @type {CounterState} */
			const counterState = {
				chatId,
				counter,
				parentChatId,
				type: counterType,
			};

			this.setCounter(counterState);
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

			this.#storageWriter.clearChannelCommentsCounter(chatId);
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
				logger.log(`${this.getClassName()}.handleUnreadMessage. This event sent by current device. Skip event`);

				return;
			}

			const {
				chatId,
				counter,
				type = CounterType.chat,
				parentChatId = 0,
			} = params;

			this.setCounter({
				chatId,
				counter,
				type,
				parentChatId,
			});
		}

		async handleUnreadMessageChat(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			logger.log(`${this.constructor.name}.handleUnreadMessageChat`, params, extra, command);

			if (this.#isLocalActionUuid(extra))
			{
				logger.log(`${this.getClassName()}.handleUnreadMessageChat. This event sent by current device. Skip event`);

				return;
			}

			const {
				chatId,
				counter,
				counterType,
			} = params;

			if (counterType === CounterType.tasksTask)
			{
				return;
			}

			const storedEvent = await this.#getStoredState(chatId);

			this.setCounter({
				chatId,
				counter,
				type: counterType,
				parentChatId: storedEvent?.parentChatId ?? 0,
			});
		}

		/**
		 * @param {ChatUnreadPullHandlerParams} params
		 * @param {PullExtraData} extra
		 */
		async handleChatUnread(params, extra)
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

			const storedEvent = await this.#getStoredState(chatId);

			this.setCounter({
				chatId,
				counter,
				type: counterType,
				parentChatId: storedEvent?.parentChatId ?? 0,
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

			this.deleteCounter(chatId);
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

			this.deleteCounter(chatId);
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

			this.deleteCounter(chatId);
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
				parentChatId = 0,
			} = params;

			if (counterType === CounterType.tasksTask)
			{
				return;
			}

			this.setCounter({
				chatId,
				counter,
				parentChatId,
				type: counterType,
			});
		}

		/**
		 * @param {CounterState} counterState
		 */
		setCounter(counterState)
		{
			logger.log(`${this.constructor.name}.setCounter`, counterState);

			void this.#storageWriter.set(counterState);
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

		/**
		 * @param chatId
		 * @return {Promise<CounterState | undefined>}
		 */
		async #getStoredState(chatId)
		{
			return (await this.#storageWriter.getCollection()).findById(chatId);
		}

		deleteCounter(chatId)
		{
			void this.#storageWriter.deleteFromCollection([chatId]);
		}
	}

	module.exports = { CounterPullHandler };
});
