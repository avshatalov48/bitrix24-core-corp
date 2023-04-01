/**
 * @module im/messenger/controller/user-add/view
 */
jn.define('im/messenger/controller/user-add/view', (require, exports, module) => {

	const { MultiSelector } = require('im/messenger/lib/ui/selector');
	const { UserSearchController } = require('im/messenger/controller/search');
	class UserAddView extends LayoutComponent
	{
		constructor(props) {
			super(props);

			this.selector = new MultiSelector({
				itemList: this.props.itemList,
				searchMode: 'inline',
				onSearchShow: () => this.searchShow(),
				onSearchClose: () => this.searchClose(),
				onChangeText: text => this.search(text),
			});

			this.searchController = new UserSearchController(this.selector);
		}


		render() {
			return View (
				{},
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

		searchShow()
		{
			this.searchController.open();
		}

		searchClose() {
			this.selector.showMainContent();
			this.selector.disableShadow();
		}
	}

	module.exports = { UserAddView };
});