/**
* @bxjs_lang_path extension.php
*/
(()=>{

	/**
	 *  @interface ListDelegate
	 * */
	/**
	 * @requires
	 * @name  ListDelegate#searchDelegate
	 * @type {Function}
	 * @return {ListSearcherDelegate}
	 *
	 * @name  ListDelegate#eventHandlers
	 * @type {Function}
	 * @requires
	 */

	/**
	 * @class BaseList
	 */
	class BaseList
	{
		/**
		 *
		 * @param listObject
		 */
		constructor(listObject)
		{
			this._list = listObject;
			this.inited = false;
			this.listId = this.constructor.id();
			this.handlers = this.defaultHandlers;
		}

		sections(items)
		{
			return this.prepareSections([{title: "", id: this.listId}, {title: "", id: "service"}], items);
		}

		setSearchDelegate(delegate)
		{
			this.searcher = new (this.searchClass());
			this.searcher.searcherId = this.listId;
			this.searcher.setDelegate(delegate);
			return this;
		}

		setHandlers(handlers)
		{
			if (typeof handlers === "object")
			{
				this.handlers = Object.assign(this.handlers, handlers);
			}

			return this;
		}



		init(enableEventListener = true, drawCachedItems = true)
		{
			return new Promise((resolve, reject)=>{
				if(this._list == null || this.inited === true)
					return reject();

				BX.onViewLoaded(() => this._list.setSections(this.sections()));
				if(enableEventListener)
					this.listenToListEvents();

				if(this.constructor.method())
				{
					this.inited = true;
					resolve();
					this.request = new RequestExecutor(this.constructor.method(), this.params())
						.setCacheHandler(cachedItems => {
							this.items = cachedItems;
							if(drawCachedItems)
								this.draw();
						})
						.setHandler(this.answerHandler.bind(this));

					this.request.call(true);

				}
				else
				{
					throw new Error("Rest method should be defined!")
				}
			});
		}

		listenToListEvents()
		{
			this._list.setListener(this.callListener.bind(this));
		}

		callListener(event,data)
		{
			if(event === "onItemSelected" && data.params && data.params.code === "more")
			{
				this.request.callNext();
			}
			else
			{
				reflectFunction(this.handlers, event, this).call(this, data);
			}

		}

		abortAllRequests()
		{
			if(this.searcher)
				this.searcher.searchRequest.abortCurrentRequest();
			if(this.request)
				this.request.abortCurrentRequest();
		}

		reload(useCache = false)
		{
			this.request
				.setCacheId(null)
				.setOptions(this.params())
				.call(useCache);
		}

		answerHandler(items, isNextPage, error = null)
		{
			if (typeof this._list.stopRefreshing === "function")
			{
				this._list.stopRefreshing();
			}
			this.isLoading = false;

			if (error != null)
			{
				console.warn("refresh error:", error);
				return;
			}

			if(!isNextPage)
			{
				this.items = items;
			}
			else
			{
				this.items = this.items.concat(items);
				this.list.addItems(this.prepareItems(items));
				if (this.request.hasNext())
				{
					this.list.updateItems([{
						filter: {sectionCode: "service"},
						element: {
							title: BX.message("LOAD_MORE") + " (" + this.request.getNextCount() + ")",
							type: "button",
							unselectable: false,
							sectionCode: "service",
							params: {"code": "more"}
						}
					}]);
				}
				else
				{
					this.list.removeItem({sectionCode: "service"});
				}
			}

			this.draw();
		}

		draw(params = {})
		{
			let items = this.prepareItems(this.items);
			if (params.filter)
			{
				let ids = [];
				let filterFunc = item =>
				{
					const match = (
						ids.indexOf(item.params.id) < 0 &&
						(item.title.toLowerCase().startsWith(params.filter.toLowerCase()))
					);

					if (match)
						ids.push(item.params.id);

					return match;
				};

				items = items.filter(filterFunc);
			}

			BX.onViewLoaded(() => this._list.setItems(items,this.sections(items)));
		}

		prepareItems(items)
		{
			if(this.handlers["prepareItems"])
			{
				return this.handlers.prepareItems.call(this,items)
			}
			else
			{
				return items.map(this.constructor.prepareItemForDrawing);
			}
		}

		prepareSections(defaultSections, items)
		{
			if(this.handlers["prepareSections"])
			{
				return this.handlers.prepareSections.call(this, defaultSections, items)
			}
			else
			{
				return defaultSections;
			}

		}

		get defaultHandlers()
		{
			return {};
		}

		get eventHandlers()
		{
			return this.handlers;
		}

		/**
		 * @return {ListSearcher}
		 */
		searchClass()
		{
			return ListSearcher;
		}



		params()
		{
			return {};
		}

		static prepareItemForDrawing(item)
		{
			console.warn("This method should be overridden in subclass");
			return {}
		}

		static method()
		{
			console.warn("This method should be overridden in subclass");
			return null;
		}

		static id()
		{
			return "default"
		}


	}


	jnexport(BaseList)

})();