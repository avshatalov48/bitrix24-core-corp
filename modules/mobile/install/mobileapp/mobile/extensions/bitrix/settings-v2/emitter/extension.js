/**
 * @module settings-v2/emitter
 */
jn.define('settings-v2/emitter', (require, exports, module) => {
	const { Type } = require('type');

	class SettingEmitter
	{
		static emit(eventName, eventData, componentCode = null)
		{
			if (!Type.isStringFilled(eventName))
			{
				throw new Error(`SettingEvent: ${eventName} is not a filled string`);
			}

			if (componentCode)
			{
				BX.postComponentEvent(eventName, [eventData], componentCode);
			}
			else
			{
				BX.postComponentEvent(eventName, [eventData]);
			}
		}
	}

	module.exports = {
		SettingEmitter,
	};
});
