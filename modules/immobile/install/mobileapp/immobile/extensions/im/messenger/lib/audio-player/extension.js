/**
 * @module im/messenger/lib/audio-player
 */
jn.define('im/messenger/lib/audio-player', (require, exports, module) => {
	const NativeAudioPlayer = require('native/media').AudioPlayer;
	const {
		FeatureFlag,
		FileType,
	} = require('im/messenger/const');
	const { Logger } = require('im/messenger/lib/logger');

	/**
	 * @class AudioPlayer
	 */
	class AudioPlayer
	{
		constructor(store)
		{
			if (!this.isSupported())
			{
				return;
			}

			this.store = store;
			this.player = null;
			this.playingMessageId = null;
			this.isPaused = false;
			this.loadingMessageIdCollection = new Set();
		}

		isSupported()
		{
			return FeatureFlag.native.mediaModuleSupported;
		}

		checkMessageWaitingToBeLoaded(messageId)
		{
			return this.loadingMessageIdCollection.has(messageId);
		}

		playMessage(messageId)
		{
			if (!this.isSupported())
			{
				return;
			}

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

			this.player = new NativeAudioPlayer({
				uri: file.urlShow,
			});

			this.loadingMessageIdCollection.add(this.playingMessageId);
			const waitingMessageId = this.playingMessageId;
			Logger.log(`AudioPlayer: message "${waitingMessageId}" is waiting to play`);

			this.player
				.on('ready', () => {
					if (!this.loadingMessageIdCollection.has(this.playingMessageId))
					{
						Logger.log(`AudioPlayer: message "${waitingMessageId}" playback was stopped before the download was complete`);

						return;
					}

					this.player.play();
					this.loadingMessageIdCollection.delete(this.playingMessageId);

					this.isPaused = false;
				})
				.on('play', () => {
					if (!this.isPaused)
					{
						return;
					}

					this.isPaused = false;
				})
				.on('pause', () => {
					this.isPaused = true;
					this.setMessageIsPlaying(false);
				})
				.on('finish', () => {
					const previousMessageId = this.playingMessageId;

					this.stop();

					const nextMessageToPlay = this.getNextMessageToPlay(previousMessageId);
					if (!nextMessageToPlay)
					{
						return;
					}

					this.store.dispatch('messagesModel/update', {
						id: nextMessageToPlay.id,
						fields: {
							audioPlaying: true,
						},
					});

					this.playMessage(nextMessageToPlay.id);
				})
				.on('error', (data) => {
					Logger.error('AudioPlayer.playMessage error: ', data);

					this.setMessageIsPlaying(false);
				})
			;

			this.setMessageIsPlaying(true);
		}

		stop()
		{
			if (!this.isSupported())
			{
				return;
			}

			if (!this.playingMessageId)
			{
				return;
			}

			if (this.checkMessageWaitingToBeLoaded(this.playingMessageId))
			{
				this.loadingMessageIdCollection.delete(this.playingMessageId);
			}

			this.setMessageIsPlaying(false);

			this.player.stop();
			this.playingMessageId = null;
			this.isPaused = null;
		}

		/**
		 * @private
		 */
		setMessageIsPlaying(isPlaying)
		{
			const message = this.store.getters['messagesModel/getById'](this.playingMessageId);
			if (!message)
			{
				return;
			}

			this.store.dispatch('messagesModel/update', {
				id: this.playingMessageId,
				fields: {
					audioPlaying: isPlaying,
				},
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
				if (!file || file.type !== FileType.audio)
				{
					return false;
				}

				return true;
			});
		}
	}

	module.exports = {
		AudioPlayer,
	};
});
