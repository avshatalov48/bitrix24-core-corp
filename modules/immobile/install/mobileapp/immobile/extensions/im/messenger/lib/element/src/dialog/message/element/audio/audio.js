/**
 * @module im/messenger/lib/element/dialog/message/element/audio/audio
 */
jn.define('im/messenger/lib/element/dialog/message/element/audio/audio', (require, exports, module) => {
	const { Type } = require('type');

	class Audio
	{
		/**
		 * @param {MessagesModelState} messageModel
		 * @param {FilesModelState} fileModel
		 * @param {object} options
		 * @param {AudioRate} options.audioRate
		 */
		constructor(messageModel, fileModel, options = {})
		{
			this.messageModel = messageModel;
			this.fileModel = fileModel;
			this.options = options;
		}

		/**
		 * @return {MessageAudio}
		 */
		toMessageFormat()
		{
			return {
				id: this.#getId(),
				type: this.#getMessageElementType(),
				localUrl: this.#getLocalUrl(),
				url: this.#getUrl(),
				size: this.#getSize(),
				isPlaying: this.#getIsPlaying(),
				playingTime: this.#getPlayingTime(),
				rate: this.#getRate(),
			};
		}

		/**
		 * @return {MessageAudio['type']}
		 */
		#getMessageElementType()
		{
			return 'audio';
		}

		/**
		 * @return {MessageAudio['id']}
		 */
		#getId()
		{
			if (Type.isNumber(this.fileModel.id))
			{
				return this.fileModel.id.toString();
			}

			if (Type.isStringFilled(this.fileModel.id))
			{
				return this.fileModel.id;
			}

			return 0;
		}

		/**
		 * @return {MessageAudio['url']}
		 */
		#getUrl()
		{
			if (Type.isStringFilled(this.fileModel.urlShow))
			{
				return this.fileModel.urlShow;
			}

			return null;
		}

		/**
		 * @return {MessageAudio['localUrl']}
		 */
		#getLocalUrl()
		{
			if (Type.isStringFilled(this.fileModel.localUrl))
			{
				return this.fileModel.localUrl;
			}

			return null;
		}

		/**
		 * @return {MessageAudio['size']}
		 */
		#getSize()
		{
			if (Type.isNumber(this.fileModel.size))
			{
				return this.fileModel.size;
			}

			return null;
		}

		/**
		 * @return {MessageAudio['playingTime']}
		 */
		#getPlayingTime()
		{
			if (Type.isNumber(this.messageModel.playingTime))
			{
				return this.messageModel.playingTime;
			}

			return null;
		}

		/**
		 * @return {MessageAudio['isPlaying']}
		 */
		#getIsPlaying()
		{
			return this.messageModel.audioPlaying;
		}

		/**
		 * @return {MessageAudio['rate']}
		 */
		#getRate()
		{
			if (Type.isNumber(this.options.audioRate))
			{
				return this.options.audioRate;
			}

			return 1;
		}
	}

	module.exports = { Audio };
});
