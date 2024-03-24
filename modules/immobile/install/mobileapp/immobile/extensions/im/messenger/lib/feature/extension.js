/**
 * @module im/messenger/lib/feature
 */
jn.define('im/messenger/lib/feature', (require, exports, module) => {
	/**
	 * @class Feature
	 */
	class Feature
	{
		static isBitrixCallEnabled()
		{
			return this.getOption('isBitrixCallEnabled', false);
		}

		/**
		 * @private
		 */
		static getOption(name, defaultValue)
		{
			const options = jnExtensionData.get('im:messenger/lib/feature');

			// eslint-disable-next-line no-prototype-builtins
			if (options.hasOwnProperty(name))
			{
				return options[name];
			}

			return defaultValue;
		}
	}

	module.exports = { Feature };
});
