(() =>
{
	const ITEMS_LOAD_LIMIT = 20;
	const DEFAULT_BLOCK_PAGE = 1;
	const MINIMAL_SEARCH_LENGTH = 3;

	const ACTION_DELETE = 'delete';
	const ACTION_UPDATE = 'update';

	/**
	 * @class StatefulList
	 */
	class StatefulList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.isLoading = false;
			this.blockPage = DEFAULT_BLOCK_PAGE;
			this.stateBeforeSearch = null;

			this.actions = this.getValue(props, 'actions', {});
			this.itemLayoutOptions = this.getValue(props, 'itemLayoutOptions', {});
			this.itemParams = this.getValue(props, 'itemParams', {});

			this.layout = this.getValue(props, 'layout', null, false);
			this.layoutMenuActions = this.getValue(props, 'layoutMenuActions', []);
			this.layoutOptions = this.getValue(props, 'layoutOptions', {});

			this.loadItemsHandler = this.loadItems.bind(this);
			this.reloadListHandler = this.reloadListHandler.bind(this);
			this.updateItemHandler = this.updateItemHandler.bind(this);
			this.deleteItemHandler = this.deleteItemHandler.bind(this);
			this.addItemHandler = this.addItemHandler.bind(this);

			this.state = this.getInitialState();
			this.state.itemType = this.getValue(props, 'itemType', ListItemsFactory.Type.Base);
			this.state.actionParams = this.getValue(props, 'actionParams', {});
			this.state.itemActions = this.getValue(props, 'itemActions', []);

			this.pull = this.getValue(props, 'pull', null);

			this.debounceSearch = CommonUtils.debounce(params => {
				this.search(params)
			}, 500, this);

			this.loadFirstItems();
		}

		componentDidMount()
		{
			this.initMenu();
		}

		componentWillReceiveProps(newProps)
		{
			super.componentWillReceiveProps(newProps);

			if (newProps.actionParams)
			{
				this.setState({
					actionParams: this.getValue(newProps, 'actionParams'),
				});
			}
		}

		componentWillUnmount()
		{
			if (this.layout && this.layout.searchBar)
			{
				this.layout.searchBar.removeAllListeners('cancel');
				this.layout.searchBar.removeAllListeners('clickSearch');
				this.layout.searchBar.removeAllListeners('textChanged');
				this.layout.removeAllListeners('onViewShown');
			}
		}

		getValue(object, property, defaultValue = null, isObjectClone = true)
		{
			if (object.hasOwnProperty(property) && isObjectClone)
			{
				return CommonUtils.objectClone(object[property]);
			}

			if (object.hasOwnProperty(property))
			{
				return object[property];
			}

			return defaultValue;
		}

		initMenu()
		{
			const buttons = [];

			if (this.layout && this.layoutOptions.useSearch)
			{
				buttons.push(this.getSearchBarButtonConfig());
				this.initSearchBar();
			}

			if (this.layoutMenuActions.length)
			{
				const menu = new UI.Menu(this.props.layoutMenuActions);
				buttons.push({
					type: 'more',
					badgeCode: 'access_more',
					callback: () => menu.show()
				});
			}

			layout.setRightButtons(buttons);
		}

		loadFirstItems()
		{
			let useOnViewLoaded = true;
			if (this.layoutOptions.useOnViewLoaded !== undefined)
			{
				useOnViewLoaded = this.layoutOptions.useOnViewLoaded;
			}

			BX.onViewLoaded(() => {
				if (useOnViewLoaded)
				{
					this.loadItems();
				}
			});
		}

		getSearchBarButtonConfig()
		{
			return {
				type: 'search',
				badgeCode: 'search',
				callback: () => this.showSearchBar(),
			}
		}

		showSearchBar()
		{
			this.layout.searchBar.show();
			if (this.state.searchText)
			{
				this.layout.searchBar.setText(this.state.searchText);
			}
		}

		initSearchBar()
		{
			const searchBar = this.layout.searchBar;
			searchBar.on('cancel', () => {
				this.cancelSearch();
			});

			searchBar.on('clickSearch', params => {
				this.debounceSearch(params);
			});

			searchBar.on('textChanged', params => {
				this.debounceSearch(params);
			});

			this.layout.on('onViewShown', () => {
				if (this.state.searchText)
				{
					this.showSearchBar();
				}
			});
		}

		cancelSearch()
		{
			this.setState(this.stateBeforeSearch, () => {
				this.stateBeforeSearch = null;
				this.reload();
			});
		}

		search(params)
		{
			if (!params.text)
			{
				return;
			}

			const actionParams = this.getValue(this.state, 'actionParams');
			const searchText = params.text;

			if (
				params.text.length < MINIMAL_SEARCH_LENGTH
				&& actionParams.loadItems.extra
				&& actionParams.loadItems.extra.search
			)
			{
				this.cancelSearch();
				return;
			}

			if (params.text.length < MINIMAL_SEARCH_LENGTH)
			{
				return;
			}

			if (this.stateBeforeSearch === null)
			{
				this.stateBeforeSearch = CommonUtils.objectClone(this.state);
			}

			this.setState(() => {
				const initialState = this.getInitialState();
				actionParams.loadItems.extra = (actionParams.loadItems.extra || {});
				actionParams.loadItems.extra.search = searchText;
				initialState.actionParams = actionParams;
				initialState.searchText = searchText;
				return initialState;
			}, () => {
				this.loadItems(DEFAULT_BLOCK_PAGE, true, {
					useCache: false,
					subscribeUser: false,
				});
			});
		}

		loadItems(blockPage = DEFAULT_BLOCK_PAGE, append = true, params = {})
		{
			if (this.actions.loadItems === undefined)
			{
				return;
			}

			if (this.state.allItemsLoaded)
			{
				this.setState({
					isRefreshing: false,
				});
				return;
			}

			if (this.isLoading)
			{
				return;
			}

			if (blockPage === DEFAULT_BLOCK_PAGE)
			{
				append = false;
			}

			this.isLoading = true;

			this.showTitleLoader();

			const config = {
/*				data: {
					extra: this.extra,
					entityType: this.actionParams,
				},*/
				data: (this.state.actionParams.loadItems || {}),
				navigation: {
					page: blockPage,
					size: ITEMS_LOAD_LIMIT,
				},
			};

			config.data.extra = (config.data.extra || {});
			config.data.extra.subscribeUser = (params.subscribeUser || true);

			const useCache = (
				blockPage === DEFAULT_BLOCK_PAGE
				&& (params.useCache === undefined || params.useCache)
			);

			new RunActionExecutor(this.actions.loadItems, config.data, config.navigation)
				.setCacheId(this.props.cacheName)
				.setCacheHandler(response => this.drawListFromCache(response, blockPage, append))
				.setHandler(response => {
					setTimeout(() => this.drawListFromAjax(response, blockPage, append), 100);
				})
				.call(useCache);
		}

		drawListFromCache(response, blockPage, append)
		{
			const params = {
				append: append,
				incBlockPage: false,
			}
			this.drawList(response, blockPage, params);
		}

		drawListFromAjax(response, blockPage, append)
		{
			const params = {
				append: append,
				incBlockPage: true,
				useLoader: true,
			};
			this.drawList(response, blockPage, params);
		}

		drawList(response, blockPage, params = {})
		{
			if (!response)
			{
				return;
			}

			if (response.errors && response.errors.length)
			{
				response.errors.forEach((error) => {
					this.showError(error.message);
				});
				this.isLoading = false;
				this.setState({
					allItemsLoaded: true,
				});
				return;
			}

			if (params.useLoader)
			{
				this.showTitleLoader();
			}

			this.isLoading = false;

			const data = response.data;

			const items = new Map();
			data.items.map(element => {
				element.type = this.generateType(element);
				element.key = String(element.id);
				items.set(element.id, element)
			});

			const newState = {};
			if (items.size < ITEMS_LOAD_LIMIT)
			{
				newState.allItemsLoaded = true;
			}

			newState.isRefreshing = false;

			if (blockPage === DEFAULT_BLOCK_PAGE)
			{
				newState.permissions = (newState.permissions || {});
				newState.permissions.editable = (data.permissions && data.permissions.write);
				newState.permissions.viewable = (data.permissions && data.permissions.read);
				newState.settingsIsLoaded = true;
			}

			if (params.append)
			{
				newState.items = this.state.items;
				for (let item of items.values())
				{
					newState.items.set(item.id, item);
				}
			}
			else
			{
				newState.items = items;
			}

			if (params.incBlockPage)
			{
				this.blockPage = blockPage;
			}

			this.setState(newState, () => {
				if (params.useLoader)
				{
					this.hideTitleLoader();
				}
			});
		}

		showTitleLoader()
		{
			layout.setTitle({useProgress: true}, true);
		}

		hideTitleLoader()
		{
			layout.setTitle({useProgress: false}, true);
		}

		reloadListHandler()
		{
			this.reload({
				skipItems: true
			});
		}

		reload(initialStateParams = {}, loadItemsParams = {})
		{
			this.isRefreshing = false;
			this.setState(this.getInitialState(initialStateParams), () => {
				this.loadItems(DEFAULT_BLOCK_PAGE, false, loadItemsParams);
			});
		}

		getInitialState(params = {})
		{
			const initialState = {
				isRefreshing: true,
				allItemsLoaded: false,
				permissions: {
					editable: false,
					viewable: false,
				},
				settingsIsLoaded: false,
				searchText: null,
			};

			if (!params.skipItems)
			{
				initialState.items = new Map();
			}

			return initialState;
		}

		updateItemHandler(itemId, params)
		{
			return new Promise((resolve, reject) => {
				const items = this.state.items;
				const item = items.get(itemId);

				for (let name in params.data)
				{
					if (name !== 'fields')
					{
						item.data[name] = CommonUtils.objectClone(params.data[name]);
					}
				}

				// @todo need add new filled fields too
				if (params.data.fields)
				{
					for (let paramsFieldIndex in params.data.fields)
					{
						for (let index in item.data.fields)
						{
							if (params.data.fields[paramsFieldIndex].name === item.data.fields[index].name)
							{
								//CommonUtils.objectClone(params.data.fields[paramsFieldIndex]);
								item.data.fields[index] = Object.assign(
									item.data.fields[index],
									params.data.fields[paramsFieldIndex]
								)
							}
						}
					}
				}

				item.type = this.generateType(item);
				items.set(itemId, item);
				this.modifyCache(ACTION_UPDATE, {item});

				this.setState({items}, () => {
					resolve();
				});
			});
		}

		addItemHandler(itemId, params)
		{
			return new Promise(resolve => {
				const stateItems = this.state.items;
				const item = {
					id: itemId,
					...params,
					key: String(itemId),
				};
				item.type = this.generateType(item);

				const itemMap = new Map();
				itemMap.set(itemId, item);

				const items = new Map([...itemMap, ...stateItems]);

				this.setState({items}, () => {
					resolve();
				});
			});
		}

		deleteItemHandler(itemId)
		{
			return new Promise((resolve, reject) => {
				this.setState(state => {
					const items = state.items;
					items.delete(itemId);
					this.modifyCache(ACTION_DELETE, {itemId});
					resolve({items});
				});
			});
		}

		/*
		 * @todo I think need create a separate class for work with list cache.
		 * Also we depend on the RunActionExecutor and if the way of working with the cache changes there, then this code will stop working
		 */
		modifyCache(action, params = {})
		{
			const cacheName = this.props.cacheName;
			const cache = Application.storage.getObject(cacheName, null);

			if (!(cache && cache.data && cache.data.items))
			{
				return;
			}

			if (action === ACTION_DELETE && this.deleteItemFromCache(cache, params.itemId))
			{
				Application.storage.setObject(cacheName, cache);
				return;
			}

			if (action === ACTION_UPDATE && this.updateItemInCache(cache, params.item))
			{
				Application.storage.setObject(cacheName, cache);
			}
		}

		deleteItemFromCache(cache, itemId)
		{
			return cache.data.items.some((item, index) => {
				if (item.id === itemId)
				{
					cache.data.items.splice(index, 1);
					return true;
				}
				return false;
			});
		}

		updateItemInCache(cache, item)
		{
			return cache.data.items.some((current, index) => {
				if (current.id === item.id)
				{
					cache.data.items[index].data = item.data;
					return true;
				}
				return false;
			});
		}

		/**
		 * First generation of hash method. This method need for correct work of a ListView widget.
		 * @param item
		 * @returns {string}
		 */
		generateType(item)
		{
			let str = '';
			let keyStatus;

			const generateForGroup = group => {
				if (!group)
				{
					return;
				}

				for (let key in group)
				{
					if (BX.type.isPlainObject(group[key]) && !Array.isArray(group[key]))
					{
						keyStatus = (group[key] ? Boolean(Object.keys(group[key]).length) : false);
					}
					else if (Array.isArray(group[key]))
					{
						keyStatus = Boolean(group[key].length);
					}
					else
					{
						keyStatus = Boolean(group[key]);
					}

					keyStatus = Number(keyStatus);
					str += key + ':' + keyStatus + '-';
				}
			};

			generateForGroup(item.data);
			generateForGroup(item.data.fields);

			const hashCode = (s => {
				let h=0;
				for(let i = 0; i < s.length; i++)
				{
					h = Math.imul(31, h) + s.charCodeAt(i) | 0;
				}

				return String(h);
			});

			return hashCode(str);
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: '#F0F2F5',
						flex: 1,
					},
				},
				View(
					{
						style: {
							flex: 1,
							flexDirection: 'column',
						},
					},

					new SimpleList({
						permissions: {
							editable: this.state.permissions.editable,
							viewable: this.state.permissions.viewable,
						},
						settingsIsLoaded: this.state.settingsIsLoaded,
						itemType: this.state.itemType,
						items: this.state.items,
						itemParams: this.itemParams,
						isSearchEnabled: this.stateBeforeSearch !== null,
						emptyListText: this.props.emptyListText,
						emptySearchText: this.props.emptySearchText,
						blockPage: this.blockPage,
						allItemsLoaded: this.state.allItemsLoaded,
						itemLayoutOptions: this.itemLayoutOptions,
						itemActions: this.state.itemActions,
						loadItemsHandler: this.loadItemsHandler,
						reloadListHandler: this.reloadListHandler,
						addItemHandler: this.addItemHandler,
						updateItemHandler: this.updateItemHandler,
						deleteItemHandler: this.deleteItemHandler,
						itemDetailOpenHandler: this.props.itemDetailOpenHandler,
						floatingButtonClickHandler: (this.props.floatingButtonClickHandler || null),
						itemsLoadLimit: ITEMS_LOAD_LIMIT,
						isRefreshing: this.state.isRefreshing,
						pull: this.pull,
					}),
				)
			);
		}

		showError(errorText)
		{
			if (errorText.length)
			{
				navigator.notification.alert(errorText, null, '');
			}
		}
	}

	this.StatefulList = StatefulList;
})();
