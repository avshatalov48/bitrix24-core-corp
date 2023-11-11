/**
 * @module storage-cache
 */
jn.define('storage-cache', (require, exports, module) => {
	class StorageCache
	{
		constructor(storageId, cacheKey)
		{
			this.storageId = storageId;
			this.cacheKey = cacheKey;

			this.setDefaultData({});
		}

		/**
		 * @return {Object}
		 */
		get()
		{
			if (!this.storage)
			{
				this.storage = Application.storageById(this.storageId);
			}

			return this.storage.getObject(this.cacheKey, this.defaultData);
		}

		set(data)
		{
			if (!this.storage)
			{
				this.storage = Application.storageById(this.storageId);
			}

			this.storage.setObject(this.cacheKey, data);
		}

		update(key, value)
		{
			if (!this.storage)
			{
				this.storage = Application.storageById(this.storageId);
			}

			this.storage.updateObject(key, value);
		}

		setDefaultData(defaultData)
		{
			this.defaultData = defaultData;
		}
	}

	module.exports = { StorageCache };
});
