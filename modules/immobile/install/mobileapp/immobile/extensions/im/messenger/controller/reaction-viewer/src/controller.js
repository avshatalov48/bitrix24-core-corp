/**
 * @module im/messenger/controller/reaction-viewer/controller
 */
jn.define('im/messenger/controller/reaction-viewer/controller', (require, exports, module) => {
	const { ReactionViewerView } = require('im/messenger/controller/reaction-viewer/view');
	const { core } = require('im/messenger/core');
	const { ReactionService } = require('im/messenger/provider/service');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EventType } = require('im/messenger/const');
	const { ChatAvatar, ChatTitle } = require('im/messenger/lib/element');
	const { Haptics } = require('haptics');

	let isWidgetOpen = false;
	class ReactionViewerController
	{
		/**
		 *
		 * @param {number} messageId
		 * @param {ReactionType} reactionType
		 * @param {LayoutWidget} parentLayout
		 */
		static open(messageId, reactionType, parentLayout = null)
		{
			if (isWidgetOpen)
			{
				return;
			}
			isWidgetOpen = true;

			parentLayout = parentLayout || PageManager;

			const widget = new ReactionViewerController(messageId, reactionType, parentLayout);
			window.widget = widget;

			Haptics.impactMedium();
			void widget.show();
		}

		constructor(messageId, reactionType, parentLayout)
		{
			this.store = core.getStore();
			/** @type {PageManager} */
			this.parentLayout = parentLayout;
			this.reactionService = new ReactionService(messageId);
			/** @type {Map<ReactionType, boolean>} */
			this.hasNextPage = new Map();

			/** @type {Map<ReactionType, ReactionViewerUser[]>} */
			this.users = new Map();

			this.currentReaction = reactionType;
			this.messageId = messageId;
			/** @type {Map<ReactionType, number>} */
			this.counters = new Map();
		}

		async show()
		{
			await this.initUserList(this.currentReaction);
			this.initCounters();

			const layoutWidget = await this.parentLayout.openWidget('layout', {
				backdrop: {
					horizontalSwipeAllowed: false,
					mediumPositionPercent: 45,
					topPosition: 150,
					onlyMediumPosition: false,
					hideNavigationBar: true,
				},
				onReady: (layoutWidget) => {
					layoutWidget.showComponent(new ReactionViewerView({
						users: this.users,
						currentReaction: this.currentReaction,
						counters: this.counters,
						hasNextPage: this.hasNextPage,
						onReactionChange: (reactionType) => {
							return this.onReactionChange(reactionType);
						},
						onLoadMore: (reactionType, lastReactionId) => {
							return this.loadNextPage(reactionType, lastReactionId);
						},
						onReactionUserClick: (userId) => {
							this.openDialog(userId, layoutWidget);
						},
					}));
				},
			});

			layoutWidget.on('onViewRemoved', () => {
				isWidgetOpen = false;
			});
		}

		/**
		 * @private
		 * @param {ReactionType} reactionType
		 * @return Promise<void>
		 */
		async initUserList(reactionType)
		{
			const { reactionViewerUsers, hasNextPage } = await this.reactionService.getReactions(reactionType);

			this.users.set(reactionType, reactionViewerUsers);
			this.hasNextPage.set(reactionType, hasNextPage);
		}

		/**
		 * @private
		 * @return void
		 */
		initCounters()
		{
			const reactionModelState = this.store.getters['messagesModel/reactionsModel/getByMessageId'](this.messageId);

			for (const [reactionType, counter] of Object.entries(reactionModelState.reactionCounters))
			{
				this.counters.set(reactionType, counter);
			}
		}

		/**
		 * @private
		 * @param {ReactionType} reactionType
		 * @param {number} lastId
		 * @return Promise<ReactionServiceGetData>
		 */
		async loadNextPage(reactionType, lastId)
		{
			const { reactionViewerUsers, hasNextPage } = await this.reactionService.getReactions(reactionType, lastId);

			this.users.set(reactionType, reactionViewerUsers);

			this.hasNextPage.set(reactionType, hasNextPage);

			return { reactionViewerUsers, hasNextPage };
		}

		/**
		 * @private
		 * @param {ReactionType} reactionType
		 * @return Promise<ReactionServiceGetData>
		 */
		async onReactionChange(reactionType)
		{
			const { reactionViewerUsers, hasNextPage } = await this.reactionService.getReactions(reactionType);

			this.users.set(reactionType, reactionViewerUsers);
			this.hasNextPage.set(reactionType, hasNextPage);

			return { reactionViewerUsers, hasNextPage };
		}

		/**
		 * @private
		 * @param {number} userId
		 * @param {LayoutWidget} layoutWidget
		 */
		openDialog(userId, layoutWidget)
		{
			const { text, detailText } = (new ChatTitle(userId)).getTitleParams();
			const { imageColor, imageUrl } = (new ChatAvatar(userId)).getTitleParams();
			layoutWidget.close();

			MessengerEmitter.emit(EventType.messenger.openDialog, {
				dialogId: userId,
				dialogTitleParams: {
					name: text,
					description: detailText,
					avatar: imageUrl,
					color: imageColor,
				},
			});
		}
	}

	module.exports = { ReactionViewerController };
});
