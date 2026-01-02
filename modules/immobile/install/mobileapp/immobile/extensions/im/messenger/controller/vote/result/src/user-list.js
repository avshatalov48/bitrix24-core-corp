/**
 * @module im/messenger/controller/vote/result/user-list
 */
jn.define('im/messenger/controller/vote/result/user-list', (require, exports, module) => {
	const { StatefulList } = require('layout/ui/stateful-list');
	const { Color, Component } = require('tokens');
	const { dispatch } = require('statemanager/redux/store');
	const { usersAdded, usersUpserted } = require('statemanager/redux/slices/users');
	const { UserProfile } = require('user-profile');

	const { Loc } = require('im/messenger/loc');
	const { RestMethod } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const {
		UserListItemsFactory,
		USER_LIST_ITEM_TYPE,
	} = require('im/messenger/controller/vote/result/user-list-item-factory');

	class UserList extends LayoutComponent
	{
		/**
		 * @param {string} messageId
		 * @param {string} answerId
		 * @param {string} answerText
		 * @param {PageManager} parentWidget
		 */
		static open({ messageId, answerId, answerText, parentWidget = PageManager })
		{
			void parentWidget
				.openWidget('layout', {
					titleParams: {
						type: 'dialog',
						text: answerText,
					},
					enableNavigationBarBorder: false,
					backgroundColor: Color.bgContentPrimary.toHex(),
				})
				.then((layout) => {
					layout.showComponent(
						new UserList({ messageId, answerId, parentWidget: layout }),
					);
				})
			;
		}

		constructor(props)
		{
			super(props);

			this.statefulList = null;
			this.parentWidget = props.parentWidget;

			/** @type {MessengerCoreStore} */
			this.store = serviceLocator.get('core').getStore();

			this.onItemsLoaded = this.onItemsLoaded.bind(this);
			this.bindRef = this.bindRef.bind(this);
			this.renderEmptyWidget = this.renderEmptyWidget.bind(this);
			this.onBeforeItemsRender = this.onBeforeItemsRender.bind(this);
			this.itemDetailOpenHandler = this.itemDetailOpenHandler.bind(this);
		}

		render()
		{
			const { messageId, answerId } = this.props;

			return new StatefulList({
				testId: 'vote-result-answer-user-list',
				showAirStyle: true,
				useCache: true,
				shouldReloadDynamically: true,
				layout: this.parentWidget,
				actions: {
					loadItems: RestMethod.voteAttachedVoteGetAnswerVoted,
				},
				actionParams: {
					loadItems: {
						moduleId: 'im',
						entityType: 'Bitrix\\Vote\\Attachment\\ImMessageConnector',
						entityId: messageId,
						userForMobileFormat: true,
						answerId,
					},
				},
				actionCallbacks: {
					loadItems: this.onItemsLoaded,
				},
				itemType: USER_LIST_ITEM_TYPE,
				itemFactory: UserListItemsFactory,
				itemsLoadLimit: 20,
				isShowFloatingButton: false,
				ref: this.bindRef,
				getEmptyListComponent: this.renderEmptyWidget,
				onBeforeItemsRender: this.onBeforeItemsRender,
				itemDetailOpenHandler: this.itemDetailOpenHandler,
			});
		}

		/**
		 * @private
		 * @param {object|undefined} responseData
		 * @param {'ajax'|'cache'} context
		 */
		async onItemsLoaded(responseData, context)
		{
			const { items = [] } = responseData || {};
			const isCache = context === 'cache';

			if (items.length === 0)
			{
				return;
			}

			if (isCache)
			{
				void dispatch(usersAdded(items));

				return;
			}

			await dispatch(usersUpserted(items));

			/** @type {VoteModelState} */
			const voteModel = this.store.getters['messagesModel/voteModel/getByMessageId'](this.props.messageId);

			void this.store.dispatch('messagesModel/voteModel/setFromResponse', {
				votes: [
					{
						...voteModel,
						answerVotedUserIds: new Map([
							...voteModel.answerVotedUserIds,
							[String(this.props.answerId), items.slice(0, 3).map((item) => item.id)],
						]),
					},
				],
			});
		}

		bindRef(ref)
		{
			if (ref)
			{
				this.statefulList = ref;
			}
		}

		renderEmptyWidget()
		{
			return Text({
				style: {
					marginHorizontal: Component.paddingLr.toNumber(),
					textAlign: 'center',
					fontSize: 20,
				},
				text: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_RESULT_USER_LIST_EMPTY'),
			});
		}

		onBeforeItemsRender(items)
		{
			return items.map((item, index) => ({ ...item, showBorder: index > 0 }));
		}

		itemDetailOpenHandler(userId)
		{
			void UserProfile.open({
				ownerId: userId,
				parentWidget: this.parentWidget,
			});
		}
	}

	module.exports = { UserList };
});
