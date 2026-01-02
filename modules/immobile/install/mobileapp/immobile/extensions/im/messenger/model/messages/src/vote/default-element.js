/**
 * @module im/messenger/model/messages/vote/default-element
 */
jn.define('im/messenger/model/messages/vote/default-element', (require, exports, module) => {
	const voteDefaultElement = Object.freeze({
		messageId: 0,
		chatId: 0,
		voteId: 0,
		isFinished: false,
		isVoted: false,
		votedAnswerIds: new Set(),
		votedCounter: 0,
		questionVotedCounter: new Map(),
		answerVotedCounter: new Map(),
	});

	module.exports = { voteDefaultElement };
});
