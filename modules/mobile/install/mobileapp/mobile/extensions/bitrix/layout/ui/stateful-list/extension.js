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
			return this.getValue(this.props, 'sortingConfig', null);
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

						if (this.props.actionCallbacks && this.props.actionCallbacks.loadItems)
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
							if (this.props.actionCallbacks && this.props.actionCallbacks.loadItems)
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

			runActionExecutor.call(useCache);
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

			return new Promise((resolve, reject) => {
				if (ids.length === 0)
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

						if (this.props.actionCallbacks && this.props.actionCallbacks.loadItems)
						{
							this.props.actionCallbacks.loadItems(response?.data, renderType.ajax);
						}

						resolve(response.data.items);
					},
					(response) => {
						console.error(response.errors);
						reject(response.errors);
					},
				);
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

		addItemsFromPull(items)
		{
			if (this.state.items.length === 0 || this.shouldReloadDynamically)
			{
				const ids = items.map((item) => item.id);

				return this.updateItems(ids, false, true);
			}
			this.simpleList.showUpdateButton(items.length);

			return Promise.resolve();
		}

		/**
		 * @param {string[]} desiredItemIds
		 * @param {Object[]} itemsFromServer
		 * @return {{add: Object[], update: Object[], delete: string[]}}
		 */
		groupItemsByOperations(desiredItemIds, itemsFromServer)
		{
			const preparedDesiredItemsIds = desiredItemIds.map((id) => String(id));
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

			preparedDesiredItemsIds.forEach((id) => {
				if (itemsFromServerMap.has(id))
				{
					if (currentItemsMap.has(id))
					{
						toUpdateItems.push(itemsFromServerMap.get(id));
					}
					else
					{
						toAddItems.push(itemsFromServerMap.get(id));
					}
				}
				else if (currentItemsMap.has(id))
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
		 * @param {Object} operations
		 * @param {Object[]} [operations.add]
		 * @param {Object[]} [operations.update]
		 * @param {string[]} [operations.delete]
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
			const { add: addItems = [], update: updateItems = [], delete: deleteItems = [] } = operations;
			const preparedAnimationTypes = this.prepareAnimationTypes(animationTypes);

			return new Promise((resolve, reject) => {
				let shouldUpdateSimpleList = false;

				if (Type.isArrayFilled(addItems))
				{
					if (showAnimateOnView)
					{
						this.addToAnimateItems(addItems);
					}
					addItems.forEach((item, index) => {
						if (this.hasItem(item.id))
						{
							return;
						}

						shouldUpdateSimpleList = true;

						const position = this.findItemPosition(item, this.state.items);

						this.state.items.splice(position, 0, item);

						if (this.props.onItemAdded)
						{
							this.props.onItemAdded(item);
						}
					});
				}

				if (Type.isArrayFilled(deleteItems))
				{
					deleteItems.forEach((itemId) => {
						if (this.hasItem(itemId))
						{
							shouldUpdateSimpleList = true;
							const index = this.state.items.findIndex((item) => String(item.id) === String(itemId));
							const itemToDelete = this.state.items.splice(index, 1)[0];

							if (this.props.onItemDeleted)
							{
								this.props.onItemDeleted(itemToDelete);
							}
						}
					});
				}

				if (Type.isArrayFilled(updateItems))
				{
					if (showAnimateOnView)
					{
						this.addToAnimateItems(updateItems);
					}

					updateItems.forEach((item) => {
						if (!this.hasItem(item.id))
						{
							return;
						}

						shouldUpdateSimpleList = true;

						const oldPosition = this.state.items.findIndex((elem) => item.id === elem.id);
						this.state.items.splice(oldPosition, 1);

						const newPosition = this.findItemPosition(item, this.state.items);

						if (newPosition === this.state.items.length - 1)
						{
							item = this.getReloadedItemByIndex(this.state.items.length, item);
						}

						this.state.items.splice(newPosition, 0, item);

						if (this.props.onItemUpdated)
						{
							this.props.onItemUpdated(item);
						}
					});
				}

				if (shouldUpdateSimpleList)
				{
					const preparedItems = this.prepareItemsForRender(this.state.items);

					this.simpleList.changeItemsState(
						preparedItems,
						showAnimateImmediately
							? preparedAnimationTypes
							: {
								insert: 'none',
								delete: 'none',
								update: 'none',
								move: false,
							},
					)
						.then(resolve)
						.catch((error) => {
							console.error(error);
							reject();
						});

					this.modifyCache();
				}
				else
				{
					resolve();
				}
			});
		}

		updateItemsFromPull(items)
		{
			if (this.shouldReloadDynamically)
			{
				const ids = items.map((item) => item.id);

				return this.updateItems(ids, false, true);
			}

			this.simpleList.showUpdateButton();

			return Promise.resolve();
		}

		deleteItemsFromPull(items)
		{
			const ids = items.map((item) => item.id);

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
					items: response.data.items,
					blockPage: this.state.blockPage,
					params: loadItems,
				},
			]);

			const params = {
				append,
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

			if (response.errors && response.errors.length > 0 && response.data)
			{
				response.errors
					.filter(({ code }) => code !== 'NETWORK_ERROR')
					.forEach(({ message }) => this.showError(message));

				return;
			}

			const { data } = response;
			let items = [];

			if (data.items)
			{
				items = this.prepareItems(data.items);
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
				const notAdded = items.filter((item) => !newState.items.some((element) => element.id === item.id));
				newState.items.push(...notAdded);
			}
			else
			{
				newState.items = items;
			}

			newState.items = this.prepareItemsState(newState.items);

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

		prepareItemsState(items)
		{
			let preparedItems = clone(items);

			if (this.props.onBeforeItemsSetState)
			{
				preparedItems = this.props.onBeforeItemsSetState(preparedItems);
			}

			return preparedItems;
		}

		prepareItemsForRender(items)
		{
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

		render()
		{
			const testId = this.getValue(this.props, 'testId');
			const title = this.getValue(this.props, 'title');

			return View(
				{
					testId,
					onPan: this.props.onPanListHandler || null,
					style: {
						backgroundColor: AppTheme.colors.bgPrimary,
						flex: 1,
					},
				},
				View(
					{
						style: {
							flex: 1,
							flexDirection: 'column',
							backgroundColor: title
								? AppTheme.colors.bgContentPrimary
								: AppTheme.colors.bgPrimary,
						},
					},
					title && Text({
						style: {
							fontSize: 13,
							color: AppTheme.colors.base1,
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
						showFloatingButton: BX.prop.getBoolean(this.props, 'isShowFloatingButton', true),
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
		 * @returns {Object[]}
		 */
		getItems()
		{
			return this.state.items;
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

		sortItems(items)
		{
			if (!this.sorting)
			{
				return;
			}

			items.sort((a, b) => {
				const aSection = this.sorting.getSection(a) ?? 0;
				const bSection = this.sorting.getSection(b) ?? 0;
				if (aSection < bSection)
				{
					return -1;
				}

				if (aSection > bSection)
				{
					return 1;
				}

				const noProperty = this.sorting.noPropertyValue;
				const aSortProperty = this.sorting
					.getPropertyValue(a, this.sorting.sortByProperty) ?? noProperty;
				const bSortProperty = this.sorting
					.getPropertyValue(b, this.sorting.sortByProperty) ?? noProperty;

				if (aSortProperty < bSortProperty)
				{
					return this.sorting.isASC ? -1 : 1;
				}

				if (aSortProperty > bSortProperty)
				{
					return this.sorting.isASC ? 1 : -1;
				}

				return 0;
			});
		}

		/**
		 * @param {obj} newItem
		 * @param {array|null} sortedItems
		 * @returns {number}
		 */
		// sortedItems must not contain newItem
		findItemPosition(newItem, sortedItems = [])
		{
			if (!this.sorting)
			{
				return 0;
			}

			const noPropertyValue = this.sorting.noPropertyValue;

			let newItemSection = 0;
			if (typeof this.sorting.getSection === 'function')
			{
				newItemSection = this.sorting.getSection(newItem) ?? 0;
			}

			let newSortPropertyValue = noPropertyValue;
			if (typeof this.sorting.getPropertyValue === 'function')
			{
				newSortPropertyValue = this.sorting
					.getPropertyValue(newItem, this.sorting.sortByProperty) ?? noPropertyValue;
			}

			let found = false;
			let index = -1;

			for (const item of sortedItems)
			{
				index++;
				let currentItemSection = 0;
				if (typeof this.sorting.getSection === 'function')
				{
					currentItemSection = this.sorting.getSection(item) ?? 0;
				}

				if (currentItemSection < newItemSection)
				{
					continue;
				}
				else if (currentItemSection > newItemSection)
				{
					found = true;
					break;
				}

				let currentSortPropertyValue = noPropertyValue;
				if (typeof this.sorting.getPropertyValue === 'function')
				{
					currentSortPropertyValue = this.sorting
						.getPropertyValue(item, this.sorting.sortByProperty) ?? noPropertyValue;
				}

				if ((this.sorting.isASC && currentSortPropertyValue >= newSortPropertyValue)
					|| (!this.sorting.isASC && currentSortPropertyValue <= newSortPropertyValue))
				{
					found = true;
					break;
				}
			}

			return found ? index : index + 1;
		}

		getReloadedItemByIndex(index, fallback)
		{
			let item = fallback;

			const config = {
				data: (this.state.actionParams.loadItems || {}),
				navigation: {
					page: index + 1,
					size: 1,
				},
			};

			new RunActionExecutor(this.props.actions.loadItems, config.data, config.navigation)
				.setHandler((response) => {
					if (response.errors.length > 0)
					{
						return;
					}
					item = response.data.items[0];
				})
				.call(false);

			return item;
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
			const itemIds = entities.map((entity) => entity.id);
			const operations = this.groupItemsByOperations(itemIds, entities);

			return this.processItemsGroupsByData(
				operations,
				showAnimateOnView,
				showAnimateImmediately,
			);
		}

		/**
		 * @param {string} itemId
		 * @param {string} [animationType]
		 * @returns {Promise}
		 */
		deleteItem(itemId, animationType)
		{
			const animation = animationType || get(this.props, 'animationTypes.deleteRow', 'fade');

			return this.processItemsGroupsByData(
				{ delete: [itemId] },
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
				layout.setRightButtons(buttons);
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

			if (this.layoutMenuActions.length > 0)
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
				NavigationLoader.show();
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
				NavigationLoader.hide();
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
			items.forEach((item) => this.addToAnimateIds(item.id));
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
			if (isObjectClone && Object.prototype.hasOwnProperty.call(object, property))
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
	}

	module.exports = { StatefulList };
});
