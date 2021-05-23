(() =>
{
	/**
	 * @class EntitySelector
	 */
	class EntitySelector
	{
		constructor()
		{
			/**
			 * @type {JNRecipientPicker}
			 */
			this.ui = dialogs.createRecipientPicker()
			this.ui.setListener(this.onEvent.bind(this))
			this.provider = null;
			this.sections = [];
			this.items = [];
			this.selectedItems = [];
			this.doSearch = EntitySelector.debounce(function (text)
			{
				this.items = [];
				this.provider.doSearch(text)
			}, 100, this);

			this.updateList = items => this.ui.setItems(items, null, false);
		}


		setProvider(provider)
		{
			this.provider = provider;
			this.provider.listener = this;
			this.sections.push({
				id: "recent",
				backgroundColor: "#ffffff",
				height: 30,
				title: BX.message("MOBILE_SELECTOR_RECENT").toUpperCase()
			})
			this.sections.push({
				id: "common",
				backgroundColor: "#ffffff",
				height: 30,
				title: provider.title()
			})

			this.sections.unshift({id: "service"});
			this.ui.setSections(this.sections);
		}

		onFetchResult(items, cache = false)
		{
			if (this.items.length > 0) {
				let ids = this.items.map(item => item.id);
				items = items.filter(item => !ids.includes(item.id))
				this.items = this.items.concat(items);
			}
			else
			{
				this.items = items;
			}
			this.items.forEach(item => item.sectionCode = "common")
			this.scopeFilter(this.items, cache);
		}

		onRecentResult(items) {
			items.forEach(item => item.sectionCode = "recent")
			this.scopeFilter(items);
		}

		scopeFilter(items, cache)
		{
			if (items.length === 0)
			{
				if (this.query !== "" && cache !== true){
					this.updateList([{type: "button", unselectable: true, title: BX.message("MOBILE_SELECTOR_NO_RESULTS"), sectionCode: "service"}]);
				}
				else {

					this.updateList([{type: "loading", title: BX.message("MOBILE_SELECTOR_SEARCH"), sectionCode: "service"}])
				}

				return;
			}

			this.updateList(items)
		}

		showRecent() {
			this.provider.loadRecent()
		}

		/**
		 * @return {Promise}
		 */
		open(params = {})
		{
			if(params.selected) {
				this.setSelected(params.selected)
			}

			if (params.title) {
				this.ui.setTitle({text: params.title})
			}
			
			return new Promise((function (resolve, reject)
			{
				this.resolve = resolve;
				this.showRecent();

				this.ui.show().then(data =>
				{
					let result = data
					if (typeof this.provider["prepareResult"] === "function") {
						result = this.provider.prepareResult(data);
					}

					this.onResult(result);
				})

				setTimeout(()=> this.ui.setSelected(this.selectedItems), 0)
			}).bind(this))


		}

		setSelected(selected) {
			this.selectedItems = this.provider.prepareSelected(selected)
		}

		onEvent(eventName, data)
		{
			if (typeof this[eventName] === "function")
			{
				this[eventName].apply(this, [data])
			}
		}

		onResult(data)
		{

			this.resolve(data);
		}

		onListFill({text})
		{
			this.provider.setQuery(text)
			let search = this.query !== text
			this.query = text;
			if (search) {
				if (this.query === "")
				{
					this.doSearch.cancel();
					this.showRecent()
				}
				else
				{
					this.doSearch(text)
				}
			}

		}

		onScopeChanged({scope})
		{
			this.scope = scope.id;
			this.scopeFilter()
		}

		onSelectedChanged(data)
		{
			//
		}

		static debounce(fn, timeout, ctx)
		{
			let timer = 0;
			let func = function ()
			{
				clearTimeout(timer);
				timer = setTimeout(() => fn.apply(ctx, arguments), timeout);
			};

			func.cancel = function () {
				clearTimeout(timer)
			}

			return func;

		}
	}

	window.EntitySelector = EntitySelector;
})();