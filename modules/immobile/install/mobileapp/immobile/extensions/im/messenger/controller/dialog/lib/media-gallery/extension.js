/**
 * @module im/messenger/controller/dialog/lib/media-gallery
 */
jn.define('im/messenger/controller/dialog/lib/media-gallery', (require, exports, module) => {
	const { isEmpty } = require('utils/object');
	const { parser } = require('im/messenger/lib/parser');
	const { MediaGallery } = require('media-gallery');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { PropTypes } = require('utils/validation');
	const { FileType } = require('im/messenger/const');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');
	const { MediaMenu } = require('im/messenger/controller/dialog/lib/media-gallery/src/media-menu');
	const { getLogger } = require('im/messenger/lib/logger');

	const logger = getLogger('dialog--dialog');

	/**
	 * @class DialogMediaGallery
	 */
	class DialogMediaGallery
	{
		/**
		 * @param {mediaHandlerProps} props
		 */
		constructor(props)
		{
			const { dialogLocator, dialogModel } = props;

			this.props = props;
			this.gallery = null;
			this.store = dialogLocator.get('store');
			this.dialogHelper = DialogHelper.createByModel(dialogModel);
		}

		/**
		 * @param {mediaHandlerProps} props
		 */
		static open(props)
		{
			return (new DialogMediaGallery(props)).openCollection();
		}

		/**
		 * @returns {MediaMenu}
		 */
		createMediaMenu()
		{
			const { dialogLocator, onBeforeAction, forceDelete } = this.props;

			return MediaMenu.create({
				dialogLocator,
				gallery: MediaGallery,
				dialogId: this.getDialogId(),
				onBeforeAction,
				forceDelete,
			});
		}

		openCollection()
		{
			const { mediaId, messageId, localUrl = null } = this.props;

			logger.log(`${this.constructor.name}.imageTapHandler`, mediaId, messageId, localUrl);

			void this.openMediaCollection();
		}

		async openMediaCollection()
		{
			const mediaMenu = this.createMediaMenu();
			const mediaCollection = this.createMediaCollection(mediaMenu);
			this.gallery = await MediaGallery.open(mediaCollection).catch(console.error);
			mediaMenu.setGalleryWidget(this.gallery);
		}

		/**
		 * @returns {mediaGalleryItem}
		 */
		createMediaCollection(mediaMenu)
		{
			const { mediaId, direction } = this.props;

			const mediaList = this.getMediaList().map((media) => ({
				id: media.id,
				type: media.type,
				url: media.urlShow,
				previewUrl: media.urlPreview,
				default: media.id === Number(mediaId),
				description: parser.simplify({ text: media.description }),
				header: {
					title: media.authorName,
					subtitle: DateFormatter.getMediaFormat(new Date(media.date)),
				},
				customData: {
					mediaId,
					messageId: media.messageId,
					dialogId: this.getDialogId(),
				},
				menu: mediaMenu.getItems(media.messageId),
			}));

			return direction === 'right' ? mediaList.reverse() : mediaList;
		}

		/**
		 * @returns {Array<mediaListItem>}
		 */
		getMediaList()
		{
			const { mediaList } = this.props;
			if (mediaList)
			{
				return mediaList;
			}

			return this.getMessageList()
				.flatMap((message) => {
					if (isEmpty(message.files))
					{
						return [];
					}

					return this.#getModelStateList(message.files)
						.map((file, index) => ({
							...file,
							description: index === 0 ? message.text : '',
							messageId: message.id,
						}));
				})
				.filter((file) => file && (file.type === FileType.image || file.type === FileType.video));
		}

		/**
		 * @returns {Array<MessagesModelState>}
		 */
		getMessageList()
		{
			const { dialogModel } = this.props;
			const messageList = this.store.getters['messagesModel/getByChatId'](dialogModel.chatId);
			const parentMessage = this.getParentMessage();
			if (parentMessage)
			{
				messageList.unshift(parentMessage);
			}

			return messageList;
		}

		/**
		 * @returns {MessagesModelState | null}
		 */
		getParentMessage()
		{
			if (!this.dialogHelper.isComment)
			{
				return null;
			}

			const { dialogModel } = this.props;

			const message = this.#getMessageModelById(dialogModel.parentMessageId);

			if (!message.id)
			{
				return null;
			}

			return message;
		}

		/**
		 * @returns {DialogId}
		 */
		getDialogId()
		{
			const { dialogModel } = this.props;

			return dialogModel.dialogId;
		}

		/**
		 * @param {MessageId} messageId
		 * @returns {MessagesModelState|{}}
		 */
		#getMessageModelById(messageId)
		{
			return this.store.getters['messagesModel/getById'](messageId);
		}

		/**
		 * @param {Array<number>} fileIds
		 * @returns {Array<FilesModelState>}
		 */
		#getModelStateList(fileIds)
		{
			return this.store.getters['filesModel/getByIdList'](fileIds);
		}
	}

	DialogMediaGallery.propTypes = {
		messageId: PropTypes.oneOfType([PropTypes.number, PropTypes.string]),
		mediaId: PropTypes.oneOfType([PropTypes.number, PropTypes.string]),
		mediaType: PropTypes.oneOf([FileType.video, FileType.image]),
		localUrl: PropTypes.string,
		dialogLocator: PropTypes.object,
		dialogModel: PropTypes.object,
		onBeforeAction: PropTypes.func,
		forceDelete: PropTypes.bool,
		direction: PropTypes.oneOf(['left', 'right']),
	};

	module.exports = {
		DialogMediaGallery,
	};
});
