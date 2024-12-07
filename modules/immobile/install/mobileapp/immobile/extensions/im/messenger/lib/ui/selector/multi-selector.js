/**
 * @module im/messenger/lib/ui/selector/multi-selector
 */
jn.define('im/messenger/lib/ui/selector/multi-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Theme } = require('im/lib/theme');
	const { Notification } = require('im/messenger/lib/ui/notification');
	const { Type } = require('type');
	const { Carousel } = require('im/messenger/lib/ui/base/carousel');
	const { MultiSelectedList } = require('im/messenger/lib/ui/selector/multi-selected-list');
	const { SingleSelector } = require('im/messenger/lib/ui/selector/single-selector');
	class MultiSelector extends SingleSelector
	{
		/**
		 *
		 * @param {Object} props
		 * @param {Array} props.itemList
		 * @param {Function} props.onItemSelected
		 * @param {Function} props.onItemUnselected
		 * @param {string} props.searchMode 'inline' or 'overlay'
		 * @param {Function} [props.onSearchItemSelected] with props.searchMode === 'overlay'
		 * @param {Function} [props.onChangeText] with props.searchMode === 'inline'
		 * @param {Function} [props.onSearchShow] with props.searchMode === 'inline'
		 * @param {Function} [props.recentText]
		 * @param {string} [props.carouselSize]
		 * @param {Object} [props.listStyle]
		 * @param {Array} [props.buttons]
		 * @param {Function} [props.ref]
		 * @param {boolean} [props.isSuperEllipseAvatar]
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
				this.createFakeTabs(),
				this.createList(),
			];
		}

		createList()
		{
			return new MultiSelectedList({
				isShadow: this.state.isShadow,
				itemList: this.itemList,
				recentText: this.props.recentText,
				onSelectItem: (itemData) => this.selectItem(itemData),
				onUnselectItem: (itemData) => this.deleteItemInCarousel(itemData),
				isCarouselEnabled: this.getSelectedItems().length > 0,
				style: this.props.listStyle,
				isSuperEllipseAvatar: this.props.isSuperEllipseAvatar,
				ref: (ref) => {
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
				isSuperEllipseAvatar: this.props.isSuperEllipseAvatar,
				onItemSelected: (itemData) => this.unselectItemInList(itemData),
				ref: (ref) => this.carouselRef = ref,
			});
		}

		getCarousel()
		{
			return this.carouselRef;
		}

		createFakeTabs()
		{
			if (!this.props.isFakeTabsEnabled)
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						paddingTop: 12,
						paddingBottom: 12,
						paddingLeft: 18,
						paddingRight: 18,
						backgroundColor: Theme.colors.bgNavigation,
					},
				},
				View(
					{
						style: {
							borderColor: Theme.colors.base3,
							borderWidth: 1.2,
							borderRadius: 8,
							alignItems: 'center',
							paddingRight: 12,
							paddingBottom: 7.5,
							paddingTop: 7.5,
							paddingLeft: 12,
						},
					},
					Text({
						text: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_PARTICIPANTS_RECENT_TAB_NAME'),
						style: {
							fontSize: 16,
							fontWeight: '500',
							color: Theme.colors.base1,
						},
					}),
				),
				View(
					{
						style: {
							marginLeft: 8,
							borderColor: Theme.colors.bgSeparatorPrimary,
							borderWidth: 1.2,
							borderRadius: 8,
							alignItems: 'center',
							paddingRight: 12,
							paddingBottom: 7.5,
							paddingTop: 7.5,
							paddingLeft: 12,
						},
						onClick: () => {
							Notification.showComingSoon();
						},
					},
					Text(
						{
							text:  Loc.getMessage('IMMOBILE_DIALOG_CREATOR_PARTICIPANTS_DEPARTMENT_TAB_NAME'),
							style: {
								fontSize: 16,
								fontWeight: '400',
								color: Theme.colors.base3,
							},
						},
					),
				),
			);
		}

		selectItem(itemData)
		{
			this.itemList.forEach((item) => {
				if (itemData.id === item.data.id)
				{
					item.selected = true;
				}
			});
			this.selectedItems.push(itemData);
			this.getCarousel().addItem(itemData);
			if (Type.isFunction(this.props.onItemSelected))
			{
				this.props.onItemSelected(itemData);
			}
		}

		unselectItemInList(itemData)
		{
			this.itemList.forEach((item) => {
				if (itemData.id === item.data.id)
				{
					item.selected = false;
				}
			});
			this.selectedItems = this.selectedItems.filter((currentItem) => currentItem.id !== itemData.id);
			this.getList().unselectItem(itemData);
			if (Type.isFunction(this.props.onItemUnselected))
			{
				this.props.onItemUnselected();
			}
		}

		deleteItemInCarousel(itemData)
		{
			this.itemList.forEach((item) => {
				if (itemData.id === item.data.id)
				{
					item.selected = false;
				}
			});
			this.selectedItems = this.selectedItems.filter((currentItem) => currentItem.id !== itemData.id);
			this.getCarousel().removeItem(itemData);
			if (Type.isFunction(this.props.onItemUnselected))
			{
				this.props.onItemUnselected();
			}
		}

		findSelectedItem(itemList)
		{
			if (!Array.isArray(itemList) || itemList.length === 0)
			{
				return [];
			}

			return itemList.filter((item) => item.selected && item.selected === true);
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
			items.forEach((item) => {
				const id = item.data.id.toString().replace('user/', '');
				const foundItem = this.selectedItems.find((selectedItem) => selectedItem.id === id);

				if (typeof foundItem !== 'undefined')
				{
					item.selected = true;
				}
			});
			super.setItems(items, withLoader);
		}
	}

	module.exports = { MultiSelector };
});
