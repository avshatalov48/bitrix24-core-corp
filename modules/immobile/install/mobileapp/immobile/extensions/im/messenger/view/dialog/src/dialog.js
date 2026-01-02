/**
 * @module im/messenger/view/dialog/dialog
 */
jn.define('im/messenger/view/dialog/dialog', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Type } = require('type');
	const { Loc } = require('im/messenger/loc');
	const { isModuleInstalled } = require('module');

	const { Icon } = require('ui-system/blocks/icon');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { View } = require('im/messenger/view/base');
	const { EventType, MessageType, MessageIdType, AttachPickerId, EventFilterType } = require('im/messenger/const');
	const { VisibilityManager } = require('im/messenger/lib/visibility-manager');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { getLogger } = require('im/messenger/lib/logger');
	const { Feature } = require('im/messenger/lib/feature');
	const { AnalyticsService } = require('im/messenger/provider/services/analytics');
	const { UnreadSeparatorMessage } = require('im/messenger/lib/element/dialog');

	const { StateManager } = require('im/messenger/view/lib/state-manager');
	const { DialogTextField } = require('im/messenger/view/dialog/text-field');
	const { DialogMentionPanel } = require('im/messenger/view/dialog/mention-panel');
	const { DialogPinPanel } = require('im/messenger/view/dialog/pin-panel');
	const { DialogCommentsButton } = require('im/messenger/view/dialog/comments-button');
	const { DialogActionPanel } = require('im/messenger/view/dialog/action-panel');
	const { DialogStatusField } = require('im/messenger/view/dialog/status-field');
	const { DialogJoinButton } = require('im/messenger/view/dialog/join-button');
	const { DialogSelector } = require('im/messenger/view/dialog/selector');
	const { DialogRestrictions } = require('im/messenger/view/dialog/restrictions');
	const { DialogNotifyPanel } = require('im/messenger/view/dialog/notify-panel');
	const { Theme } = require('im/lib/theme');

	const AfterScrollMessagePosition = Object.freeze({
		top: 'top',
		center: 'center',
		bottom: 'bottom',
	});

	const InputQuoteType = Object.freeze({
		reply: 'reply',
		forward: 'forward',
		edit: 'edit',
	});

	const messagesCountToPageLoad = 20;
	const logger = getLogger('dialog--view');

	const doNothing = () => {};

	/**
	 * @class DialogView
	 */
	class DialogView extends View
	{
		constructor(options = {})
		{
			super(options);

			this.setCustomEvents([
				EventType.dialog.messageRead,
				EventType.dialog.loadTopPage,
				EventType.dialog.loadBottomPage,
				EventType.dialog.visibleMessagesChanged,
			]);

			/**
			 * @private
			 * @type {string | number}
			 */
			this.dialogId = options.dialogId;
			/**
			 * @private
			 * @type {string | number}
			 */
			this.dialogCode = options.dialogCode;
			/**
			 * @private
			 * @type {number}
			 */
			this.chatId = options.chatId;
			/**
			 * @private
			 * @type {number}
			 */
			this.readingMessageId = options.lastReadId;
			this.messageIdToScrollAfterSet = options.lastReadId;
			this.delayedMessageListToRead = [];

			this.onShowScrollToNewMessageButton = options.onShowScrollToNewMessageButton ?? doNothing;
			this.onHideScrollToNewMessageButton = options.onHideScrollToNewMessageButton ?? doNothing;
			/**
			 * @private
			 * @type {VisibilityManager}
			 */
			this.visibilityManager = VisibilityManager.getInstance();
			this.resetState();
			this.subscribeEvents();

			this.initStateManager();
		}

		initStateManager()
		{
			const state = {
				floatingText: {
					isShow: false,
					text: null,
				},
				rightButtons: null,
				leftButtons: null,
				title: null,
				avatar: null,
				isShowTopLoader: false,
				isShowMessageListLoader: false,
				isShowWelcomeScreen: false,
				background: null,
			};

			this.stateManager = new StateManager(state);
		}

		/**
		 * @return {AvailableEventCollection}
		 */
		getAvailableEvents()
		{
			return {
				[EventFilterType.selectMessagesMode]: [
					EventType.dialog.topReached,
					EventType.dialog.bottomReached,
					EventType.dialog.loadBottomPage,
					EventType.dialog.loadTopPage,
					EventType.dialog.messageRead,
					EventType.dialog.visibleMessagesChanged,
					EventType.dialog.scrollToNewMessages,
				],
			};
		}

		resetState()
		{
			/**
			 * @type {Array<Message>}
			 */
			this.messageList = [];
			/**
			 * @private
			 * @type {Array<Message>}
			 */
			this.messageListOnScreen = [];
			/**
			 * @private
			 * @type {Array<number>}
			 */
			this.messageIndexListOnScreen = [];
			/**
			 * @private
			 * @type {boolean}
			 */
			this.shouldEmitMessageRead = false;
			/**
			 * @private
			 * @type {boolean}
			 */
			this.shouldShowScrollToNewMessagesButton = true;
			/**
			 * @private
			 * @type {boolean}
			 */
			this.isScrollToNewMessageButtonVisible = false;
			/**
			 * @private
			 * @type {boolean}
			 */
			this.unreadSeparatorAdded = false;
			/**
			 * @private
			 * @type {boolean}
			 */
			this.scrollToFirstUnreadCompleted = false;

			this.resetStatusFieldState();
			this.resetContextOptions();
		}

		/* region nested objects */
		/**
		 * @return {DialogTextField}
		 */
		get textField()
		{
			this.textFieldView ??= new DialogTextField(this.ui.textField, this.eventFilter);

			return this.textFieldView;
		}

		/**
		 * @return {DialogMentionPanel}
		 */
		get mentionPanel()
		{
			this.mentionPanelView ??= new DialogMentionPanel(this.ui.mentionPanel, this.eventFilter);

			return this.mentionPanelView;
		}

		/**
		 * @return {DialogPinPanel}
		 */
		get pinPanel()
		{
			this.pinPanelView ??= new DialogPinPanel(this.ui.pinPanel, this.eventFilter);

			return this.pinPanelView;
		}

		/**
		 * @return {DialogCommentsButton}
		 */
		get commentsButton()
		{
			this.commentsButtonView ??= new DialogCommentsButton(this.ui.commentsButton, this.eventFilter);

			return this.commentsButtonView;
		}

		/**
		 * @return {DialogActionPanel}
		 */
		get actionPanel()
		{
			this.actionPanelView ??= new DialogActionPanel(this.ui.actionPanel, this.eventFilter);

			return this.actionPanelView;
		}

		/**
		 * @return {DialogStatusField}
		 */
		get statusField()
		{
			this.statusFieldView ??= new DialogStatusField(this.ui.statusField, this.eventFilter);

			return this.statusFieldView;
		}

		/**
		 * @return {DialogJoinButton}
		 */
		get chatJoinButton()
		{
			this.joinButtonView ??= new DialogJoinButton(this.ui.chatJoinButton, this.eventFilter);

			return this.joinButtonView;
		}

		/**
		 * @return {DialogSelector}
		 */
		get selector()
		{
			this.selectorView ??= new DialogSelector(this.ui.selector, this.eventFilter);

			return this.selectorView;
		}

		/**
		 * @return {DialogRestrictions}
		 */
		get restrictions()
		{
			this.restrictionsView ??= new DialogRestrictions(this.ui.restrictions, this.eventFilter);

			return this.restrictionsView;
		}

		/**
		 * @return {DialogNotifyPanel}
		 */
		get notifyPanel()
		{
			this.notifyPanelView ??= new DialogNotifyPanel(this.ui.notifyPanel, this.eventFilter);

			return this.notifyPanelView;
		}

		/* endregion nested objects */

		/* region Events */
		/**
		 * @desc subscribe to events
		 */
		subscribeEvents()
		{
			this.subscribeCommonEvents();
			this.subscribeReachedEvents();
		}

		subscribeCommonEvents()
		{
			this.ui
				.on(EventType.dialog.viewAreaMessagesChanged, this.onViewAreaMessagesChanged.bind(this))
				.on(EventType.view.show, this.onViewShown.bind(this))
			;
		}

		subscribeReachedEvents()
		{
			this.ui
				.on(EventType.dialog.topReached, this.onTopReached.bind(this))
				.on(EventType.dialog.bottomReached, this.onBottomReached.bind(this));
		}

		/**
		 * @param {Array<number>} indexList
		 * @param {Array<Message>}messageList
		 */
		onViewAreaMessagesChanged(indexList, messageList)
		{
			if (this.scrollToFirstUnreadCompleted)
			{
				if (indexList.includes(0))
				{
					this.hideScrollToNewMessagesButton();
				}
				else
				{
					this.showScrollToNewMessagesButton();
				}
			}

			this.messageListOnScreen = messageList;
			this.messageIndexListOnScreen = indexList;

			if (this.checkNeedToLoadTopPage())
			{
				this.emitCustomEvent(EventType.dialog.loadTopPage);
			}

			if (this.checkNeedToLoadBottomPage())
			{
				this.emitCustomEvent(EventType.dialog.loadBottomPage);
			}

			// eslint-disable-next-line promise/catch-or-return
			this.visibilityManager.checkIsDialogVisible({ dialogCode: this.dialogCode }).then((isDialogVisible) => {
				if (!isDialogVisible)
				{
					return;
				}

				this.emitCustomEvent(
					EventType.dialog.visibleMessagesChanged,
					{
						indexList,
						messageList,
					},
				);

				if (this.shouldEmitMessageRead)
				{
					this.readVisibleUnreadMessages(messageList);
				}
			});
		}

		/**
		 * @param {Array<Message>} messageList
		 */
		readVisibleUnreadMessages(messageList)
		{
			const unreadMessages = messageList.filter((message) => {
				const messageId = Number(message.id);
				const isRealMessage = Type.isNumber(messageId);
				if (!isRealMessage)
				{
					return false;
				}

				const modelMessage = serviceLocator.get('core').getStore().getters['messagesModel/getById'](messageId);

				if (this.chatId && modelMessage.chatId !== this.chatId)
				{
					// filter fake messages: need for comment chats
					return false;
				}

				return modelMessage.viewed === false;
			});

			if (unreadMessages.length === 0)
			{
				return;
			}

			this.readingMessageId = Number(unreadMessages[0].id);
			const readingMessageIds = unreadMessages.map((message) => message.id);

			this.emitCustomEvent(EventType.dialog.messageRead, readingMessageIds);
		}

		onTopReached()
		{
			this.emitCustomEvent(EventType.dialog.loadTopPage);
		}

		onBottomReached()
		{
			this.emitCustomEvent(EventType.dialog.loadBottomPage);
		}

		onViewShown()
		{
			if (!this.shouldEmitMessageRead)
			{
				return;
			}

			logger.log(`${this.constructor.name}.onViewShown`, this.messageListOnScreen);
			this.readVisibleUnreadMessages(this.messageListOnScreen);
		}

		/* endregion Events */

		/* region Message */
		/**
		 * @return {Promise<{messageList: Array<Message>, indexList: Array<number>}>}
		 */
		getViewableMessages = async () => {
			const {
				indexList,
				messageList,
			} = await this.ui.getViewableMessagesAsync();

			return {
				indexList,
				messageList,
			};
		}

		/**
		 * @return {{messageList: Array<Message>, indexList: Array<number>} || null}
		 */
		getCompletelyVisibleMessages()
		{
			if (this.ui.getCompletelyVisibleMessages)
			{
				return this.ui.getCompletelyVisibleMessages();
			}

			return null;
		}

		resetContextOptions()
		{
			/**
			 * @private
			 * @type {object}
			 */
			this.setMessagesOptions = {
				targetMessageId: null,
				targetMessagePosition: null,
				withMessageHighlight: null,
			};
		}

		/**
		 * @param {MessagesContextOptions.targetMessageId} targetMessageId
		 * @param {MessagesContextOptions.withMessageHighlight} withMessageHighlight
		 * @param {MessagesContextOptions.targetMessagePosition} targetMessagePosition
		 */
		setContextOptions(
			targetMessageId,
			withMessageHighlight = false,
			targetMessagePosition = AfterScrollMessagePosition.top,
		)
		{
			this.setMessagesOptions = {
				targetMessageId: String(targetMessageId),
				targetMessagePosition,
				withMessageHighlight,
			};
		}

		/**
		 * @param {Array<Message>} messageList
		 * @param {MessagesContextOptions} messagesOptions
		 */
		async setMessages(messageList, messagesOptions)
		{
			if (Type.isArrayFilled(messageList))
			{
				this.hideWelcomeScreen();
			}

			const options = this.getSetMessagesContextOptions(messagesOptions);
			this.unreadSeparatorAdded = messageList.some((message) => message.id === UnreadSeparatorMessage.getDefaultId());
			logger.log(`${this.constructor.name}.setMessages:`, messageList, options);
			await this.ui.setMessages(this.#prepareMessagesToDialogWidgetItem(messageList), options);
			this.setMessageList(messageList);

			if (options.targetMessageId && options.withMessageHighlight)
			{
				this.highlightMessageById(options.targetMessageId);
			}

			this.afterSetMessages();
			this.resetContextOptions();
		}

		/**
		 * @private
		 */
		afterSetMessages()
		{
			this.scrollToFirstUnreadCompleted = true;

			if (this.unreadSeparatorAdded)
			{
				logger.log(`${this.constructor.name}: scroll to the first unread completed`);
			}
			else
			{
				logger.log(`${this.constructor.name}: scrolling to the first unread is not required, everything is read`);
			}

			if (this.checkNeedToLoadTopPage())
			{
				this.emitCustomEvent(EventType.dialog.loadTopPage);
			}

			if (this.checkNeedToLoadBottomPage())
			{
				this.emitCustomEvent(EventType.dialog.loadBottomPage);
			}

			// TODO: refactor temporary hack. Without it, extra messages are read, somehow connected with the scroll
			setTimeout(() => {
				this.#processReadMessagesAfterSet()
					.catch((error) => {
						logger.error(`${this.constructor.name}.#processReadMessagesAfterSet`, error);
					});
			}, 200);
		}

		readDelayedMessageList()
		{
			if (!Type.isArrayFilled(this.delayedMessageListToRead))
			{
				return;
			}

			logger.log('DialogView.readDelayedMessageList: ', this.delayedMessageListToRead);

			this.readVisibleUnreadMessages(this.delayedMessageListToRead);
			this.shouldEmitMessageRead = true;

			this.delayedMessageListToRead = [];
		}

		/**
		 * @param {Array<Message>} messageList
		 */
		async pushMessages(messageList)
		{
			if (Type.isArrayFilled(messageList))
			{
				this.hideWelcomeScreen();
			}

			await this.ui.pushMessages(this.#prepareMessagesToDialogWidgetItem(messageList));
			this.pushMessageList(messageList);
		}

		/**
		 * @param {Array<Message>} messageList
		 */
		async addMessages(messageList)
		{
			if (Type.isArrayFilled(messageList))
			{
				this.hideWelcomeScreen();
			}

			this.disableShowScrollButton();

			await this.ui.addMessages(this.#prepareMessagesToDialogWidgetItem(messageList));
			this.addMessageList(messageList);
			this.visibilityManager.checkIsDialogVisible({ dialogCode: this.dialogCode })
				.then((isDialogVisible) => {
					if (isDialogVisible)
					{
						return;
					}

					this.enableShowScrollButton();
					this.showScrollToNewMessagesButton();
				})
				.catch((error) => logger.error(
					`${this.constructor.name}.addMessages.checkIsDialogVisible catch:`,
					error,
				));

			this.enableShowScrollButton();
		}

		/**
		 *
		 * @param {string} pointedId
		 * @param {Array<Message>} messageList
		 * @param {'below'} position
		 */
		async insertMessages(pointedId, messageList, position)
		{
			if (position !== 'below')
			{
				logger.error(`${this.constructor.name}.insertMessages error: inserting messages works correctly in JS only with the below position`);

				return;
			}
			await this.ui.insertMessages(pointedId, this.#prepareMessagesToDialogWidgetItem(messageList), position);

			this.insertMessageList(pointedId, messageList);
		}

		getTopMessageOnScreen()
		{
			const topMessage = this.messageListOnScreen[this.messageListOnScreen.length - 1];

			return topMessage || {};
		}

		/**
		 * @param {number} id
		 * @param {Message} message
		 * @param {string | null} section
		 */
		async updateMessageById(id, message, section = null)
		{
			if (section)
			{
				await this.ui.updateMessageById(id, this.#prepareMessageToDialogWidgetItem(message), [section]);
			}
			else
			{
				await this.ui.updateMessageById(id, this.#prepareMessageToDialogWidgetItem(message));
			}

			this.updateMessageListById(id, message);
		}

		/**
		 * @param {string} messageId
		 * @return {number}
		 */
		getPlayingTime(messageId)
		{
			try
			{
				return this.ui.getPlayingTime?.(messageId) || 0;
			}
			catch (error)
			{
				logger.error(`${this.constructor.name}.getPlayingTime catch:`, error);

				return 0;
			}
		}

		/**
		 * @desc update messages
		 * @param {object} messages
		 */
		async updateMessagesByIds(messages)
		{
			await this.ui.updateMessagesByIds(messages);
		}

		/**
		 * @param {Array<number>} idList
		 * @return {Promise<boolean>}
		 */
		async removeMessagesByIds(idList)
		{
			if (!Type.isArrayFilled(idList))
			{
				logger.warn(`${this.constructor.name}.removeMessagesByIds: idList is empty`);

				return false;
			}

			const removeIdList = idList.map((id) => String(id));
			await this.ui.removeMessagesByIds(removeIdList);
			this.removeMessageListByIds(removeIdList);
			if (this.messageList.length === 0)
			{
				this.showWelcomeScreen();
			}

			return true;
		}

		/**
		 * @param {number} id
		 * @return {boolean}
		 */
		isMessageWithIdOnScreen(id)
		{
			const messageIndex = this.messageListOnScreen
				.findIndex((message) => String(message.id) === String(id))
			;

			return messageIndex !== -1;
		}

		/**
		 * @param {number} index
		 * @return {boolean}
		 */
		isMessageWithIndexOnScreen(index)
		{
			return this.messageIndexListOnScreen.includes(index);
		}

		/**
		 * @param {Message} message
		 * @param {MessageMenu} menu
		 */
		showMenuForMessage(message, menu)
		{
			this.ui.showMenuForMessage(message, menu);
		}

		/* endregion Message */

		/* region ViewMessageList */
		/**
		 * @param {array} messageList
		 */
		setMessageList(messageList)
		{
			this.messageList = Type.isArrayFilled(messageList) ? messageList : [];
		}

		/**
		 * @param {array} messageList
		 */
		addMessageList(messageList)
		{
			this.messageList.unshift(...messageList);
		}

		/**
		 * @param {array} messageList
		 */
		pushMessageList(messageList)
		{
			this.messageList.push(...messageList);
		}

		/**
		 * @param {string} pointedId
		 * @param {Array<Message>} messageList
		 */
		insertMessageList(pointedId, messageList)
		{
			const index = this.messageList.findIndex((message) => message.id === pointedId);

			if (index === -1)
			{
				logger.error(
					`${this.constructor.name}.insertMessageList error: message with pointedId not found in messageList`,
					pointedId,
					messageList,
				);

				return;
			}

			this.messageList.splice(index, 0, ...messageList);

			logger.warn(
				`${this.constructor.name}.insertMessages: after inserting`,
				pointedId,
				messageList,
				[...this.messageList],
			);
		}

		/**
		 * @param {string} id
		 * @param {object} message
		 */
		updateMessageListById(id, message)
		{
			this.messageList = this.messageList.map((viewMessage) => {
				if (viewMessage.id === id)
				{
					return message;
				}

				return viewMessage;
			});
		}

		/**
		 * @param {array<string>} idList
		 */
		removeMessageListByIds(idList)
		{
			this.messageList = this.messageList.filter((message) => {
				return !idList.includes(message.id);
			});
		}

		/**
		 * @param {string} id
		 */
		getMessageAboveSelectedById(id)
		{
			const selectedId = String(id);
			const messageIndex = this.messageList.findIndex((message) => {
				return message.id === selectedId;
			});

			if (messageIndex === -1)
			{
				return null;
			}

			const aboveMessage = this.messageList[messageIndex + 1];
			if (aboveMessage)
			{
				return aboveMessage;
			}

			return null;
		}

		getMessagesCount()
		{
			return this.messageList.length;
		}

		getTopMessage()
		{
			if (!Type.isArrayFilled(this.messageList))
			{
				return null;
			}

			return this.messageList[this.messageList.length - 1];
		}

		getBottomMessage()
		{
			if (!Type.isArrayFilled(this.messageList))
			{
				return null;
			}

			return this.messageList[0];
		}

		/**
		 * @return {boolean}
		 */
		isHasPlanLimitMessage()
		{
			if (!Type.isArrayFilled(this.messageList))
			{
				return false;
			}

			return this.messageList.some((message) => {
				return (message.id === MessageIdType.planLimitBanner) && message.type === MessageType.banner;
			});
		}

		/**
		 * @param {MessageId} messageId
		 * @returns {Promise<boolean>}
		 */
		isAllContentCache(messageId)
		{
			return this.ui.isAllContentCache(messageId);
		}

		/**
		 * @param {MessageId} messageId
		 * @returns {Promise<boolean>}
		 */
		downloadFilesForMessage(messageId)
		{
			return this.ui.downloadFilesForMessage(messageId);
		}

		/**
		 * @param {MessageId} messageId
		 * @returns {Promise<string[]>}
		 */
		getLocalPathListForMessage(messageId)
		{
			return this.ui.getLocalPathListForMessage(messageId);
		}

		/* endregion ViewMessageList */

		/* region Input */

		clearInput()
		{
			this.textField.clear();
		}

		/**
		 * @param {string} text
		 */
		setInputPlaceholder(text)
		{
			this.textField.setPlaceholder(text);
		}

		/**
		 * @param {object} message
		 * @param {string} type
		 * @param {boolean} [openKeyboard=true]
		 * @param {string?} [title=null] (56+ API)
		 * @param {string?} [text=null] (56+ API)
		 */
		setInputQuote(message, type, openKeyboard = true, title = null, text = null)
		{
			if (InputQuoteType[type])
			{
				if (title && text)
				{
					this.textField.setQuote(message, type, openKeyboard, title, text);
				}

				this.textField.setQuote(message, type, openKeyboard);
			}
			else
			{
				this.textField.setQuote(message);
			}
		}

		enableAlwaysSendButtonMode(enable)
		{
			this.textField.enableAlwaysSendButtonMode(enable);
		}

		removeInputQuote()
		{
			return new Promise((resolve) => {
				this.textField.once(EventType.dialog.textField.quoteRemoveAnimationEnd, () => {
					resolve();
				});

				this.textField.removeQuote();
			});
		}

		setInput(text)
		{
			this.textField.setText(text);
		}

		getInput()
		{
			return this.textField.getText();
		}

		// Input === textField
		hideTextField(isAnimated)
		{
			this.textField.hide(isAnimated);
		}

		hideKeyboard()
		{
			this.textField.hideKeyboard();
		}

		showTextField(isAnimated)
		{
			this.textField.show(isAnimated);
		}

		/**
		 * @param {boolean} isActive
		 */
		showActionButton(isActive = false)
		{
			if (!Feature.isActionButtonAvailable)
			{
				return;
			}

			const color = isActive ? Theme.colors.base1 : Theme.colors.base3;

			const actionButton = {
				id: 'message-auto-delete',
				icon: {
					name: Icon.TIMER_DOT.getIconName(),
					tintColor: color,
				},
			};

			this.textField.showActionButton(actionButton);
		}

		/**
		 * @param {[Array<object>, Array<object>]} params
		 */
		showActionButtonPopupMenu(params)
		{
			if (!Feature.isActionButtonAvailable)
			{
				return;
			}

			this.textField.showActionButtonPopupMenu(params);
		}

		hideActionButton()
		{
			if (!Feature.isActionButtonAvailable)
			{
				return;
			}

			this.textField.hideActionButton();
		}

		/**
		 * @param {string} text - text button
		 * @param {string} backgroundColor
		 * @param {string} textColor - text color
		 * @param {string} testId
		 */
		showChatJoinButton({
			text,
			backgroundColor,
			testId,
			textColor = AppTheme.colors.baseWhiteFixed,
		})
		{
			this.chatJoinButton.show({
				text,
				backgroundColor,
				textColor,
				testId,
			});
		}

		/**
		 * @param {boolean} isAnimated
		 */
		hideChatJoinButton(isAnimated)
		{
			this.chatJoinButton.hide(isAnimated);
		}

		/* endregion Input */

		/* region Scroll */

		hideScrollToNewMessagesButton()
		{
			if (this.isScrollToNewMessageButtonVisible)
			{
				if (Feature.isFloatingButtonsBarAvailable)
				{
					this.onHideScrollToNewMessageButton();
				}
				else
				{
					this.ui.hideScrollToNewMessagesButton();
				}

				this.isScrollToNewMessageButtonVisible = false;
			}
		}

		showScrollToNewMessagesButton()
		{
			if (this.shouldShowScrollToNewMessagesButton && !this.isScrollToNewMessageButtonVisible)
			{
				if (Feature.isFloatingButtonsBarAvailable)
				{
					this.onShowScrollToNewMessageButton();
				}
				else
				{
					this.ui.showScrollToNewMessagesButton();
				}

				this.isScrollToNewMessageButtonVisible = true;
			}
		}

		disableReadingEvent()
		{
			this.shouldEmitMessageRead = false;
		}

		enableReadingEvent()
		{
			this.shouldEmitMessageRead = true;
		}

		checkIsScrollToNewMessageButtonVisible()
		{
			return this.isScrollToNewMessageButtonVisible;
		}

		async scrollToMessageByIndex(
			index,
			withAnimation = false,
			afterScrollEndCallback = () => {
			},
			position = AfterScrollMessagePosition.bottom,
		)
		{
			await this.ui.scrollToMessageByIndex(index, withAnimation, afterScrollEndCallback, position);
		}

		/**
		 * @param {string|number} id
		 * @param {boolean} withAnimation
		 * @param {()=>any} afterScrollEndCallback
		 * @param {string} position
		 */
		async scrollToMessageById(
			id,
			withAnimation = false,
			afterScrollEndCallback = () => {
			},
			position = AfterScrollMessagePosition.bottom,
		)
		{
			await this.ui.scrollToMessageById(String(id), withAnimation, afterScrollEndCallback, position);
		}

		/**
		 * @desc calculating the message to which widget need to move after rendering.
		 * @param {MessagesContextOptions} options
		 */
		getSetMessagesContextOptions(options)
		{
			if (options)
			{
				return options;
			}

			if (this.setMessagesOptions.targetMessageId)
			{
				return this.setMessagesOptions;
			}

			let scrollPosition = AfterScrollMessagePosition.top;
			if (this.unreadSeparatorAdded)
			{
				return {
					targetMessageId: UnreadSeparatorMessage.getDefaultId(),
					withMessageHighlight: false,
					targetMessagePosition: scrollPosition,
				};
			}

			scrollPosition = AfterScrollMessagePosition.bottom;

			if (this.messageList.length === 0)
			{
				return {
					targetMessageId: null,
					withMessageHighlight: null,
					targetMessagePosition: null,
				};
			}

			if (Number(this.messageIdToScrollAfterSet) === 0)
			{
				this.messageIdToScrollAfterSet = Number(this.messageList[this.messageList.length - 1].id);
				logger.log(
					`${this.constructor.name}.getSetMessagesContextOptions: messageIdToScrollAfterSet = 0. New Id:`,
					this.messageIdToScrollAfterSet,
				);

				scrollPosition = AfterScrollMessagePosition.top;
			}

			return {
				targetMessageId: this.messageIdToScrollAfterSet,
				withMessageHighlight: false,
				targetMessagePosition: scrollPosition,
			};
		}

		async scrollToBottomSmoothly(
			afterScrollEndCallback = () => {
			},
			position = AfterScrollMessagePosition.bottom,
		)
		{
			await this.ui.scrollToMessageByIndex(0, true, afterScrollEndCallback, position);
		}

		async scrollToLastReadMessage(
			afterScrollEndCallback = () => {
			},
			position = AfterScrollMessagePosition.center,
		)
		{
			await this.scrollToMessageById(this.readingMessageId, true, afterScrollEndCallback, position);
		}

		disableShowScrollButton()
		{
			this.shouldShowScrollToNewMessagesButton = false;
		}

		enableShowScrollButton()
		{
			this.shouldShowScrollToNewMessagesButton = true;
		}

		/* endregion Scroll */

		highlightMessageById(messageId)
		{
			const messageIdAsString = String(messageId);
			if (!Type.isFunction(this.ui.highlightMessageById) || !Type.isStringFilled(messageIdAsString))
			{
				return;
			}

			logger.log(`${this.constructor.name}.highlightMessageById`, messageIdAsString);
			this.ui.highlightMessageById(messageIdAsString);
		}

		setFloatingText(text = '')
		{
			const { floatingText } = this.stateManager.state;
			const newState = { floatingText: { ...floatingText, text } };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (hasChanges)
			{
				this.ui.setFloatingText(text);
				this.stateManager.updateState(newState);
			}
		}

		showFloatingText()
		{
			const { floatingText } = this.stateManager.state;
			const newState = { floatingText: { ...floatingText, isShow: true } };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (hasChanges)
			{
				this.ui.showFloatingText();
				this.stateManager.updateState(newState);
			}
		}

		hideFloatingText()
		{
			const { floatingText } = this.stateManager.state;
			const newState = { floatingText: { ...floatingText, isShow: false } };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (hasChanges)
			{
				this.ui.hideFloatingText();
				this.stateManager.updateState(newState);
			}
		}

		setRightButtons(buttonList)
		{
			const newState = { rightButtons: buttonList };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (hasChanges)
			{
				this.ui.setRightButtons(buttonList);
				this.stateManager.updateState(newState);
			}
		}

		setLeftButtons(buttonList)
		{
			const newState = { leftButtons: buttonList };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (hasChanges)
			{
				this.ui.setLeftButtons(buttonList);
				this.stateManager.updateState(newState);
			}
		}

		setTitle(titleParams)
		{
			const newState = { title: titleParams };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (hasChanges)
			{
				this.ui.setTitle(titleParams);
				this.stateManager.updateState(newState);
			}
		}

		setMessageIdToScrollAfterSet(messageId)
		{
			this.messageIdToScrollAfterSet = messageId;
		}

		/**
		 * @param {{imageUrl?: string, defaultIconSvg?: string, avatar?: object}} currentUserAvatar
		 */
		setCurrentUserAvatar(currentUserAvatar)
		{
			const newState = { avatar: currentUserAvatar };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.ui.setCurrentUserAvatar && hasChanges)
			{
				this.ui.setCurrentUserAvatar(currentUserAvatar);
				this.stateManager.updateState(newState);
			}
		}

		setReadingMessageId(lastReadId)
		{
			if (lastReadId > this.readingMessageId)
			{
				this.readingMessageId = lastReadId;
			}
		}

		showMessageListLoader()
		{
			const newState = { isShowMessageListLoader: true };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (hasChanges)
			{
				this.ui.showLoader();
				this.stateManager.updateState(newState);
			}
		}

		showTopLoader()
		{
			const newState = { isShowTopLoader: true };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (!Type.isFunction(this.ui.showTopLoader) || !hasChanges)
			{
				return;
			}

			this.ui.showTopLoader();
			this.stateManager.updateState(newState);
		}

		hideTopLoader()
		{
			const newState = { isShowTopLoader: false };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (!Type.isFunction(this.ui.hideTopLoader) || !hasChanges)
			{
				return;
			}

			this.ui.hideTopLoader();
			this.stateManager.updateState(newState);
		}

		close()
		{
			this.ui.close();
		}

		back()
		{
			this.ui.back();
		}

		hideMessageListLoader()
		{
			const newState = { isShowMessageListLoader: false };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (hasChanges)
			{
				this.ui.hideLoader();
				this.stateManager.updateState(newState);
			}
		}

		showAttachPicker(
			selectedFilesHandler = () => {},
			closeCallback = () => {},
			itemSelectedCallback = () => {},
		)
		{
			const imagePickerParams = {
				settings: {
					previewMaxWidth: 640,
					previewMaxHeight: 640,
					resize: {
						targetWidth: -1,
						targetHeight: -1,
						sourceType: 1,
						encodingType: 0,
						mediaType: 2,
						allowsEdit: false,
						saveToPhotoAlbum: true,
						popoverOptions: false,
						cameraDirection: 0,
					},
					sendFileSeparately: true,
					showAttachedFiles: true,
					editingMediaFiles: false,
					maxAttachedFilesCount: 100,
					attachButton: {
						items: [
							{
								id: AttachPickerId.camera,
								name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_VIEW_INPUT_ATTACH_CAMERA'),
							},
							{
								id: AttachPickerId.mediateka,
								name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_VIEW_INPUT_ATTACH_GALLERY'),
							},
							{
								id: AttachPickerId.disk,
								name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_VIEW_INPUT_ATTACH_DISK'),
								dataSource: {
									multiple: false,
									url: `${MessengerParams.getSiteDir()}mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=${MessengerParams.getUserId()}`,
									TABLE_SETTINGS: {
										searchField: true,
										showtitle: true,
										modal: true,
										name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_VIEW_INPUT_ATTACH_DISK_FILES'),
									},
								},
							},
						],
					},
				},
			};

			if (isModuleInstalled('tasks'))
			{
				imagePickerParams.settings.attachButton.items.push({
					id: AttachPickerId.task,
					name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_VIEW_INPUT_ATTACH_TASK'),
					iconName: Icon.TASK.getIconName(),
				});
			}

			if (isModuleInstalled('calendar'))
			{
				imagePickerParams.settings.attachButton.items.push({
					id: AttachPickerId.meeting,
					name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_VIEW_INPUT_ATTACH_MEETING'),
					iconName: Icon.CALENDAR_WITH_SLOTS.getIconName(),
				});
			}

			if (Feature.isVoteMessageAvailable && !DialogHelper.createByDialogId(this.dialogId).isDirect)
			{
				imagePickerParams.settings.attachButton.items.push({
					id: AttachPickerId.vote,
					name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_VIEW_INPUT_ATTACH_VOTE'),
					iconName: Icon.POLL.getIconName(),
				});
			}

			AnalyticsService.getInstance().sendShowImagePicker(this.dialogId);
			dialogs.showImagePicker(imagePickerParams, selectedFilesHandler, closeCallback, itemSelectedCallback);
		}

		showWelcomeScreen()
		{
			const newState = { isShowWelcomeScreen: true };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (!this.ui.welcomeScreen || !hasChanges)
			{
				return false;
			}

			this.ui.welcomeScreen.show({
				title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_VIEW_WELCOME_SCREEN_TITLE'),
				text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_VIEW_WELCOME_SCREEN_TEXT'),
			});

			this.stateManager.updateState(newState);

			return true;
		}

		hideWelcomeScreen()
		{
			const newState = { isShowWelcomeScreen: false };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (!this.ui.welcomeScreen || !hasChanges)
			{
				return false;
			}

			this.ui.welcomeScreen.hide();
			this.stateManager.updateState(newState);

			return true;
		}

		setNewMessageCounter(counter)
		{
			this.ui.setNewMessageCounter(counter);
		}

		/**
		 * @param {string} enabled
		 * @param {string} disabled
		 */
		setSendButtonColors({ enabled, disabled })
		{
			this.textField.setSendButtonColors({
				enabled,
				disabled,
			});
		}

		/**
		 * @private
		 */
		checkNeedToLoadTopPage()
		{
			if (this.getMessagesCount() < (messagesCountToPageLoad * 2))
			{
				return true;
			}

			const topIndexToLoad = this.getMessagesCount() - messagesCountToPageLoad;
			const index = this.messageIndexListOnScreen.findIndex((messageIndex) => {
				return messageIndex >= topIndexToLoad;
			});

			return index !== -1;
		}

		/**
		 * @private
		 */
		checkNeedToLoadBottomPage()
		{
			if (this.getMessagesCount() < (messagesCountToPageLoad * 2))
			{
				return true;
			}

			const bottomIndexToLoad = messagesCountToPageLoad;
			const index = this.messageIndexListOnScreen.findIndex((messageIndex) => {
				return messageIndex <= bottomIndexToLoad;
			});

			return index !== -1;
		}

		/**
		 * @desc Call native method for update load text progress in message
		 * @param {object} data
		 * @param {string} data.messageId
		 * @param {number} data.currentBytes
		 * @param {number} data.totalBytes
		 * @param {string} data.textProgress
		 * @param {string} data.mediaId
		 * @return {boolean}
		 */
		updateUploadProgressByMessageId(data)
		{
			if (Type.isUndefined(data.messageId))
			{
				return false;
			}

			this.ui.updateUploadProgressByMessageId(
				data.messageId,
				data.currentBytes,
				data.totalBytes,
				data.textProgress,
				data.mediaId,
			);

			return true;
		}

		/**
		 * @desc Call native method for set status field
		 *
		 * @param {StatusFieldIconType} iconType
		 * @param {string} text
		 * @param {string} additionalText
		 * @return {boolean}
		 */
		setStatusField(iconType, text, additionalText)
		{
			if (
				!Type.isString(iconType)
				|| !Type.isString(text)
				|| !Type.isString(additionalText)
			)
			{
				return false;
			}

			if (this.statusField.isUiAvailable() && this.checkShouldUpdateStatusField(iconType, text, additionalText))
			{
				this.statusField.set({ iconType, text, additionalText });
				this.setStatusFieldState(iconType, text, additionalText);
			}

			return true;
		}

		/**
		 * @desc Remember the current state of the status field
		 *
		 * @param {string} iconType
		 * @param {string} text
		 * @param {string} additionalText
		 * @return {boolean}
		 */
		setStatusFieldState(iconType, text, additionalText)
		{
			this.statusFieldState = {
				iconType,
				text,
				additionalText,
			};
		}

		/**
		 * @param {StatusFieldIconType} iconType
		 * @param {string} text
		 * @param {string} additionalText
		 */
		checkShouldUpdateStatusField(iconType, text, additionalText)
		{
			return (
				this.statusFieldState.iconType !== iconType
				|| this.statusFieldState.text !== text
				|| this.statusFieldState.additionalText !== additionalText
			);
		}

		/**
		 * @desc Call native method for clear status field
		 * @return {boolean}
		 */
		clearStatusField()
		{
			this.statusField.clear();
			this.resetStatusFieldState();

			return true;
		}

		resetStatusFieldState()
		{
			/**
			 * @private
			 */
			this.statusFieldState = {
				iconType: null,
				text: null,
				additionalText: null,
			};
		}

		/**
		 * @param {boolean} [animated=false]
		 */
		async enableSelectMessagesMode(animated = false)
		{
			return this.selector.setEnabled(true, animated);
		}

		/**
		 * @param {boolean} [animated=false]
		 */
		async disableSelectMessagesMode(animated = false)
		{
			return this.selector.setEnabled(false, animated);
		}

		/**
		 * @param {object} title
		 */
		async setActionPanelTitle(title)
		{
			return this.actionPanel.setTitle(title);
		}

		/**
		 * @param {object} titleData
		 * @param {Array<ActionPanelButton>} buttons
		 */
		async actionPanelShow(titleData, buttons)
		{
			return this.actionPanel.show(titleData, buttons);
		}

		/**
		 * @param {boolean} [animated=false]
		 */
		async actionPanelHide(animated = false)
		{
			return this.actionPanel.hide(animated);
		}

		/**
		 * @param {Array<ActionPanelButton>} buttons
		 */
		async setActionPanelButtons(buttons)
		{
			return this.actionPanel.setButtons(buttons);
		}

		/**
		 * @param {Array<string>} messageIds
		 */
		async selectMessages(messageIds)
		{
			return this.selector.select(messageIds);
		}

		/**
		 * @param {Array<string>} messageIds
		 */
		async unselectMessages(messageIds)
		{
			return this.selector.unselect(messageIds);
		}

		/**
		 * @return {Array<string>} messageIds
		 */
		async getSelectedItems()
		{
			return this.selector.getSelectedItems();
		}

		/**
		 * @return {boolean}
		 */
		getSelectEnable()
		{
			return this.selector.getSelectEnable();
		}

		/**
		 * @param {number} count
		 */
		setSelectMaxCount(count)
		{
			this.selector.setSelectMaxCount(count);
		}

		/**
		 * @param {ChatRestrictionsParams} restrictions
		 */
		updateRestrictions(restrictions)
		{
			this.restrictions.update(restrictions);
		}

		/**
		 * @param {BackgroundConfiguration} background
		 */
		setBackground(background)
		{
			const newState = { background };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (hasChanges)
			{
				logger.log(`${this.constructor.name}.setBackground backgroundConfiguration:`, background);

				this.ui.setBackground(background);
				this.stateManager.updateState(newState);
			}
		}

		async #processReadMessagesAfterSet()
		{
			const {
				indexList,
				messageList,
			} = await this.getViewableMessages();
			const messageIdList = messageList
				.map((message) => message.id)
				.filter((messageId) => {
					return !String(messageId).startsWith(MessageIdType.templateSeparatorUnread)
						&& !String(messageId).startsWith(MessageIdType.templateSeparatorDate);
				})
			;
			const hasPushUnreadMessage = serviceLocator.get('core').getStore()
				.getters['messagesModel/hasUnreadPushMessage'](messageIdList)
			;

			if (hasPushUnreadMessage)
			{
				this.delayedMessageListToRead = messageList;

				return;
			}
			this.shouldEmitMessageRead = true;

			logger.log(`${this.constructor.name}.afterSetMessages: visible messages:`, messageList);
			if (indexList.length > 0 && !indexList.includes(0))
			{
				this.showScrollToNewMessagesButton();
			}

			this.emitCustomEvent(EventType.dialog.visibleMessagesChanged, { indexList, messageList });
			this.readVisibleUnreadMessages(messageList);
		}

		/**
		 * @param {Array<Message>|Message} messageData
		 * @return {Array<DialogWidgetItem>|DialogWidgetItem}
		 */
		#prepareMessagesToDialogWidgetItem(messageData)
		{
			return messageData.map((message) => this.#prepareMessageToDialogWidgetItem(message));
		}

		/**
		 * @param {Message} messageData
		 * @return {DialogWidgetItem}
		 */
		#prepareMessageToDialogWidgetItem(messageData)
		{
			return messageData.toDialogWidgetItem();
		}
	}

	module.exports = {
		DialogView,
		AfterScrollMessagePosition,
		InputQuoteType,
	};
});
