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
			this.writeCache = BasePickerCache.debounce((items, key) => this.storage.setObject(key, {items}), 500, this)
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

		save(items, key, options)
		{
			let {saveDisk, unique} = options
			if (typeof this.data[key] === "undefined")
			{
				this.data[key] = [];
			}
			let cacheItems = this.data[key];

			if (unique)
			{
				let ids = items.map(item => item.id);
				cacheItems = cacheItems
					.filter(item => !ids.includes(item.id))
					.concat(items)
				this.data[key] = cacheItems;
			}
			else
			{
				this.data[key] = items
			}

			if (saveDisk)
			{
				// noinspection JSCheckFunctionSignatures
				this.writeCache(this.data[key], key)
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
				let queryWords = query.split(" ");
				let shouldMatch = queryWords.length;
				return items.map(item =>
				{
					let sort = this.getEntityWeight(item.params.type);
					let matchCount = 0;
					let matchedWords = [];
					if (this.searchFields.length > 0 && query)
					{
						let reverse = this.searchFields.slice(0);
						reverse.reverse().forEach(name =>
						{
							if (excludeFields.includes(name))
								return;

							let field = item[name];
							if (field)
							{
								let fieldWords = field.toLowerCase().split(" ");
								let findHandler = (word) => {
									let items = queryWords.filter(queryWord =>
									{
										let match = word.indexOf(queryWord) === 0
											&& !matchedWords.includes(queryWord)
										if (match) {
											matchedWords.push(queryWord)
										}

										return match;
									})

									return items.length > 0;

								}

								let result = fieldWords.filter(findHandler);
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
			return items.map(item =>
			{
				let modifiedItem = this.prepareItemForDrawing(item);
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