/**
 * @module im/messenger/lib/element/dialog/message/element/audio/audio
 */
jn.define('im/messenger/lib/element/dialog/message/element/audio/audio', (require, exports, module) => {
	const { Type } = require('type');
	const { isEmpty, isEqual } = require('utils/object');
	const { Color } = require('tokens');
	const { Loc } = require('im/messenger/loc');
	const { TranscriptStatus } = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');

	class Audio
	{
		/**
		 * @param {MessagesModelState} messageModel
		 * @param {FilesModelState} fileModel
		 * @param {TranscriptModelState | null} transcriptModel
		 * @param {object} options
		 * @param {AudioRate} options.audioRate
		 */
		constructor(messageModel, fileModel, transcriptModel, options = {})
		{
			this.messageModel = messageModel;
			this.fileModel = fileModel;
			this.transcriptModel = transcriptModel;
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
				speech2text: this.#prepareTranscriptProps(),
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

		#prepareTranscriptProps()
		{
			if (!this.#canTranscript())
			{
				return null;
			}

			const hasTranscript = Type.isObject(this.transcriptModel) && !isEmpty(this.transcriptModel);
			const isPreparedFiles = isEqual(this.messageModel.params?.FILE_ID, this.messageModel.files);
			const transcript = {
				text: '',
				textColor: Color.base2.toHex(),
				status: TranscriptStatus.ready,
			};
			if (!hasTranscript || !isPreparedFiles)
			{
				return transcript;
			}

			transcript.status = this.transcriptModel.status;
			if (transcript.status === TranscriptStatus.error)
			{
				transcript.text = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_FILE_TRANSCRIPT_ERROR');
				transcript.textColor = Color.chatOtherBase1_1.toHex();

				return transcript;
			}

			transcript.text = this.transcriptModel.text;

			return transcript;
		}

		#canTranscript()
		{
			return this.fileModel?.isTranscribable && Feature.isAiFileTranscriptionAvailable;
		}
	}

	module.exports = { Audio };
});
