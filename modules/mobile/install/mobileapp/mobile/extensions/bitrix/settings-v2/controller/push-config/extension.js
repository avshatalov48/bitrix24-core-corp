/**
 * @module settings-v2/controller/push-config
 */
jn.define('settings-v2/controller/push-config', (require, exports, module) => {
	const { BaseSettingController } = require('settings-v2/controller/base');
	const { PushConfigKeys } = require('settings-v2/const');

	class PushConfigSettingController extends BaseSettingController
	{
		constructor(props)
		{
			super(props);

			if (!props.moduleId)
			{
				throw new Error('Missing required moduleId');
			}

			if (!props.pushType)
			{
				throw new Error('Missing required pushType');
			}

			this.moduleId = props.moduleId;
			this.pushType = props.pushType;
		}

		async get()
		{
			const pushConfig = Application.storage.get(PushConfigKeys.CONFIG);
			const pushConfigItem = pushConfig.find((item) => item.module_id === this.moduleId && item.type === this.pushType);

			return pushConfigItem?.active ?? false;
		}

		async set(value)
		{
			return new Promise((resolve, reject) => {
				this.setToCache(value);

				BX.rest.callMethod('mobile.push.config.set', this.getSetConfig(value), (response) => {
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
				config: [
					{
						module_id: this.moduleId,
						type: this.pushType,
						active: value ? '1' : '0',
					},
				],
			};
		}

		setToCache(value)
		{
			const pushConfig = Application.storage.get(PushConfigKeys.CONFIG);
			const itemIndex = pushConfig.findIndex((item) => item.module_id === this.moduleId && item.type === this.pushType);

			pushConfig[itemIndex].active = value;
			Application.storage.set(PushConfigKeys.CONFIG, pushConfig);
		}
	}

	module.exports = {
		PushConfigSettingController,
	};
});
