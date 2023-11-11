/**
 * @module im/messenger/view/dialog/dialog
 */
jn.define('im/messenger/view/dialog/dialog', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('loc');

	const { core } = require('im/messenger/core');
	const { View } = require('im/messenger/view/base');
	const { EventType } = require('im/messenger/const');
	const { VisibilityManager } = require('im/messenger/lib/visibility-manager');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { Logger } = require('im/messenger/lib/logger');
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
			 * @type {VisibilityManager}
			 */
			this.visibilityManager = VisibilityManager.getInstance();
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
			 * @type {number}
			 */
			this.messagesCount = 0;
			/**
			 * @private
			 * @type {number}
			 */
			this.readingMessageId = options.lastReadId;
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
			 * @type {Message | null}
			 */
			this.topMessage = null;
			/**
			 * @private
			 * @type {Message | null}
			 */
			this.bottomMessage = null;

			this.subscribeEvents();
		}

		/* region Events */

		subscribeEvents()
		{
			this.ui
				.on(EventType.dialog.viewAreaMessagesChanged, this.onViewAreaMessagesChanged.bind(this))
				.on(EventType.dialog.topReached, this.onTopReached.bind(this))
				.on(EventType.dialog.bottomReached, this.onBottomReached.bind(this))
				.on(EventType.view.show, this.onViewShown.bind(this))
			;

			this.ui.statusField
				.on(EventType.view.statusField.tap, this.onStatusFieldTap.bind(this))
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
		 * @private
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

				const modelMessage = core.getStore().getters['messagesModel/getById'](messageId);

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

		onViewShown()
		{
			if (!this.shouldEmitMessageRead)
			{
				return;
			}

			Logger.log('onViewShown', this.messageListOnScreen);
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
		 * @param {Array<Message>} messageList
		 */
		setMessages(messageList)
		{
			this.unreadSeparatorAdded = messageList.some((message) => message.id === UnreadSeparatorMessage.getDefaultId());
			this.ui.setMessages(messageList);

			this.topMessage = messageList[messageList.length - 1];
			this.bottomMessage = messageList[0];
			this.messagesCount = messageList.length;

			this.scrollToFirstUnreadMessage(
				false,
				() => this.afterSetMessages(true),
			);

			if (!this.unreadSeparatorAdded)
			{
				this.afterSetMessages(false);
			}
		}

		/**
		 * @private
		 */
		afterSetMessages(withScroll)
		{
			this.scrollToFirstUnreadCompleted = true;

			if (this.unreadSeparatorAdded)
			{
				Logger.warn('DialogView: scroll to the first unread completed');
			}
			else
			{
				Logger.warn('DialogView: scrolling to the first unread is not required, everything is read');
			}

			if (!this.messageIndexListOnScreen.includes(0))
			{
				this.showScrollToNewMessagesButton();
			}

			// TODO: refactor temporary hack. Without it, extra messages are read, somehow connected with the scroll
			setTimeout(() => {
				const {
					messageList,
				} = this.getViewableMessages();
				this.shouldEmitMessageRead = true;

				Logger.warn('DialogView.afterSetMessages: visible messages ', messageList);

				this.readVisibleUnreadMessages(messageList);
			}, 200);
		}

		/**
		 * @param {Array<Message>} messageList
		 */
		pushMessages(messageList)
		{
			this.ui.pushMessages(messageList);

			this.messagesCount += messageList.length;
			this.topMessage = messageList[messageList.length - 1];
		}

		/**
		 * @param {Array<Message>} messageList
		 */
		addMessages(messageList)
		{
			this.shouldShowScrollToNewMessagesButton = false;

			this.ui.addMessages(messageList);

			this.messagesCount += messageList.length;
			this.bottomMessage = messageList[0];

			if (this.messageIndexListOnScreen.includes(0))
			{
				this.visibilityManager.checkIsDialogVisible(this.dialogId).then((isDialogVisible) => {
					if (!isDialogVisible)
					{
						this.shouldShowScrollToNewMessagesButton = true;
						this.showScrollToNewMessagesButton();

						return;
					}

					this.scrollToBottomSmoothly(() => {
						// timeout to prevent a race condition between the end of the scroll and viewableMessagesChanged.
						// If you remove it when adding a message, the scroll down button may start blinking.
						setTimeout(() => {
							this.shouldShowScrollToNewMessagesButton = true;
						}, 300);
					});
				});

				return;
			}

			this.shouldShowScrollToNewMessagesButton = true;
		}

		getTopMessageOnScreen()
		{
			const topMessage = this.messageListOnScreen[this.messageListOnScreen.length - 1];

			return topMessage || {};
		}

		/**
		 * @param {number} id
		 * @param {Message} message
		 */
		updateMessageById(id, message)
		{
			this.ui.updateMessageById(id, message);
		}

		/**
		 * @param {Array<number>} idList
		 * @return {boolean}
		 */
		removeMessagesByIds(idList)
		{
			if (!Type.isArrayFilled(idList))
			{
				Logger.warn('DialogView.removeMessagesByIds: idList is empty');

				return false;
			}

			const removeIdList = idList.map((id) => String(id));
			this.ui.removeMessagesByIds(removeIdList);

			return true;
		}

		/**
		 * @param {number} id
		 * @return {boolean}
		 */
		removeMessageById(id)
		{
			return this.removeMessagesByIds([id]);
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

		getTopMessage()
		{
			return this.topMessage;
		}

		getBottomMessage()
		{
			return this.bottomMessage;
		}

		/* endregion Message */

		/* region Input */

		clearInput()
		{
			this.ui.clearInput();
		}

		setInputPlaceholder(text)
		{
			this.ui.setInputPlaceholder(text);
		}

		setInputQuote(message, type, openKeyboard = true)
		{
			if (InputQuoteType[type])
			{
				this.ui.setInputQuote(message, type, openKeyboard);
			}
			else
			{
				this.ui.setInputQuote(message);
			}
		}

		removeInputQuote()
		{
			return new Promise((resolve) => {
				this.ui.once(EventType.dialog.quoteRemoveAnimationEnd, () => {
					resolve();
				});

				this.ui.removeInputQuote();
			});
		}

		setInput(text)
		{
			this.ui.setInput(text);
		}

		getInput()
		{
			return this.ui.getInput();
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

		checkIsScrollToNewMessageButtonVisible()
		{
			return this.isScrollToNewMessageButtonVisible;
		}

		scrollToMessageByIndex(
			index,
			withAnimation = false,
			afterScrollEndCallback = () => {},
			position = AfterScrollMessagePosition.bottom,
		)
		{
			this.ui.scrollToMessageByIndex(index, withAnimation, afterScrollEndCallback, position);
		}

		scrollToMessageById(
			id,
			withAnimation = false,
			afterScrollEndCallback = () => {},
			position = AfterScrollMessagePosition.bottom,
		)
		{
			this.ui.scrollToMessageById(id, withAnimation, afterScrollEndCallback, position);
		}

		scrollToFirstUnreadMessage(withAnimation = false, afterScrollEndCallback = () => {})
		{
			this.scrollToMessageById(
				UnreadSeparatorMessage.getDefaultId(),
				withAnimation,
				afterScrollEndCallback,
				AfterScrollMessagePosition.top,
			);
		}

		scrollToBottomSmoothly(
			afterScrollEndCallback = () => {},
			position = AfterScrollMessagePosition.bottom,
		)
		{
			this.ui.scrollToMessageByIndex(0, true, afterScrollEndCallback, position);
		}

		scrollToLastReadMessage(
			afterScrollEndCallback = () => {},
			position = AfterScrollMessagePosition.center,
		)
		{
			this.scrollToMessageById(this.readingMessageId, true, afterScrollEndCallback, position);
		}

		/* endregion Scroll */

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

		showMessageListLoader()
		{
			this.ui.showLoader();
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
								name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_VIEW_INPUT_ATTACH_DISK'),
								dataSource: {
									multiple: false,
									url:
										`${MessengerParams.getSiteDir()
										 }mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=${
										 MessengerParams.getUserId()}`,
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

		setNewMessageCounter(counter)
		{
			this.ui.setNewMessageCounter(counter);
		}

		/**
		 * @private
		 */
		checkNeedToLoadTopPage()
		{
			if (this.messagesCount < (messagesCountToPageLoad * 2))
			{
				return false;
			}

			const topIndexToLoad = this.messagesCount - messagesCountToPageLoad;
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
			if (this.messagesCount < (messagesCountToPageLoad * 2))
			{
				return false;
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
			this.ui.statusField.set({ iconType, text });

			return true;
		}

		/**
		 * @desc Call native method for clear status field
		 * @return {boolean}
		 */
		clearStatusField()
		{
			this.ui.statusField.clear();

			return true;
		}
	}

	module.exports = {
		DialogView,
		AfterScrollMessagePosition,
		InputQuoteType,
	};
});
