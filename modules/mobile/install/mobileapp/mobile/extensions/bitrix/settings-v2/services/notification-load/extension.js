/**
 * @module settings-v2/services/notifications-load
 */
jn.define('settings-v2/services/notification-load', (require, exports, module) => {
	const { PushConfigKeys, NotificationCounterKey } = require('settings-v2/const');

	class NotificationLoadService
	{
		static async fetchPushSettings()
		{
			return new Promise((resolve) => {
				BX.rest.callBatch(
					{
						pushConfig: ['mobile.push.config.get'],
						pushTypes: ['mobile.push.types.get'],
					},
					(result) => {
						const pushTypes = NotificationLoadService.processResponse(result.pushTypes, PushConfigKeys.TYPES);
						const pushConfig = NotificationLoadService.processResponse(result.pushConfig, PushConfigKeys.CONFIG);

						resolve({
							pushTypes,
							pushConfig,
						});
					},
				);
			});
		}

		static async fetchCounterSettings()
		{
			return new Promise((resolve) => {
				BX.rest.callBatch(
					{
						counterTypes: ['mobile.counter.types.get'],
						counterConfig: ['mobile.counter.config.get'],
					},
					(result) => {
						const counterTypes = NotificationLoadService.processResponse(
							result.counterType,
							NotificationCounterKey.TYPES,
						);
						const counterConfig = NotificationLoadService.processResponse(
							result.counterConfig,
							NotificationCounterKey.CONFIG,
						);

						resolve({
							counterTypes,
							counterConfig,
						});
					},
				);
			});
		}

		static async fetchAll()
		{
			return new Promise((resolve) => {
				BX.rest.callBatch(
					{
						pushConfig: ['mobile.push.config.get'],
						pushTypes: ['mobile.push.types.get'],
						counterTypes: ['mobile.counter.types.get'],
						counterConfig: ['mobile.counter.config.get'],
					},
					(result) => {
						const pushTypes = NotificationLoadService.processResponse(
							result.pushTypes,
							PushConfigKeys.TYPES,
						);
						const pushConfig = NotificationLoadService.processResponse(
							result.pushConfig,
							PushConfigKeys.CONFIG,
						);
						const counterTypes = NotificationLoadService.processResponse(
							result.counterTypes,
							NotificationCounterKey.TYPES,
						);
						const counterConfig = NotificationLoadService.processResponse(
							result.counterConfig,
							NotificationCounterKey.CONFIG,
						);

						resolve({
							pushTypes,
							pushConfig,
							counterTypes,
							counterConfig,
						});
					},
				);
			});
		}

		static processResponse(response, key)
		{
			if (response.answer?.error || !response.answer?.result)
			{
				console.error(response.answer?.error);

				return null;
			}

			const result = response.answer.result;
			Application.storage.set(key, result);

			return result;
		}
	}

	module.exports = {
		NotificationLoadService,
	};
});
