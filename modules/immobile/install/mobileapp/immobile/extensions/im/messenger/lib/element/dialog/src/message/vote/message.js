/**
 * @module im/messenger/lib/element/dialog/message/vote/message
 */
jn.define('im/messenger/lib/element/dialog/message/vote/message', (require, exports, module) => {
	const { MessageType, MessageParams } = require('im/messenger/const');
	const { VoteButton } = require('im/messenger/lib/element/dialog/message/vote/enum/button');
	const { CustomMessage } = require('im/messenger/lib/element/dialog/message/custom/message');
	const { Loc } = require('im/messenger/loc');

	/**
	 * @class VoteMessage
	 */
	class VoteMessage extends CustomMessage
	{
		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 */
		constructor(modelMessage, options = {})
		{
			super(modelMessage, options);

			/** @type {VoteMessageData} */
			this.vote = {};

			const storeMessageModel = this.getModelMessage();

			this.dialogCode = options.dialogCode;
			this.messageModel = Object.keys(storeMessageModel).length > 0 ? storeMessageModel : modelMessage;
			this.voteModel = this.messageModel.vote;

			this
				.#setShouldShowResults()
				.#setQuestions()
				.#setButton()
				.#setBottomText()
			;
		}

		static getComponentId()
		{
			return MessageParams.ComponentId.VoteMessage;
		}

		/**
		 * @return {VoteDialogWidgetItem}
		 */
		toDialogWidgetItem()
		{
			return {
				...super.toDialogWidgetItem(),
				vote: this.vote,
			};
		}

		getType()
		{
			return MessageType.vote;
		}

		get metaData()
		{
			return {};
		}

		getComponentParams()
		{
			return this.messageModel?.params?.COMPONENT_PARAMS ?? {};
		}

		#setShouldShowResults()
		{
			this.vote.shouldShowResults = this.#isVoted || this.#isFinished;

			return this;
		}

		#setQuestions()
		{
			const { questions } = this.getComponentParams().data;

			this.vote.questions = Object.values(questions).map((question) => {
				const questionVotedCounter = this.#getVotedCounterByQuestion(question.id);
				const answers = Object.values(question.answers).map((answer) => ({
					id: String(answer.id),
					text: answer.message,
					progressPercent: (
						questionVotedCounter > 0
							? Math.round((this.#getVotedCounterByAnswer(answer.id) / questionVotedCounter) * 100)
							: 0
					),
					isSelected: (
						(this.#isVoted && this.#votedAnswerIds.has(String(answer.id)))
						|| this.#selectedAnswers.includes(String(answer.id))
					),
				}));

				return {
					answers,
					id: String(question.id),
					text: question.question,
					isMultiple: question.fieldType === 1,
				};
			});

			return this;
		}

		#setButton()
		{
			if (!this.voteModel)
			{
				return this.#setLoaderButton();
			}

			if (this.vote.shouldShowResults)
			{
				if (this.#votedCounter > 0)
				{
					return this.#setWithVotesButton();
				}

				return this.#setNoVotesButton();
			}

			if (this.vote.questions.some((question) => question.isMultiple))
			{
				return this.#setVoteButton();
			}

			if (this.#votedCounter > 0)
			{
				return this.#setWithVotesDisabledButton();
			}

			return this.#setNoVotesButton();
		}

		#setLoaderButton()
		{
			this.vote.button = VoteButton.LOADER.getValue();

			return this;
		}

		#setNoVotesButton()
		{
			this.vote.button = {
				...VoteButton.NO_VOTES.getValue(),
				text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_VOTE_NO_VOTED_BUTTON'),
			};

			return this;
		}

		#setWithVotesButton()
		{
			this.vote.button = {
				...VoteButton.WITH_VOTES.getValue(),
				text: Loc.getMessagePlural(
					'IMMOBILE_ELEMENT_DIALOG_MESSAGE_VOTE_VOTED_BUTTON',
					this.#votedCounter,
					{
						'#COUNT#': this.#votedCounter,
					},
				),
			};

			return this;
		}

		#setWithVotesDisabledButton()
		{
			this.vote.button = {
				...VoteButton.WITH_VOTES_DISABLED.getValue(),
				text: Loc.getMessagePlural(
					'IMMOBILE_ELEMENT_DIALOG_MESSAGE_VOTE_VOTED_BUTTON',
					this.#votedCounter,
					{
						'#COUNT#': this.#votedCounter,
					},
				),
			};

			return this;
		}

		#setVoteButton()
		{
			const hasSelectedAnswers = (
				this.vote.questions.some((question) => question.answers.some((answer) => answer.isSelected))
			);

			this.vote.button = {
				...(hasSelectedAnswers ? VoteButton.VOTE_ACTIVE : VoteButton.VOTE_INACTIVE).getValue(),
				text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_VOTE_VOTE_BUTTON'),
			};

			return this;
		}

		#setBottomText()
		{
			const { anonymity, options } = this.getComponentParams().data;

			this.vote.bottomText = Loc.getMessage(
				anonymity === 2
					? 'IMMOBILE_ELEMENT_DIALOG_MESSAGE_VOTE_ANONYMOUS'
					: 'IMMOBILE_ELEMENT_DIALOG_MESSAGE_VOTE_PUBLIC',
			);

			if (options === 0)
			{
				this.vote.bottomText += Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_VOTE_REVOTE_DISABLED');
			}

			if (this.#isFinished)
			{
				this.vote.bottomText += Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_VOTE_FINISHED');
			}

			return this;
		}

		#getVotedCounterByQuestion(questionId)
		{
			return new Map(this.voteModel?.questionVotedCounter).get(String(questionId)) ?? 0;
		}

		#getVotedCounterByAnswer(answerId)
		{
			return new Map(this.voteModel?.answerVotedCounter).get(String(answerId)) ?? 0;
		}

		get #isFinished()
		{
			return Boolean(this.voteModel?.isFinished);
		}

		get #isVoted()
		{
			return Boolean(this.voteModel?.isVoted);
		}

		get #votedAnswerIds()
		{
			return new Set(this.voteModel?.votedAnswerIds ?? []);
		}

		get #votedCounter()
		{
			return Number(this.voteModel?.votedCounter ?? 0);
		}

		get #selectedAnswers()
		{
			const answerLocalSelection = this.voteModel?.answerLocalSelection;
			if (!answerLocalSelection)
			{
				return [];
			}

			const selectedAnswers = new Map(answerLocalSelection).get(this.dialogCode);
			if (!selectedAnswers)
			{
				return [];
			}

			return Object.values(selectedAnswers).flat();
		}
	}

	module.exports = { VoteMessage };
});
