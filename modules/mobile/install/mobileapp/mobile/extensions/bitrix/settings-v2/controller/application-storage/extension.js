/**
 * @module settings-v2/controller/application-storage
 */
jn.define('settings-v2/controller/application-storage', (require, exports, module) => {
	const { BaseSettingController } = require('settings-v2/controller/base');

	class ApplicationStorageSettingController extends BaseSettingController
	{
		/**
		 * @param props
		 * @param {string} props.settingId
		 * @param {string|null} props.settingScopeId
		 * @param {*} props.fallbackValue
		 */
		constructor(props)
		{
			super(props);

			this.settingScopeId = props.settingScopeId ?? null;
		}

		#getAllScopeSettings()
		{
			return Application.storage.getObject(this.settingScopeId, {});
		}

		/**
		 * @public
		 */
		async get()
		{
			if (this.settingScopeId)
			{
				const settings = this.#getAllScopeSettings();

				return settings[this.settingId] ?? this.fallbackValue;
			}

			return Application.storage.get(this.settingId, this.fallbackValue);
		}

		/**
		 * @public
		 * @param {*} value
		 */
		async set(value)
		{
			if (this.settingScopeId)
			{
				const settings = this.#getAllScopeSettings();
				settings[this.settingId] = value;
				Application.storage.setObject(this.settingScopeId, settings);
			}
			else
			{
				Application.storage.set(this.settingId, value);
			}

			if (this.onChange)
			{
				this.onChange(value);
			}
		}
	}

	module.exports = {
		ApplicationStorageSettingController,
	};
});
