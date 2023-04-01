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

			this.style = props.style ? props.style : this.getDefaultStyle();

			this.loader = new LoaderItem({
				enable: false,
			});

			if(props.ref && Type.isFunction(props.ref))
			{
				props.ref(this);
			}
		}

		render()
		{
			return ListView({
				style: {
					flex: 1,
				},
				data: [{items: this.state.itemList}],
				//isScrollable: this.isScrollable(),
				renderItem: (props) => {
					if (props.type === 'empty')
					{
						return new EmptySearchItem();
					}
					return new Item(
						{
							...props,
							onClick: itemData => this.props.onItemSelected(itemData)
						}
					);
				},
				onLoadMore: () =>
				{
				},
				renderLoadMore: () => {
					return this.loader;
				},
				ref: ref => this.listRef = ref
			});
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
			return itemList.map(item => {
				return {
					...item,
					key: this.keyBuilder.getKeyById(item.data.id)
				};
			})
		}

		/**
		 *
		 * @param {Array} itemList
		 * @param {boolean} withLoader
		 */
		setItems(itemList, withLoader = true)
		{
			itemList = this.creatingKeysForItems(itemList);
			if(this.isEmpty() || itemList.length === 0)
			{
				this.setState({itemList: itemList}, ()=> {
					this.tryAddEmptySearchItem();
				});

				return;
			}

			if (this.loader.isEnable() !== withLoader)
			{
				this.loader.setState({enable: withLoader})
			}

			const deleteItemList = [];
			const currentItemList = [];
			const itemListToAdd = [];
			this.state.itemList.forEach(item => {
				const foundItem = itemList.find(currentItem => currentItem.data.id === item.data.id);
				if (typeof foundItem === 'undefined')
				{
					deleteItemList.push(item);
					return;
				}
				currentItemList.push(item);
			});

			itemList.forEach(item => {
				const foundItem = currentItemList.find(currentItem => currentItem.data.id === item.data.id);
				if (typeof foundItem === 'undefined')
				{
					itemListToAdd.push(item);
				}
			});

			const removeItems = () => mapPromise(deleteItemList, (item) => new Promise(resolve => {
				const {section, index} = this.listRef.getElementPosition(item.key);

				this.listRef.deleteRow(section, index, 'none', () => {
					this.state.itemList = this.state.itemList.filter(removingItem => item.data.id !== removingItem.data.id);

					resolve();
				});
			}));

			const addItems = () => {
				const items = clone(this.state.itemList);

				return mapPromise(itemListToAdd, (item) => new Promise(resolve => {
					const index = this.calculateIndexByKey(item.key, items);
					items.splice(index, 0, item);
					this.state.itemList = items;
					this.listRef.insertRows([item], 0, index, 'none').then(() => {

						this.state.itemList = items;

						resolve();
					});
				}));
			}

			removeItems().then(() => addItems()).then(() => {
				this.tryAddEmptySearchItem();
			});
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
		 * @param {number} key
		 * @param {Array<{key:string}>} itemsList
		 * @return {number}
		 */
		calculateIndexByKey(key, itemsList)
		{
			for (let i = 0; i < itemsList.length; i++)
			{
				if (key < Number(itemsList[i].key))
				{
					return i;
				}
			}

			return itemsList.length;
		}
		getDefaultStyle()
		{
			return {


			};
		}

		tryAddEmptySearchItem()
		{
			if (this.state.itemList.length === 0)
			{
				const list = this.creatingKeysForItems([{data: {id: -1},type: 'empty'}])

				this.setState({itemList: list})
			}
		}

	}

	const List = MessengerList
	module.exports = { List };
});