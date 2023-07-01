/**
 * @module layout/ui/simple-list
 */
jn.define('layout/ui/simple-list', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { clone, merge } = require('utils/object');
	const { ViewMode } = require('layout/ui/simple-list/view-mode');
	const { SkeletonFactory, SkeletonTypes } = require('layout/ui/simple-list/skeleton');
	const { PureComponent } = require('layout/pure-component');
	const { Type } = require('type');

	const svgImages = {
		empty: {
			content: '<svg width="95" height="95" viewBox="0 0 95 95" fill="none" xmlns="http://www.w3.org/2000/svg"><circle opacity="0.3" cx="47.1198" cy="47.1198" r="46.1198" stroke="#A8ADB4" stroke-width="2"/><path fill-rule="evenodd" clip-rule="evenodd" d="M47.0423 19.6253C47.0213 19.6316 46.9996 19.6429 46.9773 19.6546L46.9571 19.6649L34.2047 24.7125C33.8851 24.8628 33.7692 25.1882 33.7597 25.5713V41.8236C33.7601 41.8704 33.7637 41.9169 33.7701 41.9626L21.3141 46.8928C20.9946 47.0431 20.8786 47.3685 20.8691 47.7517V64.004C20.8727 64.3673 21.0632 64.7125 21.3141 64.8099L33.9436 69.8043C34.0998 69.8736 34.3045 69.8637 34.4738 69.8175L47.0138 64.8635C47.0191 64.8658 47.0245 64.868 47.0299 64.8701L59.6593 69.8644C59.8156 69.9338 60.0203 69.9239 60.1895 69.8776L72.9323 64.8436C73.1832 64.7412 73.3701 64.3894 73.3678 64.0244V47.9175C73.3749 47.422 73.2518 47.1313 72.9228 46.9794L60.5074 42.0651C60.5307 41.9751 60.5432 41.88 60.5426 41.7839V25.677C60.5497 25.1815 60.4266 24.8908 60.0976 24.7389L47.2791 19.6651C47.1915 19.6205 47.1228 19.6021 47.0423 19.6253ZM47.1181 21.3562L57.7026 25.558L47.1181 29.7333L36.5241 25.5448L47.1181 21.3562ZM59.9433 43.5967L70.5278 47.7984L59.9433 51.9738L49.3494 47.7853L59.9433 43.5967ZM44.8121 47.7383L34.2275 43.5365L23.6336 47.7251L34.2275 51.9136L44.8121 47.7383Z" fill="#A8ADB4"/></svg>',
		},
	};

	const MIN_ROWS_COUNT_FOR_LOAD_MORE = 10;

	const PULL_COMMAND_UPDATED = 'UPDATED';
	const PULL_COMMAND_DELETED = 'DELETED';
	const PULL_COMMAND_ADDED = 'ADDED';

	const ACTION_DELETE = 'delete';
	const ACTION_UPDATE = 'update';

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

			this.testId = this.props.testId || '';
			this.itemType = props.itemType;
			this.currentViewMode = null;

			this.state.viewMode = ViewMode.loading;
			this.state.isShowReloadListNotification = false;
			this.state.countOfNewElementsFromPull = 0;

			this.items = (props.items || new Map());
			this.itemViews = {};
			this.pull = (props.pull || {});
			this.lastElementIdAddedWithAnimation = null;

			this.loadItemsHandler = props.loadItemsHandler;
			this.reloadListHandler = props.reloadListHandler;
			this.updateItemHandler = this.updateItemHandler.bind(this);
			this.showItemMenuHandler = this.showItemMenu.bind(this);
			this.modifyItemsListHandler = this.modifyItemsList.bind(this);

			this.pullUnsubscribe = null;
			this.delayedItemActions = new Map();

			this.createFloatingButton();

			this.onDetailCardUpdate = this.onDetailCardUpdateHandler.bind(this);
			this.onDetailCardCreate = this.onDetailCardCreateHandler.bind(this);
		}

		componentDidMount()
		{
			BX.addCustomEvent('DetailCard::onUpdate', this.onDetailCardUpdate);
			BX.addCustomEvent('DetailCard::onCreate', this.onDetailCardCreate);

			if (this.pull.moduleId)
			{
				this.pullUnsubscribe = BX.PULL.subscribe({
					moduleId: this.pull.moduleId,
					callback: this.pullCallback.bind(this),
				});
			}
		}

		get contextMenuAnalyticsLabel()
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

		onDetailCardCreateHandler(uid, params)
		{
			if (this.props.onDetailCardCreateHandler)
			{
				this.props.onDetailCardCreateHandler(params);
				return;
			}

			this.reloadList();
		}

		componentWillUnmount()
		{
			if (this.pullUnsubscribe)
			{
				this.pullUnsubscribe();
			}
			BX.removeCustomEvent('DetailCard::onUpdate', this.onDetailCardUpdate);
			BX.removeCustomEvent('DetailCard::onCreate', this.onDetailCardCreate);
		}

		componentWillReceiveProps(newProps)
		{
			super.componentWillReceiveProps(newProps);

			this.testId = newProps.testId || '';
			this.items = (newProps.items || new Map());

			this.state.countOfNewElementsFromPull = 0;

			if (this.isForbiddenViewMode(newProps) && newProps.onNotViewableHandler && newProps.renderType === CONTEXT_AJAX)
			{
				this.props.onNotViewableHandler();
				return false;
			}

			this.createFloatingButton();
		}

		createFloatingButton()
		{
			if (!this.props.showFloatingButton)
			{
				return;
			}

			this.floatingButton = (this.floatingButton || null);

			if (this.floatingButton)
			{
				return;
			}

			this.floatingButton = new UI.FloatingButtonComponent({
				testId: `${this.testId}_ADD_BTN`,
				onClick: this.props.floatingButtonClickHandler,
				onLongClick: this.props.floatingButtonLongClickHandler,
			});
		}

		pullCallback(data = {})
		{
			if (!this.pull.callback)
			{
				return;
			}

			this.pull.callback(data, this.props.context).then(response => {
				if (response.isBatchMode)
				{
					for (const eventName in response.data)
					{
						this.processPullItem(eventName, response.data[eventName]);
					}
				}
				else
				{
					this.processPullItem(response.params.eventName, response.params.items);
				}
			});
		}

		processPullItem(eventName, items)
		{
			if (eventName === PULL_COMMAND_UPDATED)
			{
				items.map(item => {
					this.updateItemFromPull(item.id, item);
				});
			}

			if (eventName === PULL_COMMAND_DELETED)
			{
				items.map(item => {
					this.deleteItem(item.id);
				});
			}

			if (eventName === PULL_COMMAND_ADDED)
			{
				this.addItemsFromPull(items);
			}
		}

		deleteItem(id, params = {})
		{
			this.updateItemHandler(ACTION_DELETE, id, params);
		}

		updateItemFromPull(id, item)
		{
			if (!this.items.size)
			{
				this.reloadList();
				return;
			}

			const isShowReloadListNotification = (item.config && item.config.showReloadListNotification);

			if (!this.items.has(id) && !isShowReloadListNotification)
			{
				return;
			}

			if (isShowReloadListNotification)
			{
				this.setState({
					isShowReloadListNotification,
				});
				return;
			}

			this.updateItemHandler(ACTION_UPDATE, id, item);
		}

		updateItemHandler(action, id, params)
		{
			params = (params || {});

			const oldItem = clone(this.items.get(id));
			const item = this.getItemComponent(id);
			if (!item)
			{
				this.delayedItemActions.set(id, {
					action,
					id,
					params,
				});
				return;
			}

			const eventData = {
				id,
				oldItem,
				item: params,
			};

			if (action === ACTION_DELETE)
			{
				this.props.deleteItemHandler(id).then(() => this.sendEvent(action, eventData));
			}
			else
			{

				// trying to protect against re-animating the update when the element was just added
				const params = {
					showAnimate: id !== this.lastElementIdAddedWithAnimation,
				};
				this.lastElementIdAddedWithAnimation = null;

				this.props.updateItemHandler(id, params).then(() => this.sendEvent(action, eventData));
			}
		}

		sendEvent(action, data)
		{
			const eventName = action[0].toUpperCase() + action.slice(1);
			BX.postComponentEvent(`UI.SimpleList::on${eventName}Item`, [data]);
		}

		addItemsFromPull(items)
		{
			this.lastElementIdAddedWithAnimation = items[items.length - 1].id;

			if (!this.items.size)
			{
				this.reloadList();
				return;
			}

			this.setState((state, props) => ({
				isShowReloadListNotification: true,
				countOfNewElementsFromPull: state.countOfNewElementsFromPull + (items.length || 1),
			}));
		}

		/**
		 * This method is temporarily not used, since we do not add elements in real time,
		 * but only show a notification that the list needs to be updated.
		 * @param id
		 * @param params
		 */
		addItemHandler(id, params)
		{
			this.props.addItemHandler(id, params).then(() => {
				void this.blinkItem(id);
			});
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
			return new Promise(resolve => {
				const item = this.getItemComponent(itemId);
				if (!item)
				{
					resolve();
					return;
				}

				if (action === animateActions.blink)
				{
					item.blink(resolve, showUpdated);
				}
				else if (action === animateActions.setLoading)
				{
					item.setLoading(resolve);
				}
				else if (action === animateActions.dropLoading)
				{
					item.dropLoading(resolve, showUpdated);
				}
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

		showItemMenu(itemId)
		{
			const actions = clone(this.props.itemActions);

			if (this.itemViews[itemId])
			{
				this.itemViews[itemId].prepareActions(actions);
			}

			this.menu = new ContextMenu({
				parent: this.items.get(itemId),
				parentId: itemId,
				id: 'SimpleList-' + itemId,
				testId: this.testId,
				actions,
				updateItemHandler: this.updateItemHandler,
				params: {
					showCancelButton: true,
					showPartiallyHidden: actions.length > 7,
				},
				analyticsLabel: this.contextMenuAnalyticsLabel,
			});

			this.menu.show();
		}

		loadItems(blockPage, append)
		{
			return this.loadItemsHandler(blockPage, append);
		}

		getItems()
		{
			let items = Array.from(this.items.values());

			if (this.props.showEmptySpaceItem)
			{
				items = [
					{
						factoryType: ListItemsFactory.Type.EmptySpace,
					},
					...items,
				];
			}

			if (
				this.getViewMode() === ViewMode.list
				&& this.props.showFloatingButton
				&& this.props.allItemsLoaded
			)
			{
				items = [
					...items,
					{
						factoryType: ListItemsFactory.Type.EmptySpace,
						height: 84,
					},
				];
			}

			return items;
		}

		onLoadMoreDummy()
		{
		}

		render()
		{
			let container = null;

			const viewMode = this.getViewMode();

			const lastViewMode = this.currentViewMode;
			this.currentViewMode = viewMode;

			if (viewMode === ViewMode.list)
			{
				const { allItemsLoaded } = this.props;
				container = ListView({
					testId: `${this.testId}_LIST_VIEW`,
					style: this.getStyle('container'),
					data: [{
						items: this.getItems(),
					}],
					renderItem: (item, section, row) => {

						const customStyles = (
							this.props.getItemCustomStyles
								? this.props.getItemCustomStyles(item, section, row)
								: {}
						);

						return ListItemsFactory.create(item.factoryType || this.itemType, {
							testId: this.testId,
							item,
							params: (this.props.itemParams || {}),
							customStyles,
							itemLayoutOptions: this.props.itemLayoutOptions,
							showMenuHandler: this.showItemMenuHandler,
							itemDetailOpenHandler: this.props.itemDetailOpenHandler,
							itemCounterLongClickHandler: this.props.itemCounterLongClickHandler,
							modifyItemsListHandler: this.modifyItemsListHandler,
							hasActions: (
								this.props.itemActions
								&& Array.isArray(this.props.itemActions)
								&& this.props.itemActions.length
							),
							ref: ref => {
								if (item.id)
								{
									const { id } = item;
									this.itemViews[id] = ref;
									this.processDelayedItemActions(id);
								}
							},
						});
					},
					onRefresh: () => {
						this.reloadList();
						BX.postComponentEvent('UI.SimpleList::onRefresh');
					},
					isRefreshing: this.props.isRefreshing,
					onLoadMore: (allItemsLoaded || this.props.items.size < MIN_ROWS_COUNT_FOR_LOAD_MORE) ? null : this.onLoadMoreDummy, // need for show the loader at the bottom of the list ))
					onViewableItemsChanged: items => {
						if (allItemsLoaded)
						{
							return;
						}

						this.visibleIndexes = (items[0].items || null);
						if (this.visibleIndexes)
						{
							const maxIndex = Math.max(...this.visibleIndexes) + 1;

							if (this.props.blockPage * this.props.itemsLoadLimit - maxIndex < MIN_ROWS_COUNT_FOR_LOAD_MORE)
							{
								this.loadMore();
							}
						}
					},
					renderLoadMore: () => {
						return this.renderSkeleton({
							itemParams: this.props.itemParams || {},
							length: 1,
						});
					},
					ref: ref => this.listView = ref,
				});

			}

			if (viewMode === ViewMode.empty)
			{
				container = View(
					{
						style: this.getStyle('container'),
					},
					this.renderEmptyScreen(),
				);
			}

			if (viewMode === ViewMode.loading)
			{
				if (lastViewMode === ViewMode.empty && !this.props.forcedShowSkeleton)
				{
					container = null;
				}
				else if (SkeletonTypes[this.itemType])
				{
					container = View(
						{},
						this.renderSkeleton({
							itemParams: this.props.itemParams || {},
						}),
					);
				}
				else
				{
					container = View(
						{
							style: this.getStyle('container'),
						},
						new LoadingScreenComponent(),
					);
				}
			}

			if (viewMode === ViewMode.forbidden)
			{
				container = View(
					{
						style: this.getStyle('container'),
					},
					this.renderForbiddenScreen(),
				);
			}

			return View(
				{
					style: this.getStyle('wrapper'),
				},
				container,
				this.shouldShowReloadListNotification() && this.renderReloadNotification(),
				this.props.showFloatingButton && this.floatingButton,
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

			if (this.props.allItemsLoaded)
			{
				return this.items.size ? ViewMode.list : ViewMode.empty;
			}

			if (!this.items.size)
			{
				return ViewMode.loading;
			}

			return ViewMode.list;
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

		renderEmptyScreen()
		{
			if (typeof this.props.getEmptyListComponent === 'function')
			{
				return this.props.getEmptyListComponent();
			}

			return new EmptyListComponent({
				text: (
					this.props.isSearchEnabled
						? (this.props.emptySearchText || BX.message('SIMPLELIST_SEARCH_EMPTY'))
						: (this.props.emptyListText || BX.message('SIMPLELIST_LIST_EMPTY'))
				),
				svg: svgImages.empty,
			});
		}

		renderForbiddenScreen()
		{
			return View(
				{},
				Text({
					text: BX.message('SIMPLELIST_FORBIDDEN'),
				}),
			);
		}

		renderImage(props)
		{
			return Image({
				style: props.style,
				resizeMode: 'cover',
				svg: props.svg,
			});
		}

		renderSkeleton(params = {})
		{
			return SkeletonFactory.make(this.itemType, params);
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

						if (ref)
						{
							setTimeout(() => ref && ref.animate({
								duration: 300,
								top: 15,
							}), 50);
						}
					},
				},
				View(
					{
						style: this.getStyle('reloadNotification'),
						onClick: () => {
							Haptics.impactLight();

							this.state.countOfNewElementsFromPull = 0;

							if (this.listView)
							{
								this.listView.scrollToBegin(true);
							}

							let promise = Promise.resolve();

							if (this.reloadNotificationRef)
							{
								promise = promise.then(() => new Promise((resolve) => this.reloadNotificationRef.animate({
									duration: 300,
									top: -50,
								}, resolve)));
							}

							promise.then(() => {
								this.reloadList();
							});
						},
					},
					Image({
						style: {
							width: 16,
							height: 16,
						},
						svg: {
							content: `<svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><defs><style>.cls-1 { fill: #fff; fill-rule: evenodd; }</style></defs><path class="cls-1" d="M8.094 13.558a5.558 5.558 0 1 1 3.414-9.944l-1.466 1.78 5.95.585L14.22.324l-1.146 1.39a7.99 7.99 0 1 0 .926 11.736l-1.744-1.726a5.62 5.62 0 0 1-4.16 1.834z"/></svg>`,
						},
					}),
					Text({
						style: this.getStyle('textNotification'),
						text: this.getNotificationText(),
					}),
				),
			);
		}

		getNotificationText()
		{
			const count = this.state.countOfNewElementsFromPull;

			if (count)
			{
				const text = (this.pull.notificationAddText || BX.message('SIMPLELIST_PULL_NOTIFICATION_ADD'));
				return text.replace('%COUNT%', count);
			}

			return this.pull.notificationUpdateText || BX.message('SIMPLELIST_PULL_NOTIFICATION_UPDATE');
		}

		loadMore()
		{
			this.loadItems(this.props.blockPage + 1, true);
		}

		reloadList()
		{
			this.reloadListHandler();
		}

		processDelayedItemActions(id)
		{
			if (this.delayedItemActions.has(id))
			{
				const data = this.delayedItemActions.get(id);
				this.delayedItemActions.delete(id);
				this.updateItemHandler(data.action, data.id, data.params);
			}
		}

		modifyItemsList(itemsData)
		{
			itemsData.forEach(item => {
				const { id } = item;
				const currentItem = this.items.has(id) && clone(this.items.get(id));

				if (currentItem)
				{
					merge(currentItem.data, item.data);
					this.items.set(id, currentItem);
				}
			});
		}

		getStyle(name)
		{
			return (this.getStyles()[name] || {});
		}

		getStyles()
		{
			return {
				wrapper: {
					flexDirection: 'column',
					flex: 1,
					backgroundColor: '#f5f7f8',
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
					color: '#ffffff',
					fontSize: 14,
					fontWeight: '700',
					marginLeft: 10,
				},
			};
		}
	}

	module.exports = { SimpleList };
});
