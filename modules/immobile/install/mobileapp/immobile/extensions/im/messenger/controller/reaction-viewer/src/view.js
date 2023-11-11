/**
 * @module im/messenger/controller/reaction-viewer/view
 */
jn.define('im/messenger/controller/reaction-viewer/view', (require, exports, module) => {
	const { ReactionItem } = require('im/messenger/controller/reaction-viewer/reaction-item');
	const { ReactionUserList } = require('im/messenger/controller/reaction-viewer/user-list');
	const { ReactionType } = require('im/messenger/const');
	const { ReactionAssets } = require('im/messenger/assets/common');

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
			this.eventEmmiter = new JNEventEmitter();
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				GridView(
					{
						style: {
							height: 57,
							paddingLeft: 10,
							paddingRight: 10,
							borderBottomColor: '#D5D7DB',
							borderBottomWidth: 2,
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
										);

										return;
									}
									this.props.onReactionChange(reactionType).then((data) => {
										this.state.reactionUsers.set(reactionType, data.reactionViewerUsers);
										this.list.setUsers(data.reactionViewerUsers, data.hasNextPage);

										this.state.currentReaction = reactionType;
									});
								},
							});
						},

					},
				),
				this.list = new ReactionUserList({
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
			return [...this.state.visibleReactions.keys()].map((reactionType, index) => {
				return {
					imageUrl: this.state.visibleReactions.get(reactionType),
					reactionType,
					isCurrent: this.state.currentReaction === reactionType,
					counter: this.props.counters.get(reactionType),
					eventEmitter: this.eventEmmiter,
					key: index.toString(),
					type: 'reaction',
				};
			});
		}
	}

	module.exports = { ReactionViewerView };
});
