/**
 * @module storage/local-storage
 */
jn.define('storage/local-storage', (require, exports, module) => {
	function checkKey(key)
	{
		if (BX.type.isNotEmptyString(key))
		{
			return true;
		}

		throw new Error('LocalStorage requires a key to store and moduleId');
	}

	/**
	 * @class StorageUnit
	 */
	class StorageUnit
	{
		/**
		 * Creating a combined storage that stores both data locally and in the phone's memory.
		 *
		 * @param key
		 * @param useShared
		 */
		constructor(key, useShared = true)
		{
			this.sharedStorage = Application.sharedStorage(key);
			this.localStorage = {};
			this.useShared = useShared;
		}

		/**
		 * Filling the combined storage with data.
		 *
		 * @param {string} key
		 * @param value
		 */
		set(key, value)
		{
			checkKey(key);

			this.localStorage[key] = value;

			if (this.useShared)
			{
				this.sharedStorage.set(key, value);
			}
		}

		/**
		 * Checks by key that the storage is full of data
		 *
		 * @param {string} key
		 * @param {boolean} resetCache
		 * @returns {boolean}
		 */
		has(key, resetCache = false)
		{
			if (resetCache === false && this.localStorage[key] !== undefined)
			{
				return true;
			}

			if (this.useShared)
			{
				const sharedValue = this.sharedStorage.get(key);

				if (sharedValue !== undefined)
				{
					this.localStorage[key] = sharedValue;

					return true;
				}
			}

			return false;
		}

		/**
		 * Returns value from the storage by the value key.
		 *
		 * @param {string} key
		 * @param defaultValue
		 * @param {boolean} resetCache
		 * @returns {*|null}
		 */
		get(key, defaultValue = null, resetCache = false)
		{
			if (this.has(key, resetCache) && this.localStorage[key] !== null && this.localStorage[key] !== undefined)
			{
				return this.localStorage[key];
			}

			return defaultValue;
		}
	}

	/**
	 * @class LocalStorage
	 */
	class LocalStorage
	{
		constructor()
		{
			this.storages = {};
		}

		/**
		 * Returns or creates and returns storage by key.
		 * If you pass the module id, the key template will be used: "moduleId:youKey".
		 *
		 * @param {string} storageKey
		 * @param {string} moduleId
		 * @param {string} useShared
		 * @returns {StorageUnit}
		 */
		get(storageKey, moduleId = null, useShared = true)
		{
			checkKey(storageKey);

			const builtKey = this.#buildStorageKey(moduleId, storageKey);

			if (this.storages[builtKey] === undefined)
			{
				this.storages[builtKey] = new StorageUnit(builtKey, useShared);
			}

			return this.storages[builtKey];
		}

		#buildStorageKey(moduleId, key)
		{
			if (BX.type.isNotEmptyString(moduleId))
			{
				return `${moduleId}:${key}`;
			}

			return key;
		}
	}

	module.exports = { LocalStorage: new LocalStorage() };
});
