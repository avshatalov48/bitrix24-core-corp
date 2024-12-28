/**
 * @module disk/cache
 */
jn.define('disk/cache', (require, exports, module) => {

	const storage = Application.sharedStorage('disk');
	class Cache
	{
		/**
		 * @public
		 * @param {string} key
		 * @param {*} value
		 */
		static set(key, value)
		{
			try
			{
				const stringValue = JSON.stringify(value);
				storage.set(key, stringValue);
			}
			catch (e)
			{
				console.error(e);
			}
		}

		/**
		 * @public
		 * @param {string} key
		 * @param {*} fallback
		 * @returns {*}
		 */
		static get(key, fallback = null)
		{
			const stringValue = storage.get(key);
			if (!stringValue)
			{
				return fallback;
			}

			try
			{
				return JSON.parse(stringValue);
			}
			catch (e)
			{
				console.error(e);
			}

			return fallback;
		}

		/**
		 * @public
		 */
		static clear()
		{
			storage.clear();
		}
	}

	module.exports = { Cache };
});
