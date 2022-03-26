(function()
{
	class ApplicationStorage
	{
		static set(key, value, storageId = 'default')
		{
			return new Promise((resolve, reject) =>
			{
				app.exec("setStorageValue", {storageId: storageId, key: key, value: {value: String(value)}, callback: result =>
				{
					resolve(String(value));
				}});
			});
		}

		static get(key, fallback = null, storageId = 'default')
		{
			return new Promise((resolve, reject) =>
			{
				if (!key)
				{
					return resolve(undefined);
				}

				app.exec("getStorageValue", {storageId: storageId, key: key, callback: value =>
				{
					if (!value || typeof value === 'undefined')
					{
						return resolve(fallback);
					}

					if (typeof value === 'object' && typeof value.value !== 'undefined')
					{
						return resolve(value.value);
					}

					resolve(value);
				}});
			});
		}

		static setNumber(key, value)
		{
			return ApplicationStorage.set(key, Number(value));
		}

		static getNumber(key, fallback = null)
		{
			return new Promise((resolve, reject) =>
			{
				ApplicationStorage.get(key, NaN).then(value => {
					let result = Number.isNaN(value)? fallback: Number(value);
					resolve(result);
				});
			});
		}

		static setBoolean(key, value = false)
		{
			return ApplicationStorage.set(key, Boolean(value)? "1": "0");
		}

		static getBoolean(key, fallback = false)
		{
			return new Promise((resolve, reject) =>
			{
				ApplicationStorage.get(key, NaN).then(value => {
					if (Number.isNaN(value))
					{
						resolve(fallback);
					}
					else
					{
						resolve(value === "1");
					}
				});
			});
		}

		static setObject(key, value)
		{
			let result;
			if (value === null)
			{
				result = "null";
			}
			else if (value && typeof value === "object")
			{
				try
				{
					result = JSON.stringify(value);
				}
				catch (e)
				{
					result = "null";
				}
			}
			if (!window.debug)
			{
				window.debug = {};
			}

			window.debug[key] = result;

			return ApplicationStorage.set(key, result);
		}

		static updateObject(key, object = {}, handler = undefined)
		{
			if (typeof object !== 'object' || !object)
			{
				return;
			}

			if (handler && typeof handler !== 'function')
			{
				return;
			}

			return new Promise((resolve, reject) =>
			{
				this.getObject(key, NaN).then(value =>
				{
					if (!Number.isNaN(value))
					{
						if (handler)
						{
							object = handler(value, object);
						}
						else
						{
							object = Object.assign({}, value, object);
						}
					}
					ApplicationStorage.setObject(key, object).then(value => resolve(value));
				});
			});
		}

		static getObject(key, fallback = {}, storageId = 'default')
		{
			return new Promise((resolve, reject) =>
			{
				ApplicationStorage.get(key, NaN, storageId).then(value =>
				{
					if (Number.isNaN(value))
					{
						return resolve(fallback);
					}

					try
					{
						let result = JSON.parse(value);
						if (typeof fallback !== 'object' || !fallback)
						{
							fallback = {};
						}

						resolve(Object.assign(fallback, result));
					}
					catch (e)
					{
						resolve(fallback);
					}
				})
			});
		}
	}

	this.ApplicationStorage = ApplicationStorage;

})();