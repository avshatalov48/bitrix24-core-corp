/**
 * @module im/messenger/lib/element/dialog/message/audio
 */
jn.define('im/messenger/lib/element/dialog/message/audio', (require, exports, module) => {
	const { Type } = require('type');

	const { MessageType } = require('im/messenger/const');
	const { Message } = require('im/messenger/lib/element/dialog/message/base');
	const { Audio } = require('im/messenger/lib/element/dialog/message/element/audio/audio');

	/**
	 * @class AudioMessage
	 */
	class AudioMessage extends Message
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

			const audio = new Audio(modelMessage, file, options);
			this.audio = audio.toMessageFormat();

			/* region deprecated properties */
			this.audioUrl = this.audio.url;
			this.localAudioUrl = this.audio.localUrl;
			this.size = this.audio.size;
			/* end region */
		}

		getType()
		{
			return MessageType.audio;
		}

		getIsPlaying()
		{
			return this.audio.isPlaying;
		}

		setPlayingTime(playingTime)
		{
			if (!Type.isNumber(playingTime))
			{
				return;
			}

			this.audio.playingTime = playingTime;
		}
	}

	module.exports = {
		AudioMessage,
	};
});
