/**
 * @module im/messenger/lib/element/dialog/message/media-gallery
 */
jn.define('im/messenger/lib/element/dialog/message/media-gallery', (require, exports, module) => {
	const {
		FileType,
		MessageType,
	} = require('im/messenger/const');
	const { Message } = require('im/messenger/lib/element/dialog/message/base');
	const { Image } = require('im/messenger/lib/element/dialog/message/element/image/image');
	const { Video } = require('im/messenger/lib/element/dialog/message/element/video/video');

	/**
	 * @class MediaGalleryMessage
	 */
	class MediaGalleryMessage extends Message
	{
		mediaList;

		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 * @param {Array<FilesModelState>} fileList
		 */
		constructor(modelMessage = {}, options = {}, fileList = [])
		{
			super(modelMessage, options);

			this.setMessage(modelMessage.text);
			this.setShowTail(true);
			this.setLoadText();

			this.mediaList = this.createMediaList(fileList);
		}

		/**
		 * @protected
		 * @return {string}
		 */
		getType()
		{
			return MessageType.mediaGallery;
		}

		/**
		 * @protected
		 * @param {Array<FilesModelState>} fileList
		 */
		createMediaList(fileList)
		{
			const mediaList = [];
			fileList.forEach((file) => {
				if (file.type === FileType.image)
				{
					const image = Image.createByFileModel(file).toMessageFormat();

					mediaList.push(image);
				}

				if (file.type === FileType.video)
				{
					const video = Video.createByFileModel(file).toMessageFormat();

					mediaList.push(video);
				}
			});

			return mediaList;
		}
	}

	module.exports = {
		MediaGalleryMessage,
	};
});
