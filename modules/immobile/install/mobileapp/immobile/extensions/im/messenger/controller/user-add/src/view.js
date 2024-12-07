/**
 * @module im/messenger/controller/user-add/view
 */
jn.define('im/messenger/controller/user-add/view', (require, exports, module) => {
	const { MultiSelector } = require('im/messenger/lib/ui/selector');
	const { UserSearchController, CopilotSearchController } = require('im/messenger/controller/search');
	const { WidgetHeaderButton } = require('layout/ui/widget-header-button');

	class UserAddView extends LayoutComponent
	{
		constructor(props) {
			super(props);

			this.selector = new MultiSelector({
				itemList: this.props.itemList,
				searchMode: 'inline',
				recentText: this.props.recentText,
				onItemSelected: (item) => this.onItemSelected(item),
				onItemUnselected: () => this.onItemUnselected(),
				onSearchShow: () => this.onSearchShow(),
				onSearchClose: () => this.onSearchClose(),
				onChangeText: (text) => this.search(text),
				carouselSize: 'L',
				isSuperEllipseAvatar: this.props.isSuperEllipseAvatar,
			});

			this.saveButton = new WidgetHeaderButton({
				widget: props.widget,
				text: props.textRightBtn,
				loadingText: props.loadingTextRightBtn,
				disabled: true,
				onClick: () => props.callback.onClickRightBtn(),
			});

			if (props.isCopilotDialog)
			{
				this.searchController = new CopilotSearchController(this.selector);
			}
			else
			{
				this.searchController = new UserSearchController(this.selector);
			}
		}

		render() {
			return View(
				{
					style: {
						flex: 1,
					},
				},
				this.selector,
			);
		}

		search(query)
		{
			if (query === '')
			{
				this.selector.showMainContent();

				return;
			}
			this.searchController.setSearchText(query);
		}

		onSearchShow()
		{
			this.searchController.open();
		}

		onSearchClose() {
			this.selector.showMainContent();
			this.selector.disableShadow();
		}

		/**
		 * @desc Handle selected item
		 * @param {object} itemData
		 * @private
		 */
		onItemSelected(itemData)
		{
			this.checkItem(itemData);
			this.saveButton.enable(this.isItemsClick());
		}

		/**
		 * @desc Handle unselected item
		 * @private
		 */
		onItemUnselected()
		{
			this.saveButton.enable(this.isItemsClick());
		}

		/**
		 * @desc Check item on items (if item from search than push to items)
		 * @param {object} itemData
		 * @private
		 */
		checkItem(itemData)
		{
			const items = this.props.itemList;
			const isItems = items.find((item) => {
				return item.data.id === itemData.id;
			});

			if (!isItems)
			{
				items.push({ data: itemData, selected: true });
			}
		}

		isItemsClick()
		{
			let selected = false;
			const items = this.props.itemList;
			for (const item of items)
			{
				if (item.selected)
				{
					selected = true;
					break;
				}
			}

			return selected;
		}
	}

	module.exports = { UserAddView };
});
