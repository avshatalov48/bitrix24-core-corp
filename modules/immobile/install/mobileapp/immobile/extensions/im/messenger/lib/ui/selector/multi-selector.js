/**
 * @module im/messenger/lib/ui/selector/multi-selector
 */
jn.define('im/messenger/lib/ui/selector/multi-selector', (require, exports, module) => {

	const { Carousel} = require('im/messenger/lib/ui/base/carousel');
	const { MultiSelectedList } = require('im/messenger/lib/ui/selector/multi-selected-list');
	const { SingleSelector } = require('im/messenger/lib/ui/selector/single-selector');
	class MultiSelector extends SingleSelector
	{

		/**
		 *
		 * @param {Object} props
		 * @param {Array} props.itemList
		 * @param {Function} props.onItemSelected
		 * @param {string} props.searchMode 'inline' or 'overlay'
		 * @param {Function} [props.onSearchItemSelected] with props.searchMode === 'overlay'
		 * @param {Function} [props.onChangeText] with props.searchMode === 'inline'
		 * @param {Function} [props.onSearchShow] with props.searchMode === 'inline'
		 * @param {string} [props.carouselSize]
		 * @param {Object} [props.listStyle]
		 * @param {Array} [props.buttons]
		 * @param {Function} [props.ref]
		 */
		constructor(props)
		{
			super(props);
			this.selectedItems = this.findSelectedItem(props.itemList);

			this.carouselRef = null;
		}

		getMainContent()
		{
			return [
				this.createButtonSection(),
				this.createCarousel(),
				this.createList(),
			];
		}

		createList()
		{
			return new MultiSelectedList({
				isShadow: this.state.isShadow,
				itemList: this.itemList,
				onSelectItem: (itemData) => this.selectItem(itemData),
				onUnselectItem: (itemData) => this.deleteItemInCarousel(itemData),
				isCarouselEnabled: this.getSelectedItems().length > 0,
				style: this.props.listStyle,
				ref: ref => {
					this.listRef = ref;

					if (this.props.searchMode === 'inline')
					{
						this.searchWrapperRef = ref;
					}
				},
			});
		}

		createCarousel()
		{
			return new Carousel({
				isShadow: this.state.isShadow,
				itemList: this.selectedItems,
				size: this.props.carouselSize,
				onItemSelected: itemData => this.unselectItemInList(itemData),
				ref: ref => this.carouselRef = ref,
			});
		}

		getCarousel()
		{
			return this.carouselRef;
		}

		selectItem(itemData)
		{
			this.itemList.forEach(item => {
				if (itemData.id === item.data.id)
				{
					item.selected = true;
				}
			});
			this.selectedItems.push(itemData);
			this.getCarousel().addItem(itemData);
		}

		unselectItemInList(itemData)
		{
			this.itemList.forEach(item => {
				if (itemData.id === item.data.id)
				{
					item.selected = false;
				}
			});
			this.selectedItems = this.selectedItems.filter(currentItem => currentItem.id !== itemData.id);
			this.getList().unselectItem(itemData);
		}

		deleteItemInCarousel(itemData)
		{
			this.itemList.forEach(item => {
				if (itemData.id === item.data.id)
				{
					item.selected = false;
				}
			});
			this.selectedItems = this.selectedItems.filter(currentItem => currentItem.id !== itemData.id);
			this.getCarousel().removeItem(itemData);
		}

		findSelectedItem(itemList)
		{
			if (!Array.isArray(itemList) || itemList.length === 0)
			{
				return [];
			}

			return itemList.filter(item => item.selected && item.selected === true);
		}

		getSelectedItems()
		{
			if (!Array.isArray(this.selectedItems))
			{
				return this.findSelectedItem(this.props.itemList);
			}

			return this.selectedItems;
		}

		setItems(items, withLoader = false)
		{
			items.forEach(item => {
				const id = item.data.id.toString().replace('user/', '');
				const foundItem = this.selectedItems.find(selectedItem => selectedItem.id === id);

				if(typeof foundItem !== 'undefined')
				{
					item.selected = true;
				}
			})
			super.setItems(items, withLoader);
		}
	}

	module.exports = { MultiSelector };
});