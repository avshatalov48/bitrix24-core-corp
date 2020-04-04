/**
 * * @bxjs_lang_path extension.php
 * @let BaseList list
 */

(() =>
{
	/** @interface UserListDelegate */
	class UserListDelegate
	{
		onUserSelected(item){}
		formatUserData(item){}
		filterUserList(items){}
	}

	class RequestSearchExecutor extends RequestExecutor
	{
		constructor(method, options)
		{

			super(method, options);
			this.timeoutId = null;
			this.timeout = 300;
		}

		call()
		{
			clearTimeout(this.timeoutId);
			this.timeoutId = setTimeout(() => super.call(), this.timeout)
		}
	}

	let tables = {
		users: {
			name: "users",
			fields: [{name: "id", unique: true}, "value"]
		},
		users_last_search: {
			name: "users_last_search",
			fields: [{name: "id", unique: true}, "value"]
		},
	};
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
			if (listObject == null)
			{
				throw new Error("List object is null");
			}

			this.list = listObject;
			this.hasRemoteData = false;
			this._delegate = delegate;
			this.db = new ReactDatabase("users");

			BX.onViewLoaded(() =>
			{
				if (Application.getPlatform() === "ios")
				{
					this.list.setSearchFieldParams({placeholder: BX.message("SEARCH_PLACEHOLDER")})
				}
				this.list.setSections([
					{title: "", id: "people"}, {title: "", id: "service"}
				]);
			});

			this.list.setListener((event, data) =>
			{
				if (this.eventHandlers[event])
				{
					this.eventHandlers[event].apply(this, [data]);
				}
			});

			this.db.table(tables.users).then(
				table =>
					table.get().then(
						items =>
						{
							if (items.length > 0)
							{
								let cachedItems = JSON.parse(items[0].VALUE);
								if (!this.hasRemoteData)
								{
									this.items = cachedItems;
									this.draw();
								}
							}
						}
					)
			);

			this.searcher = new Searcher(listObject, this.db, this._delegate);
			this.request = new RequestExecutor("user.search",
				{"IMAGE_RESIZE": "small", "SORT": "LAST_NAME", "ORDER": "ASC", "FILTER": {"ACTIVE": "Y", "HAS_DEPARTAMENT":"Y"}});
			this.request.handler = this.answerHandler.bind(this);
			this.request.call();
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

		answerHandler(users, loadMore, error = null)
		{
			this.list.stopRefreshing();
			this.isLoading = false;

			if (error != null)
			{
				console.warn("refresh error:", error);
				return;
			}

			let listData = Utils.prepareListForDraw(users);
			let modifiledListData = this.prepareItems(listData);

			this.hasRemoteData = true;

			if (loadMore === false)
			{
				this.items = modifiledListData;
				this.draw();
				this.db.table(tables.users).then(
					table =>
					{
						table.delete().then(() =>
						{
							table.add({value: listData}).then(() =>
							{
								console.info("User cached");
							})
						})
					}
				);
			}
			else
			{
				this.list.addItems(modifiledListData);
				if (this.request.hasNext())
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

		draw()
		{
			let items = [];
			items = this.items;
			if (this.request.hasNext())
			{
				items = items.concat({
					title: BX.message("LOAD_MORE_USERS") + " (" + this.request.getNextCount() + ")",
					type: "button",
					unselectable: false,
					sectionCode: "service",
					params: {"code": "more"}
				});
			}


			BX.onViewLoaded(() => this.list.setItems(items));
		}

		prepareItems(items)
		{
			if(this.delegate)
			{
				if(this.delegate.filterUserList)
					items = this.delegate.filterUserList(items);
				if(this.delegate.formatUserData)
				{
					items = items.map(item => this.delegate.formatUserData(item));
				}
			}

			return items;
		}

		get eventHandlers()
		{
			return {
				onRefresh: function ()
				{
					this.request.call();
				},
				onViewRemoved: ()=>
				{
					console.log("onViewRemoved");
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

					if(this.delegate)
					{
						console.log("search selec11t", data);
						this.delegate.onUserSelected(data);
					}

				},
				onItemSelected: function (data)
				{
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
						if(this.delegate)
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
			}
		}

		/**
		 * @return {Promise}
		 */
		static openPicker ()
		{
			return new Promise((resolve, reject)=>{
				PageManager.openWidget(
					"list",
					{
						backdrop: {
							bounceEnable: true,
							swipeAllowed: false,
							showOnTop:true,
						},
						modal:true,
						title: BX.message("USER_LIST_COMPANY"),
						useSearch:true,
						useClassicSearchField:true,
						onReady: list => {
							let delegate = {
								onUserSelected: user=> list.close(() => resolve(user)),
							formatUserData:(item)=>
								{
									item.type = "info";
									return item;
								}
							};
							(new UserList(list, delegate));
						},
						onError: error=> reject(error),
					});
			});
		}
	}

	class Searcher
	{
		/**
		 *
		 * @param {BaseList} list
		 * @param {ReactDatabase} db
		 * @param {UserListDelegate} delegate
		 */
		constructor(list = null, db = null, delegate = null)
		{
			this.searchRequest = new RequestSearchExecutor("user.search",
				{
					"SORT": "LAST_NAME",
					"ORDER": "ASC",
					"FILTER": {"ACTIVE": "Y", "HAS_DEPARTAMENT":"Y"}
				});

			this.db = db;
			this.delegate = delegate;
			this.list = list;
			this.lastSearchItems = [];
			if (this.db)
			{
				this.db.table(tables.users_last_search).then(
					table =>
						table.get().then(
							items =>
							{
								if (items.length > 0)
								{
									this.lastSearchItems = JSON.parse(items[0].VALUE);
								}
							}
						)
				);
			}
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
						if (!result.length)
						{
							this.list.setSearchResultItems([{
								title: BX.message("SEARCH_EMPTY_RESULT"),
								unselectable: true,
								type: "button",
								params: {"code": "skip_handle"}
							}], []);
						}
						else
						{
							let items = this.postProgressing(result, data.text);
							items = this.prepareItems(items);

							this.currentSearchItems = items;
							items = SearchUtils.setServiceCell(items,
								this.searchRequest.hasNext()
									? SearchUtils.Const.SEARCH_MORE_RESULTS
									: null
							);

							this.list.setSearchResultItems(items, [{id: "people"}, {id: "service"}])
						}
					}
					else if (error)
					{
						if (error.code !== "REQUEST_CANCELED")
						{
							this.list.setSearchResultItems([{
								title: BX.message("SEARCH_EMPTY_RESULT"),
								unselectable: true,
								type: "button",
								params: {"code": "skip_handle"}
							}], []);
						}
					}
				};
				this.list.setSearchResultItems([{
					title: BX.message("SEARCH_LOADING"),
					unselectable: true,
					type: "loading",
					params: {"code": "skip_handle"}
				}], []);
				this.searchRequest.call();

			}
			else if (data.text.length === 0)
			{
				this.showRecentResults();
			}
		}

		prepareItems(items)
		{
			if(this.delegate)
			{
				if(this.delegate.filterUserList)
					items = this.delegate.filterUserList(items);
				if(this.delegate.formatUserData)
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
					this.list.setSearchResultItems(items, [{id: "people"}, {id: "service"}])
				};

				let items = this.currentSearchItems;
				items = SearchUtils.setServiceCell(items, SearchUtils.Const.SEARCH_LOADING);
				this.list.setSearchResultItems(items, [{id: "people"}, {id: "service"}]);
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
			this.list.setSearchResultItems(this.prepareItems(preparedLastSearchItems), [
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

			this.db.table(tables.users_last_search).then(
				table =>
					table.delete().then(() =>
					{
						table.add({value: this.lastSearchItems}).then(() =>
						{
							console.info("Last search saved");
						})
					})
			);
		}

		removeRecentSearchItem(data)
		{
			this.lastSearchItems = this.lastSearchItems.filter(item => item.params.id != data.item.params.id);
			this.db.table(tables.users_last_search).then(
				table =>
					table.delete()
						.then(() => table.add({value: this.lastSearchItems})
							.then(() => console.info("Last search changed")))
			);
		}

		postProgressing(searchResult, query)
		{
			console.log("Post progressing", searchResult, query);
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

		get searchFieldWeights() {
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

	let Utils = {
		prepareListForDraw: function (list)
		{
			if (list)
			{
				return list
					.filter(user => user["UF_DEPARTMENT"] !== false)
					.map(user => ({
							title: Utils.getFormattedName(user),
							subtitle: user.WORK_POSITION,
							sectionCode: "people",
							color: "#5D5C67",
							useLetterImage: true,
							imageUrl: (user.PERSONAL_PHOTO === null ? undefined : encodeURI(user.PERSONAL_PHOTO)),
							sortValues: {
								name: user.LAST_NAME
							},
							params: {
								id: user.ID,
								profileUrl: "/mobile/users/?user_id=" + user.ID
							},
						})
					);
			}

			return [];

		},
		getFormattedName: function (userData, format = null)
		{
			let replace = {
				"#NAME#": userData.NAME,
				"#LAST_NAME#": userData.LAST_NAME,
				"#SECOND_NAME#": userData.SECOND_NAME,

			};

			if(format == null)
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
			let name = format
				.replace(/#NAME#|#LAST_NAME#|#SECOND_NAME#|#LAST_NAME_SHORT#|#SECOND_NAME_SHORT#|#NAME_SHORT#/gi,
					match => (typeof replace[match] != "undefined" && replace[match] != null) ? replace[match] : "")
				.trim();

			if(name === "")
			{
				if(userData.EMAIL)
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

})();
