/**
 * @module im/messenger/controller/dialog/scroll-manager
 */
jn.define('im/messenger/controller/dialog/scroll-manager', (require, exports, module) => {
	const { VisibilityManager } = require('im/messenger/lib/visibility-manager');
	const { EventType, AppStatus } = require('im/messenger/const');
	const { core } = require('im/messenger/core');
	const { AfterScrollMessagePosition } = require('im/messenger/view/dialog');

	const BOTTOM_MESSAGE_INDEX = 0;

	/**
	 * @class ScrollManager
	 */
	class ScrollManager
	{
		/**
		 * @param {DialogView} view
		 * @param {number || string} dialogId
		 */
		constructor({ view, dialogId })
		{
			this.view = view;
			this.dialogId = dialogId;
			this.visibilityManager = VisibilityManager.getInstance();
			this.store = core.getStore();
			this.needScrollToFirstUnread = false;

			this.isScrollToBottomEnable = true;
			this.scrollToBottomHandler = this.onScrollToBottom.bind(this);
			this.scrollToFirstUnreadHandler = this.onScrollToFirstUnread.bind(this);
			this.disableScrollToBottomHandler = this.disableScrollToBottom.bind(this);
			this.appPausedHandler = this.onAppPaused.bind(this);
			this.scrollBeginHandler = this.onScrollBegin.bind(this);
		}

		setChatId(chatId)
		{
			this.chatId = chatId;
		}

		subscribeEvents()
		{
			BX.addCustomEvent(EventType.dialog.external.scrollToBottom, this.scrollToBottomHandler);
			BX.addCustomEvent(EventType.dialog.external.scrollToFirstUnread, this.scrollToFirstUnreadHandler);
			BX.addCustomEvent(EventType.dialog.external.disableScrollToBottom, this.disableScrollToBottomHandler);
			BX.addCustomEvent(EventType.app.paused, this.appPausedHandler);

			this.view.on(EventType.dialog.scrollBegin, this.scrollBeginHandler);
		}

		unsubscribeEvents()
		{
			BX.removeCustomEvent(EventType.dialog.external.scrollToBottom, this.scrollToBottomHandler);
			BX.removeCustomEvent(EventType.dialog.external.scrollToFirstUnread, this.scrollToFirstUnreadHandler);
			BX.removeCustomEvent(EventType.dialog.external.disableScrollToBottom, this.disableScrollToBottomHandler);
			BX.removeCustomEvent(EventType.app.paused, this.appPausedHandler);

			this.view.off(EventType.dialog.scrollBegin, this.scrollBeginHandler);
		}

		/**
		 * @param {ScrollToBottomEvent} params
		 * @return {Promise<void>}
		 */
		async onScrollToBottom(params)
		{
			const {
				dialogId,
				withAnimation = true,
				force = false,
				prevMessageId = null,
			} = params;

			if (!this.isScrollToBottomEnable)
			{
				return;
			}

			const isDialogOnScreen = await this.isDialogOnScreen(dialogId);
			if (!isDialogOnScreen)
			{
				return;
			}

			const status = core.getStore().getters['applicationModel/getStatus']();

			if (status !== AppStatus.running)
			{
				return;
			}

			if (force === false && !this.checkMessageOnScreen(prevMessageId))
			{
				return;
			}

			this.view.disableShowScrollButton();
			this.view.disableReadingEvent();

			this.view.scrollToMessageByIndex(
				0,
				withAnimation,
				() => {
					setTimeout(() => {
						this.view.enableReadingEvent();
						this.view.enableShowScrollButton();

						const { messageList } = this.view.getViewableMessages();

						this.view.readVisibleUnreadMessages(messageList);
					}, 300);
				},
				AfterScrollMessagePosition.top,
			);
		}

		async onScrollToFirstUnread()
		{
			if (!this.needScrollToFirstUnread)
			{
				return;
			}

			const isDialogOnScreen = await this.isDialogOnScreen(this.dialogId);
			if (!isDialogOnScreen)
			{
				return;
			}

			const dialogModel = this.store.getters['dialoguesModel/getById'](this.dialogId);
			const firstUnreadId = this.store.getters['messagesModel/getFirstUnreadId'](dialogModel.chatId);

			if (!firstUnreadId)
			{
				return;
			}

			this.view.disableShowScrollButton();
			this.view.scrollToMessageById(
				firstUnreadId,
				true,
				() => {
					setTimeout(() => {
						if (firstUnreadId !== dialogModel.lastMessageId)
						{
							this.view.showScrollToNewMessagesButton();
						}

						this.view.enableShowScrollButton();
						this.enableScrollToBottom();
					}, 300);
				},
				AfterScrollMessagePosition.center,
			);
		}

		disableScrollToBottom()
		{
			this.isScrollToBottomEnable = false;
		}

		enableScrollToBottom()
		{
			this.isScrollToBottomEnable = true;
		}

		/**
		 *
		 * @param {number || string} dialogId
		 * @return {Promise<boolean>}
		 */
		async isDialogOnScreen(dialogId)
		{
			if (this.dialogId.toString() !== dialogId.toString())
			{
				return false;
			}

			return this.visibilityManager.checkIsDialogVisible(this.dialogId);
		}

		checkMessageOnScreen(messageId)
		{
			const messageList = this.view.getCompletelyVisibleMessages()?.messageList;

			if (!messageList)
			{
				return this.view.isMessageWithIdOnScreen(messageId);
			}

			const lastReadMessage = messageList.find((message) => {
				return Number(message.id) === Number(messageId);
			});

			return Boolean(lastReadMessage);
		}

		onAppPaused()
		{
			const { indexList } = this.view.getCompletelyVisibleMessages();

			this.needScrollToFirstUnread = indexList.includes(BOTTOM_MESSAGE_INDEX);
		}

		onScrollBegin()
		{
			if (this.needScrollToFirstUnread)
			{
				this.needScrollToFirstUnread = false;
			}
		}
	}

	module.exports = { ScrollManager };
});
