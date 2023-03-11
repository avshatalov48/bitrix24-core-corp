(() =>
{
	/**
	 * @class BasePickerCache
	 */
	class BasePickerCache
	{
		constructor(id)
		{
			this.id = id;
			this.storage = Application.storageById(`selector${this.id}`)
			this.data = {};
		}

		get(key, diskCache)
		{
			if (this.data[key])
			{
				return this.data[key]
			}
			else if (diskCache)
			{
				this.data[key] = this.storage.getObject(key, {"items": []}).items;
				return this.data[key];
			}

			return [];
		}

		save(items, key, options = {})
		{
			const {saveDisk, unique} = options
			if (typeof this.data[key] === "undefined")
			{
				this.data[key] = [];
			}
			let cacheItems = this.data[key];

			if (unique)
			{
				const ids = items.map(item => item.id);

				cacheItems = (
					cacheItems
						.filter(item => !ids.includes(item.id))
						.concat(items)
				);

				this.data[key] = cacheItems;
			}
			else
			{
				this.data[key] = items
			}

			if (saveDisk)
			{
				this.storage.setObject(key, {items});
			}
		}

		static debounce(fn, timeout, ctx)
		{
			let timer = 0;
			return function ()
			{
				clearTimeout(timer);
				timer = setTimeout(() => fn.apply(ctx, arguments), timeout);
			};
		}

	}

	/**
	 * @class BaseSelectorProvider
	 */
	class SelectorProvider
	{
		constructor(id)
		{
			this.providerId = id || this.id();
			this.listener = null;
			this.searchFields = [];
			this.entityWeight = [];
			this.items = [];
			this.queryString = "";
			this.cache = new BasePickerCache(this.providerId);
			this.singleSelection = false;
		}

		getEntityWeight(id) {
			if (this.entityWeight[id])
				return this.entityWeight[id];
			return 0;
		}

		prepareSelected(selected) {
			//not implemented
			return selected;
		}

		doSearch(text) {
			//not implemented
		}

		loadRecent(text) {
			//not implemented
		}

		isSingleChoose() {
			return this.singleSelection ? this.singleSelection : false;
		}

		processResult(query, items, excludeFields = [])
		{
			try
			{
				query = query.toLowerCase()
				const queryWords = query.split(" ");
				const shouldMatch = queryWords.length;
				return items.map(item =>
				{
					let sort = this.getEntityWeight(item.params.type);
					const matchCount = 0;
					const matchedWords = [];
					if (this.searchFields.length > 0 && query)
					{
						const reverse = this.searchFields.slice(0);
						reverse.reverse().forEach(name =>
						{
							if (excludeFields.includes(name))
								return;

							const field = item[name];
							if (field)
							{
								const fieldWords = field.toLowerCase().split(" ");
								const findHandler = (word) => {
									const items = queryWords.filter(queryWord =>
									{
										const match = word.indexOf(queryWord) === 0
											&& !matchedWords.includes(queryWord)
										if (match) {
											matchedWords.push(queryWord)
										}

										return match;
									})

									return items.length > 0;

								}

								const result = fieldWords.filter(findHandler);
								if (result.length > 0)
								{
									sort += this.searchFields.indexOf(name) + 1;
								}
							}
						})
					}
					else
					{
						sort = 1;
					}

					item.sort = (matchedWords.length >= shouldMatch) ? sort + matchCount: -1;
					return item;
				})
					.filter(item => item.sort >= 0)
					.sort((item1, item2) =>
					{
						if (item1.sort > item2.sort) return -1
						if (item1.sort < item2.sort) return 1
						return 0
					})

			}
			catch (e)
			{
				console.error(e);
				return items;
			}
		}

		abortAllRequests()
		{

		}

		addRecentItems(items)
		{
			//not implemented
		}

		setQuery(value)
		{
			this.queryString = value
		}

		prepareItems(items)
		{
			return items.map((item) => {
				const modifiedItem = this.prepareItemForDrawing(item);
				modifiedItem.searchFields = {}
				this.searchFields.forEach(fieldName =>
				{
					if (modifiedItem.hasOwnProperty(fieldName))
					{
						modifiedItem.searchFields[fieldName] = modifiedItem[fieldName]
					}
				})

				return modifiedItem;
			});
		}

		prepareResult(items) {
			return items;
		}

		prepareItemForDrawing(item)
		{
			console.warn("This method should be overridden in subclass");
			return {}
		}

		id()
		{
			return "default"
		}

		title()
		{
			return ""
		}

		setListener(listener)
		{
			this.listener = listener;

			return this;
		}
	}

	window.BaseSelectorProvider = SelectorProvider
	window.BasePickerCache = BasePickerCache
})();