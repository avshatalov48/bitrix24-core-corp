/**
 * @module layout/ui/reaction-list/user-list
 */
jn.define('layout/ui/reaction-list/user-list', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Component } = require('tokens');
	const { StatefulList } = require('layout/ui/stateful-list');
	const { ListItemType, ListItemsFactory } = require('layout/ui/reaction-list/user-list/src/reaction-items-factory');
	const { UserProfile } = require('user-profile');

	const PullCommand = {
		RATING_VOTE: 'rating_vote',
	};
	const PullTypes = {
		ADD: 'ADD',
		CHANGE: 'CHANGE',
		CANCEL: 'CANCEL',
	};

	class UserList extends LayoutComponent
	{
		static constants = Object.freeze({
			ALL: 'all',
		});

		/**
		 * @constructor
		 * @param {UserListProps} props
		 */
		constructor(props)
		{
			super(props);

			this.statefulList = null;
			this.parentWidget = props.layout ?? layout;
			this.itemDetailOpenHandler = this.itemDetailOpenHandler.bind(this);
		}

		componentDidUpdate(prevProps, prevState)
		{
			if (prevProps !== this.props.selectedTab)
			{
				this.reloadStatefulList();
			}
		}

		reloadStatefulList()
		{
			this.statefulList?.reload();
		}

		render()
		{
			const {
				actions,
				actionParams,
				actionCallbacks,
				itemTypeWithRedux,
				actionResponseAdapter,
				itemsLoadLimit = 20,
			} = this.props;

			return new StatefulList({
				testId: 'reaction-list',
				showAirStyle: true,
				shouldReloadDynamically: true,
				layout: this.parentWidget,
				actionResponseAdapter,
				actions,
				actionParams,
				actionCallbacks,
				getEmptyListComponent: this.renderEmptyWidget,
				itemType: itemTypeWithRedux ? ListItemType.REDUX_REACTION : ListItemType.REACTION,
				itemFactory: ListItemsFactory,
				itemsLoadLimit,
				isShowFloatingButton: false,
				useCache: true,
				pull: this.getPull,
				itemDetailOpenHandler: this.itemDetailOpenHandler,
				onBeforeItemsRender: this.onBeforeItemsRender,
				ref: this.bindRef,
			});
		}

		onBeforeItemsRender = (items) => {
			return this.props.onBeforeItemsRender(items, this.props.selectedTab, this.parentWidget);
		};

		getPull = () => {
			if (!this.props.pull)
			{
				return {};
			}

			return {
				moduleId: this.props.pull.moduleId,
				callback: async (pullParams) => this.props.pull.callback(pullParams, this.statefulList, this.props.selectedTab),
				shouldReloadDynamically: this.props.pull.shouldReloadDynamically,
			};
		};

		renderEmptyWidget()
		{
			return Text({
				style: {
					marginHorizontal: Component.paddingLr.toNumber(),
					textAlign: 'center',
					fontSize: 20,
				},
				text: Loc.getMessage('REACTION_LIST_WIDGET_NO_REACTIONS'),
			});
		}

		itemDetailOpenHandler(userId)
		{
			void UserProfile.open({
				ownerId: userId,
				parentWidget: this.parentWidget,
			});
		}

		bindRef = (ref) => {
			if (ref)
			{
				this.statefulList = ref;
			}
		};
	}

	module.exports = {
		UserList,
		PullCommand,
		PullTypes,
	};
});
