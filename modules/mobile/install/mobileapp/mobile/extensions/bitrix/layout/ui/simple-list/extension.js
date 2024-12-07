/**
 * @module layout/ui/simple-list
 */
jn.define('layout/ui/simple-list', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { Haptics } = require('haptics');
	const AppTheme = require('apptheme');
	const { clone, merge, get, isEqual } = require('utils/object');
	const { useCallback } = require('utils/function');
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');
	const { ListItemType } = require('layout/ui/simple-list/items');
	const { ViewMode } = require('layout/ui/simple-list/view-mode');
	const { SkeletonFactory, SkeletonTypes } = require('layout/ui/simple-list/skeleton');
	const { PureComponent } = require('layout/pure-component');
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { StatusBlock, makeLibraryImagePath } = require('ui-system/blocks/status-block');
	const { OptimizedListView } = require('layout/ui/optimized-list-view');
	const { isNil } = require('utils/type');
	const { Feature } = require('feature');
	const { FloatingButtonComponent } = require('layout/ui/floating-button');
	const { MenuEngine } = require('layout/ui/simple-list/menu-engine');

	const MIN_ROWS_COUNT_FOR_LOAD_MORE = 10;
	const ACTION_DELETE = 'delete';
	const CONTEXT_AJAX = 'ajax';

	const animateActions = {
		blink: 'blink',
		setLoading: 'setLoading',
		dropLoading: 'dropLoading',
	};

	/**
	 * @class SimpleList
	 */
	class SimpleList extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.currentViewMode = null;
			this.itemViews = {};
			this.itemRootViewRefs = {};
			this.itemMenuViewRefs = {};
			this.lastElementIdAddedWithAnimation = null;
			this.delayedItemActions = new Map();
			this.isDynamicMode = props.isDynamicMode ?? true;
			const currentState = this.initItemsState(props.items);
			this.currentItemsState = currentState.itemsState;
			this.currentIdsOrder = currentState.idsOrder;

			this.state.isShowReloadListNotification = false;
			this.state.countOfNewElementsFromPull = 0;
			this.state.allItemsLoaded = props.allItemsLoaded;
			this.state.isEmpty = props.items.length === 0;

			this.updateItemHandler = this.updateItem.bind(this);
			this.showItemMenuHandler = this.showItemMenu.bind(this);
			this.modifyItemsListHandler = this.modifyItemsList.bind(this);
			this.onDetailCardCreate = this.onDetailCardCreateHandler.bind(this);
			this.onDetailCardUpdate = this.onDetailCardUpdateHandler.bind(this);
			this.operationsQueue = Promise.resolve();
			this.changeItemsStateQueue = Promise.resolve();
		}

		get testId()
		{
			return this.props.testId || 'SIMPLE_LIST';
		}

		get minRowsForLoadMore()
		{
			return Math.max(
				Math.ceil(this.props.itemsLoadLimit / 2),
				MIN_ROWS_COUNT_FOR_LOAD_MORE,
			);
		}

		componentDidMount()
		{
			BX.addCustomEvent('DetailCard::onCreate', this.onDetailCardCreate);
			BX.addCustomEvent('DetailCard::onUpdate', this.onDetailCardUpdate);
		}

		componentWillUnmount()
		{
			BX.removeCustomEvent('DetailCard::onCreate', this.onDetailCardCreate);
			BX.removeCustomEvent('DetailCard::onUpdate', this.onDetailCardUpdate);
		}

		onDetailCardCreateHandler(uid, params)
		{
			if (this.props.onDetailCardCreateHandler)
			{
				this.props.onDetailCardCreateHandler(params);

				return;
			}

			this.reloadList();
		}

		onDetailCardUpdateHandler(uid, params)
		{
			if (params.actionName === 'deleteEntity')
			{
				this.deleteItem(params.entityId);

				return;
			}

			if (this.props.onDetailCardUpdateHandler)
			{
				this.props.onDetailCardUpdateHandler(params);

				return;
			}

			this.reloadList();
		}

		componentWillReceiveProps(newProps)
		{
			super.componentWillReceiveProps(newProps);

			this.state.countOfNewElementsFromPull = 0;
			this.state.allItemsLoaded = newProps.allItemsLoaded;
			this.state.isEmpty = newProps.items.length === 0;

			if (
				this.isForbiddenViewMode(newProps)
				&& newProps.onNotViewableHandler
				&& newProps.renderType === CONTEXT_AJAX
			)
			{
				this.props.onNotViewableHandler();
			}

			const currentState = this.initItemsState(newProps.items);
			this.currentItemsState = currentState.itemsState;
			this.currentIdsOrder = currentState.idsOrder;
		}

		initItemsState(items)
		{
			const itemsState = new Map();
			items.forEach((item) => {
				itemsState.set(item.id, item);
			});
			const idsOrder = items.map((item) => item.id);

			return {
				itemsState,
				idsOrder,
			};
		}

		getSortMoveWithMaxDistance(currentIdsOrder, newIdsOrder)
		{
			let itemId = null;
			let maxDistance = 0;
			let oldPosition = null;
			let newPosition = null;

			for (const [i, element] of currentIdsOrder.entries())
			{
				const currentDistance = Math.abs(i - newIdsOrder.indexOf(element));
				if (currentDistance > maxDistance)
				{
					itemId = element;
					maxDistance = currentDistance;
					oldPosition = i;
					newPosition = newIdsOrder.indexOf(element);
				}
			}

			if (maxDistance > 0)
			{
				return {
					itemId,
					oldPosition,
					newPosition,
				};
			}

			return null;
		}

		getSortMoves(currentIdsOrder, newIdsOrder)
		{
			if (currentIdsOrder.length !== newIdsOrder.length)
			{
				console.error('different length of order arrays');

				return [];
			}
			const sortMoves = [];
			let nextSortMove = this.getSortMoveWithMaxDistance(currentIdsOrder, newIdsOrder);
			while (nextSortMove !== null)
			{
				sortMoves.push(nextSortMove);
				currentIdsOrder.splice(nextSortMove.oldPosition, 1);
				currentIdsOrder.splice(nextSortMove.newPosition, 0, nextSortMove.itemId);
				nextSortMove = this.getSortMoveWithMaxDistance(currentIdsOrder, newIdsOrder);
			}

			return sortMoves;
		}

		groupItemsByOperations(currentItems, newItems, currentIdsOrder, newIdsOrder)
		{
			const currentIdsOrderClone = [...currentIdsOrder];
			const currentItemsClone = new Map(currentItems);

			const toReplaceItems = [];
			newItems.forEach((value, id) => {
				if (!currentItemsClone.has(id) && currentItemsClone.has(value.idToReplace))
				{
					toReplaceItems.push({ ...value, key: value.idToReplace });
					currentIdsOrderClone.splice(currentIdsOrderClone.indexOf(value.idToReplace), 1, id);
				}
			});

			const toDeleteItems = [];
			currentItemsClone.forEach((value, id) => {
				if (!newItems.has(id) && !toReplaceItems.some((item) => item.idToReplace === id))
				{
					toDeleteItems.push(value);
					currentIdsOrderClone.splice(currentIdsOrderClone.indexOf(id), 1);
					currentItemsClone.delete(id);
				}
			});

			const toAddItems = [];
			newItems.forEach((value, id) => {
				if (!currentItemsClone.has(id) && !toReplaceItems.some((item) => item.id === id))
				{
					const insertIndex = newIdsOrder.indexOf(value.id);
					toAddItems.push({
						item: value,
						position: insertIndex,
					});
					currentIdsOrderClone.splice(insertIndex, 0, id);
					currentItemsClone.set(id, value);
				}
			});

			const toUpdateItems = [];
			currentItemsClone.forEach((value, id) => {
				const newItem = newItems.get(id);
				if (newItems.has(id) && !isEqual(value, newItem))
				{
					toUpdateItems.push(newItems.get(id));
				}
			});

			const toMoveItems = [...this.getSortMoves(currentIdsOrderClone, newIdsOrder)];
			const groupedItems = {
				toAddItems,
				toUpdateItems,
				toDeleteItems,
				toMoveItems,
				toReplaceItems,
			};

			if (this.props.changeItemsOperations)
			{
				return {
					...this.props.changeItemsOperations(
						this.currentIdsOrder.map((id) => this.currentItemsState.get(id)),
						newIdsOrder.map((id) => newItems.get(id)),
						groupedItems,
					),
				};
			}

			return groupedItems;
		}

		getItemsInsertGroups(toAddItems)
		{
			const insertGroups = [];
			toAddItems.sort((a, b) => a.position - b.position);
			let currentGroup = [];
			let currentInsertPosition = toAddItems[0].position;

			toAddItems.forEach((item, index) => {
				if (index === 0 || item.position === toAddItems[index - 1].position + 1)
				{
					currentGroup.push(item.item);
				}
				else
				{
					insertGroups.push({
						items: currentGroup,
						position: currentInsertPosition,
					});
					currentGroup = [item.item];
					currentInsertPosition = item.position;
				}
			});

			if (currentGroup.length > 0)
			{
				insertGroups.push({
					items: currentGroup,
					position: currentInsertPosition,
				});
			}

			return insertGroups;
		}

		changeItemsState(
			newItemsState,
			animationTypes = {
				insert: 'automatic',
				delete: 'automatic',
				update: 'automatic',
				move: true,
			},
		)
		{
			return new Promise((resolve) => {
				this.changeItemsStateQueue = this.changeItemsStateQueue.then(() => {
					const sectionIndex = 0;
					const { itemsState: newItems, idsOrder: newIdsOrder } = this.initItemsState(newItemsState);
					const {
						toAddItems,
						toUpdateItems,
						toDeleteItems,
						toMoveItems,
						toReplaceItems,
					} = this.groupItemsByOperations(
						this.currentItemsState,
						newItems,
						this.currentIdsOrder,
						newIdsOrder,
					);

					if (
						toAddItems.length === 0
						&& toUpdateItems.length === 0
						&& toDeleteItems.length === 0
						&& toMoveItems.length === 0
						&& toReplaceItems.length === 0
					)
					{
						resolve();

						// eslint-disable-next-line promise/no-return-wrap
						return Promise.resolve();
					}

					const modificationsPromises = [];
					if (toReplaceItems.length > 0)
					{
						modificationsPromises.push(this.replaceRows(toReplaceItems));
					}

					if (toUpdateItems.length > 0)
					{
						modificationsPromises.push(this.updateRows(toUpdateItems, animationTypes.update));
					}

					if (toDeleteItems.length > 0)
					{
						const ids = toDeleteItems.map((item) => item.id);
						modificationsPromises.push(this.deleteRows(ids, animationTypes.delete));
					}

					if (toAddItems.length > 0)
					{
						toAddItems.sort((a, b) => a.position - b.position);
						const insertGroups = this.getItemsInsertGroups(toAddItems);
						insertGroups.forEach((group) => {
							modificationsPromises.push(
								this.insertRows(group.items, group.position, animationTypes.insert),
							);
						});
					}

					if (toMoveItems.length > 0)
					{
						toMoveItems.forEach((item) => {
							modificationsPromises.push(
								this.moveRow(
									newItems.get(item.itemId),
									item.newPosition,
									sectionIndex,
									animationTypes.move,
								),
							);
						});
					}

					return Promise.allSettled(modificationsPromises).then(() => {
						this.currentIdsOrder = newIdsOrder;
						this.currentItemsState = newItems;
						resolve();
					});
				});
			});
		}

		showUpdateButton(newElementsCount = 0)
		{
			this.setState((state, props) => ({
				isShowReloadListNotification: true,
				countOfNewElementsFromPull: state.countOfNewElementsFromPull + newElementsCount,
			}));
		}

		processDelayedItemActions(id)
		{
			if (this.delayedItemActions.has(id))
			{
				const data = this.delayedItemActions.get(id);
				this.delayedItemActions.delete(id);
				this.updateItem(data.action, data.id, data.params);
			}
		}

		deleteItem(id, params = {})
		{
			this.updateItem(ACTION_DELETE, id, params);
		}

		updateItem(action, id, params)
		{
			params = (params || {});

			if (!this.getItemComponent(id))
			{
				this.delayedItemActions.set(id, { action, id, params });

				return;
			}

			const eventData = {
				id,
				oldItem: clone(this.getItem(id)),
				item: params,
			};

			if (action === ACTION_DELETE)
			{
				this.props.deleteItemHandler(id)
					.then(() => this.sendEvent(action, eventData))
					.catch(console.error);
			}
			else
			{
				// trying to protect against re-animating the update when the element was just added
				const handlerParams = {
					showAnimate: id !== this.lastElementIdAddedWithAnimation,
				};

				this.lastElementIdAddedWithAnimation = null;

				this.props.updateItemHandler(id, handlerParams)
					.then(() => {
						this.sendEvent(action, eventData);
					})
					.catch(console.error);
			}
		}

		sendEvent(action, data)
		{
			const eventName = action[0].toUpperCase() + action.slice(1);
			BX.postComponentEvent(`UI.SimpleList::on${eventName}Item`, [data]);
		}

		isForbiddenViewMode(props)
		{
			return (
				props.settingsIsLoaded
				&& props.permissions
				&& BX.type.isBoolean(props.permissions.view)
				&& !props.permissions.view
			);
		}

		/**
		 * @returns {string}
		 */
		getViewMode()
		{
			if (this.isForbiddenViewMode(this.props))
			{
				return ViewMode.forbidden;
			}

			if (this.state.allItemsLoaded)
			{
				return this.state.isEmpty ? ViewMode.empty : ViewMode.list;
			}

			if (this.isEmptyList())
			{
				return ViewMode.loading;
			}

			return ViewMode.list;
		}

		scrollToBegin(animated = true)
		{
			this.listView?.scrollToBegin(animated);
		}

		/**
		 * @param {string[]} itemIds
		 * @param {boolean} animated
		 * @param {boolean} checkVisibility
		 * @return {Promise<void>}
		 */
		async scrollToTopItem(itemIds, animated = true, checkVisibility = true)
		{
			let topIndex = null;
			let topItemId = null;

			itemIds.forEach((itemId) => {
				const { index } = this.listView.getElementPosition(itemId);
				if (topIndex === null || topIndex > index)
				{
					topIndex = index;
					topItemId = itemId;
				}
			});

			if (topItemId)
			{
				await this.scrollToItem(topItemId, animated, checkVisibility);
			}
		}

		/**
		 * @param {string} itemId
		 * @param {boolean} animated
		 * @param {boolean} checkVisibility
		 * @return {Promise<void>}
		 */
		async scrollToItem(itemId, animated = true, checkVisibility = true)
		{
			if (checkVisibility)
			{
				const isVisible = await this.listView?.isItemVisible(itemId);
				if (isVisible)
				{
					return;
				}
			}

			const { section, index } = this.listView.getElementPosition(itemId);
			this.listView.scrollTo(section, index, animated);

			if (animated)
			{
				await new Promise((resolve) => {
					setTimeout(resolve, 500);
				});
			}
		}

		render()
		{
			let container = null;

			this.currentViewMode = this.getViewMode();

			switch (this.currentViewMode)
			{
				case ViewMode.list:
					container = this.getListViewContainer();
					break;

				case ViewMode.empty:
					container = this.getEmptyScreenContainer();
					break;

				case ViewMode.loading:
					container = this.getLoadingScreenContainer();
					break;

				case ViewMode.forbidden:
					container = this.getForbiddenContainer();
					break;

				// no default
			}

			return View(
				{
					style: this.getStyle('wrapper'),
				},
				container,
				this.shouldShowReloadListNotification() && this.renderReloadNotification(),
				this.props.showFloatingButton && this.renderFloatingButton(),
			);
		}

		reloadList()
		{
			this.props.reloadListHandler();
		}

		getListViewContainer()
		{
			const items = this.getItems();

			return OptimizedListView({
				testId: `${this.testId}_LIST_VIEW`,
				style: this.getStyle('container'),
				data: [{ items }],
				onScrollCalculated: this.props.onScrollCalculated,
				onMomentumScrollEnd: this.props.onMomentumScrollEnd,
				onMomentumScrollBegin: this.props.onMomentumScrollBegin,
				onScrollEndDrag: this.props.onScrollEndDrag,
				onScroll: this.props.onScroll,
				renderItem: (item, section, row) => {
					const customStyles = (
						this.props.getItemCustomStyles
							? this.props.getItemCustomStyles(item, section, row)
							: {}
					);
					const itemType = item.itemType || this.props.itemType;
					const itemFactory = item.itemFactory || this.props.itemFactory;

					return itemFactory.create(itemType, {
						testId: this.testId,
						layout: this.props.layout,
						showAirStyle: this.props.showAirStyle,
						item,
						params: this.props.itemParams || {},
						customStyles,
						itemLayoutOptions: this.props.itemLayoutOptions,
						showMenuHandler: this.showItemMenuHandler,
						itemDetailOpenHandler: this.props.itemDetailOpenHandler,
						onItemLongClick: this.props.onItemLongClick || null,
						itemCounterLongClickHandler: this.props.itemCounterLongClickHandler,
						modifyItemsListHandler: this.modifyItemsListHandler,
						hasActions: (
							this.props.itemActions
							&& Array.isArray(this.props.itemActions)
							&& this.props.itemActions.length > 0
						),
						ref: useCallback((ref) => {
							if (item.id)
							{
								this.itemViews[item.id] = ref;
								this.processDelayedItemActions(item.id);
							}
						}, [item.id]),
						forwardRef: useCallback((ref) => {
							if (item.id)
							{
								this.itemRootViewRefs[item.id] = ref;
							}
						}, [item.id]),
						menuViewRef: useCallback((ref) => {
							if (item.id)
							{
								this.itemMenuViewRefs[item.id] = ref;
							}
						}, [item.id]),
						analyticsLabel: this.props.analyticsLabel,
					});
				},
				onRefresh: () => {
					this.reloadList();
					BX.postComponentEvent('UI.SimpleList::onRefresh');
				},
				isRefreshing: this.props.isRefreshing,
				onLoadMore: (
					this.state.allItemsLoaded || (this.currentIdsOrder.length < this.props.itemsLoadLimit)
						? null
						: this.onLoadMoreDummy // need for show the loader at the bottom of the list
				),
				onViewableItemsChanged: (viewableItems) => {
					if (this.state.allItemsLoaded)
					{
						return;
					}

					this.visibleIndexes = (viewableItems[0].items || null);
					if (this.visibleIndexes)
					{
						const maxIndex = Math.max(...this.visibleIndexes) + 1;

						if (this.currentIdsOrder.length - maxIndex < this.minRowsForLoadMore)
						{
							const blockPage = Math.floor(this.currentIdsOrder.length / this.props.itemsLoadLimit);
							this.loadMore(blockPage);
						}
					}
				},
				renderLoadMore: () => {
					if (this.state.allItemsLoaded)
					{
						return null;
					}

					return this.renderSkeleton({
						itemParams: this.props.itemParams || {},
						showAirStyle: this.props.showAirStyle,
						length: 1,
					});
				},
				ref: (ref) => {
					this.listView = ref;
				},
			});
		}

		isEmptyList()
		{
			return this.getItemsCount() === 0;
		}

		getItemsCount()
		{
			return this.currentIdsOrder.length;
		}

		getEmptyScreenContainer()
		{
			const { isSearchEnabled, getEmptyListComponent } = this.props;

			return View(
				{
					style: this.getStyle('container'),
				},
				getEmptyListComponent
					? getEmptyListComponent({ isSearchEnabled })
					: this.renderEmptyScreen(),
			);
		}

		renderEmptyScreen()
		{
			const { showAirStyle } = this.props;

			const imageParams = {
				resizeMode: 'contain',
				style: {
					width: 172,
					height: 172,
				},
				svg: {
					uri: makeLibraryImagePath('empty-list.svg'),
				},
			};

			return showAirStyle
				? StatusBlock({
					emptyScreen: true,
					title: this.getEmptyScreenTitle(),
					image: Image(imageParams),
				})
				: new EmptyScreen({
					title: this.getEmptyScreenTitle(),
					image: imageParams,
				});
		}

		getEmptyScreenTitle()
		{
			const { isSearchEnabled, emptySearchText, emptyListText } = this.props;

			return isSearchEnabled
				? (emptySearchText || Loc.getMessage('SIMPLELIST_SEARCH_EMPTY'))
				: (emptyListText || Loc.getMessage('SIMPLELIST_LIST_EMPTY'));
		}

		getLoadingScreenContainer()
		{
			const { showAirStyle, forcedShowSkeleton, itemParams = {}, itemType } = this.props;

			if (forcedShowSkeleton && SkeletonTypes[itemType])
			{
				return View(
					{},
					this.renderSkeleton({
						itemParams,
						showAirStyle,
						fullScreen: true,
					}),
				);
			}

			return View(
				{
					style: this.getStyle('container'),
				},
				new LoadingScreenComponent({ showAirStyle }),
			);
		}

		getForbiddenContainer()
		{
			return View(
				{
					style: this.getStyle('container'),
				},
				View(
					{},
					Text({
						text: BX.message('SIMPLELIST_FORBIDDEN'),
					}),
				),
			);
		}

		renderSkeleton(params = {})
		{
			return SkeletonFactory.make(this.props.itemType, params);
		}

		renderFloatingButton()
		{
			const { layout, onFloatingButtonLongClick, onFloatingButtonClick } = this.props;

			return new FloatingButtonComponent({
				testId: `${this.testId}_ADD_BTN`,
				onClick: onFloatingButtonClick,
				onLongClick: onFloatingButtonLongClick,
				accent: this.isEmptyList(),
				parentLayout: layout,
			});
		}

		loadItems(blockPage, append)
		{
			return this.props.loadItemsHandler(blockPage, append);
		}

		loadMore(blockPage)
		{
			this.loadItems(blockPage + 1, true);
		}

		onLoadMoreDummy()
		{}

		hasItem(id)
		{
			return Boolean(this.getItem(id));
		}

		getItem(id)
		{
			return this.currentItemsState.get(id);
		}

		getItems()
		{
			let items = this.currentIdsOrder.map((id) => this.currentItemsState.get(id));
			if (this.props.showEmptySpaceItem)
			{
				items = [
					{
						itemType: ListItemType.EMPTY_SPACE,
						type: ListItemType.EMPTY_SPACE,
						key: `${ListItemType.EMPTY_SPACE}_top`,
					},
					...items,
				];
			}

			if (
				this.getViewMode() === ViewMode.list
				&& this.props.showFloatingButton
				&& this.state.allItemsLoaded
			)
			{
				items = [
					...items,
					{
						itemType: ListItemType.EMPTY_SPACE,
						type: ListItemType.EMPTY_SPACE,
						key: `${ListItemType.EMPTY_SPACE}_bottom`,
						height: 84,
					},
				];
			}

			return items;
		}

		modifyItemsList(itemsData)
		{
			itemsData.forEach((item) => {
				const { id } = item;
				const currentItem = this.hasItem(id) && clone(this.getItem(id));

				if (currentItem)
				{
					merge(currentItem, item);
					this.currentItemsState.set(currentItem.id, currentItem);
				}
			});
		}

		/**
		 * @returns {Boolean}
		 */
		shouldShowReloadListNotification()
		{
			return this.state.isShowReloadListNotification;
		}

		dropShowReloadListNotification()
		{
			this.state.isShowReloadListNotification = false;
		}

		setShowReloadListNotification()
		{
			this.state.isShowReloadListNotification = true;
		}

		renderReloadNotification()
		{
			return View(
				{
					style: this.getStyle('reloadNotificationWrapper'),
					ref: (ref) => {
						this.reloadNotificationRef = ref;
						if (this.reloadNotificationRef)
						{
							setTimeout(() => {
								this.reloadNotificationRef.animate({ duration: 300, top: 15 });
							}, 50);
						}
					},
				},
				View(
					{
						style: this.getStyle('reloadNotification'),
						onClick: () => {
							Haptics.impactLight();

							this.state.countOfNewElementsFromPull = 0;

							if (!isNil(this.listView))
							{
								this.listView.scrollToBegin(true);
							}

							let promise = Promise.resolve();

							if (this.reloadNotificationRef)
							{
								promise = promise.then(() => new Promise((resolve) => {
									this.reloadNotificationRef.animate({ duration: 300, top: -50 }, resolve);
								}));
							}

							promise.then(() => this.reloadList()).catch(console.error);
						},
					},
					Image({
						style: {
							width: 16,
							height: 16,
						},
						svg: {
							content: `<svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><defs><style>.cls-1 { fill: ${this.colors.baseWhiteFixed}; fill-rule: evenodd; }</style></defs><path class="cls-1" d="M8.094 13.558a5.558 5.558 0 1 1 3.414-9.944l-1.466 1.78 5.95.585L14.22.324l-1.146 1.39a7.99 7.99 0 1 0 .926 11.736l-1.744-1.726a5.62 5.62 0 0 1-4.16 1.834z"/></svg>`,
						},
					}),
					Text({
						style: this.getStyle('textNotification'),
						text: this.props.getNotificationText(this.state.countOfNewElementsFromPull),
					}),
				),
			);
		}

		getItemMenuEngine(itemId)
		{
			const commonProps = {
				parent: this.getItem(itemId),
				parentId: itemId,
				testId: this.testId,
				updateItemHandler: this.updateItemHandler,
				params: {
					showCancelButton: true,
					mediumPositionPercent: 51,
				},
				analyticsLabel: this.getContextMenuAnalyticsLabel(),
				layoutWidget: layout,
			};
			const popupItemMenu = this.props.popupItemMenu;

			return MenuEngine.createMenu(commonProps, popupItemMenu);
		}

		showItemMenu(itemId)
		{
			const actions = clone(this.props.itemActions);
			const engine = this.getItemMenuEngine(itemId);

			if (this.itemViews[itemId])
			{
				this.itemViews[itemId].prepareActions(actions);
			}
			const target = this.getItemMenuViewRef(itemId);

			void engine.show(actions, { target });
		}

		getContextMenuAnalyticsLabel()
		{
			const { analyticsLabel } = this.props;

			if (Type.isPlainObject(analyticsLabel))
			{
				return {
					event: 'list-item-menu-click',
					...analyticsLabel,
				};
			}

			return null;
		}

		insertRows(items, elementIndex, animationType, section = 0)
		{
			const wasEmpty = this.isEmptyList();
			const animation = animationType || get(this.props, 'animationTypes.insertRows', 'fade');

			// ToDo temp fix for kanban view, remove it when single update method will be implemented
			if (this.props.showEmptySpaceItem)
			{
				elementIndex += 1;
			}

			if (!items || items.length === 0)
			{
				return new Promise((resolve) => {
					resolve();
				});
			}

			const notExistsInListItems = items.filter((item) => !this.currentIdsOrder.includes(item.id));
			if (notExistsInListItems.length === 0)
			{
				return new Promise((resolve) => {
					resolve();
				});
			}

			const ids = notExistsInListItems.map((item) => item.id);
			this.currentIdsOrder.splice(elementIndex, 0, ...ids);

			notExistsInListItems.forEach((item) => {
				this.currentItemsState.set(item.id, item);
			});

			return new Promise((resolve, reject) => {
				if (!items)
				{
					reject();

					return;
				}

				this.operationsQueue = this.operationsQueue.then(() => {
					return new Promise((_resolve) => {
						if (wasEmpty)
						{
							this.setState({
								isEmpty: false,
							}, () => {
								_resolve();
								resolve();
							});

							return;
						}

						if (isNil(this.listView))
						{
							_resolve();
							reject();

							return;
						}

						this.listView.insertRows(items, section, elementIndex, animation)
							.then(resolve)
							.catch((error) => {
								console.error(error);
								reject();
							})
							.finally(_resolve);
					});
				});
			});
		}

		replaceRows(items)
		{
			if (!Feature.isListViewUpdateRowByKeySupported() || !items || items.length === 0)
			{
				return new Promise((resolve) => {
					resolve();
				});
			}

			items.forEach((item) => {
				const { id, idToReplace } = item;
				if (this.currentItemsState.has(idToReplace))
				{
					this.currentIdsOrder.splice(this.currentIdsOrder.indexOf(idToReplace), 1, id);
					this.currentItemsState.delete(idToReplace);
					this.currentItemsState.set(id, item);
				}
			});

			return new Promise((resolve, reject) => {
				this.operationsQueue = this.operationsQueue.then(() => {
					return new Promise((_resolve) => {
						if (isNil(this.listView))
						{
							_resolve();
							reject();

							return;
						}

						const promises = items.map((item) => {
							return this.listView.updateRowByKey(item.key, { ...item, key: String(item.id) }, 'none');
						});

						Promise.allSettled(promises)
							.then(() => this.listView.updateRows(items, 'none'))
							.then(resolve)
							.catch((error) => {
								console.error(error);
								reject();
							})
							.finally(_resolve);
					});
				});
			});
		}

		updateRows(items, animationType)
		{
			const animation = animationType || get(this.props, 'animationTypes.updateRows', 'automatic');

			if (!items || items.length === 0)
			{
				return new Promise((resolve) => {
					resolve();
				});
			}

			items.forEach((item) => {
				if (this.currentItemsState.has(item.id))
				{
					this.currentItemsState.set(item.id, item);
				}
			});

			return new Promise((resolve, reject) => {
				if (!items)
				{
					reject();

					return;
				}

				this.operationsQueue = this.operationsQueue.then(() => {
					return new Promise((_resolve) => {
						if (isNil(this.listView))
						{
							_resolve();
							reject();

							return;
						}

						this.listView.updateRows(items, animation)
							.then(resolve)
							.catch((error) => {
								console.error(error);
								reject();
							})
							.finally(_resolve);
					});
				});
			});
		}

		appendRows(items, isAllItemsLoaded, animationType)
		{
			const animation = animationType || get(this.props, 'animationTypes.appendRows', 'none');

			if (!items || items.length === 0)
			{
				return new Promise((resolve) => {
					resolve();
				});
			}

			const notExistsInListItems = items.filter((item) => !this.currentIdsOrder.includes(item.id));
			if (notExistsInListItems.length === 0)
			{
				return new Promise((resolve) => {
					resolve();
				});
			}

			notExistsInListItems.forEach((item, index) => {
				this.currentIdsOrder.push(item.id);
				this.currentItemsState.set(item.id, item);
			});

			if (this.state.allItemsLoaded !== isAllItemsLoaded)
			{
				this.setState({ allItemsLoaded: isAllItemsLoaded });
			}

			return new Promise((resolve, reject) => {
				if (!items)
				{
					reject();

					return;
				}

				this.operationsQueue = this.operationsQueue.then(() => {
					return new Promise((_resolve) => {
						if (isNil(this.listView))
						{
							_resolve();
							reject();

							return;
						}

						this.listView.appendRowsToSection(items, 0, animation)
							.then(resolve)
							.catch((error) => {
								console.error(error);
								reject();
							})
							.finally(_resolve);
					});
				});
			});
		}

		deleteRow(id, animationType)
		{
			const animation = animationType || get(this.props, 'animationTypes.deleteRow', 'fade');

			return new Promise((resolve, reject) => {
				if (!this.currentIdsOrder.includes(id))
				{
					reject();

					return;
				}

				const index = this.currentIdsOrder.indexOf(id);
				this.currentIdsOrder.splice(index, 1);
				this.currentItemsState.delete(id);

				this.operationsQueue = this.operationsQueue.then(() => {
					return new Promise((_resolve) => {
						if (isNil(this.listView))
						{
							_resolve();
							reject();

							return;
						}
						const { section, index } = this.getItemPosition(id);
						this.listView.deleteRow(
							section,
							index,
							animation,
							() => {
								if (this.isEmptyList() && this.state.allItemsLoaded)
								{
									this.setState({
										isEmpty: true,
									}, () => {
										_resolve();
										resolve();
									});
								}
								else
								{
									_resolve();
									resolve();
								}
							},
						);
					});
				});
			});
		}

		deleteRows(ids, animationType)
		{
			const animation = animationType || get(this.props, 'animationTypes.deleteRow', 'fade');

			return new Promise((resolve, reject) => {
				const existsIds = ids.filter((id) => this.currentIdsOrder.includes(id));
				if (existsIds.length === 0)
				{
					reject();

					return;
				}

				existsIds.forEach((id) => {
					const index = this.currentIdsOrder.indexOf(id);
					this.currentIdsOrder.splice(index, 1);
					this.currentItemsState.delete(id);
				});

				this.operationsQueue = this.operationsQueue.then(() => {
					return new Promise((_resolve) => {
						if (isNil(this.listView))
						{
							_resolve();
							reject();

							return;
						}
						this.listView.deleteRowsByKeys(existsIds.map((id) => String(id)), animation, () => {
							if (this.isEmptyList() && this.state.allItemsLoaded)
							{
								this.setState({
									isEmpty: true,
								}, () => {
									_resolve();
									resolve();
								});
							}
							else
							{
								_resolve();
								resolve();
							}
						});
					});
				});
			});
		}

		moveRow(item, elementIndex, sectionIndex = 0, withAnimation = null)
		{
			const useAnimation = withAnimation === null
				? get(this.props, 'animationTypes.moveRow', true)
				: withAnimation;

			// ToDo temp fix for kanban view, remove it when single update method will be implemented
			if (this.props.showEmptySpaceItem)
			{
				elementIndex += 1;
			}

			return new Promise((resolve, reject) => {
				if (!item)
				{
					reject();

					return;
				}

				this.operationsQueue = this.operationsQueue.then(() => {
					return new Promise((_resolve) => {
						if (isNil(this.listView))
						{
							_resolve();
							reject();

							return;
						}

						this.listView.moveRow(item, sectionIndex, elementIndex, Boolean(useAnimation))
							.then(resolve)
							.catch((error) => {
								console.error(error);
								reject();
							})
							.finally(_resolve);
					});
				});
			});
		}

		/**
		 * @param itemId
		 * @returns {LayoutComponent}
		 */
		getItemComponent(itemId)
		{
			return this.itemViews[itemId];
		}

		/**
		 * @param itemId
		 * @returns {LayoutComponent}
		 */
		getItemRootViewRef(itemId)
		{
			return this.itemRootViewRefs[itemId];
		}

		/**
		 * @param itemId
		 * @returns {LayoutComponent}
		 */
		getItemMenuViewRef(itemId)
		{
			return this.itemMenuViewRefs[itemId];
		}

		/**
		 * @param key
		 * @returns {null|{section: number, index: number}}
		 */
		getItemPosition(key)
		{
			return this.listView?.getElementPosition(String(key));
		}

		blinkItem(itemId, showUpdated = true)
		{
			return this.animateItem(animateActions.blink, itemId, { showUpdated });
		}

		setLoading(itemId)
		{
			return this.animateItem(animateActions.setLoading, itemId);
		}

		dropLoading(itemId, showUpdated = true)
		{
			return this.animateItem(animateActions.dropLoading, itemId, { showUpdated });
		}

		animateItem(action, itemId, { showUpdated = true } = {})
		{
			return new Promise((resolve) => {
				const item = this.getItemComponent(itemId);
				if (!item)
				{
					resolve();

					return;
				}

				switch (action)
				{
					case animateActions.blink:
					{
						item.blink(resolve, showUpdated);
						break;
					}

					case animateActions.setLoading:
					{
						item.setLoading(resolve);
						break;
					}

					case animateActions.dropLoading:
					{
						item.dropLoading(resolve, showUpdated);
						break;
					}

					// no default
				}
			});
		}

		scrollBy = (props) => {
			this.listView.scrollBy(props);
		};

		getStyle(name)
		{
			return (this.getStyles()[name] || {});
		}

		get colors()
		{
			return this.props.showAirStyle ? AppTheme.realColors : AppTheme.colors;
		}

		getStyles()
		{
			return {
				wrapper: {
					flexDirection: 'column',
					flex: 1,
					backgroundColor: this.colors.bgPrimary,
				},
				container: {
					flexDirection: 'column',
					justifyContent: 'center',
					alignItems: 'center',
					flex: 1,
				},
				reloadNotificationWrapper: {
					width: '100%',
					position: 'absolute',
					top: -50,
					flexDirection: 'row',
					justifyContent: 'center',
				},
				reloadNotification: {
					paddingLeft: 14,
					paddingRight: 16,
					paddingVertical: 10,
					backgroundColor: '#cc2fc6f6',
					borderRadius: 18,
					flexDirection: 'row',
					alignItems: 'center',
				},
				textNotification: {
					color: this.colors.baseWhiteFixed,
					fontSize: 14,
					fontWeight: '700',
					marginLeft: 10,
				},
			};
		}
	}

	module.exports = { SimpleList };
});
