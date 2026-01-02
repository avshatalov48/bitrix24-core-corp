/**
 * @module im/messenger/controller/dialog/lib/media-gallery/src/media-menu
 */
jn.define('im/messenger/controller/dialog/lib/media-gallery/src/media-menu', (require, exports, module) => {
	const { Color } = require('tokens');
	const { isFunction, isEmpty } = require('utils/object');
	const { MessageHelper } = require('im/messenger/lib/helper');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { PropTypes } = require('utils/validation');
	const { MessageMenuActionType, EventType, ComponentCode, DialogActionType } = require('im/messenger/const');
	const { AfterScrollMessagePosition } = require('im/messenger/view/dialog');
	const { showDeleteGalleryAlert } = require('im/messenger/lib/ui/alert');
	const { VisibilityManager } = require('im/messenger/lib/visibility-manager');
	const { MenuActions, MessageMenuController } = require('im/messenger/controller/dialog/lib/message-menu');

	/**
	 * @class MediaMenu
	 */
	class MediaMenu
	{
		/**
		 * @param {mediaMenuProps} props
		 */
		constructor(props)
		{
			const { dialogLocator, gallery } = props;

			/** @type {mediaMenuProps} */
			this.props = props;
			this.gallery = gallery;
			this.galleryWidget = null;
			this.store = dialogLocator.get('store');
			this.visibilityManager = VisibilityManager.getInstance();
			this.messageMenu = new MessageMenuController({
				dialogLocator,
				getDialog: this.getDialog,
			});
		}

		/**
		 * @param {mediaMenuProps} props
		 * @returns {MediaMenu}
		 */
		static create(props)
		{
			return new MediaMenu(props);
		}

		/**
		 * @param gallery
		 */
		setGalleryWidget(gallery)
		{
			this.galleryWidget = gallery;
		}

		/**
		 * @param {menuItemParams} menuItem
		 * @param {galleryItemParams} galleryItem
		 * @returns {Promise<void>}
		 */
		onReplySelected = async ({ menuItem, galleryItem }) => {
			await this.onBeforeAction();
			await this.closeGallery();
			const isVisible = await this.isVisibleDialog();

			if (!isVisible)
			{
				return this.#openDialog({
					actionsAfterOpen: [
						{
							type: DialogActionType.reply,
							messageId: galleryItem.messageId,
						},
					],
				});
			}

			this.onSelectedMessageMenuHandler({
				actionType: menuItem.id,
				messageId: galleryItem.messageId,
			});
		};

		/**
		 * @param {menuItemParams} menuItem
		 * @param {galleryItemParams} galleryItem
		 * @returns {Promise<void>}
		 */
		onForwardSelected = async ({ menuItem, galleryItem }) => {
			const selector = await this.onSelectedMessageMenuHandler(
				{
					actionType: menuItem.id,
					messageId: galleryItem.messageId,
				},
				{
					parentWidget: this.galleryWidget,
					onItemSelected: async () => {
						selector?.close();
						await this.onBeforeAction();
						await this.closeGallery();
					},
					closeOnSelect: false,
				},
			);
		};

		/**
		 * @param {menuItemParams} [menuItem]
		 * @param {galleryItemParams} galleryItem
		 * @returns {Promise<*>}
		 */
		onGoToMessageSelected = async ({ galleryItem }) => {
			await this.onBeforeAction();
			await this.closeGallery();
			const isVisible = await this.isVisibleDialog();

			if (!isVisible)
			{
				return this.#openDialog({
					actionsAfterOpen: [
						{
							type: DialogActionType.goToMessage,
							messageId: galleryItem.messageId,
							targetMessagePosition: AfterScrollMessagePosition.center,
						},
					],
				});
			}

			return this.onSelectedMessageMenuHandler(
				{
					actionType: MessageMenuActionType.goToMessage,
					messageId: galleryItem.messageId,

				},
				{
					targetMessagePosition: AfterScrollMessagePosition.center,
				},
			);
		};

		/**
		 * @param {menuItemParams} menuItem
		 * @param {galleryItemParams} galleryItem
		 * @returns {Promise<void>}
		 */
		onDownloadToDiskSelected = ({ menuItem, galleryItem }) => {
			void this.onSelectedMessageMenuHandler(
				{
					actionType: menuItem.id,
					messageId: galleryItem.messageId,
				},
				{
					parentWidget: this.galleryWidget,
				},
			);
		};

		/**
		 * @param {menuItemParams} menuItem
		 * @param {galleryItemParams} galleryItem
		 * @returns {Promise<void>}
		 */
		onDownloadToDeviceSelected = ({ menuItem, galleryItem }) => {
			void this.onSelectedMessageMenuHandler(
				{
					actionType: menuItem.id,
					messageId: galleryItem.messageId,
				},
				{
					parentWidget: this.galleryWidget,
				},
			);
		};

		/**
		 * @param {menuItemParams} menuItem
		 * @param {galleryItemParams} galleryItem
		 * @returns {Promise<void>}
		 */
		onDeleteSelected = async ({ menuItem, galleryItem }) => {
			const { forceDelete } = this.props;
			const messageHelper = MessageHelper.createById(galleryItem.messageId);

			if (forceDelete && !messageHelper.isGallery)
			{
				await this.closeGallery();
			}
			else
			{
				await this.onGoToMessageSelected({ galleryItem });
			}

			const deleteCallback = async () => {
				// To have time to see the result of the action after closing the widget
				await this.#timeout(800);
				this.onSelectedMessageMenuHandler({
					actionType: menuItem.id,
					messageId: galleryItem.messageId,
				});
			};

			if (messageHelper.isGallery)
			{
				showDeleteGalleryAlert({ deleteCallback });

				return;
			}

			void deleteCallback();
		};

		/**
		 * @param {string} actionType
		 * @param {MessageId} messageId
		 * @param {Object} params
		 */
		onSelectedMessageMenuHandler({ actionType, messageId }, params = {})
		{
			return this.messageMenu.onMessageMenuActionTap?.(
				actionType,
				this.getMessageModel(messageId),
				params,
			);
		}

		/**
		 * @param {MessageId} messageId
		 */
		getMessageModel(messageId)
		{
			return this.store.getters['messagesModel/getById'](messageId);
		}

		/**
		 * @param {MessageId} messageId
		 */
		getItems(messageId)
		{
			const messageModel = this.getMessageModel(messageId);
			if (!messageId || isEmpty(messageModel))
			{
				return [];
			}

			const messageHelper = MessageHelper.createById(messageId);
			const messageMenuMessage = this.messageMenu.createMessageMenuMessage(messageId);
			const isPossibleSaveToLibrary = this.isPossibleSaveToLibrary(messageHelper);

			return [
				messageMenuMessage.isPossibleReply() && this.createMenuItem({
					action: MenuActions.ReplyAction,
				}),
				messageMenuMessage.isPossibleForward() && this.createMenuItem({
					action: MenuActions.ForwardAction,
				}),
				this.createMenuItem({
					action: MenuActions.GoToMessageAction,
				}),
				isPossibleSaveToLibrary && this.createMenuItem({
					action: MenuActions.DownloadToDeviceAction,
				}),
				isPossibleSaveToLibrary && this.createMenuItem({
					action: MenuActions.DownloadToDiskAction,
				}),
				messageMenuMessage.isPossibleDelete() && this.createMenuItem({
					action: MenuActions.DeleteAction,
					styles: {
						icon: {
							color: Color.accentMainAlert.toHex(),
						},
						title: {
							font: {
								color: Color.accentMainAlert.toHex(),
							},
						},
					},
				}),
			].filter(Boolean);
		}

		/**
		 * @param {Object} action
		 * @param {Object} styles
		 * @returns {{title, testId: string}}
		 */
		createMenuItem = ({ action, styles }) => {
			const { text, type, style, iconFallbackUrl, ...actionParams } = action;

			const item = {
				...actionParams,
				title: text,
				testId: `dialog-galleryMenuAction-${action.id}`,
			};

			if (styles)
			{
				item.styles = styles;
			}
			item.onItemSelected = this.handleOnGalleryMenuItemSelected;

			return item;
		};

		/**
		 * @returns {Promise}
		 */
		closeGallery()
		{
			return this.gallery.close();
		}

		/**
		 * @param {menuItemParams} menuItem
		 * @param {galleryItemParams} galleryItem
		 */
		handleOnGalleryMenuItemSelected = async ({ menuItem, galleryItem }) => {
			const callbacksMap = this.getCallbacksMap();
			const callbackHandler = callbacksMap[menuItem.id];

			void callbackHandler?.({ menuItem, galleryItem });
		};

		/**
		 * @returns {Object<string, {galleryItem, menuItem}>}
		 */
		getCallbacksMap()
		{
			return ({
				[MessageMenuActionType.reply]: this.onReplySelected,
				[MessageMenuActionType.forward]: this.onForwardSelected,
				[MessageMenuActionType.goToMessage]: this.onGoToMessageSelected,
				[MessageMenuActionType.downloadToDisk]: this.onDownloadToDiskSelected,
				[MessageMenuActionType.downloadToDevice]: this.onDownloadToDeviceSelected,
				[MessageMenuActionType.delete]: this.onDeleteSelected,
			});
		}

		/**
		 * @returns {DialogId}
		 */
		getDialogId()
		{
			const { dialogId } = this.props;

			return dialogId;
		}

		/**
		 * @return {DialoguesModelState|{}}
		 */
		getDialog = () => {
			return this.store.getters['dialoguesModel/getById'](this.getDialogId()) || {};
		};

		/**
		 * @param {MessageHelper} messageHelper
		 * @returns {boolean}
		 */
		isPossibleSaveToLibrary(messageHelper)
		{
			const { isDeleted } = messageHelper;

			return !isDeleted;
		}

		async onBeforeAction()
		{
			const { onBeforeAction } = this.props;

			if (isFunction(onBeforeAction))
			{
				return onBeforeAction();
			}

			return Promise.resolve();
		}

		/**
		 * @param {DialogOpenOptions} params
		 */
		#openDialog(params)
		{
			MessengerEmitter.emit(
				EventType.messenger.openDialog,
				{
					dialogId: this.getDialogId(),
					...params,
				},
				ComponentCode.imMessenger,
			);
		}

		#timeout(timeout)
		{
			return new Promise((resolve) => {
				setTimeout(resolve, timeout);
			});
		}

		isVisibleDialog()
		{
			return this.visibilityManager.checkIsDialogVisible({
				dialogId: this.getDialogId(),
				currentContextOnly: true,
			});
		}
	}

	MediaMenu.propTypes = {
		dialogId: PropTypes.oneOfType([PropTypes.number, PropTypes.string]),
		dialogLocator: PropTypes.object,
		gallery: PropTypes.object,
		forceDelete: PropTypes.bool,
	};

	module.exports = {
		MediaMenu,
	};
});
