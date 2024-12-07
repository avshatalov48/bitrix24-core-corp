/**
 * @bxjs_lang_path extension.php
 * @let BaseList list
 */

(() => {
	const require = (ext) => jn.require(ext);

	const AppTheme = require('apptheme');
	const { splitByWords, compareWords } = require('utils/string');

	/** @interface UserListDelegate */
	class UserListDelegate
	{
		onUserSelected(item)
		{}

		formatUserData(item)
		{}

		filterUserList(items)
		{}

		onSearchResult(items, sections, list)
		{}

		eventHandlers()
		{}
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
			if (this.list == null || this.inited === true)
			{
				return;
			}

			BX.onViewLoaded(() => {
				if (typeof this.list.setSearchFieldParams === 'function')
				{
					this.list.setSearchFieldParams({ placeholder: BX.message('SEARCH_PLACEHOLDER') });
				}
				this.list.setSections([
					{ title: '', id: 'people' }, { title: '', id: 'service' },
				]);
			});

			if (enableEventListener)
			{
				this.list.setListener((event, data) => reflectFunction(this.eventHandlers, event, this).call(
					this,
					data,
				));
			}

			this.searcher = new UserSearcher(this.list, this._delegate, this.options.filter);
			this.searcher.resultHandler = this.onSearchResult.bind(this);

			const filter = {
				ACTIVE: 'Y',
				HAS_DEPARTAMENT: 'Y',
			};
			if (this.options.filter)
			{
				Object.assign(filter, this.options.filter);
			}
			this.request = new RequestExecutor(
				'user.search',
				{
					IMAGE_RESIZE: 'small',
					SORT: 'LAST_NAME',
					ORDER: 'ASC',
					FILTER: filter,
				},
			)
				.setCacheHandler((data) => {
					this.items = UserListUtils.prepareListForDraw(data);
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
			reflectFunction(this.delegate, 'onSearchResult')
				.apply(this, [items, sections, this.list, state]);
		}

		answerHandler(users, loadMore, error = null)
		{
			if (typeof this.list.stopRefreshing === 'function')
			{
				this.list.stopRefreshing();
			}
			this.isLoading = false;

			if (error != null)
			{
				console.warn('refresh error:', error);

				return;
			}

			const listData = UserListUtils.prepareListForDraw(users);
			const modifiedListData = this.prepareItems(listData, loadMore);

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
					this.list.updateItems([
						{
							filter: { sectionCode: 'service' },
							element: {
								title: `${BX.message('LOAD_MORE_USERS')} (${this.request.getNextCount()})`,
								type: 'button',
								unselectable: false,
								sectionCode: 'service',
								params: { code: 'more' },
							},
						},
					]);
				}
				else
				{
					this.list.removeItem({ sectionCode: 'service' });
				}
			}
		}

		draw(params = {})
		{
			let items = [];
			if (params.filter)
			{
				const ids = [];
				const filterFunc = (item) => {
					const query = params.filter.toLowerCase();
					try
					{
						const match = (
							!ids.includes(item.params.id)
							&& (
								(
									item.title
									&& item.title.toLowerCase().startsWith(query)
								)
								|| (
									item.subtitle
									&& item.subtitle.toLowerCase().startsWith(query)
								)
								|| (
									item.sortValues
									&& item.sortValues.name
									&& item.sortValues.name.toLowerCase().startsWith(query)
								)
							)
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

					return typeof match !== 'undefined';
				};

				items = this.items.filter(filterFunc).concat(this.searcher.currentSearchItems.filter(filterFunc));
			}
			else
			{
				items = this.prepareItems(this.items);
				if (this.request.hasNext() && this.options.disablePagination !== true)
				{
					items = items.concat({
						title: `${BX.message('LOAD_MORE_USERS')} (${this.request.getNextCount()})`,
						type: 'button',
						unselectable: false,
						sectionCode: 'service',
						params: { code: 'more' },
					});
				}
			}

			BX.onViewLoaded(() => this.list.setItems(items, [{ id: 'people' }, { id: 'service' }]));
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
					items = items.map((item) => this.delegate.formatUserData(item));
				}
			}

			const uniqueItems = [];
			items.forEach((item) => {
				if (item.id && !uniqueItems[item.id])
				{
					uniqueItems[item.id] = item;
				}
			});

			return Object.values(uniqueItems);
		}

		get eventHandlers()
		{
			let defaultHandlers = {
				onRefresh()
				{
					this.request.call();
				},
				onViewRemoved: () => {},
				onUserTypeText(data)
				{
					this.searcher.fetchResults(data);
				},
				onSearchShow()
				{
					this.searcher.showRecentResults();
				},
				onSearchItemSelected(data)
				{
					if (data.params.code)
					{
						if (data.params.code === 'skip_handle')
						{
							return;
						}

						if (data.params.code === 'more_search_result')
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
				onItemSelected(selectionData)
				{
					const data = selectionData.item || selectionData;
					if (data.params.code)
					{
						if (data.params.code === 'more' && this.request.hasNext())
						{
							this.list.updateItems([
								{
									filter: { sectionCode: 'service' },
									element: {
										title: BX.message('USER_LOADING'),
										type: 'loading',
										sectionCode: 'service',
										unselectable: true,
										params: { code: 'loading' },
									},
								},
							]);

							this.request.callNext();
						}
					}
					else if (this.delegate)
					{
						this.delegate.onUserSelected(data);
					}
				},
				onItemAction(data)
				{
					if (data.action.identifier === 'delete')
					{
						this.searcher.removeRecentSearchItem(data);
					}
				},
			};

			if (typeof this.delegate.eventHandlers === 'function')
			{
				const delegateHandlers = this.delegate.eventHandlers();
				if (typeof delegateHandlers === 'object')
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
			return new Promise((resolve, reject) => {
				(new RecipientList(['users'], options.listOptions))
					.open(options)
					.then((data) => resolve(data.users))
					.catch((e) => reject(e));
			});
		}
	}

	class UserSearcher
	{
		/**
		 *
		 * @param {BaseList} list
		 * @param {UserListDelegate} delegate
		 * @param listFilter
		 */
		constructor(list = null, delegate = null, listFilter = {})
		{
			const filter = {
				ACTIVE: 'Y',
				HAS_DEPARTAMENT: 'Y',
			};
			if (listFilter)
			{
				Object.assign(filter, listFilter);
			}

			this.searchRequest = new DelayedRestRequest(
				'user.search',
				{
					SORT: 'LAST_NAME',
					ORDER: 'ASC',
					FILTER: filter,
				},
			);
			this.resultHandler = null;
			this.delegate = delegate;
			this.list = list;
			this.currentSearchItems = [];
			this.currentQueryString = '';
			this.lastSearchItems = Application.storage.getObject('users_last_search', { items: [] }).items;
		}

		fetchResults(data)
		{
			this.currentQueryString = data.text;
			if (data.text.length >= 3)
			{
				this.currentSearchItems = [];
				this.searchRequest.options.FILTER.FIND = data.text;
				this.searchRequest.handler = (result, loadMore, error) => {
					if (result)
					{
						let items = this.postProgressing(result, data.text);
						items = this.prepareItems(items);
						if (items.length === 0)
						{
							this.sendResult(
								[
									{
										title: BX.message('SEARCH_EMPTY_RESULT'),
										unselectable: true,
										type: 'button',
										params: { code: 'skip_handle' },
									},
								],
								[],
							);
						}
						else
						{
							this.currentSearchItems = items;
							items = SearchUtils.setServiceCell(
								items,
								this.searchRequest.hasNext()
									? SearchUtils.Const.SEARCH_MORE_RESULTS
									: null,
							);

							this.sendResult(items, [{ id: 'people' }, { id: 'service' }]);
						}
					}
					else if (error && error.code !== 'REQUEST_CANCELED')
					{
						this.sendResult(
							[
								{
									title: BX.message('SEARCH_EMPTY_RESULT'),
									unselectable: true,
									type: 'button',
									params: { code: 'skip_handle' },
								},
							],
							[],
						);
					}
				};

				this.sendResult(
					[
						{
							title: BX.message('SEARCH_LOADING'),
							unselectable: true,
							sectionCode: 'service',
							type: 'loading',
							params: { code: 'skip_handle' },
						},
					],
					[
						{ id: 'service' },
						{ id: 'people' },
					],
					'searching',
				);
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
					items = items.map((item) => this.delegate.formatUserData(item));
				}
			}

			return items;
		}

		fetchNextResults()
		{
			if (this.searchRequest.hasNext())
			{
				this.searchRequest.handler = (result, error) => {
					let items = this.currentSearchItems;
					if (result)
					{
						const moreItems = this.postProgressing(result, this.currentQueryString);
						items = items.concat(moreItems);
						this.currentSearchItems = items;
					}
					items = this.prepareItems(items);
					items = SearchUtils.setServiceCell(
						items,
						this.searchRequest.hasNext()
							? SearchUtils.Const.SEARCH_MORE_RESULTS
							: null,
					);
					this.sendResult(items, [{ id: 'people' }, { id: 'service' }], 'result');
				};

				let items = this.currentSearchItems;
				items = SearchUtils.setServiceCell(items, SearchUtils.Const.SEARCH_LOADING);
				this.sendResult(items, [{ id: 'service' }, { id: 'people' }], 'result_loading');
				this.searchRequest.callNext();
			}
		}

		showRecentResults()
		{
			const preparedLastSearchItems = this.lastSearchItems.map((item) => {
				item.actions = [
					{
						title: BX.message('ACTION_DELETE'),
						identifier: 'delete',
						destruct: true,
						color: AppTheme.colors.accentMainAlert,
					},
				];

				return item;
			});
			this.sendResult(this.prepareItems(preparedLastSearchItems), [
				{
					id: 'people',
					title: this.lastSearchItems.length > 0 ? BX.message('RECENT_SEARCH') : '',
				},
			]);
		}

		addRecentSearchItem(data)
		{
			this.lastSearchItems = this.lastSearchItems.filter((item) => item.params.id !== data.params.id);
			this.lastSearchItems.unshift(data);
			Application.storage.setObject('users_last_search', { items: this.lastSearchItems });
		}

		removeRecentSearchItem(data)
		{
			this.lastSearchItems = this.lastSearchItems.filter((item) => item.params.id !== data.item.params.id);
			Application.storage.setObject('users_last_search', { items: this.lastSearchItems });
		}

		postProgressing(items, query)
		{
			try
			{
				query = query.toLowerCase();
				const queryWords = splitByWords(query);
				const shouldMatch = queryWords.length;
				const searchFields = Object.keys(this.searchFieldWeights);
				const result = items.map((item) => {
					let sort = 0;
					const matchCount = 0;
					const matchedWords = [];
					if (searchFields.length > 0 && query)
					{
						searchFields.reverse().forEach((name) => {
							const field = item[name];
							if (field)
							{
								const fieldWords = splitByWords(field);
								const findHandler = (word) => {
									const items = queryWords.filter((queryWord) => {
										const match = compareWords(queryWord, word);
										if (match && !matchedWords.includes(queryWord))
										{
											matchedWords.push(queryWord);
										}

										return match;
									});

									return items.length > 0;
								};

								const result = fieldWords.filter(findHandler);
								if (result.length > 0)
								{
									sort += searchFields.indexOf(name) + 1;
								}
							}
						});
					}
					else
					{
						sort = 1;
					}

					item.sort = (matchedWords.length >= shouldMatch) ? sort + matchCount : -1;

					return item;
				})
					.filter((item) => item.sort >= 0)
					.sort((item1, item2) => {
						if (item1.sort > item2.sort)
						{
							return -1;
						}

						if (item1.sort < item2.sort)
						{
							return 1;
						}

						return 0;
					});

				return UserListUtils.prepareListForDraw(result);
			}
			catch (e)
			{
				console.error(e);

				return UserListUtils.prepareListForDraw(items);
			}
		}

		get searchFieldWeights()
		{
			return {
				NAME: 100,
				LAST_NAME: 99,
				WORK_POSITION: 98,
			};
		}
	}

	/**
	 * Search utils
	 */

	let SearchUtils = {
		Const: {
			SEARCH_LOADING: {
				title: BX.message('SEARCH_LOADING'),
				code: 'loading',
				type: 'loading',
				unselectable: true,
			},
			SEARCH_MORE_RESULTS: { title: BX.message('LOAD_MORE_RESULT'), code: 'more_search_result', type: 'button' },
		},
		setServiceCell(items, data, customParams)
		{
			items = items.filter((item) => item.sectionCode !== 'service');
			if (data)
			{
				const params = customParams || {};
				params.code = data.code;
				items.push({
					title: data.title,
					sectionCode: 'service',
					type: data.type,
					params: { code: data.code },
				});
			}

			return items;
		},

	};

	this.UserList = UserList;
	jnexport(['UserListUtils', Utils]);
})();
