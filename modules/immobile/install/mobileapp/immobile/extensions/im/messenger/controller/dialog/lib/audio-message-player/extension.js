/**
 * @module im/messenger/controller/dialog/lib/audio-player
 */
jn.define('im/messenger/controller/dialog/lib/audio-player', (require, exports, module) => {
	const {
		FileType,
	} = require('im/messenger/const');
	const { Logger } = require('im/messenger/lib/logger');

	/**
	 * @class AudioMessagePlayer
	 */
	class AudioMessagePlayer
	{
		constructor(store)
		{
			this.store = store;
			this.player = null;
			this.playingMessageId = null;
		}

		play(messageId, playingTime = 0)
		{
			Logger.log('AudioMessagePlayer.play: messageId: ', messageId, ' playingTime:', playingTime);
			if (this.playingMessageId)
			{
				this.stop();
			}

			this.playingMessageId = messageId;
			const playingMessage = this.store.getters['messagesModel/getById'](this.playingMessageId);
			if (!playingMessage || !playingMessage.files[0])
			{
				return;
			}

			const file = this.store.getters['filesModel/getById'](playingMessage.files[0]);
			if (!file || file.type !== FileType.audio)
			{
				return;
			}

			this.setMessageIsPlaying(true, playingTime);
		}

		playNext()
		{
			const previousMessageId = this.playingMessageId;

			this.stop();

			const nextMessageToPlay = this.getNextMessageToPlay(previousMessageId);
			if (!nextMessageToPlay)
			{
				return;
			}

			this.store.dispatch('messagesModel/setPlayAudio', {
				id: nextMessageToPlay.id,
				audioPlaying: true,
			});

			this.play(nextMessageToPlay.id);
		}

		stop(playingTime = 0)
		{
			if (!this.playingMessageId)
			{
				return;
			}

			this.setMessageIsPlaying(false, playingTime);
			this.playingMessageId = null;
		}

		/**
		 * @private
		 */
		setMessageIsPlaying(isPlaying, playingTime)
		{
			const message = this.store.getters['messagesModel/getById'](this.playingMessageId);
			if (!message)
			{
				return;
			}

			this.store.dispatch('messagesModel/setAudioState', {
				id: this.playingMessageId,
				audioPlaying: isPlaying,
				playingTime,
			});
		}

		/**
		 * @private
		 */
		getNextMessageToPlay(previousMessageId)
		{
			const previousMessage = this.store.getters['messagesModel/getById'](previousMessageId);
			if (!previousMessage)
			{
				return null;
			}

			const chatMessageList = this.store.getters['messagesModel/getByChatId'](previousMessage.chatId);

			return chatMessageList.find((message) => {
				if (message.id <= previousMessage.id || !message.files[0])
				{
					return false;
				}

				const file = this.store.getters['filesModel/getById'](message.files[0]);

				return file && file.type === FileType.audio;
			});
		}
	}

	module.exports = {
		AudioMessagePlayer,
	};
});
