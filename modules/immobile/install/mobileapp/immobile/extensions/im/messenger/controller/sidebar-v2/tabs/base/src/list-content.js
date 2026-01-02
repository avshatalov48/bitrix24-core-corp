/**
 * @module im/messenger/controller/sidebar-v2/tabs/base/src/list-content
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/base/src/list-content', (require, exports, module) => {
	const { UIMenu } = require('layout/ui/menu');
	const { isEqual } = require('utils/object');
	const { assertDefined } = require('utils/validation');

	const { SpinnerLoaderItem } = require('im/messenger/lib/ui/base/loader');
	const { SidebarBaseTabContent } = require('im/messenger/controller/sidebar-v2/tabs/base/src/content');

	/**
	 * @abstract class SidebarBaseTabListContent
	 */
	class SidebarBaseTabListContent extends SidebarBaseTabContent
	{
		constructor(props)
		{
			super(props);

			/** @type {SidebarDataProvider} */
			this.dataProvider = props.dataProvider;
			assertDefined(this.dataProvider, 'dataProvider property is required');

			const { items, hasNextPage } = this.getItemsFromStore();

			this.state = {
				items,
				hasNextPage,
				pending: items === null,
			};

			/** @type {LoaderItem|null} */
			this.listViewPaginationLoaderRef = null;
			this.listViewRef = null;

			/** @type {Map<number, UIMenu>} */
			this.itemContextMenuRegistry = new Map();

			this.bindMethods();
			this.subscribeStoreEvents();
		}

		/**
		 * @protected
		 * @abstract
		 * @return {{ items: SidebarTabListItemModel[]|null, hasNextPage: boolean }}
		 */
		getItemsFromStore()
		{
			return {
				items: null,
				hasNextPage: true,
			};
		}

		/**
		 * @protected
		 */
		onStoreItemsUpdated()
		{
			const { items, hasNextPage } = this.getItemsFromStore();

			if (this.isPending())
			{
				if (items !== null)
				{
					this.setState({ items, hasNextPage, pending: false });
				}

				return;
			}

			if (this.getItems().length === 0)
			{
				this.setState({ items, hasNextPage });

				return;
			}

			this.state.hasNextPage = hasNextPage;
			void this.syncRows(items);
		}

		/**
		 * @protected
		 * @param {SidebarTabListItemModel[]} items
		 * @return {Promise<void>}
		 */
		async syncRows(items = [])
		{
			const existingItems = new Map(this.getItems().map((item) => [item.getId(), item]));
			const newItems = items.filter(
				(newItem) => !existingItems.has(newItem.getId()),
			);
			const removedItems = this.getItems().filter(
				(existingItem) => !items.some((item) => item.getId() === existingItem.getId()),
			);

			const updatedItems = items.filter((newItem) => {
				const existingItem = existingItems.get(newItem.getId());

				return existingItem && !isEqual(existingItem.getData(), newItem.getData());
			});

			if (newItems.length > 0)
			{
				await this.appendRows(newItems);
			}

			if (removedItems.length > 0)
			{
				void this.removeRows(removedItems);
			}

			if (updatedItems.length > 0)
			{
				void this.updateRows(updatedItems);
			}
		}

		/**
		 * @protected
		 * @param {Array<SidebarTabListItemModel>} newItems
		 * @return {Promise<void>}
		 */
		async appendRows(newItems = [])
		{
			const rows = newItems.map((item) => item.toListView());
			const animation = 'none';

			await this.listViewRef.appendRows(rows, animation);
			this.state.items.push(...newItems);
		}

		/**
		 * @protected
		 * @param {Array<SidebarTabListItemModel>} removedItems
		 * @param {string} [animation='none']
		 * @return {Promise<void>}
		 */
		async removeRows(removedItems, animation = 'none')
		{
			return new Promise((resolve) => {
				const rowKeys = new Set(removedItems.map((item) => item.getKey()));

				const afterDelete = () => {
					const nextItems = this.getItems().filter(
						(item) => !rowKeys.has(item.getKey()),
					);
					if (nextItems.length === 0)
					{
						this.setState({ items: [] }, resolve);
					}
					else
					{
						this.state.items = nextItems;
						resolve();
					}
				};

				this.listViewRef.deleteRowsByKeys([...rowKeys], animation, afterDelete);
			});
		}

		/**
		 * @protected
		 * @param {string} [animation='none']
		 * @return {Promise<void>}
		 */
		async deleteAllData(animation = 'none')
		{
			return new Promise((resolve) => {
				const rowKeys = new Set(this.getItems().map((item) => item.getKey()));
				const afterDelete = () => this.setState({ items: [] }, resolve);

				this.listViewRef.deleteRowsByKeys([...rowKeys], animation, afterDelete);
			});
		}

		/**
		 * @param {Array<SidebarTabListItemModel>} items
		 * @param {string} animation
		 * @returns {Promise<void>}
		 */
		async updateRows(items, animation = 'none')
		{
			const rows = items.map((item) => item.toListView());

			this.listViewRef.updateRows(rows, animation);

			const updatedItemsMap = new Map(items.map((item) => [item.getId(), item]));
			this.state.items = this.state.items.map((existingItem) => {
				return updatedItemsMap.get(existingItem.getId()) || existingItem;
			});
		}

		/**
		 * @protected
		 * @return {SidebarTabListItemModel[]}
		 */
		getItems()
		{
			return Array.isArray(this.state.items) ? this.state.items : [];
		}

		/**
		 * @protected
		 * @param {number} id
		 * @return {SidebarTabListItemModel|undefined}
		 */
		getItem(id)
		{
			return this.getItems().find((item) => item.getId() === id);
		}

		/**
		 * @protected
		 * @param {number} id
		 * @param {string} [animation='automatic']
		 * @return {Promise} resolves after all animations and update state.
		 */
		async deleteItemById(id, animation = 'automatic')
		{
			const itemToDelete = this.getItem(id);
			if (!itemToDelete)
			{
				return Promise.resolve();
			}

			return this.removeRows([itemToDelete], animation);
		}

		getListViewData()
		{
			const items = this.getItems().map((item) => item.toListView());

			return [{ items }];
		}

		/**
		 * @protected
		 * @abstract
		 */
		convertToSortedList()
		{}

		/**
		 * @public
		 * @param {boolean} animated
		 * @return {Promise}
		 */
		scrollToBegin(animated = true)
		{
			if (this.listViewRef)
			{
				this.listViewRef.scrollToBegin(animated);

				return Promise.resolve();
			}

			if (this.isEmpty())
			{
				return Promise.resolve();
			}

			return Promise.reject(new Error('Scrollable ref not found'));
		}

		/**
		 * @protected
		 */
		enablePaginationLoader()
		{
			this.listViewPaginationLoaderRef?.enable();
		}

		/**
		 * @protected
		 */
		disablePaginationLoader()
		{
			this.listViewPaginationLoaderRef?.disable();
		}

		/**
		 * @protected
		 */
		onLoadMore()
		{
			const offset = this.getItems().length;

			if (this.isLastPage())
			{
				return;
			}

			this.enablePaginationLoader();

			this.dataProvider.loadPage(offset)
				.then((data) => {
					this.logger.info('onLoadMore', data);
				})
				.catch((error) => {
					this.logger.error('onLoadMore', error);
				})
				.finally(() => {
					this.disablePaginationLoader();
				});
		}

		/**
		 * @protected
		 * @param {object} targetRef
		 * @param {number} itemId
		 * @param {function} actionsFactory
		 */
		showItemContextMenu(targetRef, itemId, actionsFactory)
		{
			if (!targetRef || !itemId)
			{
				return;
			}

			if (!this.itemContextMenuRegistry.has(itemId))
			{
				const actions = actionsFactory();
				this.itemContextMenuRegistry.set(itemId, new UIMenu(actions));
			}

			this.getMenuRefByItemId(itemId).show({ target: targetRef });
		}

		/**
		 * @param itemId
		 * @returns {UIMenu}
		 */
		getMenuRefByItemId(itemId)
		{
			return this.itemContextMenuRegistry.get(itemId);
		}

		renderItemsContainer()
		{
			const {
				onScrollCalculated,
				onScroll,
				onOverscrollTop,
				onOverscrollBottom,
				onScrollBeginDrag,
				onScrollEndDrag,
				testId,
			} = this.props;

			return View(
				{
					testId: `${testId}-items-container`,
					style: {
						flex: 1,
					},
				},
				ListView({
					onScroll,
					onScrollCalculated,
					onOverscrollTop,
					onOverscrollBottom,
					onScrollBeginDrag,
					onScrollEndDrag,
					testId: `${testId}-items-container-grid`,
					ref: (ref) => {
						this.listViewRef = ref;
					},
					style: {
						flexDirection: 'column',
						flex: 1,
					},
					data: this.getListViewData(),
					renderItem: (item) => this.renderItem(item),
					onLoadMore: () => this.onLoadMore(),
					renderLoadMore: () => this.renderPaginationLoader(),
				}),
			);
		}

		/**
		 * @protected
		 * @abstract
		 * @return {{ testId: string, title: string, image?: string }}
		 */
		getEmptyScreenProps()
		{
			return {};
		}

		/**
		 * @protected
		 * @abstract
		 * @param {object} item
		 */
		renderItem(item)
		{}

		/**
		 * @protected
		 */
		renderPaginationLoader()
		{
			if (this.isLastPage())
			{
				return null;
			}

			return new SpinnerLoaderItem({
				testId: 'loader-pagination',
				text: '',
				enable: true,
				ref: (ref) => {
					this.listViewPaginationLoaderRef = ref;
				},
			});
		}
	}

	module.exports = { SidebarBaseTabListContent };
});
