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
			]);

			this.dialogId = options.dialogId;
			this.chatId = options.chatId;
			this.visibilityManager = VisibilityManager.getInstance();
			this.messageListOnScreen = [];
			this.messageIndexListOnScreen = [];
			this.messagesCount = 0;
			this.readingMessageId = 0;
			this.shouldEmitMessageRead = false;
			this.shouldShowScrollToNewMessagesButton = true;
			this.isScrollToNewMessageButtonVisible = false;
			this.unreadSeparatorAdded = false;
			this.scrollToFirstUnreadCompleted = false;

			this.subscribeEvents();
		}

		/* region Events */

		subscribeEvents()
		{
			this.ui
				.on(EventType.dialog.viewAreaMessagesChanged, this.onViewAreaMessagesChanged.bind(this))
				.on(EventType.dialog.topReached, this.onTopReached.bind(this))
				.on(EventType.dialog.bottomReached, this.onBottomReached.bind(this))
			;
		}

		async onViewAreaMessagesChanged(indexList, messageList)
		{
			if (indexList.includes(0))
			{
				this.hideScrollToNewMessagesButton();
			}
			else
			{
				this.showScrollToNewMessagesButton();
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

			const isDialogVisible = await this.visibilityManager.checkIsDialogVisible(this.dialogId);
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

			if (!this.shouldEmitMessageRead)
			{
				return;
			}

			this.readVisibleUnreadMessages(messageList);
		}

		/**
		 * @private
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

				const modelMessage = core.getStore().getters['messagesModel/getMessageById'](messageId);

				return (
					modelMessage.unread === true
					&& messageId > this.readingMessageId
				);
			});

			if (unreadMessages.length === 0)
			{
				return;
			}

			this.readingMessageId = Number(unreadMessages.shift().id);

			this.emitCustomEvent(EventType.dialog.messageRead, this.readingMessageId);
		}

		onTopReached()
		{
			this.emitCustomEvent(EventType.dialog.loadTopPage);
		}

		onBottomReached()
		{
			this.emitCustomEvent(EventType.dialog.loadBottomPage);
		}

		/* endregion Events */

		/* region Message */

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

		setMessages(messageList)
		{
			this.ui.setMessages(messageList);
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
			this.shouldEmitMessageRead = true;

			if (this.unreadSeparatorAdded)
			{
				Logger.warn('DialogView: scroll to the first unread completed');
			}
			else
			{
				Logger.warn('DialogView: scrolling to the first unread is not required, everything is read');
			}

			// TODO: refactor temporary hack. Without it, extra messages are read, somehow connected with the scroll
			setTimeout(() => {
				const {
					messageList,
				} = this.getViewableMessages();

				Logger.warn('DialogView.afterSetMessages: visible messages ', messageList);

				this.readVisibleUnreadMessages(messageList);
			}, 200);
		}

		pushMessages(messageList)
		{
			this.ui.pushMessages(messageList);
			this.messagesCount += messageList.length;
		}

		addMessages(messageList)
		{
			this.shouldShowScrollToNewMessagesButton = false;

			this.ui.addMessages(messageList);
			this.messagesCount += messageList.length;

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
						this.shouldShowScrollToNewMessagesButton = true;
					});
				});

				return;
			}

			this.shouldShowScrollToNewMessagesButton = true;
		}

		getTopMessage()
		{
			const topMessage = this.messageListOnScreen[this.messageListOnScreen.length - 1];

			return topMessage || {};
		}

		updateMessageById(id, message)
		{
			this.ui.updateMessageById(id, message);
		}

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

		removeMessageById(id)
		{
			return this.removeMessagesByIds([id]);
		}

		isMessageWithIdOnScreen(id)
		{
			const messageIndex = this.messageListOnScreen
				.findIndex((message) => String(message.id) === String(id))
			;

			return messageIndex !== -1;
		}

		isMessageWithIndexOnScreen(index)
		{
			return this.messageIndexListOnScreen.includes(index);
		}

		showMenuForMessage(message, menu)
		{
			this.ui.showMenuForMessage(message, menu);
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
										MessengerParams.getSiteDir()
										+ 'mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId='
										+ MessengerParams.getUserId()
									,
									TABLE_SETTINGS: {
										searchField: true,
										showtitle: true,
										modal: true,
										name: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_VIEW_INPUT_ATTACH_DISK_FILES')
									},
								},
							},
						],
					},
				},
			};

			dialogs.showImagePicker(imagePickerParams, selectedFilesHandler);
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
	}

	module.exports = {
		DialogView,
		AfterScrollMessagePosition,
		InputQuoteType,
	};
});
