/**
 * @module im/messenger/model/messages/vote/validator
 */
jn.define('im/messenger/model/messages/vote/validator', (require, exports, module) => {
	const { Uuid } = require('utils/uuid');
	const { Type } = require('type');

	/**
	 * @param {object} vote
	 * @return {VoteModelState}
	 */
	function validate(vote)
	{
		const result = {
			chatId: Number(vote.chatId),
			voteId: Number(vote.voteId),
			isFinished: Boolean(vote.isFinished),
			isVoted: Boolean(vote.isVoted),
			votedAnswerIds: new Set(vote.votedAnswerIds),
			votedCounter: Number(vote.votedCounter ?? 0),
			questionVotedCounter: new Map(vote.questionVotedCounter),
			answerVotedCounter: new Map(vote.answerVotedCounter),
		};

		if (Uuid.isV4(vote.messageId))
		{
			result.id = vote.messageId;
			result.messageId = vote.messageId;
		}
		else
		{
			result.id = Number(vote.messageId);
			result.messageId = Number(vote.messageId);
		}

		if (Type.isStringFilled(vote.resultUrl))
		{
			result.resultUrl = vote.resultUrl;
		}

		if (!Type.isUndefined(vote.answerVotedUserIds))
		{
			result.answerVotedUserIds = new Map(vote.answerVotedUserIds);
		}

		if (!Type.isUndefined(vote.answerLocalSelection))
		{
			result.answerLocalSelection = new Map(vote.answerLocalSelection);
		}

		return result;
	}

	module.exports = { validate };
});
