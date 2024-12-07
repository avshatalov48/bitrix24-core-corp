/**
 * @module im/messenger/lib/element/dialog/message/gallery/factory
 */
jn.define('im/messenger/lib/element/dialog/message/gallery/factory', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('loc');

	const {
		FileType,
	} = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');
	const { CustomMessageFactory } = require('im/messenger/lib/element/dialog/message/custom/factory');
	const { GalleryMessage } = require('im/messenger/lib/element/dialog/message/gallery/message');
	const { ImageMessage } = require('im/messenger/lib/element/dialog/message/image');
	const { TextMessage } = require('im/messenger/lib/element/dialog/message/text');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const {
		getShortFileName,
		getFileTypeByExtension,
		getFileExtension,
	} = require('im/messenger/lib/helper');

	/**
	 * @class GalleryMessageFactory
	 */
	class GalleryMessageFactory extends CustomMessageFactory
	{
		/**
		 * @abstract
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 * @param {FilesModelState} file
		 * @return {Message}
		 */
		static create(modelMessage = {}, options = {})
		{
			const store = serviceLocator.get('core').getStore();
			const fileList = store.getters['filesModel/getListByMessageId'](modelMessage.id);
			const isImageGallery = fileList.every((file) => file.type === FileType.image);

			let message;
			let messageText;
			if (isImageGallery)
			{
				message = new ImageMessage(modelMessage, options, fileList[0]);
				messageText = this.createImageMessageText(message, modelMessage);
			}
			else
			{
				message = new TextMessage(modelMessage, options);
				messageText = this.createFileMessageText(modelMessage);
			}

			message.setMessage(messageText);

			return message;
		}

		/**
		 * @param {Message} message
		 * @param {MessagesModelState} modelMessage
		 * @return {string}
		 */
		static createImageMessageText(message, modelMessage)
		{
			const otherFilesLength = ((modelMessage?.files?.length ?? 0) - 1)?.toString();
			const galleryText = Loc
				.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_GALLERY_PHOTO')
				.replace('#COUNT#', otherFilesLength)
			;

			// link to avoid processing by the general rules for /mobile/
			const host = serviceLocator.get('core').getHost();
			const openGalleryUrl = `${host}/immobile/in-app/message-gallery/${modelMessage.id}`;

			let messageText = `[url=${openGalleryUrl}]${galleryText} >[/url]`;
			if (Type.isStringFilled(modelMessage.text))
			{
				messageText += `\n\n${modelMessage.text}`;
			}

			return messageText;
		}

		/**
		 * @param {MessagesModelState} modelMessage
		 * @return {string}
		 */
		static createFileMessageText(modelMessage)
		{
			const store = serviceLocator.get('core').getStore();
			const fileList = store.getters['filesModel/getListByMessageId'](modelMessage.id);
			if (!Type.isArrayFilled(fileList))
			{
				return modelMessage.text;
			}

			// link to avoid processing by the general rules for /mobile/
			const host = serviceLocator.get('core').getHost();
			const openFileUrlBase = `${host}/immobile/in-app/file-open/`;

			let messageText = '';
			const fileListLength = fileList.length;
			fileList.forEach((file, index, files) => {
				const fileExtension = getFileExtension(file.name);
				const fileType = getFileTypeByExtension(fileExtension);
				let emojiIcon;
				switch (fileType)
				{
					case FileType.image:
						emojiIcon = '🖼️';
						break;

					case FileType.video:
						emojiIcon = '📺';
						break;

					case FileType.audio:
						emojiIcon = '🎤';
						break;

					default:
						emojiIcon = '🗂️';
						break;
				}

				const openFileUrl = `${openFileUrlBase}${file.id}`;
				let fileName = file.name;
				// because dialog widget tries to render text as an image if it sees .webp
				if (fileExtension === 'webp')
				{
					fileName = file.name.replace(`.${fileExtension}`, '');
				}

				const shortFileName = getShortFileName(fileName, 30);
				messageText += `${emojiIcon} [url=${openFileUrl}]${shortFileName}[/url]`;

				if (index < fileListLength - 1)
				{
					messageText += '\n\n';
				}
			});

			if (Type.isStringFilled(modelMessage.text))
			{
				messageText += `\n\n${modelMessage.text}`;
			}

			return messageText;
		}

		/**
		 * @abstract
		 * @return {boolean}
		 */
		static checkSuitableForDisplay(modelMessage)
		{
			if (Feature.isGalleryMessageSupported)
			{
				return false;
			}

			return Type.isArray(modelMessage.files) && modelMessage.files.length > 1;
		}

		/**
		 * @abstract
		 * @return {string}
		 */
		static getComponentId()
		{
			return GalleryMessage.getComponentId();
		}
	}

	module.exports = {
		GalleryMessageFactory,
	};
});
