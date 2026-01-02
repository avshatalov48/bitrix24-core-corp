/**
 * @module im/messenger/provider/services/analytics/message-pin
 */
jn.define('im/messenger/provider/services/analytics/message-pin', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { Analytics } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { AnalyticsHelper } = require('im/messenger/provider/services/analytics/helper');

	class MessagePin
	{
		constructor()
		{
			/**
			 * @type {MessengerCoreStore}
			 */
			this.store = serviceLocator.get('core').getStore();
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} chatId
		 */
		sendMessagePin({ dialogId, chatId })
		{
			const chatData = this.store.getters['dialoguesModel/getById'](dialogId);

			if (!chatData)
			{
				return;
			}

			const pinsCounter = this.store.getters['messagesModel/pinModel/getPinsCounter'](chatId);
			const type = pinsCounter > 1 ? Analytics.Type.multiplePins : Analytics.Type.singlePin;
			const p3Value = `pinnedCount_${pinsCounter}`;
			const analyticsEvent = new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(AnalyticsHelper.getCategoryByChatType(chatData.type))
				.setEvent(Analytics.Event.pinMessage)
				.setType(type)
				.setP1(AnalyticsHelper.getP1ByChatType(chatData.type))
				.setP3(p3Value)
			;

			analyticsEvent.send();
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} chatId
		 */
		sendMessageUnpin({ dialogId, chatId })
		{
			const chatData = this.store.getters['dialoguesModel/getById'](dialogId);

			if (!chatData)
			{
				return;
			}

			const pinsCounter = this.store.getters['messagesModel/pinModel/getPinsCounter'](chatId);
			const type = pinsCounter ? Analytics.Type.selectedPin : Analytics.Type.singlePin;
			const analyticsEvent = new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(AnalyticsHelper.getCategoryByChatType(chatData.type))
				.setEvent(Analytics.Event.unpinMessage)
				.setType(type)
				.setP1(AnalyticsHelper.getP1ByChatType(chatData.type))
			;

			analyticsEvent.send();
		}

		/**
		 * @param {DialogId} dialogId
		 */
		sendPinListOpened({ dialogId })
		{
			const chatData = this.store.getters['dialoguesModel/getById'](dialogId);

			if (!chatData)
			{
				return;
			}

			const analyticsEvent = new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(AnalyticsHelper.getCategoryByChatType(chatData.type))
				.setEvent(Analytics.Event.openPinList)
				.setP1(AnalyticsHelper.getP1ByChatType(chatData.type))
			;

			analyticsEvent.send();
		}

		/**
		 * @param {DialogId} dialogId
		 */
		sendPinnedMessageLimitException({ dialogId })
		{
			const chatData = this.store.getters['dialoguesModel/getById'](dialogId);

			if (!chatData)
			{
				return;
			}

			const analyticsEvent = new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(AnalyticsHelper.getCategoryByChatType(chatData.type))
				.setEvent(Analytics.Event.pinnedMessageLimitException)
				.setP1(AnalyticsHelper.getP1ByChatType(chatData.type))
			;

			analyticsEvent.send();
		}
	}

	module.exports = { MessagePin };
});
