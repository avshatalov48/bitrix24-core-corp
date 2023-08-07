/**
 * @module im/messenger/lib/element/dialog/message/audio
 */
jn.define('im/messenger/lib/element/dialog/message/audio', (require, exports, module) => {
	const { Type } = require('type');
	const { Message } = require('im/messenger/lib/element/dialog/message/base');

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

			/* region deprecated properties */
			this.audioUrl = null;
			this.isPlaying = null;
			this.localAudioUrl = null;
			this.size = null;
			this.playingTime = null;
			/* end region */

			this.audio = {
				localUrl: null,
				url: null,
				size: null,
				isPlaying: null,
				playingTime: null,
			};

			this.setAudioUrl(file.urlShow);
			this.setLocalAudioUrl(file.localUrl);
			this.setPlayingTime(modelMessage.playingTime);
			this.setSize(file.size);
			this.setIsPlaying(modelMessage.audioPlaying);

			if (modelMessage.text !== '')
			{
				this.setMessage(modelMessage.text);
			}

			this.setShowTail(true);
		}

		getType()
		{
			return 'audio';
		}

		setAudioUrl(audioUrl)
		{
			if (!Type.isStringFilled(audioUrl))
			{
				return;
			}

			this.audioUrl = audioUrl;
			this.audio.url = audioUrl;
		}

		setLocalAudioUrl(localUrl)
		{
			if (!Type.isStringFilled(localUrl))
			{
				return;
			}

			this.localAudioUrl = localUrl;
			this.audio.localUrl = localUrl;
		}

		setPlayingTime(playingTime)
		{
			if (!Type.isNumber(playingTime))
			{
				return;
			}

			this.playingTime = playingTime;
			this.audio.playingTime = playingTime;
		}

		setSize(size)
		{
			if (!Type.isNumber(size))
			{
				return;
			}

			this.size = size;
			this.audio.size = size;
		}

		setIsPlaying(audioPlaying)
		{
			this.isPlaying = Boolean(audioPlaying);
			this.audio.isPlaying = Boolean(audioPlaying);
		}
	}

	module.exports = {
		AudioMessage,
	};
});
