/**
 * @module im/messenger/controller/vote/result/pull
 */
jn.define('im/messenger/controller/vote/result/pull', (require, exports, module) => {
	const { dispatch } = require('statemanager/redux/store');
	const { usersUpserted } = require('statemanager/redux/slices/users');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { EventType } = require('im/messenger/const');
	const { VoteService } = require('im/messenger/provider/services/vote');

	const PullCommand = {
		VOTING: 'voting',
		USER_VOTE: 'user_vote',
		STOP: 'stop',
	};

	class Pull
	{
		/**
		 * @param {number} voteMessageId
		 * @param {boolean} isAnonymous
		 */
		constructor(voteMessageId, isAnonymous)
		{
			this.voteMessageId = voteMessageId;
			this.isAnonymous = isAnonymous;

			/** @type {MessengerCoreStore} */
			this.store = serviceLocator.get('core').getStore();
		}

		subscribe()
		{
			this.unsubscribeCallback = BX.PULL.subscribe({
				moduleId: 'vote',
				callback: this.#processPullEvent.bind(this),
			});

			BX.postComponentEvent(
				EventType.messenger.voteResultPullSubscribed,
				[{ voteMessageId: this.voteMessageId }],
				MessengerParams.getComponentCode(),
			);
		}

		unsubscribe()
		{
			this.unsubscribeCallback?.();

			BX.postComponentEvent(
				EventType.messenger.voteResultPullUnsubscribed,
				[{ voteMessageId: this.voteMessageId }],
				MessengerParams.getComponentCode(),
			);
		}

		#processPullEvent(eventData)
		{
			const { command, params } = eventData;

			if (Number(params.entityId) !== Number(this.voteMessageId))
			{
				return;
			}

			/** @type {VoteModelState} */
			this.voteModel = this.store.getters['messagesModel/voteModel/getByMessageId'](params.entityId);
			if (!this.voteModel)
			{
				return;
			}

			const pullMap = {
				[PullCommand.VOTING]: this.#handleVoting.bind(this),
				[PullCommand.USER_VOTE]: this.#handleUserVote.bind(this),
				[PullCommand.STOP]: this.#handleStop.bind(this),
			};

			pullMap[command]?.(params);
		}

		#handleVoting(params)
		{
			if (Number(params.AUTHOR_ID) === MessengerParams.getUserId())
			{
				return;
			}

			let answerVotedUserIds = new Map();

			if (!this.isAnonymous)
			{
				void dispatch(
					usersUpserted([params.user]),
				);

				const votedAnswerIds = new Set(
					Object.values(params.userAnswerMap).flatMap((answers) => Object.keys(answers)),
				);

				answerVotedUserIds = this.#getAnswerVotedUserIds(params.user.id, votedAnswerIds);
			}

			void this.store.dispatch('messagesModel/voteModel/setFromPullEvent', {
				votes: [
					{
						...this.voteModel,
						...this.#getVotedCounters(params),
						answerVotedUserIds,
					},
				],
			});
		}

		#handleUserVote(params)
		{
			const votedAnswerIds = new Set(
				Object.values(params.userAnswerMap).flatMap((answers) => Object.keys(answers)),
			);
			const answerVotedUserIds = (
				this.isAnonymous
					? new Map()
					: this.#getAnswerVotedUserIds(Number(params.AUTHOR_ID), votedAnswerIds)
			);

			void this.store.dispatch('messagesModel/voteModel/setFromPullEvent', {
				votes: [
					{
						...this.voteModel,
						votedAnswerIds,
						answerVotedUserIds,
						isVoted: votedAnswerIds.size > 0,
						answerLocalSelection: new Map(),
						...this.#getVotedCounters(params),
					},
				],
			});
		}

		#getAnswerVotedUserIds(userId, votedAnswerIds)
		{
			const answerIdsToFetchVotedUsers = [];

			const answerVotedUserIds = new Map(this.voteModel.answerVotedUserIds);
			const affectedAnswerIds = new Set([...answerVotedUserIds.keys(), ...votedAnswerIds]);

			affectedAnswerIds.forEach((answerId) => {
				const currentUserIds = answerVotedUserIds.get(answerId) || [];
				const currentUsersCount = this.voteModel.answerVotedCounter.get(answerId) || 0;
				const shouldBeInVotedUsers = votedAnswerIds.has(answerId);
				const isInAvatars = currentUserIds.includes(userId);

				if (shouldBeInVotedUsers)
				{
					if (currentUsersCount < 3 && !isInAvatars)
					{
						answerVotedUserIds.set(answerId, [...currentUserIds, userId]);
					}
				}
				else if (isInAvatars)
				{
					if (currentUsersCount > 3)
					{
						answerIdsToFetchVotedUsers.push(answerId);
					}
					else
					{
						answerVotedUserIds.set(answerId, currentUserIds.filter((id) => id !== userId));
					}
				}
			});

			void VoteService.getInstance().getAnswerVotedUsers(this.voteMessageId, answerIdsToFetchVotedUsers);

			return answerVotedUserIds;
		}

		#getVotedCounters(params)
		{
			const questions = Object.entries(params.QUESTIONS).map(([id, question]) => ({
				id: String(id),
				votedCounter: Number(question.COUNTER),
				answers: Object.entries(question.ANSWERS).map(([answerId, answer]) => ({
					id: String(answerId),
					votedCounter: Number(answer.COUNTER),
				})),
			}));

			return {
				votedCounter: Number(params.COUNTER),
				questionVotedCounter: new Map(
					questions.map((question) => [question.id, question.votedCounter]),
				),
				answerVotedCounter: new Map(
					questions.flatMap((question) => {
						return question.answers.map((answer) => [answer.id, answer.votedCounter]);
					}),
				),
			};
		}

		#handleStop()
		{
			void this.store.dispatch('messagesModel/voteModel/setFromPullEvent', {
				votes: [
					{
						...this.voteModel,
						isFinished: true,
						answerLocalSelection: new Map(),
					},
				],
			});
		}
	}

	module.exports = { Pull };
});
