/**
 * @module layout/ui/stateful-list
 */
jn.define('layout/ui/stateful-list', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { NavigationLoader } = require('navigation-loader');
	const { debounce } = require('utils/function');
	const { merge, mergeImmutable, get, set, clone, isEqual } = require('utils/object');
	const { PureComponent } = require('layout/pure-component');
	const { SimpleList } = require('layout/ui/simple-list');
	const { Pull } = require('layout/ui/stateful-list/pull');
	const { StatefulListCache } = require('layout/ui/stateful-list/src/cache');
	const { TypeGenerator } = require('layout/ui/stateful-list/type-generator');
	const { ListItemType, ListItemsFactory } = require('layout/ui/simple-list/items');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const {
		FloatingActionButton,
		FloatingActionButtonSupportNative,
	} = require(
		'ui-system/form/buttons/floating-action-button',
	);
	const { Type } = require('type');

	const isGetConnectionStatusSupported = Type.isFunction(device?.getConnectionStatus);

	const DEFAULT_ITEMS_LOAD_LIMIT = 20;
	// backend limitations
	const MAX_ITEMS_LOAD_LIMIT = 50;

	const DEFAULT_BLOCK_PAGE = 1;
	const MINIMAL_SEARCH_LENGTH = 3;

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

			this.currentRequestUid = null;
			this.stateBeforeSearch = null;
			/** @type {SimpleList} */
			this.simpleList = null;
			this.searchBarIsInited = false;
			this.needAnimateIds = [];
			this.layoutRightButtons = null;
			this.menuProps = null;
			this.floatingButton = null;

			this.menuButtons = this.getValue(props, 'menuButtons', []);
			this.needInitMenu = this.getValue(props, 'needInitMenu', true);

			this.state = this.getInitialState();
			this.state.itemType = this.getValue(props, 'itemType', ListItemType.BASE);
			this.state.itemFactory = this.getValue(props, 'itemFactory', ListItemsFactory);
			this.state.actionParams = this.getValue(props, 'actionParams', {});
			this.state.itemParams = this.getValue(props, 'itemParams', {});
			this.state.itemActions = this.getValue(props, 'itemActions', []);
			this.state.forcedShowSkeleton = this.getValue(props, 'forcedShowSkeleton', true);

			this.loadItemsHandler = this.loadItems.bind(this);
			this.reloadList = this.reloadList.bind(this);
			this.updateItemHandler = this.updateItemHandler.bind(this);
			this.deleteItemHandler = this.deleteItemHandler.bind(this);
			this.showMenuHandler = this.showMenu.bind(this);
			this.bindRef = this.bindRef.bind(this);

			this.pull = new Pull({
				...props.pull,
				context: props.context,
				eventCallbacks: {
					[Pull.command.ADDED]: this.addItemsFromPull.bind(this),
					[Pull.command.UPDATED]: this.updateItemsFromPull.bind(this),
					[Pull.command.DELETED]: this.deleteItemsFromPull.bind(this),
					[Pull.command.RELOAD]: this.reloadList.bind(this),
				},
			});

			this.deviceConnectionStatus = 'online';

			if (isGetConnectionStatusSupported)
			{
				this.deviceConnectionStatus = device.getConnectionStatus();
				device.on('connectionStatusChanged', this.onConnectionStatusChanged.bind(this));
			}

			/** @type {StatefulListCache} */
			this.cache = new StatefulListCache();

			this.getNotificationText = this.pull.getNotificationText.bind(this.pull);

			this.searchConfig = this.getPreparedSearchConfig();

			this.onSearchTextChanged = this.onSearchTextChangedHandler.bind(this);
			this.onSearchClick = this.onSearchClickHandler.bind(this);
			this.onSearchCancel = this.onSearchCancelHandler.bind(this);
			this.onViewShow = this.onViewShowHandler.bind(this);

			this.debounceSearch = debounce((params, callback) => this.search(params, callback), 500, this);
		}

		get layout()
		{
			return this.props.layout;
		}

		get layoutMenuActions()
		{
			return this.getValue(this.props, 'layoutMenuActions', []);
		}

		get layoutOptions()
		{
			return this.getValue(this.props, 'layoutOptions', {});
		}

		get itemsLoadLimit()
		{
			const loadLimit = this.props.itemsLoadLimit ?? DEFAULT_ITEMS_LOAD_LIMIT;

			return Math.min(loadLimit, MAX_ITEMS_LOAD_LIMIT);
		}

		get ajaxCacheTtl()
		{
			// default 3 days
			return this.getValue(this.props, 'ajaxCacheTtl', 3 * 86400);
		}

		get shouldReloadDynamically()
		{
			return this.getValue(this.props.pull, 'shouldReloadDynamically', false);
		}

		get sorting()
		{
			return this.getValue(this.props, 'sortingConfig', {}) ?? {};
		}

		get isCacheEnabled()
		{
			return Boolean(this.props.useCache ?? true);
		}

		componentDidMount()
		{
			this.loadFirstItems();

			if (this.needInitMenu)
			{
				this.initMenu();
				this.initSearchBar();
				this.initFloatingButton();
			}

			this.layout.on('onViewShown', () => this.onViewShow());

			this.pull.subscribe();
		}

		componentWillUnmount()
		{
			this.removeSearchBarEventListeners();
			this.layout.removeEventListener('onViewShown', this.onViewShow);

			this.pull.unsubscribe();
		}

		onViewShowHandler()
		{
			if (this.searchBarIsInited && this.state.searchText)
			{
				this.showSearchBar();
			}

			if (this.needAnimateIds.length > 0 && this.simpleList)
			{
				this.simpleList.lastElementIdAddedWithAnimation = this.needAnimateIds[this.needAnimateIds.length - 1];
				this.needAnimateIds.map((id) => this.blinkItem(id));
			}

			this.needAnimateIds = [];
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

			this.state.itemParams = this.getValue(newProps, 'itemParams', {});
			this.state.itemActions = this.getValue(newProps, 'itemActions', []);
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

		isLoading()
		{
			return this.currentRequestUid !== null;
		}

		loadItems(blockPage = DEFAULT_BLOCK_PAGE, append = true, params = {})
		{
			if (!this.props.actions?.loadItems)
			{
				throw new Error('StatefulList: loadItems action is not defined');
			}

			if (this.state.allItemsLoaded)
			{
				this.setState({
					isRefreshing: false,
				});

				return;
			}

			const config = {
				data: (this.state.actionParams.loadItems || {}),
				navigation: {
					page: blockPage,
					size: this.itemsLoadLimit,
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

			const requestStartTime = Date.now();
			const isDefaultBlockPage = (blockPage === DEFAULT_BLOCK_PAGE);
			const cacheId = this.props.cacheName ?? null;
			const appendItems = (isDefaultBlockPage ? false : append);
			const useCache = (
				isDefaultBlockPage
				&& (Boolean(cacheId) || this.isCacheEnabled)
				&& (params.useCache === undefined || params.useCache)
			);

			const runActionExecutor = (
				new RunActionExecutor(
					this.props.actions.loadItems,
					config.data,
					config.navigation,
				)
					.setCacheId(cacheId)
					.setCacheTtl(this.ajaxCacheTtl)
					.setCacheHandler((response, uid) => {
						if (this.currentRequestUid !== uid)
						{
							return;
						}

						if (Type.isFunction(this.props.actionCallbacks?.loadItems))
						{
							this.props.actionCallbacks.loadItems(response?.data, renderType.cache);
						}

						this.drawListFromCache(response, blockPage, appendItems);
						this.hideTitleLoader(true);
					})
					.setHandler((response, uid) => {
						if (this.currentRequestUid !== uid)
						{
							return;
						}

						const serverResponseTime = Date.now() - requestStartTime;
						const remainingTime = useCache ? 0 : Math.max(300 - serverResponseTime, 0);

						this.requestRenderTimeout = setTimeout(() => {
							if (Type.isFunction(this.props.actionCallbacks?.loadItems))
							{
								this.props.actionCallbacks.loadItems(response?.data, renderType.ajax);
							}

							this.drawListFromAjax(response, blockPage, appendItems);
							this.hideTitleLoader(false);
							this.currentRequestUid = null;
						}, remainingTime);
					})
			);

			const newRequestUid = runActionExecutor.getUid();

			if (this.currentRequestUid === newRequestUid)
			{
				return;
			}

			this.currentRequestUid = newRequestUid;
			clearTimeout(this.requestRenderTimeout);

			this.showTitleLoader({ useCache, isDefaultBlockPage });

			if (useCache)
			{
				this.cache.setRunActionExecutor(runActionExecutor);
			}

			runActionExecutor.call(useCache).catch(console.error);
		}

		/**
		 * @param {String[]|Number[]} ids
		 * @param {boolean} useFilter
		 * @returns {Promise}
		 */
		loadItemsByIds(ids = [], useFilter = true)
		{
			if (!this.props.actions?.loadItems)
			{
				throw new Error('StatefulList: loadItems action is not defined');
			}

			const idsToLoad = ids.filter(Boolean);

			return new Promise((resolve, reject) => {
				if (idsToLoad.length === 0)
				{
					resolve([]);

					return;
				}

				const data = clone(this.state.actionParams.loadItems || {});
				data.extra = data.extra || {};
				const filterParams = data.extra.filterParams || {};
				filterParams.ID = idsToLoad;

				if (!useFilter)
				{
					filterParams.FILTER_PRESET_ID = '';
					set(data.extra, 'filter.presetId', '');
				}

				merge(data.extra, { filterParams }, { subscribeUser: true, filter: {} });

				BX.ajax.runAction(this.props.actions.loadItems, { data }).then(
					(response) => {
						if (response.errors.length > 0)
						{
							reject(response.errors);

							return;
						}

						if (Type.isFunction(this.props.actionCallbacks?.loadItems))
						{
							this.props.actionCallbacks.loadItems(response?.data, renderType.ajax);
						}

						resolve(response.data.items);
					},
					(response) => {
						console.error(response.errors);
						reject(response.errors);
					},
				).catch(console.error);
			});
		}

		getViewMode()
		{
			return this.simpleList?.getViewMode();
		}

		shouldShowReloadListNotification()
		{
			return this.simpleList?.shouldShowReloadListNotification();
		}

		scrollToTop(animated = true)
		{
			this.simpleList?.scrollToBegin(animated);
		}

		async scrollToTopItem(itemIds, animated = true, blink = false)
		{
			const existingItems = itemIds.filter((id) => this.hasItem(id));
			if (existingItems.length === 0)
			{
				return;
			}

			await this.simpleList?.scrollToTopItem(existingItems, animated);

			if (blink)
			{
				existingItems.forEach((itemId) => {
					this.simpleList?.blinkItem(itemId);
				});
			}
		}

		addItemsFromPull(items)
		{
			if (this.state.items.length === 0 || this.shouldReloadDynamically)
			{
				const ids = items.map(({ id }) => id);

				return this.updateItems(ids, false, true);
			}
			this.simpleList.showUpdateButton(items.length);

			return Promise.resolve();
		}

		/**
		 * @param {string[]} itemsToProcess
		 * @param {Object[]} itemsFromServer
		 * @return {{add: Object[], update: Object[], delete: string[]}}
		 */
		groupItemsByOperations(itemsToProcess, itemsFromServer)
		{
			const { items } = this.state;
			const currentItemsMap = new Map();

			items.forEach((item) => {
				currentItemsMap.set(String(item.id), item);
			});

			const itemsFromServerState = this.prepareItemsState(itemsFromServer);
			const itemsFromServerMap = new Map();

			itemsFromServerState.forEach((item) => {
				itemsFromServerMap.set(String(item.id), item);
			});

			const toAddItems = [];
			const toUpdateItems = [];
			const toDeleteIds = [];

			itemsToProcess.forEach((id) => {
				const preparedId = String(id);

				if (itemsFromServerMap.has(preparedId))
				{
					if (currentItemsMap.has(preparedId))
					{
						toUpdateItems.push(itemsFromServerMap.get(preparedId));
					}
					else
					{
						toAddItems.push(itemsFromServerMap.get(preparedId));
					}
				}
				else if (currentItemsMap.has(preparedId))
				{
					toDeleteIds.push(id);
				}
			});

			return {
				add: this.prepareItems(toAddItems),
				update: this.prepareItems(toUpdateItems),
				delete: toDeleteIds,
			};
		}

		/**
		 * @private
		 * @param {array} addItems
		 * @param {boolean} showAnimateOnView
		 * @returns {boolean} wasChanged
		 */
		processItemsAddOperation(addItems, showAnimateOnView)
		{
			const preparedAddItems = addItems.filter(({ id }) => !this.hasItem(id));

			if (!Type.isArrayFilled(preparedAddItems))
			{
				return false;
			}

			if (showAnimateOnView)
			{
				this.addToAnimateItems(preparedAddItems);
			}

			this.state.items.unshift(...preparedAddItems);

			this.sortItemsIfCallbackExists();

			const { onItemAdded } = this.props;

			if (Type.isFunction(onItemAdded))
			{
				preparedAddItems.forEach((item) => onItemAdded(item));
			}

			return true;
		}

		/**
		 * @private
		 * @param {array} deleteItems
		 * @returns {boolean} wasChanged
		 */
		processItemsDeleteOperations(deleteItems)
		{
			const preparedDeleteItems = deleteItems.filter((id) => this.hasItem(id));

			if (!Type.isArrayFilled(preparedDeleteItems))
			{
				return false;
			}

			preparedDeleteItems.forEach((itemId) => {
				const index = this.getItemIndex(itemId);
				const itemToDelete = this.state.items.splice(index, 1)[0];
				const { onItemDeleted } = this.props;

				if (Type.isFunction(onItemDeleted))
				{
					onItemDeleted(itemToDelete);
				}
			});

			return true;
		}

		/**
		 * @private
		 * @param {array} updateItems
		 * @param {boolean} showAnimateOnView
		 * @returns {boolean} wasChanged
		 */
		processItemsUpdateOperations(updateItems, showAnimateOnView)
		{
			const preparedUpdateItems = updateItems.filter(({ id }) => this.hasItem(id));

			if (!Type.isArrayFilled(preparedUpdateItems))
			{
				return false;
			}

			if (showAnimateOnView)
			{
				this.addToAnimateItems(preparedUpdateItems);
			}

			updateItems.forEach((item) => {
				const index = this.getItemIndex(item.id);
				this.state.items[index] = item;
			});

			this.sortItemsIfCallbackExists();

			const { onItemUpdated } = this.props;

			if (Type.isFunction(onItemUpdated))
			{
				preparedUpdateItems.forEach((item) => onItemUpdated(item));
			}

			return true;
		}

		/**
		 * @private
		 * @param {array} replaceItems
		 * @returns {boolean} wasChanged
		 */
		processItemsReplaceOperations(replaceItems)
		{
			const preparedReplaceItems = replaceItems.filter(({ idToReplace }) => this.hasItem(idToReplace));

			if (!Type.isArrayFilled(preparedReplaceItems))
			{
				return false;
			}

			preparedReplaceItems.forEach((item) => {
				const position = this.getItemIndex(item.idToReplace);
				this.state.items.splice(position, 1, item);
			});

			return true;
		}

		/**
		 * @param {Object} operations
		 * @param {Object[]} [operations.add]
		 * @param {Object[]} [operations.update]
		 * @param {string[]} [operations.delete]
		 * @param {Object[]} [operations.replace]
		 * @param {boolean} [showAnimateOnView=false]
		 * @param {boolean} [showAnimateImmediately=true]
		 * @param {Object} [animationTypes]
		 * @return {Promise}
		 */
		processItemsGroupsByData(
			operations,
			showAnimateOnView = false,
			showAnimateImmediately = true,
			animationTypes = null,
		)
		{
			const {
				add: addItems = [],
				update: updateItems = [],
				delete: deleteItems = [],
				replace: replaceItems = [],
			} = operations;

			return new Promise((resolve, reject) => {
				let shouldUpdateSimpleList = false;
				shouldUpdateSimpleList = (
					this.processItemsAddOperation(addItems, showAnimateOnView) || shouldUpdateSimpleList
				);
				shouldUpdateSimpleList = (
					this.processItemsDeleteOperations(deleteItems) || shouldUpdateSimpleList
				);
				shouldUpdateSimpleList = (
					this.processItemsUpdateOperations(updateItems, showAnimateOnView) || shouldUpdateSimpleList
				);
				shouldUpdateSimpleList = (
					this.processItemsReplaceOperations(replaceItems) || shouldUpdateSimpleList
				);

				if (!shouldUpdateSimpleList)
				{
					resolve();

					return;
				}

				this.updateSimpleList(resolve, reject, { shouldUpdateSimpleList, animationTypes });
			});
		}

		updateSimpleList = (resolve = () => {}, reject = () => {}, animation = {}) => {
			const defaultAnimationTypes = {
				insert: 'none',
				delete: 'none',
				update: 'none',
				move: false,
			};

			const preparedItems = this.prepareItemsForRender(this.state.items);
			const {
				showAnimateImmediately = true,
				animationTypes = null,
			} = animation;

			this.simpleList.changeItemsState(
				preparedItems,
				(showAnimateImmediately ? this.prepareAnimationTypes(animationTypes) : defaultAnimationTypes),
			)
				.then(resolve)
				.catch((error) => {
					console.error(error);
					reject();
				})
			;
			this.modifyCache();
		};

		updateItemsFromPull(items)
		{
			if (this.shouldReloadDynamically)
			{
				const ids = items.map(({ id }) => id);

				return this.updateItems(ids, false, true);
			}

			this.simpleList.showUpdateButton();

			return Promise.resolve();
		}

		deleteItemsFromPull(items)
		{
			const ids = items.map(({ id }) => id);

			return this.processItemsGroupsByData({ delete: ids }, false, true);
		}

		drawListFromCache(response, blockPage, append)
		{
			const params = {
				append,
				renderType: renderType.cache,
			};

			BX.postComponentEvent('UI.StatefulList::onDrawList', [
				{
					renderType: renderType.cache,
					items: response.data.items,
					blockPage: this.state.blockPage,
					params,
				},
			]);

			this.drawList(response, blockPage, params);
		}

		drawListFromAjax(response, blockPage, append)
		{
			const { loadItems } = this.state.actionParams;

			BX.postComponentEvent('UI.StatefulList::onDrawListFromAjax', [
				{
					renderType: renderType.ajax,
					items: response.data?.items,
					blockPage: this.state.blockPage,
					params: loadItems,
				},
			]);

			const params = {
				append,
				loadItemsParams: loadItems,
				renderType: renderType.ajax,
			};

			this.drawList(response, blockPage, params);
		}

		drawList(response, blockPage, params = {})
		{
			if (!response)
			{
				return;
			}

			if (response?.errors?.length > 0)
			{
				console.error('StatefulList: drawList error', response.errors);

				if (response.data)
				{
					response.errors
						.filter(({ code }) => code !== 'NETWORK_ERROR')
						.forEach(({ message }) => this.showError(message));

					return;
				}
			}

			let { data } = response;

			let items = [];

			if (data?.items)
			{
				items = data.items;
			}

			const newState = {
				renderType: params.renderType,
				isRefreshing: false,
				allItemsLoaded: false,
			};

			if (items.length < this.itemsLoadLimit)
			{
				newState.allItemsLoaded = true;
			}

			if (items.length === 0)
			{
				if (params.append)
				{
					newState.items = [...this.state.items];
				}
				else
				{
					newState.items = [];
				}
				newState.items = this.prepareItemsState(newState.items, params);
				newState.items = this.prepareItems(newState.items);

				this.setState(newState);

				return;
			}

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
				newState.items = [...this.state.items];
				const notAdded = items.filter((item) => !newState.items.some(({ id }) => id === item.id));
				newState.items.push(...notAdded);
			}
			else
			{
				newState.items = items;
			}

			newState.items = this.prepareItemsState(newState.items, params);
			newState.items = this.prepareItems(newState.items);

			const newStateKeys = Object.keys(newState);
			const isEqualStateWithoutItems = (
				(
					!newStateKeys.includes('permissions')
					|| isEqual(this.state.permissions, newState.permissions)
				)
				&& (
					!newStateKeys.includes('isRefreshing')
					|| isEqual(this.state.isRefreshing, newState.isRefreshing)
				)
				&& (
					!newStateKeys.includes('settingsIsLoaded')
					|| isEqual(this.state.settingsIsLoaded, newState.settingsIsLoaded))
			);
			if (!isEqualStateWithoutItems)
			{
				this.setState(newState);

				return;
			}

			const isEqualItems = isEqual(this.state.items, newState.items);
			if (isEqualItems)
			{
				return;
			}

			if (params.append)
			{
				this.state.items = newState.items;
				this.state.allItemsLoaded = newState.allItemsLoaded;
				this.state.isRefreshing = newState.isRefreshing;

				const preparedItems = this.prepareItemsForRender(this.state.items);

				this.simpleList.changeItemsState(preparedItems, {
					insert: 'none',
				})
					.then(() => {
						if (this.state.allItemsLoaded)
						{
							this.simpleList.setState({ allItemsLoaded: this.state.allItemsLoaded });
						}
					})
					.catch((error) => {
						console.error(error);
					});
			}
			else
			{
				this.setState(newState);
			}
		}

		prepareItems(items)
		{
			const generator = new TypeGenerator({
				// groups can be set as, for example, ['data', 'data.action']
				groupsList: this.props.typeGenerator?.groups,
				properties: this.props.typeGenerator?.properties,
				propertiesCallbacks: this.props.typeGenerator?.callbacks,
			});

			if (this.props.typeGenerator?.generator)
			{
				generator.setGenerator(this.props.typeGenerator?.generator);
			}

			return items.map((item) => ({
				...item,
				type: generator.generate(item),
				key: String(item.id),
			}));
		}

		reloadList()
		{
			const initialStateParams = {
				skipItems: true,
				force: true,
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
		reload(initialStateParams = {}, loadItemsParams = {}, callback = () => {})
		{
			this.isRefreshing = false;

			if (typeof callback !== 'function')
			{
				callback = () => {};
			}

			this.setState(this.getInitialState(initialStateParams), () => {
				this.initSearchBar();
				this.loadItems(DEFAULT_BLOCK_PAGE, false, loadItemsParams);

				if (this.simpleList)
				{
					this.simpleList.dropShowReloadListNotification();
				}

				if (initialStateParams.menuButtons)
				{
					this.setMenuButtons(initialStateParams.menuButtons);
					this.initMenu();
				}

				if (this.props.onListReloaded)
				{
					const isPullToReload = initialStateParams.skipItems ?? false;

					this.props.onListReloaded(isPullToReload);
				}

				callback(initialStateParams);
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
				forcedShowSkeleton: true,
			};

			if (!params.skipItems)
			{
				initialState.items = [];
			}

			if (params.itemParams)
			{
				initialState.itemParams = params.itemParams;
			}

			if (params.actionParams)
			{
				initialState.actionParams = params.actionParams;
			}

			if (Type.isBoolean(params.forcedShowSkeleton))
			{
				initialState.forcedShowSkeleton = params.forcedShowSkeleton;
			}

			return initialState;
		}

		prepareItemsState(items, params = {})
		{
			let preparedItems = clone(items);

			if (this.props.onBeforeItemsSetState)
			{
				preparedItems = this.props.onBeforeItemsSetState(preparedItems, params);
			}

			return preparedItems;
		}

		prepareItemsForRender(items)
		{
			this.updateFloatingButton();

			let preparedItems = clone(items);

			if (this.props.onBeforeItemsRender)
			{
				const { allItemsLoaded } = this.state;

				preparedItems = this.props.onBeforeItemsRender(preparedItems, {
					allItemsLoaded,
				});
			}

			return preparedItems;
		}

		get colors()
		{
			return this.props.showAirStyle ? AppTheme.realColors : AppTheme.colors;
		}

		render()
		{
			const testId = this.getTestId();
			const title = this.getValue(this.props, 'title');

			return View(
				{
					testId,
					onPan: this.props.onPanListHandler || null,
					style: {
						backgroundColor: this.colors.bgPrimary,
						flex: 1,
					},
				},
				View(
					{
						style: {
							flex: 1,
							flexDirection: 'column',
							backgroundColor: title
								? this.colors.bgContentPrimary
								: this.colors.bgPrimary,
						},
					},
					title && Text({
						style: {
							fontSize: 13,
							color: this.colors.base1,
							marginVertical: 10,
							marginLeft: 20,
						},
						text: title,
					}),
					new SimpleList({
						...this.props,
						testId,
						permissions: this.state.permissions,
						settingsIsLoaded: this.state.settingsIsLoaded,
						renderType: this.state.renderType,
						itemType: this.state.itemType,
						itemFactory: this.state.itemFactory,
						items: this.prepareItemsForRender(this.state.items),
						itemParams: this.state.itemParams,
						getItemCustomStyles: BX.prop.getFunction(this.props, 'getItemCustomStyles', null),
						isSearchEnabled: this.stateBeforeSearch !== null,
						blockPage: this.state.blockPage,
						allItemsLoaded: this.state.allItemsLoaded,
						forcedShowSkeleton: this.state.forcedShowSkeleton,
						itemLayoutOptions: this.props.itemLayoutOptions,
						itemActions: this.state.itemActions,
						loadItemsHandler: this.loadItemsHandler,
						reloadListHandler: this.reloadList,
						updateItemHandler: this.updateItemHandler,
						deleteItemHandler: this.deleteItemHandler,
						showFloatingButton: this.isShowFloatingButton(),
						getNotificationText: this.getNotificationText,
						itemsLoadLimit: this.itemsLoadLimit,
						isRefreshing: this.state.isRefreshing,
						ref: this.bindRef,
						onDetailCardUpdateHandler: this.props.onDetailCardUpdateHandler,
						onDetailCardCreateHandler: this.props.onDetailCardCreateHandler,
						onNotViewableHandler: this.props.onNotViewableHandler,
						changeItemsOperations: this.props.changeItemsOperations,
					}),
				),
			);
		}

		getTestId()
		{
			return this.getValue(this.props, 'testId') || 'STATEFUL-LIST';
		}

		bindRef(ref)
		{
			if (ref)
			{
				this.simpleList = ref;
			}
		}

		/**
		 * @public
		 * @param {String|Number} id
		 * @returns {Boolean}
		 */
		hasItem(id)
		{
			return this.state.items.some((item) => String(item.id) === String(id));
		}

		/**
		 * @param {String|Number} id
		 * @returns {Object}
		 */
		getItem(id)
		{
			return this.state.items.find((item) => String(item.id) === String(id));
		}

		/**
		 * @param {String|Number} id
		 * @returns {number}
		 */
		getItemIndex(id)
		{
			return this.state.items.findIndex((item) => String(item.id) === String(id));
		}

		/**
		 * @public
		 * @returns {Object[]}
		 */
		getItems()
		{
			return this.state.items;
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isEmptyList()
		{
			return this.getItems().length === 0;
		}

		isFloatingButtonAccent()
		{
			if (typeof this.props.isFloatingButtonAccent === 'undefined')
			{
				return this.state.isRefreshing ? false : this.isEmptyList();
			}

			if (typeof this.props.isFloatingButtonAccent === 'function')
			{
				return Boolean(this.props.isFloatingButtonAccent());
			}

			return Boolean(this.props.isFloatingButtonAccent);
		}

		initFloatingButton()
		{
			if (!FloatingActionButtonSupportNative(this.layout) || !this.isShowFloatingButton())
			{
				return;
			}

			const { onFloatingButtonClick, onFloatingButtonLongClick } = this.props;

			this.floatingButton = FloatingActionButton({
				testId: `${this.getTestId()}_ADD_BTN`,
				accentByDefault: this.isFloatingButtonAccent(),
				parentLayout: this.layout,
				onClick: onFloatingButtonClick,
				onLongClick: onFloatingButtonLongClick,
			});
		}

		/**
		 * @public
		 * @param {boolean} [params.accentByDefault]
		 * @param {boolean} [params.hide]
		 */
		updateFloatingButton(params)
		{
			const floatingButtonParams = params || {
				accentByDefault: this.isFloatingButtonAccent(),
				hide: !this.isShowFloatingButton(),
			};

			this.floatingButton?.setFloatingButton(floatingButtonParams);
		}

		// search

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

			this.addSearchBarEventListeners(searchBar);
			searchBar.setReturnKey('done');

			this.searchBarIsInited = true;
		}

		addSearchBarEventListeners(searchBar)
		{
			searchBar.on('cancel', () => this.onSearchCancel());
			searchBar.on('clickEnter', (params) => this.onSearchClick(params));
			searchBar.on('textChanged', (params) => this.onSearchTextChanged(params));
		}

		removeSearchBarEventListeners()
		{
			if (!this.layoutOptions.useSearch)
			{
				return;
			}

			const searchBar = this.getSearchObject();
			if (!searchBar)
			{
				return;
			}

			searchBar.removeEventListener('cancel', this.onSearchCancel);
			searchBar.removeEventListener('clickEnter', this.onSearchClick);
			searchBar.removeEventListener('textChanged', this.onSearchTextChanged);
		}

		onSearchClickHandler(params)
		{
			this.debounceSearch({
				...params,
				eventName: 'clickEnter',
			});
		}

		onSearchTextChangedHandler(params)
		{
			this.debounceSearch({
				...params,
				eventName: 'textChanged',
			});
		}

		onSearchCancelHandler()
		{
			this.cancelSearch();
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
			if (this.props.getRuntimeParams)
			{
				const runtimeParams = this.props.getRuntimeParams({
					state: this.state,
					params: params || {},
				});

				if (runtimeParams.cancelSearch)
				{
					return true;
				}
			}

			return false;
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

			if (layout.search)
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
			if (search)
			{
				if (this.state.searchText)
				{
					search.text = this.state.searchText;
				}
				search.show();
			}
		}

		getPreparedSearchConfig()
		{
			const defaultConfig = {
				mode: 'bar',
			};
			const config = this.getValue(this.props, 'search', {});

			return mergeImmutable(defaultConfig, config);
		}

		// item

		/**
		 * @param {String|Number} itemId
		 * @returns {LayoutComponent}
		 */
		getItemComponent(itemId)
		{
			return this.simpleList.getItemComponent(itemId);
		}

		/**
		 * @param {any} key
		 * @returns {null|{section: number, index: number}}
		 */
		getItemPosition(key)
		{
			return this.simpleList.getItemPosition(key);
		}

		updateItemHandler(itemId, params = {})
		{
			const showAnimate = BX.prop.getBoolean(params, 'showAnimate', true);

			return this.updateItems([itemId], false, showAnimate);
		}

		deleteItemHandler(itemId)
		{
			return this.deleteItem(itemId);
		}

		/**
		 * @param {String|Number} itemId
		 * @param {String} [animationType]
		 * @returns {Promise}
		 */
		addItem(itemId, animationType)
		{
			const animation = animationType || get(this.props, 'animationTypes.insertRows', 'fade');

			return this.updateItems(
				[itemId],
				false,
				true,
				true,
				{
					insert: animation,
				},
			);
		}

		prepareAnimationTypes(animationTypes)
		{
			let types = {
				insert: get(this.props, 'animationTypes.insertRows', 'fade'),
				delete: get(this.props, 'animationTypes.deleteRow', 'fade'),
				update: get(this.props, 'animationTypes.updateRows', 'fade'),
				move: get(this.props, 'animationTypes.moveRow', true),
			};
			if (animationTypes)
			{
				types = {
					...types,
					...animationTypes,
				};
			}

			return types;
		}

		/**
		 * @public
		 * @param {string[]|number[]} ids
		 * @param {boolean} showAnimateOnView
		 * @param {boolean} showAnimateImmediately
		 * @param {boolean} useFilter
		 * @param {object} [animationTypes]
		 * @return {Promise}
		 */
		updateItems(
			ids,
			showAnimateOnView = true,
			showAnimateImmediately = false,
			useFilter = true,
			animationTypes = null,
		)
		{
			const preparedAnimationTypes = this.prepareAnimationTypes(animationTypes);

			return new Promise((resolve, reject) => {
				if (ids && ids.length === 0)
				{
					resolve();

					return;
				}
				this.loadItemsByIds(ids, useFilter)
					.then((loadedItems) => {
						const operations = this.groupItemsByOperations(ids, loadedItems);

						return this.processItemsGroupsByData(
							operations,
							showAnimateOnView,
							showAnimateImmediately,
							preparedAnimationTypes,
						);
					})
					.then(() => {
						resolve();
					})
					.catch((error) => {
						console.error(error);
						reject();
					});
			});
		}

		/**
		 * @public
		 * @param {string[]|number[]} entities
		 * @param {boolean} [showAnimateOnView=false]
		 * @param {boolean} [showAnimateImmediately=true]
		 * @return {Promise}
		 */
		updateItemsData(
			entities,
			showAnimateOnView = false,
			showAnimateImmediately = true,
		)
		{
			const itemIds = entities.map(({ id }) => id);
			const operations = this.groupItemsByOperations(itemIds, entities);

			return this.processItemsGroupsByData(
				operations,
				showAnimateOnView,
				showAnimateImmediately,
			);
		}

		replaceItems(items)
		{
			return this.processItemsGroupsByData({ replace: this.prepareItems(items) });
		}

		/**
		 * @param {string|Array<string>} itemIds
		 * @param {string} [animationType]
		 * @returns {Promise}
		 */
		deleteItem(itemIds, animationType)
		{
			const animation = animationType || get(this.props, 'animationTypes.deleteRow', 'fade');

			return this.processItemsGroupsByData(
				{ delete: Array.isArray(itemIds) ? itemIds : [itemIds] },
				false,
				true,
				{
					delete: animation,
				},
			);
		}

		removeItem(itemId, animationType)
		{
			return this.deleteItem(itemId, animationType);
		}

		blinkItem(itemId, showUpdated = true)
		{
			return this.simpleList.blinkItem(itemId, showUpdated);
		}

		setLoadingOfItem(itemId)
		{
			return this.simpleList.setLoading(itemId);
		}

		unsetLoadingOfItem(itemId, blink = true)
		{
			return this.simpleList.dropLoading(itemId, blink);
		}

		// menu

		initMenu(props = null, buttons = null)
		{
			if (!buttons)
			{
				buttons = this.getMenuButtons(props);
			}

			if (!isEqual(buttons, this.layoutRightButtons))
			{
				this.layoutRightButtons = buttons;
				this.layout.setRightButtons(buttons);
			}
		}

		getMenuButtons(props = null)
		{
			if (!props)
			{
				props = this.props;
			}

			const buttons = [];

			if (this.layout)
			{
				if (this.layoutOptions.useSearch)
				{
					buttons.push(this.getSearchBarButtonConfig());
				}

				if (Array.isArray(this.menuButtons))
				{
					this.menuButtons.forEach((button) => buttons.push(button));
				}
			}

			const layoutMenuActions = this.getValue(props, 'layoutMenuActions', []);

			if (layoutMenuActions.length > 0)
			{
				this.menuProps = props;
				buttons.push({
					type: 'more',
					badgeCode: 'access_more',
					callback: this.showMenuHandler,
				});
			}

			return buttons;
		}

		setMenuButtons(buttons)
		{
			this.menuButtons = buttons;
		}

		showMenu()
		{
			if (this.menuProps.layoutMenuActions)
			{
				const menu = new UI.Menu(this.menuProps.layoutMenuActions);
				menu.show();
			}
		}

		// title

		showTitleLoader(params)
		{
			if (this.props.showTitleLoader)
			{
				this.props.showTitleLoader(params);
			}
			else
			{
				NavigationLoader.show(this.layout);
			}
		}

		hideTitleLoader(isCache)
		{
			if (this.props.hideTitleLoader)
			{
				this.props.hideTitleLoader(isCache);
			}
			else if (!isCache)
			{
				NavigationLoader.hide(this.layout);
			}
		}

		// other

		modifyCache()
		{
			this.cache.modifyCache(this.state.items.slice(0, this.itemsLoadLimit));
		}

		showError(message)
		{
			if (message.length > 0)
			{
				navigator.notification.alert(message, null, '');
			}
		}

		addToAnimateItems(items)
		{
			items.forEach(({ id }) => this.addToAnimateIds(id));
		}

		addToAnimateIds(id)
		{
			if (!this.needAnimateIds.includes(id))
			{
				this.needAnimateIds.push(id);
			}
		}

		getValue(object, property, defaultValue = null, isObjectClone = true)
		{
			if (object && isObjectClone && Object.prototype.hasOwnProperty.call(object, property))
			{
				return clone(object[property]);
			}

			return get(object, property, defaultValue);
		}

		onConnectionStatusChanged(status)
		{
			if (status === 'online' && this.deviceConnectionStatus !== status)
			{
				this.reloadList();
			}

			this.deviceConnectionStatus = status;
		}

		getItemRef(itemId)
		{
			return this.simpleList.getItemComponent(itemId);
		}

		getItemRootViewRef(itemId)
		{
			return this.simpleList.getItemRootViewRef(itemId);
		}

		getItemMenuViewRef(itemId)
		{
			return this.simpleList.getItemMenuViewRef(itemId) ?? this.simpleList.getItemRootViewRef(itemId);
		}

		isShowFloatingButton()
		{
			return BX.prop.getBoolean(this.props, 'isShowFloatingButton', true);
		}

		sortItemsIfCallbackExists()
		{
			const { sortItemsCallback } = this.sorting;

			if (Type.isFunction(sortItemsCallback))
			{
				this.state.items.sort(sortItemsCallback);
			}
		}

		scrollBy = (props) => {
			this.simpleList.scrollBy(props);
		};
	}

	module.exports = { StatefulList };
});
