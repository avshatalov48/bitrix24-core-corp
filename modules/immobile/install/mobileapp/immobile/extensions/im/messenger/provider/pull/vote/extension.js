/**
 * @module im/messenger/provider/pull/vote
 */
jn.define('im/messenger/provider/pull/vote', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/base/pull-handler');
	const { EventType } = require('im/messenger/const');
	const { UuidManager } = require('im/messenger/lib/uuid-manager');
	const { MessageHelper } = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');

	class VotePullHandler extends BasePullHandler
	{
		constructor(options = {})
		{
			super(options);

			this.excludedVoteMessageIds = new Set();

			this.onVoteResultPullSubscribed = this.onVoteResultPullSubscribed.bind(this);
			this.onVoteResultPullUnsubscribed = this.onVoteResultPullUnsubscribed.bind(this);

			this.#subscribeEvents();
		}

		#subscribeEvents()
		{
			BX.addCustomEvent(EventType.messenger.voteResultPullSubscribed, this.onVoteResultPullSubscribed);
			BX.addCustomEvent(EventType.messenger.voteResultPullUnsubscribed, this.onVoteResultPullUnsubscribed);
		}

		onVoteResultPullSubscribed({ voteMessageId })
		{
			this.excludedVoteMessageIds.add(voteMessageId);
		}

		onVoteResultPullUnsubscribed({ voteMessageId })
		{
			this.excludedVoteMessageIds.delete(voteMessageId);
		}

		getModuleId()
		{
			return 'vote';
		}

		handleVoting(params)
		{
			if (this.excludedVoteMessageIds.has(params.entityId))
			{
				return;
			}

			if (Number(params.AUTHOR_ID) === MessengerParams.getUserId())
			{
				return;
			}

			const messageHelper = MessageHelper.createById(params.entityId);
			if (!messageHelper?.isVoteModelExist)
			{
				return;
			}

			void this.store.dispatch('messagesModel/voteModel/setFromPullEvent', {
				votes: [
					{
						...messageHelper.voteModel,
						...this.getVotedCounters(params),
					},
				],
			});
		}

		handleUser_vote(params)
		{
			if (this.excludedVoteMessageIds.has(params.entityId))
			{
				return;
			}

			if (UuidManager.getInstance().hasActionUuid(params.actionUuid))
			{
				return;
			}

			const messageHelper = MessageHelper.createById(params.entityId);
			if (!messageHelper?.isVoteModelExist)
			{
				return;
			}

			const votedAnswerIds = new Set(
				Object.values(params.userAnswerMap).flatMap((answers) => Object.keys(answers)),
			);

			void this.store.dispatch('messagesModel/voteModel/setFromPullEvent', {
				votes: [
					{
						...messageHelper.voteModel,
						votedAnswerIds,
						isVoted: votedAnswerIds.size > 0,
						answerLocalSelection: new Map(),
						...this.getVotedCounters(params),
					},
				],
			});
		}

		getVotedCounters(params)
		{
			const questions = Object.entries(params.QUESTIONS).map(([id, question]) => ({
				id: Number(id),
				votedCount: Number(question.COUNTER),
				answers: Object.entries(question.ANSWERS).map(([answerId, answer]) => ({
					id: Number(answerId),
					votedCount: Number(answer.COUNTER),
				})),
			}));

			return {
				votedCounter: Number(params.COUNTER),
				questionVotedCounter: new Map(
					questions.map((question) => [String(question.id), Number(question.votedCount)]),
				),
				answerVotedCounter: new Map(
					questions.flatMap((question) => {
						return question.answers.map((answer) => [String(answer.id), answer.votedCount]);
					}),
				),
			};
		}

		handleStop(params)
		{
			if (this.excludedVoteMessageIds.has(params.entityId))
			{
				return;
			}

			if (UuidManager.getInstance().hasActionUuid(params.actionUuid))
			{
				return;
			}

			const messageHelper = MessageHelper.createById(params.entityId);
			if (!messageHelper?.isVoteModelExist)
			{
				return;
			}

			void this.store.dispatch('messagesModel/voteModel/setFromPullEvent', {
				votes: [
					{
						...messageHelper.voteModel,
						isFinished: true,
						answerLocalSelection: new Map(),
					},
				],
			});
		}
	}

	module.exports = { VotePullHandler };
});
