/**
 * @module im/messenger/provider/services/analytics/src/audio
 */
jn.define('im/messenger/provider/services/analytics/src/audio', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { Analytics } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { AnalyticsHelper } = require('im/messenger/provider/services/analytics/helper');

	/**
	 * @class AudioAnalytics
	 */
	class AudioAnalytics
	{
		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
		}

		/**
		 * @param {DialogId} dialogId
		 */
		sendClickToPlayAudioInChat({ dialogId })
		{
			/** @type {DialoguesModelState} */
			const dialog = this.#getDialog(dialogId);

			new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(Analytics.Category.audiomessage)
				.setEvent(Analytics.Event.play)
				.setP1(AnalyticsHelper.getP1ByChatType(dialog.type))
				.send();
		}

		/**
		 * @param {DialogId} dialogId
		 */
		sendClickToPauseAudioInChat({ dialogId })
		{
			/** @type {DialoguesModelState} */
			const dialog = this.#getDialog(dialogId);

			new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(Analytics.Category.audiomessage)
				.setEvent(Analytics.Event.pause)
				.setP1(AnalyticsHelper.getP1ByChatType(dialog.type))
				.send();
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {AudioRate} speed
		 */
		sendClickToChangeAudioSpeedInChat({ dialogId, speed })
		{
			/** @type {DialoguesModelState} */
			const dialog = this.#getDialog(dialogId);

			new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(Analytics.Category.audiomessage)
				.setEvent(Analytics.Event.changeSpeed)
				.setP1(AnalyticsHelper.getP1ByChatType(dialog.type))
				.setP2(`speed_${speed}`)
				.send();
		}

		/**
		 * @param {DialogId} dialogId
		 * @returns {?DialoguesModelState}
		 */
		#getDialog(dialogId)
		{
			return this.store.getters['dialoguesModel/getById'](dialogId);
		}
	}

	module.exports = {
		AudioAnalytics,
	};
});
