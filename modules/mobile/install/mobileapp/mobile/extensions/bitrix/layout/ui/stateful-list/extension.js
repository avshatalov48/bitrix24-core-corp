/**
 * @module layout/ui/stateful-list
 */
jn.define('layout/ui/stateful-list', (require, exports, module) => {

	const { NavigationLoader } = require('navigation-loader');
	const { debounce } = require('utils/function');
	const { merge, mergeImmutable, get, set, clone, isEqual } = require('utils/object');
	const { PureComponent } = require('layout/pure-component');
	const { SimpleList } = require('layout/ui/simple-list');

	const ITEMS_LOAD_LIMIT = 20;
	const DEFAULT_BLOCK_PAGE = 1;
	const MINIMAL_SEARCH_LENGTH = 3;

	const ACTION_DELETE = 'delete';
	const ACTION_UPDATE = 'update';

	const renderType = {
		cache: 'cache',
		ajax: 'ajax',
	};

	/**
	 * @class StatefulList
	 */
	class StatefulList extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.isLoading = false;
			this.stateBeforeSearch = null;
			this.simpleList = null;
			this.searchBarIsInited = false;
			this.isApiGreaterThen44 = (Application.getApiVersion() > 44);
			this.nameOfClickEnterEvent = (this.isApiGreaterThen44 ? 'clickEnter' : 'clickSearch');
			this.needAnimateIds = [];
			this.layoutRightButtons = null;
			this.menuProps = null;

			this.actions = this.getValue(props, 'actions', {});
			this.itemLayoutOptions = this.getValue(props, 'itemLayoutOptions', {});
			this.menuButtons = this.getValue(props, 'menuButtons', []);
			this.needInitMenu = this.getValue(props, 'needInitMenu', true);
			this.canUseSearchObject = this.canUseSearchObjectHandler();

			this.layout = this.getValue(props, 'layout', null, false);
			this.layoutMenuActions = this.getValue(props, 'layoutMenuActions', []);
			this.layoutOptions = this.getValue(props, 'layoutOptions', {});

			this.loadItemsHandler = this.loadItems.bind(this);
			this.reloadListHandler = this.reloadListHandler.bind(this);
			this.updateItemHandler = this.updateItemHandler.bind(this);
			this.deleteItemHandler = this.deleteItemHandler.bind(this);
			this.addItemHandler = this.addItemHandler.bind(this);
			this.menuShowHandler = this.menuShow.bind(this);
			this.bindSimpleListRef = this.bindSimpleListRef.bind(this);

			this.state = this.getInitialState();
			this.state.itemType = this.getValue(props, 'itemType', ListItemsFactory.Type.Base);
			this.state.actionParams = this.getValue(props, 'actionParams', {});
			this.state.itemParams = this.getValue(props, 'itemParams', {});
			this.state.itemActions = this.getValue(props, 'itemActions', []);

			this.pull = this.getValue(props, 'pull', null);

			this.getRuntimeParams = this.getValue(props, 'getRuntimeParams', null);
			this.searchConfig = this.getPreparedSearchConfig();

			this.onSearchTextChanged = this.onSearchTextChangedHandler.bind(this);
			this.onSearchClick = this.onSearchClickHandler.bind(this);
			this.onSearchCancel = this.onSearchCancelHandler.bind(this);
			this.onViewShow = this.onViewShowHandler.bind(this);

			this.debounceSearch = debounce((params, callback) => {
				this.search(params, callback);
			}, 500, this);

			this.loadFirstItems();
		}

		getPreparedSearchConfig()
		{
			const defaultConfig = {
				mode: 'bar',
				searchLayout: null,
				searchLayoutCallback: null,
			};
			const config = this.getValue(this.props, 'search', {});

			return mergeImmutable(defaultConfig, config);
		}

		componentDidMount()
		{
			if (this.needInitMenu)
			{
				this.initMenu();
				this.initSearchBar();
			}

			this.layout.on('onViewShown', () => {
				this.onViewShow();
			});
		}

		componentWillReceiveProps(newProps)
		{
			if (newProps.actionParams)
			{
				this.state.actionParams = this.getValue(newProps, 'actionParams');
			}

			this.needInitMenu = this.getValue(newProps, 'needInitMenu', true);
			if (
				this.needInitMenu
				&& (
					(newProps.menuButtons && !isEqual(this.menuButtons, newProps.menuButtons))
					|| !newProps.menuButtons
				)
			)
			{
				this.menuButtons = (Array.isArray(newProps.menuButtons) ? newProps.menuButtons : []);
				this.initMenu(newProps);
			}

			this.state.itemActions = this.getValue(newProps, 'itemActions', []);
		}

		componentWillUnmount()
		{
			if (this.layoutOptions.useSearch)
			{
				this.removeSearchBarListeners();
			}

			this.layout.removeEventListener('onViewShown', this.onViewShow);
		}

		removeSearchBarListeners()
		{
			const search = this.getSearchObject();
			if (!search)
			{
				return;
			}

			// search.removeAllListeners('cancel');
			// search.removeAllListeners('clickSearch');
			// search.removeAllListeners('textChanged');
			// this.layout.removeAllListeners('onViewShown');

			search.removeEventListener('cancel', this.onSearchCancel);
			search.removeEventListener(this.nameOfClickEnterEvent, this.onSearchClick);
			search.removeEventListener('textChanged', this.onSearchTextChanged);
		}

		getValue(object, property, defaultValue = null, isObjectClone = true)
		{
			if (object.hasOwnProperty(property) && isObjectClone)
			{
				return clone(object[property]);
			}

			return get(object, property, defaultValue);
		}

		initMenu(props = null, buttons = null)
		{
			if (!buttons)
			{
				buttons = this.getMenuButtons(props);
			}

			if (!isEqual(buttons, this.layoutRightButtons))
			{
				this.layoutRightButtons = buttons;
				layout.setRightButtons(buttons);
			}
		}

		setMenuButtons(buttons)
		{
			this.menuButtons = buttons;

			return this;
		}

		getMenuButtons(props = null)
		{
			if (!props)
			{
				props = this.props;
			}

			const buttons = [];

			if (this.layout && this.layoutOptions.useSearch)
			{
				buttons.push(this.getSearchBarButtonConfig());
				//this.initSearchBar();
			}

			if (this.layout && Array.isArray(this.menuButtons))
			{
				this.menuButtons.forEach(button => {
					buttons.push(button);
				});
			}

			if (this.layoutMenuActions.length)
			{
				this.menuProps = props;
				buttons.push({
					type: 'more',
					badgeCode: 'access_more',
					callback: this.menuShowHandler,
				});
			}

			return buttons;
		}

		menuShow()
		{
			if (this.menuProps.layoutMenuActions)
			{
				const menu = new UI.Menu(this.menuProps.layoutMenuActions);
				menu.show();
			}
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
			};
		}

		showSearchBar()
		{
			const search = this.getSearchObject();
			if (!search)
			{
				return;
			}

			search.show();
			this.setSearchText();
		}

		/**
		 * @returns {boolean}
		 */
		isSearchInLayoutMode()
		{
			return (this.getSearchMode() === 'layout');
		}

		/**
		 * @returns {String}
		 */
		getSearchMode()
		{
			const search = this.getSearchObject();

			return search.mode;
		}

		setSearchText()
		{
			const searchText = this.state.searchText;
			if (!searchText)
			{
				return;
			}

			const search = this.getSearchObject();

			if (this.canUseSearchObject)
			{
				search.text = searchText;
			}
			else
			{
				search.setText(searchText);
			}
		}

		initSearchBar()
		{
			if (!this.layout || !this.layoutOptions.useSearch || this.searchBarIsInited)
			{
				return;
			}

			const searchBar = this.getSearchObject(true);

			if (!searchBar)
			{
				return;
			}

			this.searchBarIsInited = true;

			searchBar.on('cancel', () => {
				this.onSearchCancel();
			});

			searchBar.on(this.nameOfClickEnterEvent, params => {
				this.onSearchClick(params);
			});

			searchBar.on('textChanged', params => {
				this.onSearchTextChanged(params);
			});

			if (this.isApiGreaterThen44)
			{
				searchBar.setReturnKey('done');
			}
		}

		onSearchCancelHandler()
		{
			this.cancelSearch();
		}

		onSearchClickHandler(params)
		{
			params.eventName = this.nameOfClickEnterEvent;
			this.debounceSearch(params);
		}

		onSearchTextChangedHandler(params)
		{
			params.eventName = 'textChanged';
			this.debounceSearch(params);
		}

		onViewShowHandler()
		{
			if (this.searchBarIsInited && this.state.searchText)
			{
				this.showSearchBar();
			}

			const simpleList = this.getSimpleList();
			if (this.needAnimateIds.length > 0 && simpleList)
			{
				simpleList.lastElementIdAddedWithAnimation = this.needAnimateIds[this.needAnimateIds.length - 1];
				this.needAnimateIds.map(id => this.blinkItem(id));
			}

			this.needAnimateIds = [];
		}

		cancelSearch()
		{
			if (this.stateBeforeSearch && !this.isSearchCancelled())
			{
				this.setState(this.stateBeforeSearch, () => {
					this.stateBeforeSearch = null;
					this.reload();
				});
			}
		}

		search(params, callback)
		{
			if (typeof params !== 'object' || this.isSearchCancelled(params))
			{
				return;
			}

			const presetId = (params.filterPresetId || null);

			if (params.text && params.text !== this.state.searchText)
			{
				this.searchByPhrase(params.text, presetId, callback);
			}
			else if (presetId && presetId !== this.state.searchText)
			{
				this.searchByPreset(presetId, callback);
			}
			else
			{
				Keyboard.dismiss();
			}
		}

		/**
		 * @param {string} searchText
		 * @param {string} searchPresetId
		 * @param {function|null} callback
		 */
		searchByPhrase(searchText, searchPresetId, callback = null)
		{
			const actionParams = this.getValue(this.state, 'actionParams');

			if (
				searchText.length < MINIMAL_SEARCH_LENGTH
				&& actionParams.loadItems.extra
				&& actionParams.loadItems.extra.search
			)
			{
				this.cancelSearch();
				return;
			}

			if (searchText.length < MINIMAL_SEARCH_LENGTH)
			{
				return;
			}

			if (this.stateBeforeSearch === null)
			{
				this.stateBeforeSearch = clone(this.state);
			}

			this.updateStateBySearch({
				searchText,
				searchPresetId,
				callback,
			});
		}

		searchByPreset(searchPresetId, callback)
		{
			if (this.stateBeforeSearch === null)
			{
				this.stateBeforeSearch = clone(this.state);
			}

			this.updateStateBySearch({
				searchPresetId,
				callback,
			});
		}

		/**
		 * @private
		 * @param {Object} params
		 */
		updateStateBySearch(params)
		{
			this.setState(
				this.getPreparedSearchInitialState(params),
				() => {
					this.updateStateBySearchCallback(params);
				},
			);
		}

		/**
		 * @private
		 * @param {Object} params
		 */
		updateStateBySearchCallback(params)
		{
			this.loadItems(DEFAULT_BLOCK_PAGE, true, {
				useCache: false,
				subscribeUser: false,
			});

			/*
			@todo check this
			if (
				!params.skipCallback
				&& typeof this.searchConfig.callback === 'function'
				&& this.searchConfig.params
			)
			{
				this.searchConfig.callback(...Object.values(this.searchConfig.params));
			}
			*/

			if (typeof params.callback === 'function')
			{
				params.callback();
			}
		}

		/**
		 * @private
		 * @param {Object} params
		 * @returns {Object}
		 */
		getPreparedSearchInitialState(params)
		{
			// @todo maybe need to clone actionParams?
			const actionParams = this.getValue(this.state, 'actionParams');
			const initialState = this.getInitialState();
			actionParams.loadItems.extra = (actionParams.loadItems.extra || {});

			if (params.searchText)
			{
				actionParams.loadItems.extra.search = params.searchText;
				initialState.searchText = params.searchText;
			}

			if (params.searchPresetId)
			{
				actionParams.loadItems.extra.filterParams = (actionParams.loadItems.extra.filterParams || {});
				actionParams.loadItems.extra.filterParams.FILTER_PRESET_ID = params.searchPresetId;
				initialState.searchPresetId = params.searchPresetId;
			}

			initialState.actionParams = actionParams;

			return initialState;
		}

		/**
		 * @returns {boolean}
		 */
		isSearchCancelled(params)
		{
			params = (params || {});

			if (typeof this.getRuntimeParams === 'function')
			{
				const { state } = this;

				const runtimeParams = this.props.getRuntimeParams({
					state,
					params,
				});

				if (runtimeParams.cancelSearch)
				{
					return true;
				}
			}

			return false;
		}

		updateItems(ids, showAnimateOnView = true, showAnimateImmediately = false, useFilter = true)
		{
			return new Promise((resolve) => {
				this.loadItemsByIds(ids, useFilter).then((items) => {
					this.prepareItems(items);
					if (showAnimateOnView)
					{
						this.fillAnimateIds(items);
					}

					const simpleList = this.getSimpleList();
					const animatePromises = showAnimateImmediately
						? items.map(({ id }) => simpleList.setLoading(id))
						: [];

					Promise.all(animatePromises).then(() => {
						const dropLoadingPromises = items.map(({ id }) => simpleList.dropLoading(id));

						Promise.all(dropLoadingPromises).then(() => {
							simpleList.listView.updateRows(items).then(() => {
								items.forEach((item) => {
									const { id } = item;

									if (this.state.items.has(id))
									{
										this.state.items.set(id, item);
									}
								});

								this.modifyCache(ACTION_UPDATE, { items });

								resolve();
							});
						});
					});
				});
			});
		}

		/**
		 * @param {Number[]} ids
		 * @param {boolean} useFilter
		 * @returns {Promise}
		 */
		loadItemsByIds(ids = [], useFilter = true)
		{
			return new Promise((resolve, reject) => {
				if (!ids.length)
				{
					resolve([]);
					return;
				}

				const data = clone(this.state.actionParams.loadItems || {});
				data.extra = data.extra || {};
				const filterParams = data.extra.filterParams || {};
				filterParams.ID = ids;

				if (!useFilter)
				{
					filterParams.FILTER_PRESET_ID = '';
					set(data.extra, 'filter.presetId', "");
				}

				merge(data.extra, { filterParams }, { subscribeUser: true, filter: {} });

				BX.ajax.runAction(this.actions.loadItems, { data })
					.then(response => {
						if (response.errors.length)
						{
							reject(response.errors);
						}
						resolve(response.data.items);
					}, response => {
						console.error(response.errors);
						reject(response.errors);
					});
			});
		}

		fillAnimateIds(items)
		{
			items.forEach((item) => this.addToAnimateIds(item.id));
		}

		addToAnimateIds(id)
		{
			if (!this.needAnimateIds.includes(id))
			{
				this.needAnimateIds.push(id);
			}
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
				data: (this.state.actionParams.loadItems || {}),
				navigation: {
					page: blockPage,
					size: ITEMS_LOAD_LIMIT,
				},
			};

			config.data.extra = config.data.extra || {};
			if (params.extra)
			{
				config.data.extra = merge(
					config.data.extra,
					params.extra,
				);
			}
			config.data.extra.subscribeUser = (params.subscribeUser || true);

			const useCache = (
				blockPage === DEFAULT_BLOCK_PAGE
				&& (params.useCache === undefined || params.useCache)
				&& this.props.cacheName !== undefined
			);

			const cacheId = (useCache ? this.props.cacheName : null);
			const uid = this.getLoadItemsRequestUid();

			new RunActionExecutor(this.actions.loadItems, config.data, config.navigation)
				.setCacheId(cacheId)
				.setUid(uid)
				.setCacheHandler((response, requestUid) => this.drawListFromCache(response, blockPage, append, requestUid))
				.setHandler((response, requestUid) => this.drawListFromAjax(response, blockPage, append, requestUid))
				.call(useCache)
			;
		}

		getLoadItemsRequestUid()
		{
			return (this.props.cacheName || Random.getString());
		}

		drawListFromCache(response, blockPage, append, requestUid)
		{
			if (!this.isActualRequest(requestUid))
			{
				return;
			}

			const params = {
				append: append,
				incBlockPage: false,
				renderType: renderType.cache,
			};

			BX.postComponentEvent('UI.StatefulList::onDrawList', [{
				renderType: renderType.cache,
				items: response.data.items,
				blockPage: this.state.blockPage,
				params,
			}]);

			this.drawList(response, blockPage, params);
		}

		drawListFromAjax(response, blockPage, append, requestUid)
		{
			if (!this.isActualRequest(requestUid))
			{
				return;
			}

			const { loadItems } = this.state.actionParams;

			BX.postComponentEvent('UI.StatefulList::onDrawListFromAjax', [{
				renderType: renderType.ajax,
				items: response.data.items,
				blockPage: this.state.blockPage,
				params: loadItems,
			}]);

			const params = {
				append: append,
				incBlockPage: true,
				useLoader: true,
				renderType: renderType.ajax,
			};

			this.drawList(response, blockPage, params);
		}

		isActualRequest(uid)
		{
			return (uid === this.getLoadItemsRequestUid());
		}

		drawList(response, blockPage, params = {})
		{
			if (!response)
			{
				return;
			}

			this.isLoading = false;

			if (response.errors && response.errors.length && response.data)
			{
				response.errors.forEach((error) => {
					this.showError(error.message);
				});
				this.setState({
					allItemsLoaded: true,
				});
				return;
			}

			const { data } = response;

			this.prepareItems(data.items);
			const items = new Map();
			data.items.forEach(element => items.set(element.id, element));

			const newState = {
				renderType: params.renderType,
			};

			if (items.size < ITEMS_LOAD_LIMIT)
			{
				newState.allItemsLoaded = true;
			}

			newState.isRefreshing = false;

			if (blockPage === DEFAULT_BLOCK_PAGE)
			{
				newState.permissions = (newState.permissions || {});
				newState.permissions.edit = (data.permissions && data.permissions.write);
				newState.permissions.view = (data.permissions && data.permissions.read);
				newState.permissions.add = (data.permissions && data.permissions.add);
				newState.settingsIsLoaded = true;
			}

			if (params.append)
			{
				newState.items = this.state.items;
				for (const item of items.values())
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
				newState.blockPage = blockPage;
			}

			if (
				isEqual(this.state.items, newState.items)
				&& isEqual(this.state.permissions, newState.permissions)
				&& isEqual(this.state.isRefreshing, newState.isRefreshing)
				&& isEqual(this.state.settingsIsLoaded, newState.settingsIsLoaded)
			)
			{
				if (params.useLoader)
				{
					this.hideTitleLoader();
				}
				return;
			}

			this.setState(newState, () => {
				if (params.useLoader)
				{
					this.hideTitleLoader();
				}
			});
		}

		prepareItems(items)
		{
			items.map((item) => {
				item.type = this.generateType(item);
				item.key = String(item.id);
			});
		}

		showTitleLoader()
		{
			NavigationLoader.show();
		}

		hideTitleLoader()
		{
			NavigationLoader.hide();
		}

		reloadListHandler()
		{
			const initialStateParams = {
				skipItems: true,
			};

			const loadItemsParams = {
				useCache: false,
			};

			this.reload(initialStateParams, loadItemsParams, this.props.reloadListCallbackHandler);
		}

		/**
		 * @param {Object} initialStateParams
		 * @param {Object} loadItemsParams
		 * @param {Function|null} callback
		 */
		reload(initialStateParams = {}, loadItemsParams = {}, callback = () => {
		})
		{
			this.isRefreshing = false;

			if (typeof callback !== 'function')
			{
				callback = () => {
				};
			}

			this.setState(this.getInitialState(initialStateParams), () => {
				this.initSearchBar();
				this.loadItems(DEFAULT_BLOCK_PAGE, false, loadItemsParams);

				const simpleList = this.getSimpleList();
				if (simpleList)
				{
					simpleList.dropShowReloadListNotification();
				}

				if (initialStateParams.menuButtons)
				{
					this
						.setMenuButtons(initialStateParams.menuButtons)
						.initMenu()
					;
				}

				callback();
			});
		}

		getInitialState(params = {})
		{
			const initialState = {
				blockPage: DEFAULT_BLOCK_PAGE,
				isRefreshing: true,
				allItemsLoaded: false,
				permissions: {
					add: false,
					edit: false,
					view: false,
				},
				settingsIsLoaded: false,
				searchText: null,
				searchPresetId: null,
				renderType: null,
				forcedShowSkeleton: null,
			};

			if (!params.skipItems)
			{
				initialState.items = new Map();
			}

			if (params.itemParams)
			{
				initialState.itemParams = params.itemParams;
			}

			if (params.forcedShowSkeleton)
			{
				initialState.forcedShowSkeleton = params.forcedShowSkeleton;
			}

			return initialState;
		}

		updateItemHandler(itemId, params = {})
		{
			const showAnimate = BX.prop.getBoolean(params, 'showAnimate', true);
			return this.updateItems([itemId], false, showAnimate);

			/*
			Remove later. There is a field check for changes to show the animation for each field,
			but I have not yet found work with isShowAnimate in the code.
			So I'll leave it commented out for now, maybe I'll get back to this code soon.

			return new Promise((resolve, reject) => {
				const items = this.state.items;
				const item = items.get(itemId);

				if (!item)
				{
					resolve();
				}

				this.setLoadingOfItem(item.id);

				for (const name in params.data)
				{
					if (name !== 'fields')
					{
						item.data[name] = CommonUtils.objectClone(params.data[name]);
					}
				}

				if (params.data.fields)
				{
					for (const paramsFieldIndex in params.data.fields)
					{
						const newField = params.data.fields[paramsFieldIndex];
						const fieldName = newField.name;
						let field = this.getFieldByName(item, fieldName);
						if (field)
						{
							const isShowAnimate = (field.value !== newField.value);

							field = Object.assign(
								field,
								newField,
								{ isShowAnimate }
							)
						}
						else
						{
							newField.isShowAnimate = true;
							item.data.fields.push(newField);
						}
					}
				}

				item.type = this.generateType(item);
				items.set(itemId, item);
				this.modifyCache(ACTION_UPDATE, {
					items: [item],
				});

				this.getItemComponent(item.id).dropLoading(() => {
					this.setState({items}, () => {
						resolve();
					});
				});
			});*/
		}

		animateField()
		{

		}

		/**
		 * @param {Object} item
		 * @param {String} fieldName
		 */
		getFieldByName(item, fieldName)
		{
			return item.data.fields.find(field => field.name === fieldName);
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

				this.setState({ items }, () => {
					resolve();
				});
			});
		}

		deleteItemHandler(itemId)
		{
			return this.deleteItem(itemId);
		}

		/**
		 * @param {Number} itemId
		 * @param {String} animationType
		 * @returns {Promise}
		 */
		deleteItem(itemId, animationType = 'fade')
		{
			return new Promise((resolve, reject) => {
				const simpleList = this.getSimpleList();
				const item = simpleList.getItemComponent(itemId);

				if (!item)
				{
					reject();
					return;
				}

				this.deleteRowFromListView({ itemId, animationType, onDelete: resolve });
			});
		}

		deleteRowFromListView({ itemId, animationType = 'fade', onDelete })
		{
			const { items } = this.state;
			const { listView } = this.getSimpleList();
			const { index, section } = listView.getElementPosition(itemId);

			listView.deleteRow(section, index, animationType, () => {
					this.modifyCache(ACTION_DELETE, { itemId });
					this.setState((state) => {
						const items = clone(state.items);
						items.delete(itemId);

						return { items };
					}, () => {
						if (typeof onDelete === 'function')
						{
							onDelete({ items });
						}
					});
				},
			);
		}

		blinkItem(itemId, showUpdated = true)
		{
			return this.getSimpleList().blinkItem(itemId, showUpdated);
		}

		/**
		 * @param {Number} itemId
		 * @returns {LayoutComponent}
		 */
		getItemComponent(itemId)
		{
			return this.getSimpleList().getItemComponent(itemId);
		}

		/**
		 * @returns {SimpleList}
		 */
		getSimpleList()
		{
			return this.simpleList;
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

			if (action === ACTION_UPDATE && this.updateItemsInCache(cache, params.items))
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

		updateItemsInCache(cache, items = [])
		{
			return items.every(item => this.updateItemInCache(cache, item));
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

				for (const key in group)
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
				let h = 0;
				for (let i = 0; i < s.length; i++)
				{
					h = Math.imul(31, h) + s.charCodeAt(i) | 0;
				}

				return String(h);
			});

			return hashCode(str);
		}

		render()
		{
			const testId = this.getValue(this.props, 'testId');
			const title = this.getValue(this.props, 'title');
			const { permissions } = this.state;

			return View(
				{
					testId,
					onPan: this.props.onPanListHandler || null,
					style: {
						backgroundColor: '#f0f2f5',
						flex: 1,
					},
				},
				View(
					{
						style: {
							flex: 1,
							flexDirection: 'column',
							backgroundColor: title ? '#f4f7f8' : '#f0f2f5',
						},
					},
					title && Text({
						style: {
							fontSize: 13,
							color: '#525c69',
							marginVertical: 10,
							marginLeft: 20,
						},
						text: title,
					}),
					new SimpleList({
						...this.props,
						testId,
						permissions,
						settingsIsLoaded: this.state.settingsIsLoaded,
						renderType: this.state.renderType,
						itemType: this.state.itemType,
						items: this.state.items,
						itemParams: this.state.itemParams,
						getItemCustomStyles: BX.prop.getFunction(this.props, 'getItemCustomStyles', null),
						isSearchEnabled: this.stateBeforeSearch !== null,
						blockPage: this.state.blockPage,
						allItemsLoaded: this.state.allItemsLoaded,
						forcedShowSkeleton: this.state.forcedShowSkeleton,
						itemLayoutOptions: this.itemLayoutOptions,
						itemActions: this.state.itemActions,
						loadItemsHandler: this.loadItemsHandler,
						reloadListHandler: this.reloadListHandler,
						addItemHandler: this.addItemHandler,
						updateItemHandler: this.updateItemHandler,
						deleteItemHandler: this.deleteItemHandler,
						showFloatingButton: BX.prop.getBoolean(this.props, 'isShowFloatingButton', true),
						itemsLoadLimit: ITEMS_LOAD_LIMIT,
						isRefreshing: this.state.isRefreshing,
						ref: this.bindSimpleListRef,
						pull: this.pull,
						onDetailCardUpdateHandler: this.props.onDetailCardUpdateHandler || null,
						onDetailCardCreateHandler: this.props.onDetailCardCreateHandler || null,
						onNotViewableHandler: this.props.onNotViewableHandler || null,
					}),
				),
			);
		}

		bindSimpleListRef(ref)
		{
			this.simpleList = ref;
		}

		showError(errorText)
		{
			if (errorText.length)
			{
				navigator.notification.alert(errorText, null, '');
			}
		}

		/**
		 * @param {Number} id
		 * @returns {Boolean}
		 */
		hasItem(id)
		{
			return Boolean(this.getItem(id));
		}

		/**
		 * @param {Number} id
		 * @returns {Object}
		 */
		getItem(id)
		{
			id = Number(id);
			return this.state.items.get(id);
		}

		/**
		 * @returns Map
		 */
		getItems()
		{
			return this.state.items;
		}

		/**
		 * @param key: string
		 * @returns {null|{section: number, index: number}}
		 */
		getItemPosition(key)
		{
			return this.getSimpleList().listView.getElementPosition(String(key));
		}

		/**
		 * @returns {null|*}
		 */
		getSearchObject(initSearch = false)
		{
			const { layout } = this.props;

			if (!layout)
			{
				return null;
			}

			if (this.canUseSearchObject && layout.search)
			{
				if (initSearch)
				{
					layout.search.mode = this.searchConfig.mode;
				}
				return layout.search;
			}

			if (layout.searchBar)
			{
				return layout.searchBar;
			}

			return null;
		}

		/**
		 * @returns {boolean}
		 */
		canUseSearchObjectHandler()
		{
			return (Application.getApiVersion() >= 42);
		}

		setLoadingOfItem(itemId)
		{
			const item = this.getItemComponent(itemId);
			if (!item)
			{
				return Promise.resolve();
			}

			return new Promise(resolve => item.setLoading(resolve));
		}

		unsetLoadingOfItem(itemId, blink = true)
		{
			const item = this.getItemComponent(itemId);
			if (!item)
			{
				return Promise.resolve();
			}

			return new Promise(resolve => item.dropLoading(resolve, blink));
		}

		setShowReloadListNotification()
		{
			const simpleList = this.getSimpleList();
			if (simpleList)
			{
				simpleList.setShowReloadListNotification();
			}
		}
	}

	module.exports = { StatefulList };
});
