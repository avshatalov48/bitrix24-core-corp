/**
 * @module settings-v2/controller/smartfilter-status
 */
jn.define('settings-v2/controller/smartfilter-status', (require, exports, module) => {
	const { BaseSettingController } = require('settings-v2/controller/base');
	const { Type } = require('type');

	class SmartFilterStatusSettingController extends BaseSettingController
	{
		async get()
		{
			return new Promise((resolve) => {
				const cachedSmartFilterStatus = Application.storage.get('mobile.push.smartfilter.status');

				if (!Type.isNil(cachedSmartFilterStatus))
				{
					resolve(cachedSmartFilterStatus);

					return;
				}

				BX.rest.callMethod('mobile.push.smartfilter.status.get', {}, (response) => {
					if (response.answer?.error || Type.isNil(response.answer?.result))
					{
						resolve(null);

						return;
					}

					const result = response.answer.result;
					this.setToCache(result);

					resolve(result);
				});
			});
		}

		async set(value)
		{
			this.setToCache(value);
			BX.rest.callMethod('mobile.push.smartfilter.status.set', this.getSetConfig(value));
		}

		getSetConfig(value)
		{
			return { active: value ? '1' : '0' };
		}

		setToCache(value)
		{
			Application.storage.set('mobile.push.smartfilter.status', value);
		}
	}

	module.exports = {
		SmartFilterStatusSettingController,
	};
});
