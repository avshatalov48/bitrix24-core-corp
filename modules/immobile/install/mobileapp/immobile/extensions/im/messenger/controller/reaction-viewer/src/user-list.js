/**
 * @module im/messenger/controller/reaction-viewer/user-list
 */
jn.define('im/messenger/controller/reaction-viewer/user-list', (require, exports, module) => {
	const { Theme } = require('im/lib/theme');
	const { Item } = require('im/messenger/lib/ui/base/item');
	const { LoaderItem } = require('im/messenger/lib/ui/base/loader');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');
	const { Loc } = require('loc');
	const {
		ChatTitle,
		ChatAvatar,
	} = require('im/messenger/lib/element');

	/**
	 * @class
	 * @typedef {LayoutComponent<ReactionViewerListProps, ReactionViewerListState>}
	 */
	class ReactionUserList extends LayoutComponent
	{
		/**
		 *
		 * @param {ReactionViewerListProps} props
		 */
		constructor(props)
		{
			super(props);
			this.state = {
				users: this.prepareUserList(props.users),
				currentReaction: props.currentReaction,
			};
			/** @type {typeof ListView} */
			this.listViewRef = null;
			this.loader = new LoaderItem({
				enable: true,
				text: 'Loading',
			});
			this.hasNextPage = props.hasNextPage;
			/**
			 * @private
			 * @type {AllReactions}
			 */
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
							avatarUri: encodeURI(item.avatar),
							avatarColor: item.color,
							avatar: ChatAvatar.createFromDialogId(item.id).getListItemAvatarProps(),
							title: item.name,
							subtitle: this.getUserSubtitle(item),
							style: this.getUserStyle(item.id),
						},
						isCustomStyle: true,
						additionalComponent: this.getUserReaction(item.reaction),
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
			return users
				.sort((user1, user2) => {
					const date1 = new Date(user1.dateCreate);
					const date2 = new Date(user2.dateCreate);

					return date2 - date1;
				})
				.map((user, index) => {
					return {
						...user,
						key: (index + 1).toString(),
						type: 'user',
					};
				})
			;
		}

		/**
		 *
		 * @param {Array<ReactionViewerUser>} users
		 * @param {boolean} hasNextPage
		 * @param {AllReactions} reactionType
		 */
		setUsers(users, hasNextPage, reactionType)
		{
			this.hasNextPage = hasNextPage;
			this.setState({ users: this.prepareUserList(users), currentReaction: reactionType });
		}

		getUserReaction(userReactionType)
		{
			if (this.state.currentReaction !== 'all')
			{
				return null;
			}

			return View(
				{
					style: {
						width: 24,
						height: 24,
						justifyContent: 'center',
						alignItems: 'center',
						marginRight: 20,
					},
				},
				Image({
					style: {
						width: 20,
						height: 20,
					},
					resizeMode: 'contain',
					uri: this.props.assets[userReactionType],
				}),
			);
		}

		/**
		 *
		 * @param {ReactionViewerUser} user
		 */
		getUserSubtitle(user)
		{
			const date = new Date(user.dateCreate);

			if (DateFormatter.isToday(user.dateCreate))
			{
				return Loc.getMessage('IMMOBILE_REACTION_VIEWER_DATE_FORMAT_TODAY')
					.replace('#TIME#', DateFormatter.getShortTime(date))
				;
			}

			return Loc.getMessage('IMMOBILE_REACTION_VIEWER_DATE_FORMAT')
				.replace('#TIME#', DateFormatter.getShortTime(date))
				.replace('#DATE#', DateFormatter.getDayMonth(date))
			;
		}

		/**
		 * @desc get style for item list
		 * @param {number|string} itemId
		 */
		getUserStyle(itemId)
		{
			return {
				parentView: {
					backgroundColor: Theme.colors.bgContentPrimary,
				},
				itemContainer: {
					flexDirection: 'row',
					marginLeft: 18,
					alignItems: 'center',
					height: 54,
				},
				itemInfoContainer: {
					flexDirection: 'row',
					borderBottomWidth: 1,
					borderBottomColor: Theme.colors.bgSeparatorSecondary,
					flex: 1,
					paddingTop: 8.5,
					paddingBottom: 8.5,
					alignItems: 'center',
					height: '100%',
					marginLeft: 12,
				},
				itemInfo: {
					mainContainer: {
						flex: 1,
						overflow: 'hidden',
					},
					title: {
						marginBottom: 1,
						fontSize: 16,
						fontWeight: '400',
						color: ChatTitle.createFromDialogId(itemId).getTitleColor(),
					},
					isYouTitle: {
						marginLeft: 4,
						marginBottom: 1,
						color: Theme.colors.base4,
						fontSize: 14,
					},
					subtitle: {
						color: Theme.colors.base4,
						fontSize: 14,
					},
				},
			};
		}
	}

	module.exports = { ReactionUserList };
});
