/**
 * @module im/messenger/lib/element/dialog/message/vote/handler
 */
jn.define('im/messenger/lib/element/dialog/message/vote/handler', (require, exports, module) => {
	const { EventType } = require('im/messenger/const');
	const { CustomMessageHandler } = require('im/messenger/lib/element/dialog/message/custom/handler');
	const { ButtonId } = require('im/messenger/lib/element/dialog/message/vote/const/button-id');
	const { MessageHelper } = require('im/messenger/lib/helper');
	const { Uuid } = require('utils/uuid');

	/**
	 * @class VoteMessageHandler
	 */
	class VoteMessageHandler extends CustomMessageHandler
	{
		/**
		 * @param {IServiceLocator<MessengerLocatorServices>} serviceLocator
		 * @param {IServiceLocator<DialogLocatorServices>} dialogLocator
		 */
		constructor(serviceLocator, dialogLocator)
		{
			super(serviceLocator, dialogLocator);

			this.dialogLocator = dialogLocator;

			/** @type {MessengerCoreStore} */
			this.store = this.serviceLocator.get('core').getStore();
		}

		/**
		 * @return {string}
		 */
		get dialogCode()
		{
			return this.dialogLocator.get('dialogCode');
		}

		/**
		 * @return {DialogView}
		 */
		get view()
		{
			return this.dialogLocator.get('view');
		}

		/**
		 * @return {VoteManager}
		 */
		get voteManager()
		{
			return this.dialogLocator.get('vote-manager');
		}

		/**
		 * @return {void}
		 */
		bindMethods()
		{
			this.messageVoteAnswerTapHandler = this.messageVoteAnswerTapHandler.bind(this);
			this.messageVoteButtonTapHandler = this.messageVoteButtonTapHandler.bind(this);
		}

		/**
		 * @return {void}
		 */
		subscribeEvents()
		{
			this.view
				.on(EventType.dialog.voteAnswerTap, this.messageVoteAnswerTapHandler)
				.on(EventType.dialog.voteButtonTap, this.messageVoteButtonTapHandler)
			;
		}

		/**
		 * @return {void}
		 */
		unsubscribeEvents()
		{
			this.view
				.off(EventType.dialog.voteAnswerTap, this.messageVoteAnswerTapHandler)
				.off(EventType.dialog.voteButtonTap, this.messageVoteButtonTapHandler)
			;
		}

		messageVoteAnswerTapHandler(messageId, questionId, answerId)
		{
			if (Uuid.isV4(messageId))
			{
				return;
			}

			const messageHelper = MessageHelper.createById(messageId);
			if (!messageHelper?.isVoteModelExist || messageHelper?.isFinishedVote)
			{
				return;
			}

			if (messageHelper.isMultipleVote)
			{
				void this.voteManager.toggleAnswerLocalSelection(messageId, questionId, answerId);

				return;
			}

			const voteModel = messageHelper.voteModel;
			const updatedVoteModel = this.#buildUpdatedVoteModel(
				voteModel,
				[answerId],
				[[questionId, (voteModel.questionVotedCounter.get(questionId) ?? 0) + 1]],
				[[answerId, (voteModel.answerVotedCounter.get(answerId) ?? 0) + 1]],
			);

			void this.voteManager.vote(messageId, updatedVoteModel, { [questionId]: [answerId] });
		}

		/**
		 * @param {String} messageId
		 * @param {String} buttonId
		 * @return {void}
		 */
		async messageVoteButtonTapHandler(messageId, buttonId)
		{
			if (Uuid.isV4(messageId))
			{
				return;
			}

			const messageHelper = MessageHelper.createById(messageId);
			if (!messageHelper?.isVoteModelExist)
			{
				return;
			}

			if (buttonId === ButtonId.SHOW_RESULT)
			{
				this.voteManager.openVoteResult(messageId);
			}
			else if (buttonId === ButtonId.VOTE)
			{
				const voteModel = messageHelper.voteModel;
				const ballot = new Map(voteModel.answerLocalSelection).get(this.dialogCode) ?? {};
				const updatedVoteModel = this.#buildUpdatedVoteModel(
					voteModel,
					Object.values(ballot).flat(),
					Object.keys(ballot).map((questionId) => [
						questionId,
						(voteModel.questionVotedCounter.get(questionId) ?? 0) + 1,
					]),
					Object.values(ballot).flatMap((answerIds) => (
						answerIds.map((answerId) => [
							answerId,
							(voteModel.answerVotedCounter.get(answerId) ?? 0) + 1,
						])
					)),
				);

				void this.voteManager.vote(messageId, updatedVoteModel, ballot);
			}
		}

		#buildUpdatedVoteModel(voteModel, votedAnswerIds, questionVotedCounterUpdates, answerVotedCounterUpdates)
		{
			return {
				...voteModel,
				isVoted: true,
				votedAnswerIds: new Set(votedAnswerIds),
				votedCounter: voteModel.votedCounter + 1,
				questionVotedCounter: new Map([...voteModel.questionVotedCounter, ...questionVotedCounterUpdates]),
				answerVotedCounter: new Map([...voteModel.answerVotedCounter, ...answerVotedCounterUpdates]),
			};
		}
	}

	module.exports = { VoteMessageHandler };
});
