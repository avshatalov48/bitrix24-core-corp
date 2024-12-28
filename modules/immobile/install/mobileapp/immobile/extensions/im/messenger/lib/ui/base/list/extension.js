/**
 * @module im/messenger/lib/ui/base/list
 */
jn.define('im/messenger/lib/ui/base/list', (require, exports, module) => {
	const { Item, EmptySearchItem } = require('im/messenger/lib/ui/base/item');
	const { KeyBuilder } = require('im/messenger/lib/ui/base/list/key-builder');
	const { clone } = require('utils/object');
	const { mapPromise } = require('utils/function');
	const { LoaderItem } = require('im/messenger/lib/ui/base/loader');
	const { Type } = require('type');
	const { Theme } = require('im/lib/theme');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('ui--list');

	class MessengerList extends LayoutComponent
	{
		/**
		 *
		 * @param {Object} props
		 * @param {Array} props.itemList
		 * @param {Object} [props.style] custom styles for List
		 * @param {Function} [props.isScrollable]
		 * @param {Function} [props.ref]
		 */
		constructor(props)
		{
			super(props);
			this.state.itemList = props.itemList || [];
			this.keyBuilder = new KeyBuilder(this.state.itemList);
			this.state.itemList = this.creatingKeysForItems(this.state.itemList);
			if (this.props.openWithLoader)
			{
				this.state.itemList.push(this.getLoaderItem(this.props.openingLoaderTitle));
			}

			this.style = props.style ?? this.getDefaultStyle();

			if (props.ref && Type.isFunction(props.ref))
			{
				props.ref(this);
			}
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
						borderTopLeftRadius: 12,
						borderTopRightRadius: 12,
						backgroundColor: Theme.colors.bgContentTertiary,
					},
				},
				this.renderRecentText(),
				ListView({
					style: {
						flex: 1,
						backgroundColor: Theme.colors.bgContentPrimary,
					},
					data: [{ items: this.state.itemList }],
					// isScrollable: this.isScrollable(),
					renderItem: (props) => {
						if (props.type === 'empty')
						{
							return new EmptySearchItem();
						}

						if (props.type === 'loader')
						{
							return new LoaderItem({
								enable: true,
								text: props.title,
							});
						}

						return new Item(
							{
								...props,
								onClick: (itemData) => {
									if (Type.isFunction(this.props.onItemSelected))
									{
										this.props.onItemSelected(itemData);
									}
								},
							},
						);
					},
					ref: (ref) => this.listRef = ref,
				}),
			);
		}

		renderRecentText()
		{
			if (!this.props.recentText)
			{
				return null;
			}

			return View(
				{
					style: {
						backgroundColor: Theme.colors.bgContentPrimary,
						borderTopRightRadius: 12,
						borderTopLeftRadius: 12,
						paddingLeft: 20,
						paddingVertical: 10,
					},
				},
				Text({
					text: this.props.recentText,
					style: {
						color: Theme.colors.base4,
						fontSize: 15,
						fontWeight: 400,
						textStyle: 'normal',
						textAlign: 'start',
					},
				}),
			);
		}

		isScrollable()
		{
			if (this.props.isScrollable && Type.isFunction(this.props.isScrollable))
			{
				return this.props.isScrollable();
			}

			return true;
		}

		/**
		 *
		 * @param {Array<{data:{id:string|number}}>}itemList
		 */
		creatingKeysForItems(itemList)
		{
			return itemList.map((item) => {
				return {
					...item,
					key: this.keyBuilder.getKeyById(item.data.id),
				};
			});
		}

		/**
		 *
		 * @param {Array} itemList
		 * @param {boolean} withLoader
		 */
		setItems(itemList, withLoader = true)
		{
			logger.log('MessengerList: set items', itemList, withLoader);
			itemList = this.creatingKeysForItems(itemList);

			logger.log('MessengerList: items with keys', itemList, this.state.itemList);

			if (this.isEmpty() || itemList.length === 0)
			{
				this.setState({ itemList, withLoader }, () => {
					this.tryAddEmptySearchItem();
				});

				return;
			}

			const deleteItemList = [];
			const currentItemList = [];
			const itemListToAdd = [];
			this.state.itemList.forEach((item) => {
				const foundItem = itemList.find((currentItem) => currentItem.data.id === item.data.id);
				if (typeof foundItem === 'undefined')
				{
					deleteItemList.push(item);

					return;
				}
				currentItemList.push(item);
			});

			itemList.forEach((item) => {
				const foundItem = currentItemList.find((currentItem) => currentItem.data.id === item.data.id);
				if (typeof foundItem === 'undefined')
				{
					itemListToAdd.push(item);
				}
			});

			logger.log('MessengerList: delete items list', deleteItemList);
			const removeItems = () => new Promise((resolve) => {
				const itemKeysToDelete = deleteItemList.map((item) => item.key);
				itemKeysToDelete.push(this.keyBuilder.getKeyById('loader'));

				logger.log('MessengerList.removeItems: itemKeysToDelete', itemKeysToDelete);

				if (itemKeysToDelete.length === 0)
				{
					resolve();

					return;
				}

				this.listRef.deleteRowsByKeys(itemKeysToDelete, 'none', () => {
					for (const item of deleteItemList)
					{
						this.state.itemList = this.state.itemList.filter((removingItem) => item.data.id !== removingItem.data.id);
					}

					resolve();
				});
			});

			const addItems = () => {
				const minKey = this.findMinKey(currentItemList);
				const maxKey = this.findMaxKey(currentItemList);

				const itemsToAppend = [];
				const itemsToPrepend = [];
				const itemsToInsert = [];

				itemListToAdd.forEach((item) => {
					if (Number(item.key) < minKey)
					{
						itemsToPrepend.push(item);

						return;
					}

					if (Number(item.key) > maxKey)
					{
						itemsToAppend.push(item);

						return;
					}

					itemsToInsert.push(item);
				});

				logger.log('MessengerList.addItems addingPacks:', { itemsToAppend, itemsToPrepend, itemsToInsert });

				return this.prependRows(itemsToPrepend)
					.then(() => this.appendRows(itemsToAppend))
					.then(() => this.insertRows(itemsToInsert))
					.then(() => {
						if (withLoader)
						{
							this.listRef.appendRows([this.getLoaderItem()], 'none');
						}
					})
				;
			};

			removeItems()
				.then(() => addItems())
				.then(() => this.tryAddEmptySearchItem())
			;
		}

		isEmpty()
		{
			if (this.state.itemList.length === 0)
			{
				return true;
			}

			if (
				this.state.itemList.length === 1
				&& this.state.itemList[0].type === 'empty'
			)
			{
				return true;
			}

			return false;
		}

		/**
		 *
		 * @param {number || string} key
		 * @param {Array<{key:string}>} itemsList
		 * @return {number}
		 */
		calculateIndexByKey(key, itemsList)
		{
			if (key === 'loader')
			{
				return itemsList.length;
			}

			for (const [i, element] of itemsList.entries())
			{
				if (key < Number(element.key))
				{
					return i;
				}
			}

			return itemsList.length;
		}

		getDefaultStyle()
		{
			return {};
		}

		tryAddEmptySearchItem()
		{
			if (this.state.itemList.length === 0)
			{
				const list = this.creatingKeysForItems([{ data: { id: -1 }, type: 'empty' }]);

				this.setState({ itemList: list });
			}
		}

		getLoaderItem(title = null)
		{
			return {
				data: {
					id: 'loader',
				},
				title,
				key: this.keyBuilder.getKeyById('loader'),
				type: 'loader',
			};
		}

		/**
		 * @private
		 * @param {Array<{key: string}>} itemList
		 * @return {number}
		 */
		findMinKey(itemList)
		{
			if (itemList.length === 0)
			{
				return -1;
			}

			let minKey = Number(itemList[0].key);

			for (const item of itemList)
			{
				const currentKey = Number(item.key);
				if (currentKey < minKey)
				{
					minKey = currentKey;
				}
			}

			return minKey;
		}

		/**
		 * @private
		 * @param {Array<{key: string}>} itemList
		 * @return {number}
		 */
		findMaxKey(itemList)
		{
			if (itemList.length === 0)
			{
				return -1;
			}

			let maxKey = Number(itemList[0].key);

			for (const item of itemList)
			{
				const currentKey = Number(item.key);
				if (currentKey > maxKey)
				{
					maxKey = currentKey;
				}
			}

			return maxKey;
		}

		/**
		 *
		 * @param {Array<Object>}itemList
		 * @param animation
		 * @return {Promise<T>}
		 */
		async prependRows(itemList, animation = 'none')
		{
			return new Promise((resolve) => {
				if (itemList.length === 0)
				{
					resolve();

					return;
				}

				this.listRef.prependRows(itemList, animation)
					.then(() => {
						this.state.itemList = [...itemList, ...this.state.itemList];
						resolve();
					})
				;
			});
		}

		/**
		 *
		 * @param {Array<Object>}itemList
		 * @param animation
		 * @return {Promise<T>}
		 */
		appendRows(itemList, animation = 'none')
		{
			return new Promise((resolve) => {
				if (itemList.length === 0)
				{
					resolve();

					return;
				}

				this.listRef.appendRows(itemList, animation)
					.then(() => {
						this.state.itemList = [...this.state.itemList, ...itemList];
						resolve();
					})
				;
			});
		}

		async insertRows(itemList, animation = 'none')
		{
			const items = clone(this.state.itemList);

			return mapPromise(itemList, (item) => new Promise((resolve) => {
				const index = this.calculateIndexByKey(item.key, items);
				items.splice(index, 0, item);
				this.state.itemList = items;
				this.listRef.insertRows([item], 0, index, 'none')
					.then(() => {
						this.state.itemList = items;

						resolve();
					})
				;
			}));
		}
	}

	const List = MessengerList;
	module.exports = { List };
});
