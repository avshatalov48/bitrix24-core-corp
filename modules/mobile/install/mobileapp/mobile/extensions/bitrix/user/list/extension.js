/**
 * @bxjs_lang_path extension.php
 * @let BaseList list
 */

(() =>
{
	/** @interface UserListDelegate */
	class UserListDelegate
	{
		onUserSelected(item)
		{
		}

		formatUserData(item)
		{
		}

		filterUserList(items)
		{
		}

		onSearchResult(items, sections, list)
		{

		}

		eventHandlers()
		{

		}

	}

	class UserList
	{
		/**
		 *
		 * @param listObject
		 * @param {UserListDelegate} delegate
		 * @param {String} formatName
		 */
		constructor(listObject = null, delegate = null, formatName = null)
		{
			this.list = listObject;
			this._delegate = delegate;
			this.options = {};
			this.inited = false;
		}

		setOptions(options = {})
		{
			this.options = options;
		}

		init(enableEventListener = true)
		{
			if(this.list == null || this.inited === true)
				return;

			BX.onViewLoaded(() =>
			{
				if (typeof this.list.setSearchFieldParams === "function")
				{
					this.list.setSearchFieldParams({placeholder: BX.message("SEARCH_PLACEHOLDER")})
				}
				this.list.setSections([
					{title: "", id: "people"}, {title: "", id: "service"}
				]);
			});

			if(enableEventListener)
				this.list.setListener((event, data) => reflectFunction(this.eventHandlers, event, this).call(this, data));

			this.searcher = new UserSearcher(this.list, this._delegate);
			this.searcher.resultHandler = this.onSearchResult.bind(this);
			this.request = new RequestExecutor("user.search",
				{
					"IMAGE_RESIZE": "small",
					"SORT": "LAST_NAME",
					"ORDER": "ASC",
					"FILTER": {"ACTIVE": "Y", "HAS_DEPARTAMENT": "Y"}
				})
				.setCacheHandler(data =>{
					this.items = Utils.prepareListForDraw(data);
					this.draw();
				})
				.setHandler(this.answerHandler.bind(this));

			this.request.call(true);
			this.inited = true;
		}

		abortAllRequests()
		{
			this.searcher.searchRequest.abortCurrentRequest();
			this.request.abortCurrentRequest();
		}

		set setDelegate(delegate)
		{
			this.searcher.delegate = delegate;
			this._delegate = delegate;
		}

		get delegate()
		{
			return this._delegate;
		}

		onSearchResult(items, sections, state)
		{
			reflectFunction(this.delegate, "onSearchResult")
				.apply(this, [items, sections, this.list, state]);
		}

		answerHandler(users, loadMore, error = null)
		{
			if (typeof this.list.stopRefreshing === "function")
			{
				this.list.stopRefreshing();
			}
			this.isLoading = false;

			if (error != null)
			{
				console.warn("refresh error:", error);
				return;
			}

			let listData = Utils.prepareListForDraw(users);
			let modifiedListData = this.prepareItems(listData, loadMore);

			if (loadMore === false)
			{
				this.items = modifiedListData;
				this.draw();
			}
			else
			{
				this.items = this.items.concat(modifiedListData);
				this.list.addItems(modifiedListData);
				if (this.request.hasNext() && this.options.disablePagination !== true)
				{
					this.list.updateItems([{
						filter: {sectionCode: "service"},
						element: {
							title: BX.message("LOAD_MORE_USERS") + " (" + this.request.getNextCount() + ")",
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
		}

		draw(params = {})
		{
			let items = [];
			if (params.filter)
			{
				let ids = [];
				let filterFunc = item =>
				{
					let query = params.filter.toLowerCase();
					try
					{
						let match = (
							ids.indexOf(item.params.id) < 0 &&
							(item.title && item.title.toLowerCase().startsWith(query) ||
								item.subtitle && item.subtitle.toLowerCase().startsWith(query) ||
								item.sortValues && item.sortValues.name && item.sortValues.name.toLowerCase().startsWith(query))
						);

						if (match)
						{
							ids.push(item.params.id);

						}
					}
					catch (e)
					{
						console.warn(e);
					}

					return typeof match != "undefined";

				};

				items = this.items.filter(filterFunc).concat(this.searcher.currentSearchItems.filter(filterFunc));
			}
			else
			{
				items = this.prepareItems(this.items);
				if (this.request.hasNext() &&  this.options.disablePagination !== true)
				{
					items = items.concat({
						title: BX.message("LOAD_MORE_USERS") + " (" + this.request.getNextCount() + ")",
						type: "button",
						unselectable: false,
						sectionCode: "service",
						params: {"code": "more"}
					});
				}
			}

			BX.onViewLoaded(() => this.list.setItems(items, [{id: "people"}, {id: "service"}]));
		}

		prepareItems(items, loadMore)
		{
			if (this.delegate)
			{
				if (this.delegate.filterUserList)
				{
					items = this.delegate.filterUserList(items, loadMore);
				}
				if (this.delegate.formatUserData)
				{
					items = items.map(item => this.delegate.formatUserData(item));
				}
			}

			let uniqueItems = [];
			items.forEach(item => {
				if (item.id && !uniqueItems[item.id]) {
					uniqueItems[item.id] = item;
				}
			})

			return Object.values(uniqueItems);
		}

		get eventHandlers()
		{
			let defaultHandlers = {
				onRefresh: function ()
				{
					this.request.call();
				},
				onViewRemoved: () =>
				{
				},
				onUserTypeText: function (data)
				{
					this.searcher.fetchResults(data)
				},
				onSearchShow: function ()
				{
					this.searcher.showRecentResults();
				},
				onSearchItemSelected: function (data)
				{
					if (data.params.code)
					{
						if (data.params.code === "skip_handle")
						{
							return;
						}

						if (data.params.code === "more_search_result")
						{
							this.searcher.fetchNextResults(data.params.query);
							return;
						}
					}

					if (data.params.profileUrl)
					{
						this.searcher.addRecentSearchItem(data);
					}

					if (this.delegate)
					{
						this.delegate.onUserSelected(data);
					}

				},
				onItemSelected: function (selectionData)
				{
					let data = selectionData.item || selectionData;
					if (data.params.code)
					{
						if (data.params.code === "more")
						{
							if (this.request.hasNext())
							{
								this.list.updateItems([{
									filter: {sectionCode: "service"},
									element: {
										title: BX.message("USER_LOADING"),
										type: "loading",
										sectionCode: "service",
										unselectable: true,
										params: {"code": "loading"}
									}
								}]);

								this.request.callNext();
							}
						}
					}
					else
					{
						if (this.delegate)
						{
							this.delegate.onUserSelected(data);
						}
					}
				},
				onItemAction: function (data)
				{
					if (data.action.identifier === "delete")
					{
						this.searcher.removeRecentSearchItem(data);
					}
				}
			};

			if (typeof this.delegate.eventHandlers === "function")
			{
				let delegateHandlers = this.delegate.eventHandlers();
				if (typeof delegateHandlers === "object")
				{
					defaultHandlers = Object.assign(defaultHandlers, delegateHandlers);
				}
			}

			return defaultHandlers;
		}

		/**
		 * @return {Promise}
		 */
		static openPicker(options = {})
		{
			if(Application.getApiVersion() >= 32)
			{
				return new Promise((resolve, reject)=>
				{
						(new RecipientList(["users"], options.listOptions))
							.open(options)
							.then(data => resolve(data["users"]))
							.catch(e => reject(e))
				})
			}
			else
			{
				return new Promise((resolve, reject) =>
				{
					PageManager.openWidget(
						"list",
						{
							backdrop: {
								bounceEnable: true,
								swipeAllowed: false,
								showOnTop: true,
							},
							modal: true,
							title: BX.message("USER_LIST_COMPANY"),
							useSearch: true,
							useClassicSearchField: true,
							onReady: list =>
							{
								let delegate = {
									onUserSelected: user => list.close(() => resolve([user])),
									onSearchResult(items, sections, list, state)
									{
										list.setSearchResultItems(items, sections);
									},
									formatUserData: (item) =>
									{
										item.type = "info";
										return item;
									}
								};
								(new UserList(list, delegate)).init();
							},
							onError: error => reject(error),
						});
				});

			}
		}
	}

	class UserSearcher
	{
		/**
		 *
		 * @param {BaseList} list
		 * @param {UserListDelegate} delegate
		 */
		constructor(list = null, delegate = null)
		{
			this.searchRequest = new DelayedRestRequest("user.search",
				{
					"SORT": "LAST_NAME",
					"ORDER": "ASC",
					"FILTER": {"ACTIVE": "Y", "HAS_DEPARTAMENT": "Y"}
				});
			this.resultHandler = null;
			this.delegate = delegate;
			this.list = list;
			this.currentSearchItems = [];
			this.currentQueryString = "";
			this.lastSearchItems = Application.storage.getObject("users_last_search", {items: []})["items"];
		}

		fetchResults(data)
		{
			this.currentQueryString = data.text;
			if (data.text.length >= 3)
			{
				this.currentSearchItems = [];
				this.searchRequest.options["FILTER"]["FIND"] = data.text;
				this.searchRequest.handler = (result, loadMore, error) =>
				{
					if (result)
					{
						let items = this.postProgressing(result, data.text);
						items = this.prepareItems(items);
						if (!items.length)
						{
							this.sendResult([{
								title: BX.message("SEARCH_EMPTY_RESULT"),
								unselectable: true,
								type: "button",
								params: {"code": "skip_handle"}
							}], []);
						}
						else
						{
							this.currentSearchItems = items;
							items = SearchUtils.setServiceCell(items,
								this.searchRequest.hasNext()
									? SearchUtils.Const.SEARCH_MORE_RESULTS
									: null
							);

							this.sendResult(items, [{id: "people"}, {id: "service"}])
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
							}], []);
						}
					}
				};

				this.sendResult([{
					title: BX.message("SEARCH_LOADING"),
					unselectable: true,
					sectionCode: "service",
					type: "loading",
					params: {"code": "skip_handle"}
				}], [{id: "service"}, {id: "people"}], "searching");
				this.searchRequest.call();

			}
			else
			{
				this.searchRequest.abortCurrentRequest();
				if (data.text.length === 0)
				{
					this.showRecentResults();
				}
			}

		}

		sendResult(items, sections, state)
		{
			if (this.resultHandler)
			{
				this.resultHandler(items, sections, state);
			}
		}

		prepareItems(items)
		{
			if (this.delegate)
			{
				if (this.delegate.filterUserList)
				{
					items = this.delegate.filterUserList(items);
				}
				if (this.delegate.formatUserData)
				{
					items = items.map(item => this.delegate.formatUserData(item));
				}
			}

			return items;
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
					items = this.prepareItems(items);
					items = SearchUtils.setServiceCell(items,
						this.searchRequest.hasNext()
							? SearchUtils.Const.SEARCH_MORE_RESULTS
							: null
					);
					this.sendResult(items, [{id: "people"}, {id: "service"}], "result")
				};

				let items = this.currentSearchItems;
				items = SearchUtils.setServiceCell(items, SearchUtils.Const.SEARCH_LOADING);
				this.sendResult(items, [{id: "service"}, {id: "people"}], "result_loading");
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
			this.sendResult(this.prepareItems(preparedLastSearchItems), [
				{
					id: "people",
					title: this.lastSearchItems.length > 0 ? BX.message("RECENT_SEARCH") : ""
				}
			])
		}

		addRecentSearchItem(data)
		{
			this.lastSearchItems = this.lastSearchItems.filter(item => item.params.id !== data.params.id);
			this.lastSearchItems.unshift(data);
			Application.storage.setObject("users_last_search", {items: this.lastSearchItems});
		}

		removeRecentSearchItem(data)
		{
			this.lastSearchItems = this.lastSearchItems.filter(item => item.params.id !== data.item.params.id);
			Application.storage.setObject("users_last_search", {items: this.lastSearchItems});
		}

		postProgressing(searchResult, query)
		{
			let finalResult = searchResult
				.map(result =>
				{
					let weight = 0;
					for (let key in this.searchFieldWeights)
					{
						if (result[key] && result[key].toUpperCase().indexOf(query.toUpperCase()) === 0)
						{
							weight = this.searchFieldWeights[key];
						}
					}

					result.weight = weight;
					return result;
				})
				.filter(result => result.weight !== 0)
				.sort((resultOne, resultTwo) => (resultTwo.weight < resultOne.weight) ? -1 : 0)
			;

			return Utils.prepareListForDraw(finalResult);
		}

		get searchFieldWeights()
		{
			return {
				NAME: 100,
				LAST_NAME: 99,
				WORK_POSITION: 98,
			}
		}
	}

	/**
	 * Search utils
	 */

	/**
	 * @class UserListUtilss
	 * @type {{getFormattedName: (function(*=, *=): any), prepareListForDraw: (function(*=): []), getFormattedHumanName: (function(*, *=): *)}}
	 */
	let Utils = {
		prepareListForDraw: function (list)
		{
			let result = [];
			let userFormatFunction = user => ({
				title: Utils.getFormattedName(user),
				subtitle: user.WORK_POSITION,
				hasName: (Utils.getFormattedHumanName(user) !== ""),
				sectionCode: "people",
				color: "#5D5C67",
				useLetterImage: true,
				id: user.ID,
				imageUrl: (user.PERSONAL_PHOTO === null ? undefined : encodeURI(user.PERSONAL_PHOTO)),
				sortValues: {
					name: user.LAST_NAME
				},
				params: {
					id: user.ID,
					profileUrl: "/mobile/users/?user_id=" + user.ID
				},
			});

			if (list)
			{
				result = list
					.filter(user => user["UF_DEPARTMENT"] !== false && Utils.getFormattedHumanName(user))
					.map(userFormatFunction);
				let unknownUsers = list
					.filter(user => user["UF_DEPARTMENT"] !== false && !Utils.getFormattedHumanName(user))
					.map(userFormatFunction)
					.sort((u1, u2) => u1.title > u2.title ? 1 : (u1.title === u2.title ? 0 : -1));
				result = unknownUsers.concat(result);
			}

			return result;

		},
		getFormattedName: function (userData, format = null)
		{
			let name = Utils.getFormattedHumanName(userData, format);
			if (name === "")
			{
				if (userData.EMAIL)
				{
					name = userData.EMAIL;
				}
				else if (userData.PERSONAL_MOBILE)
				{
					name = userData.PERSONAL_MOBILE;
				}
				else if (userData.PERSONAL_PHONE)
				{
					name = userData.PERSONAL_PHONE;
				}
				else
				{
					name = BX.message("USER_LIST_NO_NAME")
				}
			}

			return name !== "" ? name : userData.EMAIL;

		},
		getFormattedHumanName:function(userData, format = null){
			let replace = {
				"#NAME#": userData.NAME,
				"#LAST_NAME#": userData.LAST_NAME,
				"#SECOND_NAME#": userData.SECOND_NAME,

			};

			if (format == null)
			{
				format = "#NAME# #LAST_NAME#";
			}

			if (userData.LAST_NAME)
			{
				replace["#LAST_NAME_SHORT#"] = userData.LAST_NAME[0].toUpperCase() + ".";
			}
			if (userData.SECOND_NAME)
			{
				replace["#SECOND_NAME_SHORT#"] = userData.SECOND_NAME[0].toUpperCase() + ".";
			}
			if (userData.NAME)
			{
				replace["#NAME_SHORT#"] = userData.NAME[0].toUpperCase() + ".";
			}

			return format
				.replace(/#NAME#|#LAST_NAME#|#SECOND_NAME#|#LAST_NAME_SHORT#|#SECOND_NAME_SHORT#|#NAME_SHORT#/gi,
					match => (typeof replace[match] != "undefined" && replace[match] != null) ? replace[match] : "")
				.trim();
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

	this.UserList = UserList;
	jnexport(["UserListUtils", Utils]);

})();
