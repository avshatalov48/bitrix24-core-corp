/**
 * @module im/messenger/controller/reaction-viewer/user-list
 */
jn.define('im/messenger/controller/reaction-viewer/user-list', (require, exports, module) => {
	const { Item } = require('im/messenger/lib/ui/base/item');
	const { LoaderItem } = require('im/messenger/lib/ui/base/loader');

	/**
	 * @class
	 * @typedef {LayoutComponent<ReactionViewerListProps, ReactionViewerListState>}
	 */
	class ReactionUserList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				users: this.prepareUserList(props.users),
			};
			/** @type {typeof ListView} */
			this.listViewRef = null;
			this.loader = new LoaderItem({
				enable: true,
				text: 'Loading',
			});
			this.hasNextPage = props.hasNextPage;
		}

		render()
		{
			return ListView({
				style: {
					flex: 1,
				},
				data: [{ items: this.state.users }],
				/** @param {ReactionViewerUser} item */
				renderItem: (item) => {
					return new Item({
						data: {
							id: item.id,
							avatarUri: item.avatar,
							avatarColor: item.color,
							title: item.name,
						},
						onClick: (data) => {
							this.props.onReactionUserClick(data.dialogId);
						},
					});
				},
				onLoadMore: (props) => {
					if (!this.hasNextPage)
					{
						if (this.loader.state.enable)
						{
							this.loader.disable();
						}

						return;
					}
					const lastReactionId = this.state.users[this.state.users.length - 1].reactionId;

					// eslint-disable-next-line promise/catch-or-return
					this.props.onLoadMore(lastReactionId)
						.then((data) => {
							this.hasNextPage = data.hasNextPage;
							if (!this.hasNextPage)
							{
								this.loader.disable();
							}
							const additionalUsers = this.prepareUserList(data.reactionViewerUsers);
							this.state.users = [...this.state.users, ...additionalUsers];

							this.listViewRef.appendRows(additionalUsers, 'none');
						}).catch((reason) => {})
					;
				},
				renderLoadMore: () => {
					return this.loader;
				},
				ref: (ref) => this.listViewRef = ref,
			});
		}

		/**
		 * @private
		 * @param {ReactionViewerUser[]} users
		 * @return {ReactionViewerListItem[]}
		 */
		prepareUserList(users)
		{
			return users.map((user) => {
				return {
					...user,
					key: user.reactionId.toString(),
					type: 'user',
				};
			});
		}

		setUsers(users, hasNextPage)
		{
			this.hasNextPage = hasNextPage;
			this.setState({ users: this.prepareUserList(users) });
		}
	}

	module.exports = { ReactionUserList };
});
