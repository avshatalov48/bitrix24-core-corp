/**
 * @module im/messenger/controller/users-read-message-list/view
 */
jn.define('im/messenger/controller/users-read-message-list/view', (require, exports, module) => {
	const { Item } = require('im/messenger/lib/ui/base/item');
	const { LoaderItem } = require('im/messenger/lib/ui/base/loader');

	/**
	 * @class UsersReadMessageListView
	 * @typedef {LayoutComponent<UsersReadMessageListViewProps, UsersReadMessageListViewState>} UsersReadMessageListView
	 */
	class UsersReadMessageListView extends LayoutComponent
	{
		/**
		 * @constructor
		 * @param {UsersReadMessageListViewProps} props
		 */
		constructor(props)
		{
			super(props);
			this.state = {
				itemList: props.itemList,
			};

			this.loader = new LoaderItem({
				enable: true,
				text: '',
			});
		}

		render()
		{
			const platform = Application.getPlatform();

			return View(
				{},
				ListView({
					style: {
						marginTop: 12,
						flexDirection: 'column',
						flex: 1,
					},
					data: [{ items: this.state.itemList }],
					renderItem: (item) => {
						return new Item({
							data: item,
							size: 'M',
							isCustomStyle: true,
							onClick: (event) => {
								this.props.callbacks.onItemClick(event);
							},
						});
					},
					onLoadMore: platform === 'ios' ? this.iosOnLoadMore.bind(this) : this.androidOnLoadMore.bind(this),
					renderLoadMore: platform === 'ios' ? this.iosRenderLoadMore.bind(this) : this.androidRenderLoadMore.bind(this),
				}),
			);
		}

		/**
		 * @desc Update view item state
		 * @param {Array<Object|null>} items
		 * @return void
		 */
		updateItemState(items)
		{
			this.setState({ itemList: items });
		}

		androidOnLoadMore()
		{
			if (this.state.itemList.length > 0)
			{
				this.loader.disable();
			}
		}

		iosOnLoadMore() {}

		iosRenderLoadMore()
		{
			if (this.state.itemList.length > 0)
			{
				return null;
			}

			return this.loader;
		}

		androidRenderLoadMore()
		{
			return this.loader;
		}
	}

	module.exports = { UsersReadMessageListView };
});
