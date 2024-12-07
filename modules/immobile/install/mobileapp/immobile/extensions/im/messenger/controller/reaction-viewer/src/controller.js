/**
 * @module im/messenger/controller/reaction-viewer/controller
 */
jn.define('im/messenger/controller/reaction-viewer/controller', (require, exports, module) => {
	const { ReactionViewerView } = require('im/messenger/controller/reaction-viewer/view');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { ReactionService } = require('im/messenger/provider/service');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EventType, ComponentCode } = require('im/messenger/const');
	const { ChatAvatar, ChatTitle } = require('im/messenger/lib/element');
	const { Haptics } = require('haptics');

	let isWidgetOpen = false;
	class ReactionViewerController
	{
		/**
		 *
		 * @param {number} messageId
		 * @param {ReactionType} reactionType
		 * @param {DialogId} dialogId
		 * @param {LayoutWidget} parentLayout
		 */
		static open({
			messageId,
			reactionType,
			dialogId,
			parentLayout = PageManager,
		})
		{
			if (isWidgetOpen)
			{
				return;
			}
			isWidgetOpen = true;

			const widget = new ReactionViewerController({
				messageId,
				reactionType,
				dialogId,
				parentLayout,
			});

			Haptics.impactMedium();
			void widget.show();
		}

		constructor({ messageId, reactionType, dialogId, parentLayout })
		{
			this.store = serviceLocator.get('core').getStore();
			/** @type {PageManager} */
			this.parentLayout = parentLayout;
			this.reactionService = new ReactionService(messageId);
			/** @type {Map<AllReactions, boolean>} */
			this.hasNextPage = new Map();

			/** @type {Map<AllReactions, ReactionViewerUser[]>} */
			this.users = new Map();

			this.currentReaction = 'all';
			this.messageId = messageId;
			this.dialogId = dialogId;
			/** @type {Map<AllReactions, number>} */
			this.counters = new Map();
			this.layoutWidget = null;
		}

		async show()
		{
			this.bindMethods();
			this.subscribeExternalEvents();
			await this.initUserList(this.currentReaction);
			this.initCounters();

			const layoutWidget = await this.parentLayout.openWidget('layout', {
				backdrop: {
					horizontalSwipeAllowed: false,
					mediumPositionPercent: 50,
					topPosition: 150,
					onlyMediumPosition: false,
					hideNavigationBar: true,
				},
			});

			this.layoutWidget = layoutWidget;

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

			layoutWidget.on(EventType.view.close, () => {
				isWidgetOpen = false;
				this.unsubscribeExternalEvents();
			});
		}

		bindMethods()
		{
			this.deleteDialogHandler = this.deleteDialogHandler.bind(this);
		}

		subscribeExternalEvents()
		{
			BX.addCustomEvent(EventType.dialog.external.delete, this.deleteDialogHandler);
		}

		unsubscribeExternalEvents()
		{
			BX.removeCustomEvent(EventType.dialog.external.delete, this.deleteDialogHandler);
		}

		/**
		 * @private
		 * @param {AllReactions} reactionType
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

			let summary = 0;
			for (const [reactionType, counter] of Object.entries(reactionModelState.reactionCounters))
			{
				summary += counter;
				this.counters.set(reactionType, counter);
			}

			this.counters.set('all', summary);
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

			/** @type {Array<ReactionViewerUser>} */
			const users = [...(this.users.get(reactionType) ?? []), ...reactionViewerUsers];
			this.users.set(reactionType, users);

			this.hasNextPage.set(reactionType, hasNextPage);

			return { users, hasNextPage };
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
			}, ComponentCode.imMessenger);
		}

		deleteDialogHandler({ dialogId })
		{
			if (String(this.dialogId) !== String(dialogId))
			{
				return;
			}

			this.layoutWidget.close();
		}
	}

	module.exports = { ReactionViewerController };
});
