/**
 * @module im/messenger/db/model-writer/vuex/application
 */
jn.define('im/messenger/db/model-writer/vuex/application', (require, exports, module) => {
	const { Logger } = require('im/messenger/lib/logger');
	const { Writer } = require('im/messenger/db/model-writer/vuex/writer');
	const { Setting } = require('im/messenger/const');

	class ApplicationWriter extends Writer
	{
		subscribeEvents()
		{
			this.storeManager.on('applicationModel/setSettings', this.addRouter);
		}

		unsubscribeEvents()
		{
			this.storeManager.off('applicationModel/setSettings', this.addRouter);
		}

		/**
		 * @param {MutationPayload} mutation.payload
		 */
		addRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			if (mutation.payload.actionName === 'setAudioRateSetting')
			{
				const data = mutation.payload.data ?? {};
				const field = Object.keys(data)[0];

				this.repository.option.set(Setting.option.APP_SETTING_AUDIO_RATE, String(data[field]))
					.catch((error) => Logger.error(`${this.constructor.name}.addRouter.option.set.catch:`, error));
			}
		}
	}

	module.exports = {
		ApplicationWriter,
	};
});
