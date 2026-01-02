/**
 * @module im/messenger/controller/dialog/lib/audio-panel/src/audio-panel
 */
jn.define('im/messenger/controller/dialog/lib/audio-panel/src/audio-panel', (require, exports, module) => {
	const {
		FileType,
	} = require('im/messenger/const');
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { isAudioMessageFile } = require('im/messenger/lib/helper');
	const { isEmpty, isEqual } = require('utils/object');
	const { throttle } = require('utils/function');
	const { AudioPlayer } = require('native/media');
	const { AsyncQueue } = require('im/messenger/lib/utils');
	const { Logger } = require('im/messenger/lib/logger');
	const { AudioEvents } = require('im/messenger/const');
	const { AnalyticsService } = require('im/messenger/provider/services/analytics');

	/**
	 * @class AudioPanel
	 */
	class AudioPanel
	{
		#props;
		#player;
		#asyncQueue;
		/** @type {AnalyticsService} */
		#analyticsService;
		#playingState = {
			messageId: null,
			playingTime: 0,
			isPlaying: false,
		};

		/**
		 * @param props
		 * @param {MessengerCoreStore} props.store
		 * @param {ChatId} props.chatId
		 */
		constructor(props)
		{
			this.#props = props;
			this.#player = this.#createPlayer();
			this.#asyncQueue = new AsyncQueue();
			this.#analyticsService = AnalyticsService.getInstance();

			this.setPlayingTime = throttle(this.setPlayingTime, 200, this);
			this.#initPlayerEvents();
		}

		/**
		 * @public
		 */
		initialPlayerState()
		{
			const currentAudio = this.#getCurrentAudio();
			if (!currentAudio)
			{
				return;
			}

			const messageId = currentAudio?.associatedId;
			const message = this.#getMessageById(messageId);
			if (!message)
			{
				return;
			}

			const audioSpeed = this.#player.getCurrentSpeed();
			if (audioSpeed > 0)
			{
				void this.#updateAudioMessageSpeed(audioSpeed);
			}

			const options = {
				messageId,
				isPlaying: this.#isPlayingCurrentAudio(),
				playingTime: this.getPlayingTime(),
			};

			this.#setPlayingState(options);
		}

		#initPlayerEvents()
		{
			this.#player.on(AudioEvents.playingMediaChanged, this.#playingMediaChanged);
			this.#player.on(AudioEvents.speedChanged, this.#playingSpeedChanged);
			this.#player.on(AudioEvents.timeUpdate, this.setPlayingTime);
		}

		#playingMediaChanged = ({ isPlaying, currentAudio }) => {
			const playingMessageId = this.getPlayingMessageId();
			const isPlaylistFinished = !isPlaying && !currentAudio;
			if (isPlaylistFinished)
			{
				this.stop({ messageId: playingMessageId });

				return;
			}

			const changedMessageId = currentAudio?.associatedId;
			const shouldStopPrevious = isPlaying && !this.isPlayingMessageById(changedMessageId);
			if (shouldStopPrevious)
			{
				// Stop the previous message
				this.stop({ messageId: playingMessageId });
			}

			this.#setPlayingState({
				messageId: changedMessageId,
				isPlaying,
				playingTime: this.getPlayingTime(),
			});
		};

		#playingSpeedChanged = (speed) => {
			this.#asyncQueue.enqueue(async () => {
				const audioRate = this.#roundAudioSpeed(speed);
				if (audioRate !== this.#getAudioRate())
				{
					await this.#updateAudioMessageSpeed(audioRate);
					await this.#setMessageIsPlaying({ messageId: this.getPlayingMessageId() });
				}
			});
		};

		#createPlayer()
		{
			return new AudioPlayer({
				id: `audioPanel-chatId-${this.chatId}`,
				keepPlaying: true,
			});
		}

		/**
		 * @param messageId
		 * @returns {{uri: *}[]|*[]}
		 */
		createPlayList(messageId)
		{
			const files = this.getAudioFiles(messageId);

			if (isEmpty(files))
			{
				return [];
			}

			const { dialogId } = this.#props;

			return files.map((file) => ({
				uri: file.localUrl || file.urlDownload,
				title: this.#prepareFileName(file.name, messageId),
				associatedId: String(file.messageId),
				returnData: {
					dialogId,
					type: 'chat',
					text: Loc.getMessage('IMMOBILE_AUDIO_PANEL_GO_TO_AUDIO'),
					fileId: file.id,
					messageId: file.messageId,
				},
			}));
		}

		/**
		 * @param {string} fileName
		 * @param {MessageId} messageId
		 * @returns {string}
		 */
		#prepareFileName(fileName, messageId)
		{
			const message = this.#getMessageById(messageId);
			if (!isAudioMessageFile(fileName) || !message)
			{
				return fileName;
			}

			const { authorId } = message;
			const user = this.store.getters['usersModel/getById'](authorId);

			return user.name || fileName;
		}

		/**
		 * @param {Object} options
		 * @param {MessageId} options.messageId
		 * @param {number} [options.playingTime = 0]
		 */
		play(options)
		{
			const { messageId, playingTime = 0 } = options;
			Logger.log('AudioPanel.play: messageId: ', messageId, ' playingTime:', playingTime);

			if (this.isPlayingMessageById(messageId))
			{
				this.stop(options);
				this.pause(options);
			}

			// If the message is already playing, we just resume it
			if (this.isPaused(messageId))
			{
				this.#player.play();

				return;
			}

			this.#playListByMessageId(messageId);
		}

		/**
		 * @param {Object} options
		 * @param {MessageId} options.messageId
		 * @param {number} [options.playingTime = 0]
		 */
		pause({ messageId, playingTime = 0 })
		{
			Logger.log('AudioPanel.pause: messageId: ', messageId, ' playingTime:', playingTime);
			this.#analyticsService.sendClickToPauseAudioInChat({ dialogId: this.dialogId });
			this.#player.pause();
		}

		/**
		 * @param {Object} options
		 * @param {MessageId} options.messageId
		 * @param {number} [options.playingTime=0]
		 */
		stop(options)
		{
			const { messageId } = options;

			Logger.log('AudioPanel.stop: messageId: ', messageId);
			this.#setPlayingState({
				messageId,
				isPlaying: false,
				playingTime: 0,
			});
		}

		/**
		 * @return {Promise}
		 */
		changeRate()
		{
			const audioSpeed = this.#getAudioMessageSpeed();
			this.#changePlayerSpeed(audioSpeed);
			this.#analyticsService.sendClickToChangeAudioSpeedInChat({
				dialogId: this.dialogId,
				speed: audioSpeed,
			});

			return this.#updateAudioMessageSpeed(audioSpeed);
		}

		/**
		 * @param {AudioRate} speed
		 */
		#changePlayerSpeed(speed)
		{
			this.#player.setSpeed(this.#roundAudioSpeed(speed));
		}

		readMessageList(messageList)
		{
			const isVisibleMessage = messageList.find((message) => message.id === this.getPlayingMessageId());
			const togglePanelVisibility = isVisibleMessage ? this.hidePanel : this.showPanel;

			togglePanelVisibility();
		}

		/**
		 * @param {MessageId} messageId
		 */
		#playListByMessageId(messageId)
		{
			const playList = this.createPlayList(messageId);

			this.#player.playList(playList);
		}

		/**
		 * @param {MessageId} messageId
		 * @param {boolean} [isPlaying]
		 * @param {number} [playingTime]
		 * @returns {Promise}
		 */
		async #setMessageIsPlaying({ messageId, isPlaying, playingTime = 0 })
		{
			const message = this.#getMessageById(messageId);

			if (!message)
			{
				return Promise.reject();
			}

			const { messageList } = await this.#getViewableMessages().catch(console.error);
			this.readMessageList(messageList);

			return this.store.dispatch('messagesModel/setAudioState', {
				id: messageId,
				audioPlaying: isPlaying,
				playingTime,
			});
		}

		/**
		 * @param {AudioRate} audioSpeed
		 * @return {Promise}
		 */
		#updateAudioMessageSpeed(audioSpeed)
		{
			return this.store.dispatch(
				'applicationModel/setAudioRateSetting',
				this.#roundAudioSpeed(audioSpeed),
			).catch((error) => {
				Logger.error(
					'setApplicationAudioRate.applicationModel/setAudioRateSetting.catch:',
					error,
				);
			});
		}

		/**
		 * @private
		 * @param {string|number} messageId
		 * @return {Array<(FilesModelState & {messageId: number})>}
		 */
		getAudioFiles(messageId)
		{
			const chatMessageList = this.store.getters['messagesModel/getByChatId'](this.chatId);

			if (!Type.isArrayFilled(chatMessageList))
			{
				return [];
			}

			return chatMessageList
				.filter((message) => message.id >= messageId && message.files?.length === 1)
				.map((message) => {
					const file = this.store.getters['filesModel/getById'](message.files[0]);

					return file ? { ...file, messageId: message.id } : null;
				})
				.filter((file) => file && file.type === FileType.audio);
		}

		/**
		 * @returns {AudioRate}
		 */
		#getAudioMessageSpeed()
		{
			const availableRates = [1, 1.5, 2];
			const currentRate = this.#getAudioRate();
			const currentIndex = availableRates.indexOf(currentRate);
			const nextIndex = (currentIndex + 1) % availableRates.length;

			return availableRates[nextIndex];
		}

		/**
		 * @private
		 * @param {string|number} messageId
		 * @returns {null|MessagesModelState|{}}
		 */
		#getMessageById(messageId)
		{
			const message = this.store.getters['messagesModel/getById'](messageId);

			return isEmpty(message) ? null : message;
		}

		/**
		 * @param {MessageId} messageId
		 * @returns {boolean}
		 */
		isPlayingMessageById(messageId)
		{
			const currentAudio = this.#getCurrentAudio();
			if (!this.hasPlayingMessage() && currentAudio)
			{
				return currentAudio?.associatedId === messageId && this.#isPlayingCurrentAudio();
			}

			return Number(this.getPlayingMessageId()) === Number(messageId) && this.isPlaying();
		}

		/**
		 * @param {MessageId} [messageId]
		 * @returns {boolean}
		 */
		isPaused(messageId)
		{
			const currentAudio = this.#getCurrentAudio();

			return this.hasPlayingMessage()
				&& !this.#isPlayingCurrentAudio()
				&& currentAudio?.associatedId === messageId;
		}

		/**
		 * @returns {boolean}
		 */
		hasPlayingMessage()
		{
			return this.getPlayingMessageId() !== null;
		}

		/**
		 * @param {object} state
		 * @param {MessageId|null} state.messageId
		 * @param {boolean} state.isPlaying
		 * @param {number} [state.playingTime]
		 */
		#setPlayingState(state)
		{
			const nextState = {
				...this.#playingState,
				...state,
			};

			if (isEqual(this.#playingState, nextState))
			{
				return;
			}

			this.#playingState = nextState;

			void this.#setMessageIsPlaying(state);
		}

		/**
		 * @returns {MessageId}
		 */
		getPlayingMessageId()
		{
			return this.#playingState.messageId ?? this.#getCurrentMessageId();
		}

		/**
		 * @param {number} currentTime
		 */
		setPlayingTime({ currentTime } = {})
		{
			if (currentTime > 0)
			{
				this.#playingState.playingTime = currentTime;
			}
		}

		/**
		 * @returns {number}
		 */
		getPlayingTime()
		{
			return this.#playingState.playingTime;
		}

		/**
		 * @returns {Boolean}
		 */
		#isPlayingCurrentAudio()
		{
			const { isPlaying } = this.#getCurrentMediaState();

			return isPlaying;
		}

		/**
		 * @returns {CurrentAudioState}
		 */
		#getCurrentAudio()
		{
			const { currentAudio } = this.#getCurrentMediaState();

			return currentAudio;
		}

		/**
		 * @returns {MessageId|null}
		 */
		#getCurrentMessageId()
		{
			const currentAudio = this.#getCurrentAudio();
			if (!currentAudio)
			{
				return null;
			}

			return currentAudio?.associatedId;
		}

		/**
		 * @returns {CurrentMediaState}
		 */
		#getCurrentMediaState()
		{
			return this.#player.getCurrentMediaState();
		}

		/**
		 * @returns {boolean}
		 */
		isPlaying()
		{
			return Boolean(this.#playingState.isPlaying);
		}

		destructor()
		{
			if (Application.getPlatform() === 'android')
			{
				this.#player.release();
			}
		}

		/**
		 * @param {number} number
		 * @returns {number}
		 */
		#roundAudioSpeed(number)
		{
			if (number % 1 === 0)
			{
				return number;
			}

			const precision = 1;
			const factor = 10 ** precision;

			return Math.round(number * factor) / factor;
		}

		/**
		 * @returns {number}
		 */
		#getAudioRate()
		{
			const applicationSettingState = this.store.getters['applicationModel/getSettings']();

			return applicationSettingState?.audioRate ?? 1;
		}

		/**
		 * @returns {Promise<{messageList: Array<Message>, indexList: Array<number>}>}
		 */
		#getViewableMessages()
		{
			const { getViewableMessages } = this.#props;

			return getViewableMessages();
		}

		showPanel = () => {
			this.#player.showAudioPanel();
		};

		hidePanel = () => {
			this.#player.hideAudioPanel();
		};

		/**
		 * @returns {MessengerCoreStore}
		 */
		get store()
		{
			const { store } = this.#props;

			return store;
		}

		/**
		 * @returns {ChatId}
		 */
		get chatId()
		{
			const { chatId } = this.#props;

			return chatId;
		}

		/**
		 * @returns {DialogId}
		 */
		get dialogId()
		{
			const { dialogId } = this.#props;

			return dialogId;
		}
	}

	module.exports = {
		AudioPanel,
	};
});
