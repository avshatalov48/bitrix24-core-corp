/**
 * @module im/messenger/controller/dialog/lib/transcript-manager
 */
jn.define('im/messenger/controller/dialog/lib/transcript-manager', (require, exports, module) => {
	const { Type } = require('type');

	const { throttle } = require('utils/function');
	const { TranscriptStatus, TranscriptResponseStatus, EventType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { getLogger } = require('im/messenger/lib/logger');
	const { Feature } = require('im/messenger/lib/feature');

	const logger = getLogger('dialog--transcript-manager');

	/**
	 * @class {TranscriptManager}
	 */
	class TranscriptManager
	{
		/**
		 * @type {ChatId}
		 */
		#chatId;

		/**
		 * @type {DialogId}
		 */
		#dialogId = null;

		/**
		 * @type {DialogLocator}
		 */
		#dialogLocator;

		/**
		 * @type {AudioMessagePlayer}
		 */
		#audioMessagePlayer;

		/**
		 * @desc Check and validate text message quote
		 * @param {MessagesModelState} message
		 * @return {MessagesModelState} validateQuoteMessage
		 */
		#validateQuote = (messageModel) => messageModel;

		/**
		 * @type {boolean}
		 */
		#isLocalStorageSupported = false;

		/**
		 * @param {ChatId} chatId
		 * @param {DialogId} dialogId
		 * @param {DialogLocator} dialogLocator
		 * @param {AudioMessagePlayer} audioMessagePlayer
		 * @param {MessageRenderer} messageRenderer
		 * @param {Function} validateQuote
		 * @param {boolean} isLocalStorageSupported
		 */
		constructor({
			chatId,
			dialogId,
			dialogLocator,
			audioMessagePlayer,
			validateQuote,
			isLocalStorageSupported,
		})
		{
			this.#chatId = chatId;
			this.#dialogId = dialogId;
			this.#dialogLocator = dialogLocator;
			this.#audioMessagePlayer = audioMessagePlayer;
			this.#validateQuote = validateQuote;
			this.#isLocalStorageSupported = isLocalStorageSupported;

			this.#audioTranscriptionTapHandler = throttle(this.#audioTranscriptionTapHandler, 300);

			this.#subscribeEvents();
		}

		subscribeStoreEvents()
		{
			serviceLocator.get('core').getStoreManager()
				.on('filesModel/transcriptModel/add', this.#transcriptModelUpdateHandler)
				.on('filesModel/transcriptModel/update', this.#transcriptModelUpdateHandler);
		}

		unsubscribeStoreEvents()
		{
			serviceLocator.get('core').getStoreManager()
				.off('filesModel/transcriptModel/add', this.#transcriptModelUpdateHandler)
				.off('filesModel/transcriptModel/update', this.#transcriptModelUpdateHandler);
		}

		unsubscribeEvents()
		{
			this.#dialogLocator.get('view').off(EventType.dialog.audioTranscriptionTap, this.#audioTranscriptionTapHandler);
		}

		/**
		 * @param {FileId} fileId
		 * @returns {TranscriptModelState | null}
		 */
		getTranscriptStorage(fileId)
		{
			return this.#store.getters['filesModel/transcriptModel/getById'](fileId);
		}

		/**
		 * @param {FileId} fileId
		 * @returns {boolean}
		 */
		hasTranscriptStorage(fileId)
		{
			return this.#store.getters['filesModel/transcriptModel/hasTranscriptText'](fileId);
		}

		/**
		 * @param {TranscriptModelState} transcriptModel
		 */
		async setToStorage(transcriptModel)
		{
			await this.#store.dispatch('filesModel/transcriptModel/set', transcriptModel);
		}

		/**
		 * @param {FileId} fileId
		 */
		async toggleTextStorage(fileId)
		{
			await this.#store.dispatch('filesModel/transcriptModel/toggleText', { fileId });
		}

		/**
		 * @return {MessengerCoreStore}
		 */
		get #store()
		{
			return serviceLocator.get('core').getStore();
		}

		/**
		 * @return {DiskService|null}
		 */
		get #diskService()
		{
			const diskService = this.#dialogLocator.get('disk-service');
			if (diskService)
			{
				return diskService;
			}

			return null;
		}

		/**
		 * @param {MessageId} messageId
		 * @param {FileId} fileId
		 */
		#audioTranscriptionTapHandler = async (messageId, fileId) => {
			await this.#loadTranscriptModelFromDatabase(fileId);

			if (this.hasTranscriptStorage(fileId))
			{
				void this.toggleTextStorage(fileId);

				return;
			}

			this.#audioMessagePlayer.stopPlayingMessage?.();

			await this.#transcribe({ messageId, fileId });
		};

		/**
		 * @param {MutationPayload<TranscriptAddData | TranscriptUpdateData>} payload
		 */
		#transcriptModelUpdateHandler = async ({ payload }) => {
			logger.log(`${this.constructor.name}.#transcriptModelUpdateHandler ${this.#dialogId}`, payload);

			const { transcriptList } = payload.data;
			const dialog = this.#store.getters['dialoguesModel/getById'](this.#dialogId) || {};
			const messages = transcriptList
				.map((transcript) => this.#store.getters['messagesModel/getById'](transcript.messageId))
				.filter((message) => message.chatId === this.#chatId || message?.id === dialog.parentMessageId);

			if (!Type.isArrayFilled(messages))
			{
				return;
			}

			const validateQuoteMessages = messages
				.map((message) => this.#validateQuote(message));

			logger.log(`${this.constructor.name}.#transcriptModelUpdateHandler ${this.#dialogId}`, validateQuoteMessages);

			await this.#dialogLocator.get('message-renderer')
				.updateMessageList(validateQuoteMessages, 'speech2text');

			// TODO: scrolling to the message is temporarily disabled
			// const lastTranscript = transcriptList[transcriptList.length - 1];
			// await this.#scrollToTranscriptMessage(lastTranscript);
		};

		#subscribeEvents()
		{
			this.#dialogLocator.get('view')
				.on(EventType.dialog.audioTranscriptionTap, this.#audioTranscriptionTapHandler);
		}

		/**
		 * @param {MessageId} messageId
		 * @param {FileId} fileId
		 */
		async #transcribe({ messageId, fileId })
		{
			const transcriptModel = this.#createBaseTranscriptModel({ chatId: this.#chatId, messageId, fileId });

			await this.setToStorage(transcriptModel);

			try
			{
				const data = await this.#diskService.transcribe({
					chatId: this.#chatId,
					fileId,
				});
				const { transcriptText, status } = data;

				if (status === TranscriptResponseStatus.error)
				{
					transcriptModel.status = TranscriptStatus.error;
					void this.setToStorage(transcriptModel);

					return;
				}

				if (Type.isStringFilled(transcriptText))
				{
					transcriptModel.text = transcriptText;
					transcriptModel.status = TranscriptStatus.expanded;
					void this.setToStorage(transcriptModel);
				}
			}
			catch (error)
			{
				logger.error(`${this.constructor.name}.#audioTranscriptionTapHandler.transcribe`, error);

				transcriptModel.status = TranscriptStatus.error;
				void this.setToStorage(transcriptModel);
			}
		}

		/**
		 * @param {MessageId} messageId
		 * @param {FileId} fileId
		 * @param {ChatId} chatId
		 * @returns {TranscriptModelState}
		 */
		#createBaseTranscriptModel({ messageId, fileId, chatId })
		{
			return {
				messageId,
				fileId,
				chatId,
				text: '',
				status: TranscriptStatus.progress,
			};
		}

		/**
		 * @param {FileId} fileId
		 * @returns {Promise<void>}
		 */
		async #loadTranscriptModelFromDatabase(fileId)
		{
			if (!Feature.isLocalStorageEnabled || !this.#isLocalStorageSupported || this.hasTranscriptStorage(fileId))
			{
				return;
			}

			const transcriptModelFromDatabase = await serviceLocator.get('core').getRepository().transcript.getByFileId(fileId);
			logger.log(`${this.constructor.name}.#loadTranscriptModelFromDatabase ${this.#dialogId}`, transcriptModelFromDatabase);

			if (Type.isNull(transcriptModelFromDatabase))
			{
				return;
			}

			await this.setToStorage({
				...transcriptModelFromDatabase,
				status: TranscriptStatus.ready,
			});
		}

		/**
		 * @param {TranscriptModelState} transcriptModel
		 */
		async #scrollToTranscriptMessage(transcriptModel)
		{
			const view = this.#dialogLocator.get('view');
			const { messageList } = await view.getViewableMessages();
			const isVisible = messageList?.some(message => Number(message.id) === Number(transcriptModel?.messageId));
			const canScroll = isVisible && transcriptModel.status === TranscriptStatus.expanded;

			if (!canScroll)
			{
				return;
			}

			await view.scrollToMessageById(transcriptModel.messageId, true, () => {}, 'top');
		}
	}

	module.exports = { TranscriptManager };
});
