(() =>
{
	const svgImages = {
		empty: {
			content: '<svg width="95" height="95" viewBox="0 0 95 95" fill="none" xmlns="http://www.w3.org/2000/svg"><circle opacity="0.3" cx="47.1198" cy="47.1198" r="46.1198" stroke="#A8ADB4" stroke-width="2"/><path fill-rule="evenodd" clip-rule="evenodd" d="M47.0423 19.6253C47.0213 19.6316 46.9996 19.6429 46.9773 19.6546L46.9571 19.6649L34.2047 24.7125C33.8851 24.8628 33.7692 25.1882 33.7597 25.5713V41.8236C33.7601 41.8704 33.7637 41.9169 33.7701 41.9626L21.3141 46.8928C20.9946 47.0431 20.8786 47.3685 20.8691 47.7517V64.004C20.8727 64.3673 21.0632 64.7125 21.3141 64.8099L33.9436 69.8043C34.0998 69.8736 34.3045 69.8637 34.4738 69.8175L47.0138 64.8635C47.0191 64.8658 47.0245 64.868 47.0299 64.8701L59.6593 69.8644C59.8156 69.9338 60.0203 69.9239 60.1895 69.8776L72.9323 64.8436C73.1832 64.7412 73.3701 64.3894 73.3678 64.0244V47.9175C73.3749 47.422 73.2518 47.1313 72.9228 46.9794L60.5074 42.0651C60.5307 41.9751 60.5432 41.88 60.5426 41.7839V25.677C60.5497 25.1815 60.4266 24.8908 60.0976 24.7389L47.2791 19.6651C47.1915 19.6205 47.1228 19.6021 47.0423 19.6253ZM47.1181 21.3562L57.7026 25.558L47.1181 29.7333L36.5241 25.5448L47.1181 21.3562ZM59.9433 43.5967L70.5278 47.7984L59.9433 51.9738L49.3494 47.7853L59.9433 43.5967ZM44.8121 47.7383L34.2275 43.5365L23.6336 47.7251L34.2275 51.9136L44.8121 47.7383Z" fill="#A8ADB4"/></svg>',
		},
	}

	const VIEW_MODE_LIST = 'list';
	const VIEW_MODE_EMPTY = 'empty';
	const VIEW_MODE_LOADING = 'loading';
	const VIEW_MODE_FORBIDDEN = 'forbidden';

	const MIN_ROWS_COUNT_FOR_LOAD_MORE = 5;

	const PULL_COMMAND_UPDATED = 'UPDATED';
	const PULL_COMMAND_DELETED = 'DELETED';
	const PULL_COMMAND_ADDED = 'ADDED';

	const ACTION_DELETE = 'delete';
	const ACTION_UPDATE = 'update';

	/**
	 * @class SimpleList
	 */
	class SimpleList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.itemType = this.props.itemType;

			this.state.viewMode = VIEW_MODE_LOADING;
			this.state.isShowReloadListNotification = false;
			this.state.countOfNewElementsFromPull = 0;

			this.itemViews = {};
			this.itemParams = (this.props.itemParams || {});
			this.editable = (this.props.permissions && this.props.permissions.editable) || false;
			this.pull = (this.props.pull || {});

			this.loadItemsHandler = props.loadItemsHandler;
			this.reloadListHandler = props.reloadListHandler;
			this.updateItemHandler = this.updateItemHandler.bind(this);
			this.showItemMenuHandler = this.showItemMenu.bind(this);

			if (this.editable)
			{
				this.createFloatingButton();
			}
		}

		componentDidMount()
		{
			BX.addCustomEvent('DetailCard::onUpdate', () => {
				this.reloadList();
			})

			if (this.pull.moduleId)
			{
				BX.PULL.subscribe({
					moduleId: this.pull.moduleId,
					callback: this.pullCallback.bind(this)
				});
			}
		}

		componentWillReceiveProps(newProps)
		{
			super.componentWillReceiveProps(newProps);

			if (newProps.permissions && newProps.permissions.editable)
			{
				this.editable = newProps.permissions.editable;
				this.createFloatingButton();
			}
		}

		createFloatingButton()
		{
			this.floatingButton = (this.floatingButton || null);

			if (this.floatingButton)
			{
				return;
			}

			if (this.props.floatingButtonClickHandler)
			{
				this.floatingButton = new UI.FloatingButtonComponent({
					onClickHandler: () => {
						this.props.floatingButtonClickHandler();
					}
				});
			}
		}

		pullCallback(data = {})
		{
			if (!this.pull.callback)
			{
				return;
			}

			this.pull.callback(data).then(response => {

				if (response.params.eventName === PULL_COMMAND_UPDATED)
				{
					response.params.items.map(item => {
						this.updateItemFromPull(item.id, item);
					});
				}

				if (response.params.eventName === PULL_COMMAND_DELETED)
				{
					response.params.items.map(item => {
						this.deleteItem(item.id);
					});
				}

				if (response.params.eventName === PULL_COMMAND_ADDED)
				{
					this.addItemsFromPull(response.params.items);
					// response.params.items.map(item => {
					// 	this.addItemHandler(item.id, item);
					// });
				}
			});
		}

		deleteItem(id, params = {})
		{
			this.updateItemHandler(ACTION_DELETE, id, params);
		}

		updateItemFromPull(id, item)
		{
			if (!this.props.items.get(id))
			{
				this.setState({
					isShowReloadListNotification: true,
				});
			}

			this.updateItemHandler(ACTION_UPDATE, id, item);
		}

		updateItemHandler(action, id, params)
		{
			params = (params || {});

			const handler = (action === ACTION_DELETE ? this.props.deleteItemHandler : this.props.updateItemHandler);

			handler(id, params).then(() => {
				this.itemViews[id].animate();
			});
		}

		/**
		 * Maybe we will use items in the feature, maybe not. The future is vague
		 * @param items
		 */
		addItemsFromPull(items)
		{
			this.setState((state, props) => ({
				isShowReloadListNotification: true,
				countOfNewElementsFromPull: state.countOfNewElementsFromPull + 1,
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
				this.itemViews[id].animate();
			});
		}

		showItemMenu(itemId)
		{
			this.menu = new ContextMenu({
				parent: this.props.items.get(itemId),
				parentId: itemId,
				id: 'SimpleList-' + itemId,
				actions: this.props.itemActions,
				updateItemHandler: this.updateItemHandler,
				params: {
					showCancelButton: true,
				}
			});

			this.menu.show();
		}

		loadItems(blockPage, append)
		{
			return this.loadItemsHandler(blockPage, append);
		}

		render()
		{
			let container = null;

			const viewMode = this.getViewMode();

			if (viewMode === VIEW_MODE_LIST)
			{
				const allItemsLoaded = this.props.allItemsLoaded;

				container = ListView({
					style: this.getStyle('container'),
					data: [{
						items: Array.from(this.props.items.values()),
					}],
					renderItem: (item, section, row) => {
						return ListItemsFactory.create(this.itemType, {
							section: section,
							row: row,
							editable: this.editable,
							item: item,
							params: this.itemParams,
							itemLayoutOptions: this.props.itemLayoutOptions,
							showMenuHandler: this.showItemMenuHandler,
							itemDetailOpenHandler: this.props.itemDetailOpenHandler,
							hasActions: (
								this.props.itemActions
								&& Array.isArray(this.props.itemActions)
								&& this.props.itemActions.length
							),
							ref: ref => this.itemViews[item.id] = ref,
						});
					},
					onRefresh: () => {
						this.reloadList();
					},
					isRefreshing: this.props.isRefreshing,
					onLoadMore: allItemsLoaded ? null : () => {}, // need for show the loader at the bottom of the list ))
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
						return ListItemsFactory.create('LoadingElement');
					},
				});

			}

			if (viewMode === VIEW_MODE_EMPTY)
			{
				container = View(
					{
						style: this.getStyle('container'),
					},
					this.renderEmptyScreen()
				);
			}

			if (viewMode === VIEW_MODE_LOADING)
			{
				container = View(
					{
						style: this.getStyle('container'),
					},
					new LoadingScreenComponent()
				);
			}

			if (viewMode === VIEW_MODE_FORBIDDEN)
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
				this.state.isShowReloadListNotification && this.renderReloadNotification(),
				this.floatingButton,
			);
		}

		/**
		 * @returns {string}
		 */
		getViewMode()
		{
			if (
				this.props.settingsIsLoaded
				&& this.props.permissions
				&& BX.type.isBoolean(this.props.permissions.viewable)
				&& !this.props.permissions.viewable
			)
			{
				return VIEW_MODE_FORBIDDEN;
			}

			if (this.props.allItemsLoaded)
			{
				return this.props.items.size ? VIEW_MODE_LIST : VIEW_MODE_EMPTY;
			}

			if (!this.props.items.size)
			{
				return VIEW_MODE_LOADING;
			}

			return VIEW_MODE_LIST;
		}

		renderEmptyScreen()
		{
			return new EmptyListComponent({
				text: (
					this.props.isSearchEnabled
						? (this.props.emptySearchText || BX.message('SIMPLELIST_SEARCH_EMPTY'))
						: (this.props.emptyListText || BX.message('SIMPLELIST_LIST_EMPTY'))
				),
				svg: svgImages.empty
			});
		}

		renderForbiddenScreen()
		{
			return View(
				{

				},
				Text({
					text: 'Forbidden',
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

		renderReloadNotification()
		{
			return View(
				{
					style: this.getStyle('reloadNotificationWrapper'),
				},
				View(
					{
						style: this.getStyle('reloadNotification'),
						onClick: () => {
							this.reloadList();
						}
					},
					Image({
						style: {
							width: 16,
							height: 16,
						},
						svg: {
							content: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><defs><style>.cls-1 { fill: #fff; fill-rule: evenodd; }</style></defs><path class="cls-1" d="M8.094 13.558a5.558 5.558 0 1 1 3.414-9.944l-1.466 1.78 5.95.585L14.22.324l-1.146 1.39a7.99 7.99 0 1 0 .926 11.736l-1.744-1.726a5.62 5.62 0 0 1-4.16 1.834z"/></svg>`,
						},
					}),
					Text({
						style: this.getStyle('textNotification'),
						text: this.getNotificationText(),
					}),
				)
			);
		}

		getNotificationText()
		{
			const count = this.state.countOfNewElementsFromPull;

			if (count)
			{
				const text = (this.pull.notificationAddText || BX.message('SIMPLELIST_PULL_NOTIFICATION_ADD'));
				return text.replace('%COUNT%', count) ;
			}

			return this.pull.notificationUpdateText ?? BX.message('SIMPLELIST_PULL_NOTIFICATION_UPDATE');
		}

		loadMore()
		{
			this.loadItems(this.props.blockPage + 1, true);
		}

		reloadList()
		{
			this.setState({
				isShowReloadListNotification: false,
				countOfNewElementsFromPull: 0,
			})
			this.reloadListHandler();
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
					backgroundColor: '#F0F2F5',
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
					top: 10,
					flexDirection: 'row',
					justifyContent: 'center',
				},
				reloadNotification: {
					paddingHorizontal: 10,
					paddingVertical: 8,
					backgroundColor: '#61C5F2',
					borderRadius: 20,
					opacity: 0.9,
					flexDirection: 'row',
					alignItems: 'center',
				},
				textNotification: {
					color: '#FFFFFF',
					fontSize: 17,
					marginLeft: 6,
				},
			};
		}
	}

	this.SimpleList = SimpleList;
})();
