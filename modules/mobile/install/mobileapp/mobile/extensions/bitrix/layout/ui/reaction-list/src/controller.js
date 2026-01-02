/**
 * @module layout/ui/reaction-list/src/controller
 */
jn.define('layout/ui/reaction-list/src/controller', (require, exports, module) => {
	const { ReactionListView } = require('layout/ui/reaction-list/src/view');
	const { PullCommand, PullTypes } = require('layout/ui/reaction-list/user-list');
	const { usersUpserted, usersAdded } = require('statemanager/redux/slices/users');
	const { batchActions } = require('statemanager/redux/batched-actions');
	const { fetchUsersIfNotLoaded } = require('statemanager/redux/slices/users/thunk');
	const store = require('statemanager/redux/store');
	const { dispatch } = store;

	class ReactionListController
	{
		/**
		 * @constructor
		 * @param {ReactionListControllerProps} props
		 */
		constructor(props)
		{
			this.props = props;
			this.#bindMethods();
		}

		/**
		 * @param {ReactionListControllerProps} props
		 */
		static open(props)
		{
			const viewProps = new ReactionListController(props).buildViewProps();
			void ReactionListView.open(viewProps);
		}

		/**
		 * @return {ReactionListViewProps}
		 */
		#buildViewProps()
		{
			const { voteSignToken, entityType, entityId, withRedux, reactions } = this.props;

			/** @type {ScrollPanelProps} */
			const scrollPanelProps = { withRedux };
			if (withRedux)
			{
				scrollPanelProps.entityType = entityType;
				scrollPanelProps.entityId = entityId;
			}
			else
			{
				scrollPanelProps.reactions = reactions;
			}

			return {
				scrollPanelProps,
				userListProps: {
					actions: {
						loadItems: 'mobile.Reaction.getUserList',
					},
					actionParams: (selectedTab) => ({
						loadItems: {
							params: {
								VOTE_SIGN_TOKEN: voteSignToken,
								ENTITY_ID: entityId,
								ENTITY_TYPE: entityType,
								SELECTED_TAB: selectedTab,
							},
						},
					}),
					actionCallbacks: {
						loadItems: this.onItemsLoaded,
					},
					pull: {
						moduleId: 'main',
						callback: this.onPullCallback,
						shouldReloadDynamically: true,
					},
					onBeforeItemsRender: this.onBeforeItemsRender,
				},
			};
		}

		/**
		 * @param {object[]} items
		 * @param {string} selectedTab
		 * @param {PageManager} parentWidget
		 * @return {object}
		 */
		#onBeforeItemsRender(items, selectedTab, parentWidget)
		{
			return items.map((item, index) => ({
				...item,
				showBorder: index > 0,
				showIcon: selectedTab === ReactionListView.constants.ALL,
				parentWidget,
				subtitle: item.user?.workPosition,
			}));
		}

		#onItemsLoaded(responseData, context) {
			const { users } = responseData || {};
			const isCache = context === 'cache';

			const actions = [];

			if (users && users.length > 0)
			{
				actions.push(isCache ? usersAdded(users) : usersUpserted(users));
			}

			if (actions.length > 0)
			{
				dispatch(batchActions(actions));
			}
		}

		async #onPullCallback({ params = {}, command }, statefulList, selectedTab) {
			const {
				ENTITY_ID: entityId,
				ENTITY_TYPE_ID: entityTypeId,
				USER_ID: userId,
				REACTION: reaction,
				TYPE: type,
			} = params ?? {};

			if (!entityId || !entityTypeId || !userId || !reaction || !type || !command)
			{
				throw new Error('Missing required parameters');
			}

			if (
				Number(entityId) === Number(this.props.entityId)
				&& entityTypeId === this.props.entityType
				&& command === PullCommand.RATING_VOTE
			)
			{
				const newReaction = {
					id: Number(userId),
					entityId: Number(entityId),
					entityType: entityTypeId,
					reactionId: reaction,
				};

				await dispatch(fetchUsersIfNotLoaded({ userIds: [Number(userId)] }));

				if (type === PullTypes.CANCEL)
				{
					statefulList.deleteItem([newReaction.id]);
				}
				else if (
					selectedTab === ReactionListView.constants.ALL
					|| selectedTab === reaction
				)
				{
					statefulList.updateItemsData([newReaction]);
				}

				return { params: { eventName: command } };
			}

			return { params: { eventName: null, items: [] } };
		}

		#bindMethods()
		{
			this.buildViewProps = this.#buildViewProps.bind(this);
			this.onItemsLoaded = this.#onItemsLoaded.bind(this);
			this.onPullCallback = this.#onPullCallback.bind(this);
			this.onBeforeItemsRender = this.#onBeforeItemsRender.bind(this);
		}
	}

	module.exports = {
		ReactionListController,
	};
});
