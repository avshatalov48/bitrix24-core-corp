/**
 * @module im/messenger/view/dialog/dialog
 */
jn.define('im/messenger/view/dialog/dialog', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Type } = require('type');
	const { Loc } = require('loc');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { View } = require('im/messenger/view/base');
	const { EventType, MessageType, MessageIdType } = require('im/messenger/const');
	const { VisibilityManager } = require('im/messenger/lib/visibility-manager');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const {
		UnreadSeparatorMessage,
	} = require('im/messenger/lib/element');

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
	const logger = LoggerManager.getInstance().getLogger('dialog--view');

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
				EventType.dialog.statusFieldTap,
				EventType.dialog.chatJoinButtonTap,
			]);

			/**
			 * @private
			 * @type {string | number}
			 */
			this.dialogId = options.dialogId;
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
			/**
			 * @private
			 * @type {VisibilityManager}
			 */
			this.visibilityManager = VisibilityManager.getInstance();
			this.resetState();
			this.subscribeEvents();
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

			/**
			 * @private
			 * @type {boolean}
			 */
			this.isWelcomeScreenShown = false;

			this.resetStatusFieldState();
			this.resetContextOptions();
		}

		/* region nested objects */
		/**
		 * @return {DialogTextField}
		 */
		get textField()
		{
			return this.ui.textField;
		}

		/**
		 * @return {MentionPanel}
		 */
		get mentionPanel()
		{
			return this.ui.mentionPanel;
		}

		/**
		 * @return {PinPanel}
		 */
		get pinPanel()
		{
			return this.ui.pinPanel;
		}

		get commentsButton()
		{
			return this.ui?.commentsButton;
		}

		/* endregion nested objects */

		/* region Events */

		subscribeEvents()
		{
			this.ui
				.on(EventType.dialog.viewAreaMessagesChanged, this.onViewAreaMessagesChanged.bind(this))
				.on(EventType.dialog.topReached, this.onTopReached.bind(this))
				.on(EventType.dialog.bottomReached, this.onBottomReached.bind(this))
				.on(EventType.view.show, this.onViewShown.bind(this))
			;

			if (this.ui.statusField)
			{
				this.ui.statusField
					.on(EventType.dialog.statusField.tap, this.onStatusFieldTap.bind(this))
				;
			}

			this.ui.chatJoinButton
				.on(EventType.dialog.chatJoinButton.tap, this.onChatJoinButtonTap.bind(this))
			;
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
			this.visibilityManager.checkIsDialogVisible(this.dialogId).then((isDialogVisible) => {
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

		onStatusFieldTap()
		{
			this.emitCustomEvent(EventType.dialog.statusFieldTap);
		}

		onChatJoinButtonTap()
		{
			this.emitCustomEvent(EventType.dialog.chatJoinButtonTap);
		}

		onViewShown()
		{
			if (!this.shouldEmitMessageRead)
			{
				return;
			}

			logger.log('onViewShown', this.messageListOnScreen);
			this.readVisibleUnreadMessages(this.messageListOnScreen);
		}

		/* endregion Events */

		/* region Message */

		/**
		 * @return {{messageList: Array<Message>, indexList: Array<number>}}
		 */
		getViewableMessages()
		{
			const {
				indexList,
				messageList,
			} = this.ui.getViewableMessages();

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
		 * @param {string | number} targetMessageId
		 * @param {boolean} withMessageHighlight
		 * @param {string} targetMessagePosition
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
		 * @param {Object} options
		 */
		async setMessages(messageList, options = this.setMessagesOptions)
		{
			if (Type.isArrayFilled(messageList))
			{
				this.hideWelcomeScreen();
			}

			this.unreadSeparatorAdded = messageList.some((message) => message.id === UnreadSeparatorMessage.getDefaultId());
			logger.log('DialogView.setMessages:', messageList, options);
			await this.ui.setMessages(messageList, options);
			this.setMessageList(messageList);

			if (options.targetMessageId && options.withMessageHighlight)
			{
				this.highlightMessageById(options.targetMessageId);
			}

			if (options.targetMessageId)
			{
				this.afterSetMessages();
			}
			else
			{
				setTimeout(() => {
					this.scrollToFirstUnreadMessage(
						false,
						() => this.afterSetMessages(),
					);
					if (!this.unreadSeparatorAdded)
					{
						this.afterSetMessages();
					}
				}, 0);
			}

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
				logger.log('DialogView: scroll to the first unread completed');
			}
			else
			{
				logger.log('DialogView: scrolling to the first unread is not required, everything is read');
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
				const {
					indexList,
					messageList,
				} = this.getViewableMessages();
				this.shouldEmitMessageRead = true;

				logger.log('DialogView.afterSetMessages: visible messages ', messageList);
				if (!indexList.includes(0))
				{
					this.showScrollToNewMessagesButton();
				}

				this.readVisibleUnreadMessages(messageList);
			}, 200);
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

			await this.ui.pushMessages(messageList);
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

			await this.ui.addMessages(messageList);
			this.addMessageList(messageList);

			this.visibilityManager.checkIsDialogVisible(this.dialogId).then((isDialogVisible) => {
				if (isDialogVisible)
				{
					return;
				}

				this.enableShowScrollButton();
				this.showScrollToNewMessagesButton();
			});

			this.enableShowScrollButton();
		}

		/**
		 *
		 * @param {string || number} pointedId
		 * @param {Array<Message>} messageList
		 * @param {'above' || 'below'} position
		 */
		async insertMessages(pointedId, messageList, position)
		{
			await this.ui.insertMessages(pointedId, messageList, position);
		}

		getTopMessageOnScreen()
		{
			const topMessage = this.messageListOnScreen[this.messageListOnScreen.length - 1];

			return topMessage || {};
		}

		/**
		 * @param {number} id
		 * @param {Message} message
		 * @param  {string | null} section
		 */
		async updateMessageById(id, message, section = null)
		{
			if (section)
			{
				await this.ui.updateMessageById(id, message, [section]);
			}
			else
			{
				await this.ui.updateMessageById(id, message);
			}

			this.updateMessageListById(id, message);
		}

		/**
		 * @param {string} messageId
		 * @return  {number}
		 */
		getPlayingTime(messageId)
		{
			try
			{
				return this.ui.getPlayingTime?.(messageId) || 0;
			}
			catch (error)
			{
				logger.error(error);

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
				logger.warn('DialogView.removeMessagesByIds: idList is empty');

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
		 *
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

		/* endregion ViewMessageList */

		/* region Input */

		clearInput()
		{
			this.ui.textField.clear();
		}

		setInputPlaceholder(text)
		{
			this.ui.textField.setPlaceholder(text);
		}

		setInputQuote(message, type, openKeyboard = true)
		{
			if (InputQuoteType[type])
			{
				this.ui.textField.setQuote(message, type, openKeyboard);
			}
			else
			{
				this.ui.textField.setQuote(message);
			}
		}

		enableAlwaysSendButtonMode(enable)
		{
			this.ui.textField?.enableAlwaysSendButtonMode?.(enable);
		}

		removeInputQuote()
		{
			return new Promise((resolve) => {
				this.ui.textField.once(EventType.dialog.textField.quoteRemoveAnimationEnd, () => {
					resolve();
				});

				this.ui.textField.removeQuote();
			});
		}

		setInput(text)
		{
			this.ui.textField.setText(text);
		}

		getInput()
		{
			return this.ui.textField.getText();
		}

		// Input === textField
		hideTextField(isAnimated = false)
		{
			this.ui.textField.hide({ animated: isAnimated });
		}

		showTextField(isAnimated = false)
		{
			this.ui.textField.show({ animated: isAnimated });
		}

		/**
		 * @param {string} text - text button
		 * @param backgroundColor
		 * @param textColor
		 * @param {string} textColor - text color
		 * @param {string} backgroundColor - background color
		 */
		showChatJoinButton({
			text,
			backgroundColor,
			testId,
			textColor = AppTheme.colors.baseWhiteFixed,
		})
		{
			this.ui.chatJoinButton.show({
				text,
				backgroundColor,
				textColor,
				testId
			});
		}

		hideChatJoinButton(isAnimated = false)
		{
			this.ui.chatJoinButton.hide({ animated: isAnimated });
		}

		/* endregion Input */

		/* region Scroll */

		hideScrollToNewMessagesButton()
		{
			this.ui.hideScrollToNewMessagesButton();
			this.isScrollToNewMessageButtonVisible = false;
		}

		showScrollToNewMessagesButton()
		{
			if (this.shouldShowScrollToNewMessagesButton)
			{
				this.ui.showScrollToNewMessagesButton();
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
			afterScrollEndCallback = () => {},
			position = AfterScrollMessagePosition.bottom,
		)
		{
			await this.ui.scrollToMessageByIndex(index, withAnimation, afterScrollEndCallback, position);
		}

		async scrollToMessageById(
			id,
			withAnimation = false,
			afterScrollEndCallback = () => {},
			position = AfterScrollMessagePosition.bottom,
		)
		{
			await this.ui.scrollToMessageById(id, withAnimation, afterScrollEndCallback, position);
		}

		scrollToFirstUnreadMessage(
			withAnimation = false,
			afterScrollEndCallback = () => {},
			position = AfterScrollMessagePosition.top,
		)
		{
			if (this.unreadSeparatorAdded)
			{
				this.scrollToMessageById(
					UnreadSeparatorMessage.getDefaultId(),
					withAnimation,
					afterScrollEndCallback,
					position,
				);

				return;
			}

			position = AfterScrollMessagePosition.bottom;

			if (Number(this.messageIdToScrollAfterSet) === 0)
			{
				this.messageIdToScrollAfterSet = Number(this.messageList[this.messageList.length - 1].id);
				logger.log('DialogView.scrollToFirstUnreadMessage: messageIdToScrollAfterSet = 0. New Id:', this.messageIdToScrollAfterSet);

				position = AfterScrollMessagePosition.top;
			}

			this.scrollToMessageById(
				this.messageIdToScrollAfterSet,
				withAnimation,
				afterScrollEndCallback,
				position,
			);
		}

		async scrollToBottomSmoothly(
			afterScrollEndCallback = () => {},
			position = AfterScrollMessagePosition.bottom,
		)
		{
			await this.ui.scrollToMessageByIndex(0, true, afterScrollEndCallback, position);
		}

		async scrollToLastReadMessage(
			afterScrollEndCallback = () => {},
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

			logger.log('DialogView.highlightMessageById', messageIdAsString);
			this.ui.highlightMessageById(messageIdAsString);
		}

		setFloatingText(text = '')
		{
			this.ui.setFloatingText(text);
		}

		showFloatingText()
		{
			this.ui.showFloatingText();
		}

		hideFloatingText()
		{
			this.ui.hideFloatingText();
		}

		setRightButtons(buttonList)
		{
			this.ui.setRightButtons(buttonList);
		}

		setTitle(titleParams)
		{
			this.ui.setTitle(titleParams);
		}

		setMessageIdToScrollAfterSet(messageId)
		{
			this.messageIdToScrollAfterSet = messageId;
		}

		/**
		 * @param {{imageUrl?: string, defaultIconSvg?: string}} currentUserAvatar
		 */
		setCurrentUserAvatar(currentUserAvatar)
		{
			if (this.ui.setCurrentUserAvatar)
			{
				this.ui.setCurrentUserAvatar(currentUserAvatar);
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
			this.ui.showLoader();
		}

		showTopLoader()
		{
			if (!Type.isFunction(this.ui.showTopLoader))
			{
				return;
			}

			this.ui.showTopLoader();
		}

		hideTopLoader()
		{
			if (!Type.isFunction(this.ui.hideTopLoader))
			{
				return;
			}

			this.ui.hideTopLoader();
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
			this.ui.hideLoader();
		}

		showAttachPicker(selectedFilesHandler = () => {})
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
								id: 'camera',
								name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_VIEW_INPUT_ATTACH_CAMERA'),
							},
							{
								id: 'mediateka',
								name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_VIEW_INPUT_ATTACH_GALLERY'),
							},
							{
								id: 'disk',
								name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_VIEW_INPUT_ATTACH_DISK_V2'),
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

			dialogs.showImagePicker(imagePickerParams, selectedFilesHandler);
		}

		showWelcomeScreen()
		{
			if (!this.ui.welcomeScreen)
			{
				return false;
			}

			this.ui.welcomeScreen.show({
				title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_VIEW_WELCOME_SCREEN_TITLE'),
				text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_VIEW_WELCOME_SCREEN_TEXT'),
			});

			this.isWelcomeScreenShown = true;

			return true;
		}

		hideWelcomeScreen()
		{
			if (!this.ui.welcomeScreen)
			{
				return false;
			}

			this.ui.welcomeScreen.hide();
			this.isWelcomeScreenShown = false;

			return true;
		}

		setNewMessageCounter(counter)
		{
			this.ui.setNewMessageCounter(counter);
		}

		setSendButtonColors({ enabled, disabled })
		{
			this.ui.textField?.setSendButtonColors?.({
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
			);

			return true;
		}

		/**
		 * @desc Call native method for set status field
		 *
		 * @param {string} iconType
		 * @param {string} text
		 * @return {boolean}
		 */
		setStatusField(iconType, text)
		{
			if (Type.isUndefined(iconType) || Type.isUndefined(text))
			{
				return false;
			}

			if (this.ui.statusField && this.checkShouldUpdateStatusField(iconType, text))
			{
				this.ui.statusField.set({ iconType, text });
				this.setStatusFieldState(iconType, text);
			}

			return true;
		}

		/**
		 * @desc Remember the current state of the status field
		 *
		 * @param {string} iconType
		 * @param {string} text
		 * @return {boolean}
		 */
		setStatusFieldState(iconType, text)
		{
			this.statusFieldState = {
				iconType,
				text,
			};
		}

		checkShouldUpdateStatusField(iconType, text)
		{
			return (
				this.statusFieldState.iconType !== iconType
				|| this.statusFieldState.text !== text
			);
		}

		/**
		 * @desc Call native method for clear status field
		 * @return {boolean}
		 */
		clearStatusField()
		{
			if (this.ui.statusField)
			{
				this.ui.statusField.clear();
				this.resetStatusFieldState();
			}

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
			};
		}
	}

	module.exports = {
		DialogView,
		AfterScrollMessagePosition,
		InputQuoteType,
	};
});
