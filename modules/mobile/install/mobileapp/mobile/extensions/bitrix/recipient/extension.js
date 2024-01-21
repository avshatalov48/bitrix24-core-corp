/**
 * @bxjs_lang_path extension.php
 */
(() => {
	const require = (ext) => jn.require(ext);

	const AppTheme = require('apptheme');
	/**
	 * @typedef {string} RecipientDataSet {{GROUPS: string, USERS: string, DEPARTMENTS: string}}
	 * @enum {RecipientDataSet}
	 */
	const RecipientDataSet = {
		USERS: 'users',
		GROUPS: 'groups',
		DEPARTMENTS: 'departments',
	};

	const RecipientColorSet = {
		user: AppTheme.colors.accentSoftBlue1,
		userExtranet: AppTheme.colors.accentMainWarning,
		userAll: AppTheme.colors.accentSoftGreen1,
		group: AppTheme.colors.accentSoftBlue1,
		groupExtranet: AppTheme.colors.accentMainWarning,
		department: AppTheme.colors.bgSeparatorSecondary,
	};

	const RecipientTitleColorSet = {
		userExtranet: AppTheme.colors.accentMainWarning,
		groupExtranet: AppTheme.colors.accentMainWarning,
	};

	const RecipientSubtitleColorSet = {
		userExtranet: AppTheme.colors.accentMainWarning,
		groupExtranet: AppTheme.colors.accentMainWarning,
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
		 * */
		constructor(data = null, options = {})
		{
			this.internalResolve = null;
			if (data == null)
			{
				return null;
			}

			this.ui = dialogs.createRecipientPicker();
			this.currentScope = null;
			this.datasets = {};
			const entries = Object.values(RecipientDataSet);
			const scopes = [];
			data.forEach((item) => {
				if (entries.includes(item))
				{
					scopes.push({ title: BX.message(`RECIPIENT_SCOPE_${item.toUpperCase()}`), id: item });
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
			const dataset = this.datasets[this.currentScope];
			reflectFunction(dataset, 'init', dataset).call(dataset, false);
		}

		createDepartmentsObject(options)
		{
			const departmentsList = new DepartmentsList(this.ui);
			const prepareItems = (items) => {
				let result = [];
				if (Array.isArray(items))
				{
					result = items.map((item) => {
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
						return prepareItems(items);
					},
					onListFill(data)
					{
						if (data.text === '')
						{
							this.draw();
						}
						else
						{
							this.draw({ filter: data.text });
							this.searcher.fetchResults(data);
						}
					},
					onFocusLost()
					{
						this.abortAllRequests();
					},
				},
			)
				.setSearchDelegate(
					new (
						class extends BaseListSearchDelegate
						{
							prepareItems(items)
							{
								return prepareItems(items);
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
									FIND: query,
								};
							}

							getSearchMethod()
							{
								return 'mobile.intranet.departments.get';
							}
						})(this.ui),
				);

			return departmentsList;
		}

		createGroupsObject()
		{
			return new GroupList(this.ui)
				.setHandlers(
					{
						prepareItems: (items) => {
							let result = [];
							if (!Array.isArray(items))
							{
								return result;
							}
							result = items.map((item) => {
								item = GroupList.prepareItemForDrawing(item);
								item.id = `${GroupList.id()}/${item.id}`;
								item.color = (item.params.extranet ? RecipientUtils.getColor('groupExtranet') : RecipientUtils.getColor('group'));
								if (item.params.extranet)
								{
									item.styles = {
										title: {
											font: {
												color: RecipientUtils.getTitleColor('groupExtranet'),
											},
										},
									};
								}

								return item;
							});

							return result;
						},
						onListFill(data)
						{
							if (data.text === '')
							{
								this.draw();
							}
							else
							{
								this.draw({ filter: data.text });
							}
						},
						onFocusLost()
						{
							this.abortAllRequests();
						},
					},
				);
		}

		createUsersObject(options = {})
		{
			const userList = new UserList(this.ui, {
				filterUserList: (items, loadMore) => {
					userList.recent.read();
					if (userList.searcher.currentQueryString === '' && options.useRecentSelected)
					{
						return userList.recent.get();
					}

					userList.recent.read();

					let hasAllRecipients = false;

					if (!loadMore && !hasAllRecipients && userList.searcher.currentQueryString.length === 0 && options.showAll === true)
					{
						items.unshift({
							title: BX.message('RECIPIENT_ALL'),
							subtitle: '',
							color: RecipientUtils.getColor('userAll'),
							id: 'A',
							params: { id: 'A' },
							sectionCode: 'people',
							sortValues: {
								name: BX.message('RECIPIENT_ALL'),
							},
						});
					}

					return items
						.filter((item) => (options.hideUnnamed ? item.hasName : true))
						.map((item) => {
							if (item.params.id)
							{
								if (item.params.id === 'A' && options.showAll === true)
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
											},
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
				},
				onSearchResult: (items, sections, list, state) => {
					if (state === 'searching')
					{
						this.ui.addItems(items, false);
					}
					else
					{
						this.ui.setItems(items, sections);
					}
				},
				eventHandlers()
				{
					return {
						onFocusLost()
						{
							this.abortAllRequests();
						},
						onListFill(data)
						{
							if (data.text === '')
							{
								if (options.useRecentSelected)
								{
									this.items = userList.recent.get();
								}

								this.draw();
							}
							else
							{
								this.draw({ filter: data.text });
								this.searcher.fetchResults(data);
							}
						},
					};
				},
			});

			userList.setOptions({
				disablePagination: true,
				filter: (options.filter ? options.filter : {}),
			});
			userList.recent = {
				limit: 10,
				read()
				{
					this.lastSelected = Application.storageById('recipients').getObject('last', { users: [] });
				},
				get()
				{
					this.read();

					return this.lastSelected.users.map((user) => {
						user.hasName = user.title !== '';
						delete user.checked;
						user.sectionCode = 'people';

						return user;
					});
				},
				add(users)
				{
					const lastSelected = this.lastSelected.users;
					users.forEach((user) => {
						if (!lastSelected.find((selected) => user.id === selected.id))
						{
							lastSelected.unshift(user);
						}
					});

					while (lastSelected.length > this.limit)
					{
						lastSelected.pop();
					}

					Application.storageById('recipients').setObject('last', { users: lastSelected });

					return this.lastSelected;
				},
			};

			BX.addCustomEvent('onRecipientSelected', (selectedData) => {
				if (selectedData.users)
				{
					userList.recent.add(selectedData.users);
				}
			});

			return userList;
		}

		eventHandler(event, data)
		{
			if (event === 'onScopeChanged')
			{
				const prevDataSet = this.datasets[this.currentScope];
				reflectFunction(prevDataSet.eventHandlers, 'onFocusLost', prevDataSet).call(prevDataSet, false);
				this.currentScope = data.scope.id;
				const dataset = this.datasets[this.currentScope];
				reflectFunction(dataset, 'init', dataset).call(dataset, false);
				reflectFunction(dataset.eventHandlers, 'onListFill', dataset).call(dataset, data);
			}
			else if (event === 'onSelectedChanged')
			{
				if (this.singleChoose)
				{
					this.ui.close(() => this.callResolve(data.items));
				}
			}
			else
			{
				const dataset = this.datasets[this.currentScope];
				reflectFunction(dataset.eventHandlers, event, dataset).call(dataset, data);
			}
		}

		/**
		 * @return {Promise}
		 */
		open(options = {})
		{
			const title = options.title || BX.message('RECIPIENT_TITLE');
			this.singleChoose = options.singleChoose || false;
			this.ui.setTitle({ text: title });
			let selected = [];
			if (typeof options.selected === 'object')
			{
				selected = Object.keys(options.selected)
					.reduce((result, key) => {
						const selected = options.selected[key].map((item) => {
							if (typeof item.id !== 'undefined')
							{
								item.id = `${key}/${item.id}`;
							}

							return item;
						});

						return result.concat(selected);
					}, []);
			}

			this.options = { returnShortFormat: false, allowMultipleSelection: true, singleChoose: false };
			if (typeof options === 'object')
			{
				this.options = Object.assign(this.options, options);
			}

			return new Promise((resolve, reject) => {
				this.internalResolve = resolve;
				this.ui.allowMultipleSelection(this.options.allowMultipleSelection);

				if (this.options.title)
				{
					if (typeof this.options.title === 'string')
					{
						this.ui.setTitle({ text: this.options.title });
					}
					else
					{
						this.ui.setTitle(this.options.title);
					}
				}
				this.ui.show()
					.then((data) => this.callResolve(data))
					.catch((e) => {
						console.error(e);
						reject(e);
					});
				setTimeout(() => {
					this.ui.setSelected(selected);
				}, 0);
			});
		}

		callResolve(data)
		{
			const scopes = Object.keys(this.datasets);
			const initDataFunction = () => scopes.reduce((result, value) => {
				result[value] = [];

				return result;
			}, {});
			const initResult = initDataFunction();
			const rawResult = initDataFunction();
			const result = data.reduce((result, item) => {
				const splitData = item.id.split('/');
				if (splitData.length > 1)
				{
					const scope = splitData[0];
					const id = splitData[1];
					if (scopes.includes(scope))
					{
						if (this.options.returnShortFormat === true)
						{
							result[scope].push(id);
						}
						else
						{
							result[scope].push({
								title: item.title,
								subtitle: item.subtitle,
								id,
								params: item.params,
								imageUrl: item.imageUrl,
							});
						}

						rawResult[scope].push(item);
					}
				}

				return result;
			}, initResult);

			BX.onCustomEvent('onRecipientSelected', [rawResult]);
			this.internalResolve.call(null, result);
		}
	}

	const RecipientUtils = {
		getColor(type)
		{
			return (RecipientColorSet[type] ? RecipientColorSet[type] : '');
		},
		getTitleColor(type)
		{
			return (RecipientTitleColorSet[type] ? RecipientTitleColorSet[type] : '');
		},
		getSubtitleColor(type)
		{
			return (RecipientSubtitleColorSet[type] ? RecipientSubtitleColorSet[type] : '');
		},
	};

	this.RecipientUtils = RecipientUtils;

	jnexport(RecipientList);
})();

