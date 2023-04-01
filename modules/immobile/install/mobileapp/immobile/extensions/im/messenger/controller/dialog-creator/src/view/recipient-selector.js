/**
 * @module im/messenger/controller/dialog-creator/recipient-selector/view
 */
jn.define('im/messenger/controller/dialog-creator/recipient-selector/view', (require, exports, module) => {

	const { UserSearchController } = require('im/messenger/controller/search');
	const { MultiSelector } = require('im/messenger/lib/ui/selector');

	class RecipientSelectorView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			/** @type MultiSelector */
			this.selectorRef = new MultiSelector(
				{
					itemList: props.userList,
					searchMode: 'inline',
					carouselSize: 'L',
					onSearchShow: () => this.searchShow(),
					onSearchClose: () => this.searchClose(),
					onChangeText: text => this.search(text),
					ref: ref => this.selectorRef = ref,
				}
			);

			this.searchController = new UserSearchController(this.selectorRef);
		}

		render()
		{
			return this.selectorRef;
		}

		search(query)
		{
			if (query === '')
			{
				this.selectorRef.showMainContent();
				return;
			}
			this.searchController.setSearchText(query);
		}

		searchShow()
		{
			this.searchController.open()
		}

		searchClose() {
			this.selectorRef.showMainContent();
			this.selectorRef.disableShadow();
		}

		getSelectedItems()
		{
			return this.selectorRef.getSelectedItems();
		}
	}

	module.exports = { RecipientSelectorView };
});