/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

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
		constructor(modelMessage = {}, options = {}, file = {})
		{
			super(modelMessage, options);
			this.audioUrl = null;
			this.isPlaying = modelMessage.audioPlaying;
			this.setAudioUrl(file.urlShow);
			this.setPlayingTime(modelMessage.playingTime);

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
		}

		setPlayingTime(playingTime)
		{
			if (!Type.isNumber(playingTime))
			{
				return;
			}

			this.playingTime = playingTime;
		}
	}

	module.exports = {
		AudioMessage,
	};
});
