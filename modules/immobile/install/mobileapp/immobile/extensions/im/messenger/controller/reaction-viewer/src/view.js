/**
 * @module im/messenger/controller/reaction-viewer/view
 */
jn.define('im/messenger/controller/reaction-viewer/view', (require, exports, module) => {
	const { ReactionItem } = require('im/messenger/controller/reaction-viewer/reaction-item');
	const { ReactionUserList } = require('im/messenger/controller/reaction-viewer/user-list');
	const { ReactionType } = require('im/messenger/const');
	const { ReactionAssets } = require('im/messenger/assets/common');
	const { Theme } = require('im/lib/theme');

	/**
	 * @class ReactionViewerView
	 * @typedef {LayoutComponent<ReactionViewerProps, ReactionViewerState>} ReactionViewerView
	 */
	class ReactionViewerView extends LayoutComponent
	{
		/**
		 // * @param {ReactionViewerProps} props
		 */
		constructor(props)
		{
			super(props);

			this.state = {
				currentReaction: props.currentReaction,
				visibleReactions: this.prepareAssets(),
				reactionUsers: this.props.users,
				hasNextPage: this.props.hasNextPage,
			};
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						backgroundColor: Theme.colors.bgNavigation,
					},
				},
				GridView(
					{
						style: {
							height: 46,
							paddingLeft: 10,
							paddingRight: 10,
							backgroundColor: Theme.colors.bgNavigation,
						},
						params: {
							orientation: 'horizontal',
							rows: 1,
							scrollType: 'paging',
						},
						isScrollable: true,
						data: [{ items: this.prepareReactionItems() }],
						ref: (ref) => this.gridViewRef = ref,
						renderItem: (renderItem) => {
							return new ReactionItem({
								...renderItem,
								onClick: (reactionType) => {
									if (this.state.reactionUsers.has(reactionType))
									{
										this.list.setUsers(
											this.state.reactionUsers.get(reactionType),
											this.state.hasNextPage.get(reactionType),
											reactionType
										);

										return;
									}
									this.props.onReactionChange(reactionType).then((data) => {
										this.state.reactionUsers.set(reactionType, data.reactionViewerUsers);
										this.list.setUsers(data.reactionViewerUsers, data.hasNextPage, reactionType);

										this.state.currentReaction = reactionType;
									});
								},
							});
						},

					},
				),
				View(
					{
						style: {
							borderRadius: 12,
							flex: 1,
							backgroundColor: Theme.colors.bgContentPrimary,
							paddingTop: 12,
						},
					},
					this.list = new ReactionUserList({
						assets: this.getAssets(),
						currentReaction: this.state.currentReaction,
						users: this.state.reactionUsers.get(this.state.currentReaction),
						hasNextPage: this.state.hasNextPage.get(this.state.currentReaction),
						onReactionUserClick: this.props.onReactionUserClick,
						onLoadMore: (lastId) => {
							return new Promise((resolve) => {
								this.props.onLoadMore(this.state.currentReaction, lastId).then((data) => {
									const { reactionViewerUsers, hasNextPage } = data;

									const currentUsers = this.state.reactionUsers.get(this.state.currentReaction);
									this.state.reactionUsers.set(this.state.currentReaction, [...currentUsers, ...reactionViewerUsers]);
									this.state.hasNextPage.set(this.state.currentReaction, hasNextPage);

									resolve(data);
								});
							});
						},
					}),
				),
			);
		}

		appendAdditionalUsers(users, hasNextPage)
		{
			this.list.appendUsers(users, hasNextPage);
		}

		/**
		 * @protected
		 */
		getAssets() {
			return {
				[ReactionType.like]: ReactionAssets.getImageUrl(ReactionType.like),
				[ReactionType.kiss]: ReactionAssets.getImageUrl(ReactionType.kiss),
				[ReactionType.laugh]: ReactionAssets.getImageUrl(ReactionType.laugh),
				[ReactionType.wonder]: ReactionAssets.getImageUrl(ReactionType.wonder),
				[ReactionType.cry]: ReactionAssets.getImageUrl(ReactionType.cry),
				[ReactionType.angry]: ReactionAssets.getImageUrl(ReactionType.angry),
				[ReactionType.facepalm]: ReactionAssets.getImageUrl(ReactionType.facepalm),
			};
		}

		/**
		 * @protected
		 * @return {Map<ReactionType, string>}
		 */
		prepareAssets()
		{
			/** @type {Map<ReactionType, string>} */
			const result = new Map();

			for (const [reactionType, imageUrl] of Object.entries(this.getAssets()))
			{
				if (!result.has(reactionType) && this.props.counters.has(reactionType))
				{
					result.set(reactionType, imageUrl);
				}
			}

			return result;
		}

		/**
		 * @protected
		 * @return {Map<ReactionType, ReactionViewerUser[]>}
		 */
		prepareUsers()
		{
			/** @type {Map<ReactionType, ReactionViewerUser[]>} */
			const result = new Map();
			this.props.users.forEach((user) => {
				if (!result.has(user.reaction))
				{
					result.set(user.reaction, [{ ...user, key: user.id.toString(), type: 'user' }]);

					return;
				}

				result.get(user.reaction).push({ ...user, key: user.id.toString(), type: 'user' });
			});

			return result;
		}

		/**
		 * @protected
		 */
		prepareReactionItems()
		{
			const result = [];
			result.push({
				imageUrl: '',
				reactionType: 'all',
				isCurrent: this.state.currentReaction === 'all',
				counter: this.props.counters.get('all'),
				eventEmitter: this.eventEmmiter,
				key: '0',
				type: 'reaction',
			});

			[...this.state.visibleReactions.keys()].forEach((reactionType, index) => {
				result.push({
					imageUrl: this.state.visibleReactions.get(reactionType),
					reactionType,
					isCurrent: this.state.currentReaction === reactionType,
					counter: this.props.counters.get(reactionType),
					key: (index + 1).toString(),
					type: 'reaction',
				});
			});

			return result;
		}
	}

	module.exports = { ReactionViewerView };
});
