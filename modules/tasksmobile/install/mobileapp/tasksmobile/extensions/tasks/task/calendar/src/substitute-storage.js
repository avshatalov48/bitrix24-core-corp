/**
 * @module tasks/task/calendar/src/substitute-storage
 */
jn.define('tasks/task/calendar/src/substitute-storage', (require, exports, module) => {
	class SubstituteStorage
	{
		constructor(storageKey)
		{
			this.storageKey = storageKey;
		}

		async get(key)
		{
			const currentData = await this.#getFullStorage();

			return currentData[key] || null;
		}

		async set(key, value)
		{
			const currentData = await this.#getFullStorage();
			currentData[key] = value;
			await this.#saveFullStorage(currentData);
		}

		async #getFullStorage()
		{
			const storedData = Application.storage.getObject(this.storageKey, {});

			return storedData || {};
		}

		async #saveFullStorage(data)
		{
			await Application.storage.setObject(this.storageKey, data);
		}
	}

	module.exports = { SubstituteStorage };
});
