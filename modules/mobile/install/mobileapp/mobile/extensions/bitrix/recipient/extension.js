/**
* @bxjs_lang_path extension.php
*/
(() =>
{
	/**
	 * @typedef {string} RecipientDataSet {{GROUPS: string, USERS: string, DEPARTMENTS: string}}
	 * @enum {RecipientDataSet}
	 */
	const RecipientDataSet = {
		USERS: "users",
		GROUPS: "groups",
		DEPARTMENTS: "departments"
	};

	const RecipientColorSet = {
		user: "#d5f1fc",
		userExtranet: "#ffa900",
		userAll: "#dbf188",
		group: "#ade7e4",
		groupExtranet: "#ffa900",
		department: "#e2e3e5"
	};

	const RecipientTitleColorSet = {
		userExtranet: "#ca8600",
		groupExtranet: "#ca8600",
	};

	const RecipientSubtitleColorSet = {
		userExtranet: "#ca8600",
		groupExtranet: "#ca8600",
	};

	/**
	 *@class RecipientList
	 */
	class RecipientList
	{
		/**
		 * @param {array<RecipientDataSet>} data
		 * @param options
		 * @return {null}
		 **/
		constructor(data = null, options = {})
		{
			this.internalResolve = null;
			if (data == null)  {
				return null;
			}

			this.ui = dialogs.createRecipientPicker();
			this.currentScope = null;
			this.datasets = {};
			let entries = Object.values(RecipientDataSet);
			let scopes = [];
			data.forEach(item =>
			{
				if (entries.indexOf(item) >= 0)
				{
					scopes.push({title: BX.message(`RECIPIENT_SCOPE_${item.toUpperCase()}`), id: item});
					if (scopes.length === 1)
					{
						this.currentScope = item;
					}
					this.datasets[item] = reflectFunction(this, `create ${item}Object`, this).call(this, options[item]);
				}
			});

			if (scopes.length > 1)
			{
				this.ui.setScopes(scopes);
			}
			this.ui.setListener((event, data) => this.eventHandler(event, data));
			let dataset = this.datasets[this.currentScope];
			reflectFunction(dataset, "init", dataset).call(dataset, false);
		}

		createDepartmentsObject(options)
		{
			let departmentsList = new DepartmentsList(this.ui);
			let prepareItems = (items) => {
				let result = [];
				if (Array.isArray(items))
				{
					result = items.map(item => {
						item = DepartmentsList.prepareItemForDrawing(item);
						item.id = `${DepartmentsList.id()}/${item.id}`;
						item.color = RecipientUtils.getColor('department');
						return item;
					});
				}

				return result;
			};
			departmentsList.setHandlers(
				{
					prepareItems(items)
					{
						return prepareItems(items)
					},
					onListFill: function (data)
					{
						if (data.text === "")
						{
							this.draw();
						}
						else
						{
							this.draw({filter: data.text});
							this.searcher.fetchResults(data)
						}
					},
					onFocusLost: function ()
					{
						this.abortAllRequests();
					},
				})
				.setSearchDelegate(
					new (
						class extends BaseListSearchDelegate
						{
							prepareItems(items)
							{
								return prepareItems(items)
							}

							onSearchRequestStart(items, sections)
							{
								departmentsList.abortAllRequests();
								super.onSearchRequestStart(items, sections);
							}

							getSearchQueryOption(query)
							{
								return {
									LIMIT: 50,
									FIND: query
								}
							}

							getSearchMethod()
							{
								return "mobile.intranet.departments.get";
							}
						})(this.ui)
				);

			return departmentsList;
		}

		createGroupsObject()
		{
			return new GroupList(this.ui)
				.setHandlers(
					{
						prepareItems:(items) => {
							let result = [];
							if (!Array.isArray(items))
							{
								return result;
							}
							result = items.map(item=>{
								item = GroupList.prepareItemForDrawing(item);
								item.id = `${GroupList.id()}/${item.id}`;
								item.color = (item.params.extranet ? RecipientUtils.getColor('groupExtranet') : RecipientUtils.getColor('group'));
								if (item.params.extranet)
								{
									item.styles = {
										title: {
											font: {
												color: RecipientUtils.getTitleColor('groupExtranet'),
											}
										},
									};
								}

								return item;
							});
							return result;
						},
						onListFill: function (data)
						{
							if (data.text === "")
							{
								this.draw();
							}
							else
							{
								this.draw({filter: data.text});
							}
						},
						onFocusLost: function ()
						{
							this.abortAllRequests();
						},
					});

		}

		createUsersObject(options = {})
		{
			let userList = new UserList(this.ui, {
				filterUserList: (items, loadMore) =>
				{
					userList.recent.read();
					if(userList.searcher.currentQueryString === "" && options.useRecentSelected)
					{
						return userList.recent.get();
					}
					else
					{
						userList.recent.read();
					}

					let hasAllRecipients = false;

					if(!loadMore && !hasAllRecipients && userList.searcher.currentQueryString.length  === 0 && options.showAll === true)
					{
						items.unshift({
							title: BX.message("RECIPIENT_ALL"),
							subtitle: "",
							color: RecipientUtils.getColor('userAll'),
							id: "A",
							params: {id: "A"},
							sectionCode: "people",
							sortValues:{
								name: BX.message("RECIPIENT_ALL")
							},
						});
					}

					let modifiedItems = items
						.filter(item => options.hideUnnamed ? item.hasName : true)
						.map(item =>
						{
							if (item.params.id)
							{
								if (item.params.id === "A" && options.showAll === true)
								{
									item.color = RecipientUtils.getColor('userAll');
									hasAllRecipients = true;
								}
								else if (
									typeof item.params.userType !== 'undefined'
									&& item.params.userType === 'extranet'
								)
								{
									item.styles = {
										title: {
											font: {
												color: RecipientUtils.getTitleColor('userExtranet'),
											}
										},
									};
									item.color = RecipientUtils.getColor('userExtranet');
								}
								else
								{
									item.color = RecipientUtils.getColor('user');
								}

								item.id = `users/${item.params.id}`;
							}

							return item;
						});

					return modifiedItems;
				},
				onSearchResult: (items, sections, list, state) =>
				{
					if (state === "searching")
					{
						this.ui.addItems(items, false)
					}
					else
					{
						this.ui.setItems(items, sections);
					}
				},
				eventHandlers()
				{
					return {
						onFocusLost: function ()
						{
							this.abortAllRequests();
						},
						onListFill: function (data)
						{
							if (data.text === "")
							{
								if (options.useRecentSelected)
								{
									this.items = userList.recent.get();
								}

								this.draw();
							}
							else
							{
								this.draw({filter: data.text});
								this.searcher.fetchResults(data);
							}
						}
					}
				}
			});

			userList.setOptions({
				disablePagination: true,
				filter: (options.filter ? options.filter : {})
			});
			userList.recent = {
				limit:10,
				read:function(){
					this.lastSelected = Application.storageById("recipients").getObject("last", {users:[]});
				},
				get:function()
				{
					this.read();
					return this.lastSelected.users.map(user =>{
						user.hasName = user.title !== "";
						delete user.checked;
						user.sectionCode = "people";
						return user;
					});
				},
				add:function(users)
				{
					let lastSelected = this.lastSelected["users"];
					users.forEach(user=>
					{
						if(!lastSelected.find(selected=>user.id === selected.id))
						{
							lastSelected.unshift(user);
						}
					});

					while(lastSelected.length > this.limit)
					{
						lastSelected.pop();
					}

					Application.storageById("recipients").setObject("last", {users: lastSelected});
					return this.lastSelected;
				}
			};


			BX.addCustomEvent("onRecipientSelected", (selectedData)=>
			{
				if(selectedData.users)
					userList.recent.add(selectedData.users);
			});

			return userList;
		}

		eventHandler(event, data)
		{
			if (event === "onScopeChanged")
			{
				let prevDataSet = this.datasets[this.currentScope];
				reflectFunction(prevDataSet.eventHandlers, "onFocusLost", prevDataSet).call(prevDataSet, false);
				this.currentScope = data.scope.id;
				let dataset = this.datasets[this.currentScope];
				reflectFunction(dataset, "init", dataset).call(dataset, false);
				reflectFunction(dataset.eventHandlers, "onListFill", dataset).call(dataset, data);
			}
			else if(event === "onSelectedChanged")
			{
				if(this.singleChoose) {
					this.ui.close(()=>this.callResolve(data.items));
				}
			}
			else
			{


				let dataset = this.datasets[this.currentScope];
				reflectFunction(dataset.eventHandlers, event, dataset).call(dataset, data)
			}
		}

		/**
		 * @return {Promise}
		 */
		open(options = {}) {
			let title = options.title || BX.message("RECIPIENT_TITLE");
			this.singleChoose = options.singleChoose || false
			this.ui.setTitle({text: title});
			let selected = [];
			if(typeof options.selected === "object"){
				selected = Object.keys(options.selected)
					.reduce((result, key) =>
					{
						let selected = options.selected[key].map(item => {
							if (typeof item.id !== "undefined")
								item.id = key + "/" + item.id;
							return item
						});

						return result.concat(selected)

					}, [])
			}

			this.options = {returnShortFormat: false, allowMultipleSelection: true, singleChoose: false};
			if(typeof options === "object") {
				this.options = Object.assign(this.options, options);
			}

			return new Promise((resolve, reject) =>
			{
				this.internalResolve = resolve;
				this.ui.allowMultipleSelection(this.options.allowMultipleSelection);

				if(this.options.title)
				{
					if(typeof this.options.title === "string")
						this.ui.setTitle({text: this.options.title});
					else
						this.ui.setTitle(this.options.title)
				}
				this.ui.show()
					.then(data => this.callResolve(data))
					.catch(e => {
							console.error(e);
							reject(e);
						}
					);
				setTimeout(()=>{
					this.ui.setSelected(selected);
				} , 0)
			});

		}

		callResolve(data) {
			let scopes = Object.keys(this.datasets);
			let initDataFunction = ()=>scopes.reduce((result, value) =>
			{
				result[value] = [];
				return result;
			}, {});
			let initResult = initDataFunction();
			let rawResult = initDataFunction();
			let result = data.reduce((result, item) =>
			{
				let splitData = item.id.split("/");
				if (splitData.length > 1)
				{
					let scope = splitData[0];
					let id = splitData[1];
					if (scopes.indexOf(scope) >= 0)
					{
						if(this.options.returnShortFormat === true)
						{
							result[scope].push(id)
						}
						else
						{
							result[scope].push({
								"title": item.title,
								"subtitle": item.subtitle,
								"id": id,
								"params": item.params,
								"imageUrl": item.imageUrl,
							});
						}

						rawResult[scope].push(item);
					}
				}

				return result;
			}, initResult);

			BX.onCustomEvent("onRecipientSelected", [rawResult]);
			this.internalResolve.call(null, result)
		}
	}

	const RecipientUtils = {
		getColor(type) {
			return (RecipientColorSet[type] ? RecipientColorSet[type] : '');
		},
		getTitleColor(type) {
			return (RecipientTitleColorSet[type] ? RecipientTitleColorSet[type] : '');
		},
		getSubtitleColor(type) {
			return (RecipientSubtitleColorSet[type] ? RecipientSubtitleColorSet[type] : '');
		}
	};

	this.RecipientUtils = RecipientUtils;

	jnexport(RecipientList);
})();