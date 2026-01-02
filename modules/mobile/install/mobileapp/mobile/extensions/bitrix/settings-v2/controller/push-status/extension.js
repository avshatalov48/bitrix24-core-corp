/**
 * @module settings-v2/controller/push-status
 */
jn.define('settings-v2/controller/push-status', (require, exports, module) => {
	const { BaseSettingController } = require('settings-v2/controller/base');
	const { Type } = require('type');

	class PushStatusSettingController extends BaseSettingController
	{
		async get()
		{
			return new Promise((resolve) => {
				const cachedPushStatus = Application.storage.get('mobile.push.status');

				if (!Type.isNil(cachedPushStatus))
				{
					resolve(cachedPushStatus);

					return;
				}

				BX.rest.callMethod('mobile.push.status.get', {}, (response) => {
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
			BX.rest.callMethod('mobile.push.status.set', this.getSetConfig(value));
		}

		getSetConfig(value)
		{
			return { active: value ? '1' : '0' };
		}

		setToCache(value)
		{
			Application.storage.set('mobile.push.status', value);
		}
	}

	module.exports = {
		PushStatusSettingController,
	};
});
