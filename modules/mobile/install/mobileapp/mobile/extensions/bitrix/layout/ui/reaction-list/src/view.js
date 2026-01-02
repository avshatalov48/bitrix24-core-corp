/**
 * @module layout/ui/reaction-list/src/view
 */
jn.define('layout/ui/reaction-list/src/view', (require, exports, module) => {
	const { UserList } = require('layout/ui/reaction-list/user-list');
	const { ReactionPanel, ReduxReactionPanel } = require('layout/ui/reaction-list/tab-panel');
	const { BottomSheet } = require('bottom-sheet');
	const { Color } = require('tokens');
	const { Type } = require('type');

	class ReactionListView extends LayoutComponent
	{
		static constants = Object.freeze({
			ALL: 'all',
		});

		/**
		 * @constructor
		 * @param {ReactionListViewProps} props
		 */
		constructor(props)
		{
			super(props);

			this.state = {
				selectedReactionTab: ReactionListView.constants.ALL,
			};
		}

		/**
		 * @param {ReactionListViewProps} props
		 */
		static open(props)
		{
			const { backdropMediumPositionPercent = 50 } = props;

			return this.#openBottomSheet(this, props, backdropMediumPositionPercent);
		}

		static #openBottomSheet(ComponentClass, props, mediumPositionPercent)
		{
			return new BottomSheet({
				component: (layout) => {
					return new ComponentClass({
						layout,
						...props,
					});
				},
			})
				.setBackgroundColor(Color.bgSecondary.toHex())
				.enableResizeContent()
				.disableHorizontalSwipe()
				.setMediumPositionPercent(mediumPositionPercent)
				.disableOnlyMediumPosition()
				.open();
		}

		componentDidMount()
		{
			BX.addCustomEvent('ReactionTabsEvent', (activeTab) => {
				this.setState({ selectedReactionTab: activeTab });
			});
		}

		render()
		{
			return View(
				{
					style: {
						display: 'flex',
						flexDirection: 'column',
						alignItems: 'center',
						justifyContent: 'flex-start',
					},
				},
				this.#renderScrollPanel(),
				this.#renderUserList(),
			);
		}

		#renderUserList() {
			const userListProps = this.#buildUserListProps();

			return new UserList(userListProps);
		}

		/**
		* @return {UserListProps}
		*/
		#buildUserListProps()
		{
			const {
				actions,
				actionCallbacks,
				pull,
				itemTypeWithRedux = true,
				actionResponseAdapter,
				onBeforeItemsRender,
				itemsLoadLimit,
			} = this.props.userListProps;
			const actionParams = this.props.userListProps.actionParams(this.state.selectedReactionTab);

			const props = {
				layout: this.props.layout,
				itemTypeWithRedux,
				selectedTab: this.state.selectedReactionTab,
				actions,
				actionParams,
				pull,
				onBeforeItemsRender,
				itemsLoadLimit,
			};

			if (actionCallbacks)
			{
				props.actionCallbacks = actionCallbacks;
			}

			if (Type.isFunction(actionResponseAdapter))
			{
				props.actionResponseAdapter = actionResponseAdapter;
			}

			return props;
		}

		/**
		 * @description
		 * Render tabs with reactions.
		 * To render tabs using redux, you need to pass entityId, entityType and withRedux=true to scrollPanelProps
		 */
		#renderScrollPanel()
		{
			const { entityType, entityId, reactions, withRedux } = this.props.scrollPanelProps;

			if (withRedux)
			{
				return ReduxReactionPanel({
					entityType,
					entityId,
				});
			}

			return ReactionPanel({
				reactions,
				parentWidget: this.props.layout,
			});
		}
	}

	module.exports = {
		ReactionListView,
	};
});
