/**
 * @module im/messenger/controller/reaction-viewer
 */
jn.define('im/messenger/controller/reaction-viewer', (require, exports, module) => {
	const { clone } = require('utils/object');
	const { ReactionListView } = require('layout/ui/reaction-list');

	const { Loc } = require('im/messenger/loc');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { RestMethod, EventType, ReactionType } = require('im/messenger/const');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');

	let isWidgetOpen = false;
	/**
	 * @class ReactionViewerController
	 */
	class ReactionViewerController
	{
		/**
		 * @param {messageId} props.messageId
		 * @param {DialogId} props.dialogId
		 */
		static async open(props)
		{
			if (isWidgetOpen)
			{
				return;
			}
			isWidgetOpen = true;

			const controllerWidget = new ReactionViewerController(props);

			const viewProps = controllerWidget.buildViewProps();
			controllerWidget.viewWidget = await ReactionListView.open(viewProps);

			controllerWidget.subscribeExternalEvents();
			controllerWidget.unsubscribeExternalEvents();
		}

		/**
		 * @constructor
		 * @param {messageId} props.messageId
		 * @param {DialogId} props.dialogId
		 */
		constructor(props)
		{
			this.lastId = null;
			this.viewWidget = null;
			this.messageId = props.messageId;
			this.dialogId = props.dialogId;
			this.requestLimit = 50;
			this.selectedTab = ReactionListView.constants.ALL;

			this.bindMethods();
		}

		get reactionOrderMap()
		{
			const reactionOrderMap = new Map();
			Object.values(ReactionType).forEach((reaction, index) => {
				reactionOrderMap.set(reaction, index);
			});

			return reactionOrderMap;
		}

		sortReactionsByType(reactions) {
			return reactions.sort((a, b) => {
				return this.reactionOrderMap.get(a.reactionId) - this.reactionOrderMap.get(b.reactionId);
			});
		}

		/**
		 * @return {ReactionListViewProps}
		 */
		buildViewProps()
		{
			const reactionModelState = serviceLocator.get('core').getStore().getters['messagesModel/reactionsModel/getByMessageId'](this.messageId);
			const rawReactions = Object.entries(reactionModelState.reactionCounters)
				.map(([reactionId, counter]) => {
					return { reactionId, userIds: [...Array.from({ length: counter }).keys()] };
				});

			const reactions = this.sortReactionsByType(rawReactions);

			return {
				scrollPanelProps: {
					reactions,
					withRedux: false,
				},
				userListProps: {
					actions: {
						loadItems: RestMethod.imV2ChatMessageReactionTail,
					},
					itemTypeWithRedux: false,
					actionResponseAdapter: this.actionResponseAdapter,
					actionParams: this.buildActionParams,
					onBeforeItemsRender: this.onBeforeItemsRender,
					itemsLoadLimit: this.requestLimit,
				},
			};
		}

		/**
		 * @param {string} selectedTab
		 * @return {object}
		 */
		buildActionParams(selectedTab)
		{
			if (this.selectedTab !== selectedTab)
			{
				this.selectedTab = selectedTab;
				this.lastId = null;
			}

			const filter = { lastId: this.lastId };
			if (selectedTab !== ReactionListView.constants.ALL)
			{
				filter.reaction = selectedTab;
			}

			return {
				loadItems: {
					messageId: this.messageId,
					limit: this.requestLimit,
					order: {
						id: 'ASC',
					},
					filter,
				},
			};
		}

		/**
		 * @param {object[]} items
		 * @param {string} selectedTab
		 * @param {PageManager} parentWidget
		 * @return {object}
		 */
		onBeforeItemsRender(items, selectedTab, parentWidget)
		{
			return items.map((item, index) => ({
				...item,
				showDateCreate: true,
				showBorder: index > 0,
				showIcon: selectedTab === ReactionListView.constants.ALL,
				parentWidget,
				description: item.dateCreate,
			}));
		}

		/**
		 * @param {object} response
		 * @return {object}
		 */
		actionResponseAdapter(response)
		{
			const adaptedResponse = clone(response);
			const reactions = response.data.reactions;
			const userEntities = {};

			adaptedResponse.data.users.forEach((user) => {
				userEntities[user.id] = {
					...user,
					fullName: user.name,
					avatarSize100: user.avatar.replace(currentDomain, ''),
				};
			});

			adaptedResponse.data.items = reactions
				.map((reaction) => ({
					id: reaction.userId,
					reactionId: reaction.reaction.toLowerCase(),
					dateCreate: this.formatDate(reaction.dateCreate),
					user: userEntities[reaction.userId],
				}));

			const sortedReactions = reactions.sort((a, b) => a.reactionId - b.reactionId);
			this.lastId = sortedReactions[sortedReactions.length - 1]?.id ?? null;

			return adaptedResponse;
		}

		/**
		 * @param {string} dateCreate
		 * @return {string}
		 */
		formatDate(dateCreate)
		{
			const date = new Date(dateCreate);

			if (DateFormatter.isToday(dateCreate))
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

		deleteDialogHandler({ dialogId })
		{
			if (String(this.dialogId) !== String(dialogId))
			{
				return;
			}

			this.viewWidget?.close();
		}

		bindMethods()
		{
			this.actionResponseAdapter = this.actionResponseAdapter.bind(this);
			this.buildActionParams = this.buildActionParams.bind(this);
			this.buildViewProps = this.buildViewProps.bind(this);
			this.deleteDialogHandler = this.deleteDialogHandler.bind(this);
			this.unsubscribeExternalEvents = this.unsubscribeExternalEvents.bind(this);
		}

		subscribeExternalEvents()
		{
			BX.addCustomEvent(EventType.dialog.external.delete, this.deleteDialogHandler);
		}

		unsubscribeExternalEvents()
		{
			this.viewWidget?.on(EventType.view.close, () => {
				isWidgetOpen = false;
				BX.removeCustomEvent(EventType.dialog.external.delete, this.deleteDialogHandler);
			});
		}
	}

	module.exports = { ReactionViewerController };
});
