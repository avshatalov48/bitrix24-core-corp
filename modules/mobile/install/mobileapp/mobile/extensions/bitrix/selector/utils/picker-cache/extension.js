/**
 * @module selector/utils/picker-cache
 */
jn.define('selector/utils/picker-cache', (require, exports, module) => {
	/**
	 * @class BasePickerCache
	 */
	class BasePickerCache
	{
		constructor(id)
		{
			this.id = id;
			this.storage = Application.storageById(`selector${this.id}`);
			this.data = {};
		}

		get(key, diskCache)
		{
			if (this.data[key])
			{
				return this.data[key];
			}

			if (diskCache)
			{
				this.data[key] = this.storage.getObject(key, { items: [] }).items;

				return this.data[key];
			}

			return [];
		}

		save(items, key, options = {})
		{
			const { saveDisk, unique } = options;
			if (typeof this.data[key] === 'undefined')
			{
				this.data[key] = [];
			}
			let cacheItems = this.data[key];

			if (unique)
			{
				const ids = new Set(items.map((item) => item.id));

				cacheItems = (
					cacheItems
						.filter((item) => !ids.has(item.id))
						.concat(items)
				);

				this.data[key] = cacheItems;
			}
			else
			{
				this.data[key] = items;
			}

			if (saveDisk)
			{
				this.storage.setObject(key, { items });
			}
		}

		static debounce(fn, timeout, ctx)
		{
			let timer = 0;

			return function() {
				clearTimeout(timer);
				timer = setTimeout(() => fn.apply(ctx, arguments), timeout);
			};
		}
	}

	module.exports = { BasePickerCache };
});
