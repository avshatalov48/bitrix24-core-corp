(() =>
{
	const caches = new Map();

	class StorageCache
	{
		constructor(storageName)
		{
			this.storageName = storageName;
			this.defaultData = {};
		}

		static getInstance(id)
		{
			if (!caches.has(id))
			{
				caches.set(id, (new StorageCache(id)));
			}
			return caches.get(id);
		}

		get()
		{
			return Application.storage.getObject(this.storageName, this.defaultData);
		}

		set(data)
		{
			Application.storage.setObject(this.storageName, data);
		}

		update(key, value)
		{
			const currentCache = this.get();
			currentCache[key] = value;
			this.set(currentCache);
		}

		setDefaultData(defaultData)
		{
			this.defaultData = defaultData;
		}
	}

	this.StorageCache = StorageCache;
})();
