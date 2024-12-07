/**
 * @module im/messenger/lib/element/dialog/message/video
 */
jn.define('im/messenger/lib/element/dialog/message/video', (require, exports, module) => {
	const { MessageType } = require('im/messenger/const');
	const { Message } = require('im/messenger/lib/element/dialog/message/base');
	const { Video } = require('im/messenger/lib/element/dialog/message/element/video/video');

	/**
	 * @class VideoMessage
	 */
	class VideoMessage extends Message
	{
		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 * @param {FilesModelState} file
		 */
		constructor(modelMessage = {}, options = {}, file = {})
		{
			super(modelMessage, options);

			if (modelMessage.text !== '')
			{
				this.setMessage(modelMessage.text, { dialogId: options.dialogId });
			}

			this.setShowTail(true);
			this.setLoadText();

			this.video = Video.createByFileModel(file).toMessageFormat();

			/* region deprecated properties */
			this.videoUrl = this.video.url;
			this.localVideoUrl = this.video.localUrl;
			this.previewImage = this.video.previewImage;
			this.previewParams = this.video.previewParams;
			/* end region */
		}

		getType()
		{
			return MessageType.video;
		}

		setShowTail()
		{
			return this;
		}
	}

	module.exports = {
		VideoMessage,
	};
});
