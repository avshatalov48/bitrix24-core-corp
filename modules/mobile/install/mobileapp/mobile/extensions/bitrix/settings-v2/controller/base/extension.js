/**
 * @module settings-v2/controller/base
 */
jn.define('settings-v2/controller/base', (require, exports, module) => {
	class BaseSettingController
	{
		constructor(props)
		{
			if (!props.settingId)
			{
				throw new Error('Missing required settingId');
			}

			this.settingId = props.settingId;
			this.fallbackValue = props.fallbackValue ?? null;

			/** @type {function} */
			this.onChange = null;
		}

		/**
		 * @abstract
		 * @return Promise
		 */
		async get()
		{
			throw new Error('Method "get" must be implemented in the derived class');
		}

		/**
		 * @abstract
		 * @return Promise
		 */
		async set(value)
		{
			throw new Error('Method "set" must be implemented in the derived class');
		}

		/**
		 * @public
		 * @param {function} func
		 * @returns {BaseSettingController}
		 */
		setOnChange(func)
		{
			this.onChange = func;

			return this;
		}
	}

	module.exports = {
		BaseSettingController,
	};
});
