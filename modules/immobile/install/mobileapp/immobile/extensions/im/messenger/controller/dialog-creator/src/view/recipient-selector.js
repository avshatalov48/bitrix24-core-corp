/**
 * @module im/messenger/controller/dialog-creator/recipient-selector/view
 */
jn.define('im/messenger/controller/dialog-creator/recipient-selector/view', (require, exports, module) => {
	const { Theme } = require('im/lib/theme');
	const { UserSearchController } = require('im/messenger/controller/search');
	const { MultiSelector } = require('im/messenger/lib/ui/selector');
	const { Loc } = require('loc');
	const { MessengerParams } = require('im/messenger/lib/params');

	class RecipientSelectorView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			/** @type MultiSelector */
			this.selectorRef = new MultiSelector(
				{
					recentText: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_RECENT_TEXT'),
					itemList: props.userList,
					searchMode: 'inline',
					carouselSize: 'L',
					onSearchShow: () => this.searchShow(),
					onSearchClose: () => this.searchClose(),
					onChangeText: text => this.search(text),
					isFakeTabsEnabled: Theme.isDesignSystemSupported && MessengerParams.get('HUMAN_RESOURCES_STRUCTURE_AVAILABLE', 'N') === 'Y',
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