/**
 * @module im/messenger/provider/services/recent/hide
 */
jn.define('im/messenger/provider/services/recent/hide', (require, exports, module) => {
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { EventType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { Runtime } = require('runtime');
	const { Logger } = require('im/messenger/lib/logger');

	const { RecentRest } = require('im/messenger/provider/rest');

	/**
	 * @class HideService
	 */
	class HideService
	{
		/**
		 * @constructor
		 * @param {object} store
		 */
		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
			this.sendHideChatRequestDebounced = Runtime.debounce(this.sendHideChatRequest, 500);
		}

		/**
		 * @param {DialogId} dialogId
		 */
		hideChat(dialogId)
		{
			Logger.log(`${this.constructor.name}.hideChat dialogId:`, dialogId);

			const recentItem = this.#getRecentItemById(dialogId);
			if (Type.isUndefined(recentItem))
			{
				return;
			}

			void this.#deleteRecentModel(recentItem);

			this.sendHideChatRequestDebounced(recentItem);
		}

		/**
		 * @desc Send request with call method
		 * @param {RecentModelState} recentItem
		 */
		async sendHideChatRequest(recentItem)
		{
			RecentRest.hideChat({ dialogId: recentItem.id })
				.then(() => {
					const dialog = this.store.getters['dialoguesModel/getById'](recentItem.id);
					MessengerEmitter.emit(EventType.dialog.external.delete, {
						dialogId: recentItem.id,
						shouldShowAlert: false,
						shouldSendDeleteAnalytics: false,
						chatType: dialog.type,
					});
				})
				.catch((result) => {
					Logger.error(`${this.constructor.name}.sendHideChatRequest error:`, result.error());

					this.#updateRecentModel(recentItem);
				})
			;
		}

		/**
		 * @param {RecentModelState} recentItem
		 */
		async #updateRecentModel(recentItem)
		{
			await this.store.dispatch('recentModel/set', [recentItem]);

			this.#renderRecent();
			serviceLocator.get('tab-counters').update();
		}

		/**
		 * @param {RecentModelState} recentItem
		 */
		async #deleteRecentModel(recentItem)
		{
			await this.store.dispatch('recentModel/delete', { id: recentItem.id });

			this.#renderRecent();
			serviceLocator.get('tab-counters').update();
		}

		/**
		 * @param {string} dialogId
		 * @returns {?RecentModelState}
		 */
		#getRecentItemById(dialogId)
		{
			return clone(this.store.getters['recentModel/getById'](dialogId));
		}

		#renderRecent()
		{
			MessengerEmitter.emit(EventType.messenger.renderRecent);
		}
	}

	module.exports = {
		HideService,
	};
});
