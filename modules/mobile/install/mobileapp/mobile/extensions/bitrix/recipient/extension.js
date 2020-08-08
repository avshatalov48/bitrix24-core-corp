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
			if (data == null)
			{
				return null;
			}

			this.ui = dialogs.createRecipientPicker();
			this.ui.allowMultipleSelection(true);
			this.ui.setTitle({text: BX.message("RECIPIENT_TITLE")});
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
			let prepareItems = items => items.map(item =>
			{
				item = DepartmentsList.prepareItemForDrawing(item);
				item.id = `${DepartmentsList.id()}/${item.id}`;
				item.color = "#e2e3e5";
				return item;
			});
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
						prepareItems:function(items)
						{
							return items.map(item=>{
								item = GroupList.prepareItemForDrawing(item);
								item.id = `${GroupList.id()}/${item.id}`;
								item.color = "#ade7e4";
								return item;
							})
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
					if(userList.searcher.currentQueryString === "" && options.useRecentSelected)
					{
						return userList.recent.get();
					}

					let hasAllRecipients = false;
					let modifiedItems = items
						.filter(item => options.hideUnnamed ? item.hasName : true)
						.map(item =>
						{
							if (item.params.id)
							{
								if (item.params.id === "A" && options.showAll === true)
								{
									item.color = "#dbf188";
									hasAllRecipients = true;
								}
								else
								{
									item.color = "#d5f1fc";
								}

								item.id = `users/${item.params.id}`;
							}

							return item;
						});

					if(!loadMore && !hasAllRecipients && userList.searcher.currentQueryString.length  === 0 && options.showAll === true)
					{
						modifiedItems.unshift({
							title: BX.message("RECIPIENT_ALL"),
							subtitle: "",
							color: "#dbf188",
							id: "A",
							params: {id: "A"},
							sectionCode: "people",
							sortValues:{
								name: BX.message("RECIPIENT_ALL")
							},
						});
					}

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

			userList.setOptions({disablePagination:true});
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
			else
			{
				let dataset = this.datasets[this.currentScope];
				reflectFunction(dataset.eventHandlers, event, dataset).call(dataset, data)
			}
		}

		/**
		 * @return {Promise}
		 */
		open(options = {})
		{
			let defaultOptions = {returnShortFormat: false, allowMultipleSelection: true};
			if(options != null && typeof options === "object")
			{
				options = Object.assign(defaultOptions, options);
			}

			return new Promise((resolve, reject) =>
			{
				let scopes = Object.keys(this.datasets);
				let initDataFunction = ()=>scopes.reduce((result, value) =>
				{
					result[value] = [];
					return result;
				}, {});
				let initResult = initDataFunction();
				let rawResult = initDataFunction();
				this.ui.allowMultipleSelection(options.allowMultipleSelection);
				if(options.title)
				{
					if(typeof options.title === "string")
						this.ui.setTitle({text: options.title});
					else
						this.ui.setTitle(options.title)
				}
				this.ui.show()
					.then(data =>
					{
						let result = data.reduce((result, item) =>
						{
							let splitData = item.id.split("/");
							if (splitData.length > 1)
							{
								let scope = splitData[0];
								let id = splitData[1];
								if (scopes.indexOf(scope) >= 0)
								{
									if(options.returnShortFormat === true)
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
											"imageUrl": item.imageUrl
										});
									}

									rawResult[scope].push(item);
								}
							}


							return result;
						}, initResult);

						BX.onCustomEvent("onRecipientSelected", [rawResult]);
						resolve(result);
					})
					.catch(e => reject(e));
			});

		}
	}

	jnexport(RecipientList);
})();