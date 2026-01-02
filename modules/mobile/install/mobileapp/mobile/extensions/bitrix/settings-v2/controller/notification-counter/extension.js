/**
 * @module settings-v2/controller/notification-counter
 */
jn.define('settings-v2/controller/notification-counter', (require, exports, module) => {
	const { BaseSettingController } = require('settings-v2/controller/base');
	const { NotificationCounterKey } = require('settings-v2/const');
	const { Type } = require('type');

	class NotificationCounterSettingController extends BaseSettingController
	{
		constructor(props)
		{
			super(props);

			if (!props.counterType)
			{
				throw new Error('Missing required counterType');
			}

			this.counterType = props.counterType;
		}

		async get()
		{
			return new Promise((resolve, reject) => {
				const cached = Application.storage.get(NotificationCounterKey.CONFIG);
				if (cached)
				{
					const preparedResult = cached.find((item) => item.type === this.counterType).value;
					if (!Type.isNil(preparedResult))
					{
						resolve(preparedResult);

						return;
					}
				}

				BX.rest.callMethod('mobile.counter.config.get', {}, (response) => {
					if (response.error())
					{
						resolve(this.fallbackValue);

						return;
					}

					Application.storage.set(NotificationCounterKey.CONFIG, response.answer.result);
					const preparedResult = response.answer.result.find((item) => item.type === this.counterType).value;

					resolve(preparedResult);
				});
			});
		}

		async set(value)
		{
			return new Promise((resolve, reject) => {
				this.setToCache(value);

				BX.rest.callMethod('mobile.counter.config.set', this.getSetConfig(), (response) => {
					if (response.error())
					{
						reject(response.error());
					}
					else
					{
						resolve(response.answer.result);
					}
				});
			});
		}

		getSetConfig(value)
		{
			return {
				config: {
					[this.counterType]: value ? '1' : '0',
				},
			};
		}

		setToCache(value)
		{
			const counterConfig = Application.storage.get(NotificationCounterKey.CONFIG);
			const itemIndex = counterConfig.findIndex((item) => item.type === this.counterType);

			counterConfig[itemIndex].value = value;
			Application.storage.set(NotificationCounterKey.CONFIG, counterConfig);
		}
	}

	module.exports = {
		NotificationCounterSettingController,
	};
});
