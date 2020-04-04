/**
 * * @bxjs_lang_path component.php
 * @let BaseList list
 */

(()=>{
	class RequestSearchExecutor extends RequestExecutor
	{
		constructor(method, options)
		{

			super(method,options);
			this.timeoutId = null;
			this.timeout = 300;
		}

		call()
		{
			clearTimeout(this.timeoutId);
			this.timeoutId = setTimeout(()=> super.call(), this.timeout)
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


	let componentResult = {
		get:function(){
			if(!this.result)
			{
				return result;
			}
			else
			{
				return this.result;
			}
		},
		update:function(){
			try
			{
				this.result = JSON.parse(Application.sharedStorage().get("user.component.result"));
			}
			catch (e)
			{

			}

			BX.ajax({url: component.resultUrl, dataType:"json"})
				.then(result => {
					this.result = result;
					Application.sharedStorage().set("user.component.result", JSON.stringify(result));
				})
				.catch(e => console.error(e));
		}
	};

	componentResult.update();


	let UserList = {
		init: function ()
		{
			this.profilePath = result.settings.profilePath;
			if(BX.componentParameters.get("canInvite", false))
			{
				let action = ()=>{
					PageManager.openPage({
						url:"/mobile/users/invite.php?",
						cache:false,
						modal:true,
						title:BX.message("INVITE_USERS")
					});
				};
				let addUserButton = {
					type:"plus",
					callback:action,
					icon:"plus",//for floating button
					animation: "hide_on_scroll", //for floating button
					color: "#515f69"//for floating button

				};

				if(Application.getPlatform() == "ios")
				{
					//button in navigation bar for iOS
					list.setRightButtons([addUserButton]);
				}
				else
				{
					//floating button for Android
					if(Application.getApiVersion() >= 24)
					{
						list.setFloatingButton(addUserButton);
					}
				}
			}


			BX.onViewLoaded(() =>
			{
				if(Application.getPlatform() == "ios")
				{
					list.setSearchFieldParams({placeholder:BX.message("SEARCH_PLACEHOLDER")})
				}
				list.setSections([
					{title: "", id: "people"}, {title: "", id: "service"}
				]);
			});

			list.setListener((event, data) =>
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

			Searcher.db = this.db;
			Searcher.init();
			this.request = new RequestExecutor("user.search", {"IMAGE_RESIZE":"small", "SORT": "LAST_NAME", "ORDER": "ASC", "FILTER":{"ACTIVE":"Y"}} );
			this.request.handler = this.answerHandler.bind(this);
			this.request.call();
		},
		answerHandler: function (users, loadMore, error = null)
		{
			list.stopRefreshing();
			this.isLoading = false;

			if(error != null)
			{
				console.warn("refresh error:", error);
				return;
			}

			let listData = Utils.prepareListForDraw(users);

			this.hasRemoteData = true;

			if (loadMore == false)
			{
				this.items = listData;
				this.draw();
				this.db.table(tables.users).then(
					table =>
					{
						table.delete().then(() =>
						{
							table.add({value: this.items}).then(() =>
							{
								console.info("User cached");
							})
						})
					}
				);
			}
			else
			{
				list.addItems(listData);
				if (this.request.hasNext())
				{
					list.updateItems([{
						filter: {sectionCode: "service"},
						element: {
							title: BX.message("LOAD_MORE_USERS") + " ("+this.request.getNextCount()+")",
							type: "button",
							unselectable:false,
							sectionCode: "service",
							params: {"code": "more"}
						}
					}]);
				}
				else
				{
					list.removeItem({sectionCode: "service"});
				}

			}

		},
		draw: function ()
		{
			let items = [];
			if (this.request.hasNext())
			{
				items = this.items.concat({
					title: BX.message("LOAD_MORE_USERS") + " (" + this.request.getNextCount()+")",
					type: "button",
					unselectable:false,
					sectionCode: "service",
					params: {"code": "more"}
				});
			}
			else
			{
				items = this.items;
			}

			BX.onViewLoaded(() => list.setItems(items));
		},
		openUserProfile:function(data)
		{
			ProfileView.open(
				{
					userId:data.params.id,
					imageUrl: encodeURI(data.imageUrl),
					title: BX.message("PROFILE_INFO"),
					workPosition: data.subtitle,
					name:data.title,
					url:data.params.profileUrl,
				}
			);
		},
		eventHandlers: {
			onRefresh: function ()
			{
				this.request.call();
			},
			onUserTypeText: function (data)
			{
				Searcher.fetchResults(data)
			},
			onSearchShow: function ()
			{
				Searcher.showRecentResults();
			},
			onSearchItemSelected: function (data)
			{
				if (data.params.code)
				{
					if (data.params.code === "skip_handle")
					{
						return;
					}

					if(data.params.code == "more_search_result" )
					{
						Searcher.fetchNextResults(data.params.query);
						return;
					}
				}

				if(data.params.profileUrl)
				{
					this.openUserProfile(data);
					Searcher.addRecentSearchItem(data);
				}


			},
			onItemSelected: function (data)
			{
				if (data.params.code)
				{
					if(data.params.code == "more")
					{
						if (this.request.hasNext())
						{

							list.updateItems([{
								filter: {sectionCode: "service"},
								element: {
									title: BX.message("USER_LOADING"),
									type: "loading",
									sectionCode: "service",
									unselectable:true,
									params: {"code": "loading"}
								}
							}]);

							this.request.callNext();
						}
					}

				}
				else
				{
					this.openUserProfile(data);
				}
			},
			onItemAction: function (data)
			{
				if (data.action.identifier === "delete")
				{
					Searcher.removeRecentSearchItem(data);
				}
			}
		},
		hasRemoteData: false,
		db: new ReactDatabase("users"),
		items: [],
	};

	let Searcher = {
		init:function(){
			this.searchRequest = new RequestSearchExecutor("user.search",
				{
					"SORT": "LAST_NAME",
					"ORDER": "ASC",
					"FILTER":{"ACTIVE":"Y"}
				});

			if(this.db)
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
		},
		fetchResults:function(data)
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
						if(!result.length)
						{
							list.setSearchResultItems([{
								title: BX.message("SEARCH_EMPTY_RESULT"),
								unselectable: true,
								type: "button",
								params: {"code": "skip_handle"}
							}], []);
						}
						else
						{
							let items = this.postProgressing(result, data.text);
							this.currentSearchItems =items;
							items = SearchUtils.setServiceCell(items,
								this.searchRequest.hasNext()
									?SearchUtils.Const.SEARCH_MORE_RESULTS
									:null
							);
							list.setSearchResultItems(items, [{id: "people"}, {id:"service"}])
						}
					}
					else if(error)
					{
						if(error.code !== "REQUEST_CANCELED")
						{
							list.setSearchResultItems([{
								title: BX.message("SEARCH_EMPTY_RESULT"),
								unselectable: true,
								type: "button",
								params: {"code": "skip_handle"}
							}], []);
						}
					}
				};
				list.setSearchResultItems([{
					title: BX.message("SEARCH_LOADING"),
					unselectable: true,
					type: "loading",
					params: {"code": "skip_handle"}
				}], []);
				this.searchRequest.call();

			}
			else if (data.text.length == 0)
			{
				Searcher.showRecentResults();
			}
		},
		fetchNextResults: function()
		{
			if(this.searchRequest.hasNext())
			{
				this.searchRequest.handler = (result, error) =>
				{
					let items = this.currentSearchItems;
					if(result)
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
					list.setSearchResultItems(items, [{id: "people"}, {id:"service"}])
				};

				let items = this.currentSearchItems;
				items = SearchUtils.setServiceCell(items,SearchUtils.Const.SEARCH_LOADING);
				list.setSearchResultItems(items, [{id: "people"}, {id:"service"}]);
				this.searchRequest.callNext();
			}
		},
		showRecentResults:function(){
			let preparedLastSearchItems = this.lastSearchItems.map(item => {

				item.actions = [{
					title : BX.message("ACTION_DELETE"),
					identifier : "delete",
					destruct: true,
					color : "#df532d"
				}];
				return item;
			});
			list.setSearchResultItems(preparedLastSearchItems, [
				{
					id: "people",
					title: this.lastSearchItems.length > 0 ? BX.message("RECENT_SEARCH") : ""
				}
			])
		},
		addRecentSearchItem:function(data)
		{
			this.lastSearchItems = this.lastSearchItems.filter(item => item.params.id != data.params.id);
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
		},
		removeRecentSearchItem:function(data)
		{
			this.lastSearchItems = this.lastSearchItems.filter(item=> item.params.id != data.item.params.id);
			this.db.table(tables.users_last_search).then(
				table =>
					table.delete().then(() =>
					{
						table.add({value: this.lastSearchItems}).then(() =>
						{
							console.info("Last search changed");
						});
					})
			);
		},
		postProgressing: function(searchResult, query){
			console.log("Post progressing", searchResult,query);
			let finalResult = searchResult
				.map(result =>
				{
					let weight = 0;

					for(key in this.searchFieldWeights)
					{
						if(result[key] && result[key].toUpperCase().indexOf(query.toUpperCase()) === 0)
						{
							weight = this.searchFieldWeights[key];
						}
					}

					result.weight = weight;
					return result;
				})
				.filter(result => result.weight != 0)
				.sort((resultOne, resultTwo)=> (resultTwo.weight < resultOne.weight)? -1 : 0)
			;

			return Utils.prepareListForDraw(finalResult);
		},
		searchFieldWeights:{
			NAME:100,
			LAST_NAME:99,
			WORK_POSITION:98,
		},
		lastSearchItems:[]
	};

	/**
	 * Search utils
	 */

	var Utils = {
		prepareListForDraw: function (list)
		{
			if(list)
				return list
					.filter(user => user["UF_DEPARTMENT"] != false)
					.map(user => ({
							title: Utils.getFormattedName(user),
							subtitle: user.WORK_POSITION,
							sectionCode: "people",
							color: "#5D5C67",
							useLetterImage: true,
								imageUrl: encodeURI(user.PERSONAL_PHOTO),
							sortValues: {
								name: user.LAST_NAME
							},
							params: {
								id: user.ID,
								profileUrl: "/mobile/users/?user_id=" + user.ID
							},
						})
					);

			return [];

		},
		getFormattedName:function(userData)
		{
			var replace = {
				"#NAME#": userData.NAME,
				"#LAST_NAME#": userData.LAST_NAME,
				"#SECOND_NAME#": userData.SECOND_NAME,

			};

			if(userData.LAST_NAME)
			{
				replace["#LAST_NAME_SHORT#"] = userData.LAST_NAME[0].toUpperCase()+".";
			}
			if(userData.SECOND_NAME)
			{
				replace["#SECOND_NAME_SHORT#"] = userData.SECOND_NAME[0].toUpperCase()+".";
			}
			if(userData.NAME)
			{
				replace["#NAME_SHORT#"] = userData.NAME[0].toUpperCase()+".";
			}
			let name = componentResult.get().settings.nameFormat
				.replace(/#NAME#|#LAST_NAME#|#SECOND_NAME#|#LAST_NAME_SHORT#|#SECOND_NAME_SHORT#|#NAME_SHORT#/gi, match => (typeof replace[match] != "undefined" && replace[match] != null) ? replace[match] : "" )
				.trim();

			return name != ""? name : userData.EMAIL;
		}
	};
	var SearchUtils = {
		Const:{
			SEARCH_LOADING:{title: BX.message("SEARCH_LOADING"), code:"loading", type:"loading", unselectable:true},
			SEARCH_MORE_RESULTS:{title: BX.message("LOAD_MORE_RESULT"), code:"more_search_result", type: "button"},
		},
		setServiceCell:function(items, data, customParams)
		{

			items = items.filter(item=>item.sectionCode != "service");

			if(data)
			{
				let params = customParams||{};
				params.code = data.code;
				items.push({
					title: data.title,
					sectionCode:"service",
					type: data.type,
					params: {"code": data.code}
				});
			}

			return items;
		},

	};


	UserList.init();

})();
