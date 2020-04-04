/**
* @bxjs_lang_path extension.php
*/


(()=>{

	/**
	 *  @interface ListSearcherDelegate
	 * */
	/** Result method.
	 * @name  ListSearcherDelegate#getSearchQueryOption
	 * @type {Function}
	 * @return {string}
	 */
	/** Result method.
	 * @name  ListSearcherDelegate#getSearchMethod
	 * @type {Function}
	 * @return {string}
	 */
	/**
	 * @name  ListSearcherDelegate#getFieldWeights
	 * @type {Function}
	 * @return {object}
	 */
	/**
	 * @name  ListSearcherDelegate#prepareItems
	 * @type {Function}
	 * @return {array}
	 */
	/**
	 * @name  ListSearcherDelegate#onSearchRequestStart
	 * @type {Function}
	 * @return
	 */

	/**
	 * @name  ListSearcherDelegate#onSearchResultReady
	 * @type {Function}
	 * @return
	 */
	/**
	 * @name  ListSearcherDelegate#onSearchResultReadyNext
	 * @type {Function}
	 * @return
	 */
	/**
	 * @name  ListSearcherDelegate#onSearchResultEmpty
	 * @type {Function}
	 * @return
	 */

	/**
	 * @name  ListSearcherDelegate#onSearchResultRecent
	 * @type {Function}
	 * @return
	 */

	/**
	 * @implements ListSearcherDelegate
	 */
	class BaseListSearchDelegate
	{
		constructor(list = null)
		{
			this.list = list;
		}

		getFieldWeights()
		{
			return null;
		}

		getSearchMethod()
		{
			return "";
		}
		onSearchRequestStart(items, sections)
		{
			this.list.setItems(items, sections)
		}

		onSearchResultEmpty(items, sections)
		{
			this.list.setItems(items, sections)
		}

		onSearchResultReady(items, sections)
		{
			this.list.setItems(items, sections)
		}

		onSearchResultReadyNext(items, sections)
		{
			this.list.setItems(items, sections)
		}

		onSearchResultRecent(items, sections)
		{
			this.list.setItems(items, sections)
		}
		
		getSearchQueryOption()
		{
			return "";
		}
	}

	/**
	 * @class ListSearcher
	 */
	class ListSearcher
	{
		/**
		 *
		 * @param {BaseList} list
		 * @param {ListSearcherDelegate} delegate
		 * @param searcherId
		 */
		constructor(searcherId = null, delegate = null)
		{
			this.searcherId = searcherId;
			/**
			 * @type {ListSearcherDelegate}
			 */
			this.setDelegate(delegate);
			this.currentSearchItems = [];
			if(searcherId)
				this.lastSearchItems = Application.storage.getObject(searcherId+"_last_search", {items: []})["items"];
		}

		set searcherId(value)
		{
			this._searcherId = value;
			if(value)
				this.lastSearchItems = Application.storage.getObject(value+"_last_search", {items: []})["items"];
		}

		get searcherId()
		{
			return this._searcherId
		}

		setDelegate(delegate)
		{
			if(delegate != null)
			{
				this.delegate = delegate;
				this.searchRequest = new DelayedRestRequest(this.delegate.getSearchMethod());
			}
		}
		fetchResults(data)
		{
			this.currentQueryString = data.text;
			if (data.text.length >= 3)
			{
				this.currentSearchItems = [];
				this.searchRequest.options = this.delegate.getSearchQueryOption(data.text);
				this.searchRequest.handler = (result, loadMore, error) =>
				{
					if (result)
					{
						if (!result.length)
						{
							this.sendResult([{
								title: BX.message("SEARCH_EMPTY_RESULT"),
								unselectable: true,
								type: "button",
								params: {"code": "skip_handle"}
							}], [], "result_empty");
						}
						else
						{
							let items = this.postProgressing(result, data.text);
							this.currentSearchItems = items;
							items = SearchUtils.setServiceCell(items,
								this.searchRequest.hasNext()
									? SearchUtils.Const.SEARCH_MORE_RESULTS
									: null
							);

							this.sendResult(items, [{id:this.searcherId}, {id: "service"}], "result_ready")
						}
					}
					else if (error)
					{
						if (error.code !== "REQUEST_CANCELED")
						{
							this.sendResult([{
								title: BX.message("SEARCH_EMPTY_RESULT"),
								unselectable: true,
								type: "button",
								params: {"code": "skip_handle"}
							}], [], "result_empty");
						}
					}
				};

				this.sendResult([{
					title: BX.message("SEARCH_LOADING"),
					unselectable: true,
					sectionCode: "service",
					type: "loading",
					params: {"code": "skip_handle"}
				}], [{id: "service"}, {id:this.searcherId}], "searching");
				this.searchRequest.call();

			}
			else if (data.text.length === 0)
			{
				this.showRecentResults();
			}
		}

		sendResult(items, sections, state)
		{
			const events = {
				"searching":"onSearchRequestStart",
				"result_ready":"onSearchResultReady",
				"result_ready_next":"onSearchResultReadyNext",
				"result_empty":"onSearchResultEmpty",
				"result_recent":"onSearchResultRecent",
			};

			let contentItems = this.delegate.prepareItems(items.filter(item => !item.sectionCode));
			items = contentItems.concat(items.filter(item => item.sectionCode));
			if(events[state])
			{
				reflectFunction(this.delegate, events[state], this.delegate).call(this.delegate, items, sections)
			}
		}

		fetchNextResults()
		{
			if (this.searchRequest.hasNext())
			{
				this.searchRequest.handler = (result, error) =>
				{
					let items = this.currentSearchItems;
					if (result)
					{
						let moreItems = this.postProgressing(result, this.currentQueryString);
						items = items.concat(moreItems);
						this.currentSearchItems = items;
					}
					items = SearchUtils.setServiceCell(items,
						this.searchRequest.hasNext()
							? SearchUtils.Const.SEARCH_MORE_RESULTS
							: null
					);
					this.sendResult(items, [{id: this.searcherId}, {id: "service"}], "result_ready_next")
				};

				let items = this.currentSearchItems;
				items = SearchUtils.setServiceCell(items, SearchUtils.Const.SEARCH_LOADING);
				this.sendResult(items, [{id: "service"}, {id:this.searcherId}], "result_loading");
				this.searchRequest.callNext();
			}
		}

		showRecentResults()
		{
			let preparedLastSearchItems = this.lastSearchItems.map(item =>
			{
				item.actions = [{
					title: BX.message("ACTION_DELETE"),
					identifier: "delete",
					destruct: true,
					color: "#df532d"
				}];
				return item;
			});
			this.sendResult(preparedLastSearchItems, [
				{
					id: this.searcherId,
					title: this.lastSearchItems.length > 0 ? BX.message("RECENT_SEARCH") : ""
				}
			], "result_recent")
		}

		addRecentSearchItem(data)
		{
			this.lastSearchItems = this.lastSearchItems.filter(item => item.params.id !== data.params.id);
			this.lastSearchItems.unshift(data);
			Application.storage.setObject(this.searcherId+"_last_search", {items: this.lastSearchItems});
		}

		removeRecentSearchItem(data)
		{
			this.lastSearchItems = this.lastSearchItems.filter(item => item.params.id !== data.item.params.id);
			Application.storage.setObject(this.searcherId+"_last_search", {items: this.lastSearchItems});
		}

		postProgressing(searchResult, query)
		{
			let weights = this.delegate.getFieldWeights();
			let finalResult = searchResult;
			if(weights)
			{
				finalResult = searchResult
					.map(result =>
					{
						let weight = 0;

						for (let key in weights)
						{
							if (result[key] && result[key].toUpperCase().indexOf(query.toUpperCase()) === 0)
							{
								weight = weights[key];
							}
						}

						result.weight = weight;
						return result;
					})
					.filter(result => result.weight !== 0)
					.sort((resultOne, resultTwo) => (resultTwo.weight < resultOne.weight) ? -1 : 0)
				;
			}

			return finalResult;
		}
	};

	let SearchUtils = {
		Const: {
			SEARCH_LOADING: {title: BX.message("SEARCH_LOADING"), code: "loading", type: "loading", unselectable: true},
			SEARCH_MORE_RESULTS: {title: BX.message("LOAD_MORE_RESULT"), code: "more_search_result", type: "button"},
		},
		setServiceCell: function (items, data, customParams)
		{
			items = items.filter(item => item.sectionCode !== "service");
			if (data)
			{
				let params = customParams || {};
				params.code = data.code;
				items.push({
					title: data.title,
					sectionCode: "service",
					type: data.type,
					params: {"code": data.code}
				});
			}

			return items;
		},

	};

	jnexport(ListSearcher, BaseListSearchDelegate)

})();