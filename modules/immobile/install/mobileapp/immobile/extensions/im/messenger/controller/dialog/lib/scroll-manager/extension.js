/**
 * @module im/messenger/controller/dialog/lib/scroll-manager
 */
jn.define('im/messenger/controller/dialog/lib/scroll-manager', (require, exports, module) => {
	const { Type } = require('type');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { EventType, AppStatus } = require('im/messenger/const');
	const { VisibilityManager } = require('im/messenger/lib/visibility-manager');
	const { AfterScrollMessagePosition } = require('im/messenger/view/dialog');
	const { getLogger } = require('im/messenger/lib/logger');
	const { Feature } = require('im/messenger/lib/feature');

	const logger = getLogger('dialog--scroll-manager');

	const BOTTOM_MESSAGE_INDEX = 0;

	/**
	 * @class ScrollManager
	 */
	class ScrollManager
	{
		/**
		 * @param {DialogView} view
		 * @param {number || string} dialogId
		 * @param {boolean} skipFirstScrollToFirstUnread It is necessary to disable automatic scrolling
		 * when you click on the push notification that appears after a refresh.
		 */
		constructor({ view, dialogId, skipFirstScrollToFirstUnreadMessages = false })
		{
			this.view = view;
			this.dialogId = dialogId;
			this.visibilityManager = VisibilityManager.getInstance();
			this.store = serviceLocator.get('core').getStore();
			this.needScrollToFirstUnread = true;
			this.skipFirstScrollToFirstUnreadMessages = skipFirstScrollToFirstUnreadMessages;

			this.isScrollToBottomEnable = true;
			this.replyMessageId = null;
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
			logger.log(`${this.constructor.name}.onScrollToBottom params`, params);

			logger.log(`${this.constructor.name}.onScrollToBottom isScrollToBottomEnable`, this.isScrollToBottomEnable);
			if (!this.isScrollToBottomEnable)
			{
				return;
			}

			const isDialogOnScreen = await this.isDialogOnScreen(dialogId);
			logger.log(`${this.constructor.name}.onScrollToBottom isDialogOnScreen`, isDialogOnScreen);
			if (!isDialogOnScreen)
			{
				return;
			}

			const status = serviceLocator.get('core').getStore().getters['applicationModel/getStatus']();
			logger.log(`${this.constructor.name}.onScrollToBottom status`, status);
			if (![AppStatus.running, AppStatus.backgroundSync].includes(status))
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
						this.#processAfterScrollEnd()
							.catch((error) => {
								logger.error(`${this.constructor.name}.#processAfterScrollEnd`, error);
							});
					}, 300);
				},
				AfterScrollMessagePosition.top,
			);
		}

		async onScrollToFirstUnread()
		{
			logger.log(`${this.constructor.name}.onScrollToFirstUnread needScrollToFirstUnread`, this.needScrollToFirstUnread);
			if (!this.needScrollToFirstUnread)
			{
				return;
			}

			const isDialogOnScreen = await this.isDialogOnScreen(this.dialogId);
			logger.log(`${this.constructor.name}.onScrollToFirstUnread isDialogOnScreen`, isDialogOnScreen);
			if (!isDialogOnScreen)
			{
				return;
			}

			if (this.skipFirstScrollToFirstUnreadMessages)
			{
				this.skipFirstScrollToFirstUnreadMessages = false;

				return;
			}

			const dialogModel = this.store.getters['dialoguesModel/getById'](this.dialogId);
			const firstUnreadId = this.store.getters['messagesModel/getFirstUnreadId'](dialogModel.chatId);

			logger.log(`${this.constructor.name}.onScrollToFirstUnread firstUnreadId`, firstUnreadId);
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

			return this.visibilityManager.checkIsDialogVisible({ dialogCode: this.view.dialogCode });
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

			logger.log(`${this.constructor.name}.checkMessageOnScreen`, Boolean(lastReadMessage));

			return Boolean(lastReadMessage);
		}

		/**
		 * @param {number} messageId
		 * @return {boolean}
		 */
		checkMessageIsAbove(messageId)
		{
			let actualMessageList = this.view.getCompletelyVisibleMessages()?.messageList;

			if (!actualMessageList)
			{
				actualMessageList = this.view.isMessageWithIdOnScreen(messageId);
			}

			return Number(actualMessageList?.[0]?.id) > messageId;
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

		/**
		 * @param {DialogId} dialogId
		 * @param {MessageId} quoteMessageId
		 * @param {MessageId} replyMessageId
		 */
		saveReplyMessageId(dialogId, quoteMessageId, replyMessageId)
		{
			if (!Feature.isBackToReplyAvailable)
			{
				return;
			}

			const isAnotherDialog = dialogId !== this.dialogId;
			if (isAnotherDialog)
			{
				return;
			}

			if (this.checkMessageOnScreen(quoteMessageId))
			{
				return;
			}

			logger.log(`${this.constructor.name}.saveReplyMessageId:`, replyMessageId);
			this.replyMessageId = replyMessageId;
		}

		clearReplyMessageId()
		{
			this.replyMessageId = null;
		}

		/**
		 * @return {boolean}
		 */
		shouldBackToReplyMessage()
		{
			if (Type.isNil(this.replyMessageId))
			{
				return false;
			}

			if (this.checkMessageOnScreen(this.replyMessageId))
			{
				this.clearReplyMessageId();

				return false;
			}

			if (this.checkMessageIsAbove(this.replyMessageId))
			{
				this.clearReplyMessageId();

				return false;
			}

			return true;
		}

		async #processAfterScrollEnd()
		{
			this.view.enableReadingEvent();
			this.view.enableShowScrollButton();

			const { messageList } = await this.view.getViewableMessages();

			this.view.readVisibleUnreadMessages(messageList);
		}
	}

	module.exports = { ScrollManager };
});
