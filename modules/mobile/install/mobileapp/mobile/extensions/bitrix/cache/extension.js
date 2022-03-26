(() => {
    class Cache
	{
		constructor(key)
		{
			this.key = key;
			this._data = {};

			const result = Application.sharedStorage().get(this.key);
			if (result)
			{
				try
				{
					this._data = JSON.parse(result);
				}
				catch(e)
				{
					//
				}
			}
		}

		get()
		{
			return this._data;
		}

		set(values)
		{
			this._data = values;
			Application.sharedStorage().set(this.key, JSON.stringify(values));
		}
	}

    this.Cache = Cache;
})();
