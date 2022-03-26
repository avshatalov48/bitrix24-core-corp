(() => {

	/**
	 * @class KeyValueStorage
	 */
	class KeyValueStorage
	{
		constructor(id)
		{
			this.id = id;
			this.storageObject = Application.sharedStorage(id);
		}

		setObject (key, value)
		{
			let result = null;
			if (value && typeof value == "object")
			{
				try
				{
					result = JSON.stringify(value);
					this.storageObject.set(key, result);
				}
				catch (e)
				{
					//do nothing
				}
			}
		}

		updateObject (key, object = {}, handler = null)
		{
			let result = null;
			if (object && typeof object == "object")
			{

				let savedObject = this.getObject(key, {});
				if (handler)
				{
					let result = handler(savedObject, object);
					if (typeof result != "undefined")
					{
						savedObject = result;
					}
				}
				else
				{
					savedObject = Object.assign(savedObject, object);
				}
				try
				{
					result = JSON.stringify(savedObject);
					this.storageObject.set(key, result);
				}
				catch (e)
				{
					//do nothing
				}
			}
		}

		getObject (key, fallback = {})
		{
			let result = Object.tryJSONParse(this.storageObject.get(key));
			if (result == null)
			{
				return fallback;
			}

			if(typeof fallback === "object" && fallback !== null)
			{
				return Object.assign(fallback, result);
			}
			else
			{
				return result;
			}

		}

		setBoolean (key, value = false)
		{
			this.set(key, value == true ? "1" : "0");
		}

		getBoolean (key, fallback = false)
		{
			let fallbackString = (fallback == true ? "1" : "0");
			return Boolean(parseInt(this.get(key, fallbackString)));
		}

		setNumber (key, value)
		{
			value = value? value.toString(): "0";
			this.set(key, value);
		}

		getNumber (key, fallback = null)
		{
			const result = this.get(key, NaN);
			return Number.isNaN(result)? fallback: Number(result);
		}

		get (key, fallback = null)
		{
			let result = this.storageObject.get(key);
			if (result == null && fallback != null)
			{
				return fallback;
			}

			return result;

		}

		set (key, value)
		{
			return this.storageObject.set(key, value)
		}

		clear() {
			this.storageObject.clear()
		}

	}

	let appStorages = {};

	/**
	 * @type {KeyValueStorage}
	 */
	Application.storage = new KeyValueStorage();

	/**
	 * @param storageId
	 * @returns {KeyValueStorage}
	 */
	Application.storageById = storageId => {
		if(!appStorages[storageId])
		{
			appStorages[storageId] = new KeyValueStorage(storageId);
		}

		return appStorages[storageId];
	};

})();