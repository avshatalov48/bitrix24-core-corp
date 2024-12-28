/**
 * @module im/messenger/provider/pull/base/counter
 */
jn.define('im/messenger/provider/pull/base/counter', (require, exports, module) => {
	const { CounterType } = require('im/messenger/const');
	const { BasePullHandler } = require('im/messenger/provider/pull/base/pull-handler');
	const { BaseRecentMessageManager } = require('im/messenger/provider/pull/lib/recent/base');

	/**
	 * @class BaseCounterPullHandler
	 */
	class BaseCounterPullHandler extends BasePullHandler
	{
		/**
		 * @param {MessageAddParams} params
		 * @param {PullExtraParams} extra
		 */
		handleMessage(params, extra)
		{
			this.handleMessageAdd(params, extra);
		}

		/**
		 * @param {MessageAddParams} params
		 * @param {PullExtraParams} extra
		 */
		handleMessageChat(params, extra)
		{
			this.handleMessageAdd(params, extra);
		}

		/**
		 * @param {MessageAddParams} params
		 * @param {PullExtraParams} extra
		 */
		handleMessageAdd(params, extra)
		{
			// TODO: implement when switching to a new common counter structure
			return;

			const manager = this.getMessageManager(params, extra);
			if (!manager.isCommentChat())
			{
				return;
			}

			this.updateCommentCounter({
				channelChatId: manager.getParentChatId(),
				commentChatId: manager.getChatId(),
				commentCounter: params.counter,
			});
		}

		handleMessageDeleteComplete(params)
		{
			this.handleCounters(params);
		}

		handleReadMessage(params)
		{
			this.handleCounters(params);
		}

		handleReadMessageChat(params)
		{
			this.handleCounters(params);
		}

		handleUnreadMessage(params)
		{
			this.handleCounters(params);
		}

		handleUnreadMessageChat(params)
		{
			this.handleCounters(params);
		}

		handleChatUnread(params)
		{
			this.handleCounters({
				...params,
				unread: params.active,
			});
		}

		handleChatMuteNotify(params)
		{
			this.handleCounters(params);
		}

		handleCounters(params)
		{
			const {
				chatId,
				dialogId,
				counter,
				counterType = CounterType.chat,
				parentChatId = 0,
			} = params;

			if (counterType === CounterType.openline)
			{
				return;
			}

			this.logger.log(`${this.constructor.name}.handleCounters: `, params);

			if (counterType === CounterType.comment)
			{
				this.updateCommentCounter({
					channelChatId: parentChatId,
					commentChatId: chatId,
					commentCounter: counter,
				});

				return;
			}

			const recentItem = this.store.getters['recentModel/getById'](dialogId);
			// for now existing common chats counters are stored in corresponding chat model objects
			if (recentItem)
			{
				return;
			}

			const newCounter = this.getNewCounter(params);
			this.updateCounter({
				dialogId,
				counter: newCounter,
				counterType,
			});
		}

		/**
		 * @protected
		 * @param {MessageAddParams} params
		 * @param {PullExtraParams} extra
		 * @return {BaseRecentMessageManager}
		 */
		getMessageManager(params, extra)
		{
			return new BaseRecentMessageManager(params, extra);
		}

		/**
		 * @protected
		 * @param params
		 * @return {number}
		 */
		getNewCounter(params)
		{
			const {
				counter,
				muted,
				unread,
			} = params;

			let newCounter = 0;
			if (muted)
			{
				newCounter = 0;
			}
			else if (unread && counter === 0)
			{
				newCounter = 1;
			}
			else if (unread && counter > 0)
			{
				newCounter = counter;
			}
			else if (!unread)
			{
				newCounter = counter;
			}

			return newCounter;
		}

		/**
		 * @abstract
		 * @protected
		 * @param params
		 */
		updateCounter(params)
		{
			throw new Error(`${this.constructor.name}.updateCounter() must be override in subclass.`);
		}

		updateCommentCounter(payload)
		{
			// TODO: implement when switching to a new common counter structure
		}
	}

	module.exports = {
		BaseCounterPullHandler,
	};
});
