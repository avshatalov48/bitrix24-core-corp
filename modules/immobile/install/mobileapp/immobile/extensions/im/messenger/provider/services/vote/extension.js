/**
 * @module im/messenger/provider/services/vote
 */
jn.define('im/messenger/provider/services/vote', (require, exports, module) => {
	const { Logger } = require('im/messenger/lib/logger');
	const { RestMethod } = require('im/messenger/const');
	const { runAction } = require('im/messenger/lib/rest');
	const { Type } = require('type');
	const { UuidManager } = require('im/messenger/lib/uuid-manager');
	const { MessageHelper } = require('im/messenger/lib/helper');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { dispatch } = require('statemanager/redux/store');
	const { usersUpserted } = require('statemanager/redux/slices/users');

	/**
	 * @class VoteService
	 */
	class VoteService
	{
		/** @type {VoteService} */
		static #instance;

		/**
		 * @return {VoteService}
		 */
		static getInstance()
		{
			if (!this.#instance)
			{
				this.#instance = new this();
			}

			return this.#instance;
		}

		/**
		 * @private
		 */
		constructor()
		{
			/** @type {MessengerCoreStore} */
			this.store = serviceLocator.get('core').getStore();

			/** @type {MessageRepository} */
			this.messageRepository = serviceLocator.get('core').getRepository().message;

			/** @type {Map<string, number>} */
			this.votesInActionProcess = new Map();
		}

		send(chatId, voteData, templateId)
		{
			const votePreparedData = this.#prepareVoteDataToSend(voteData);

			return runAction(RestMethod.voteIntegrationImSend, {
				data: {
					chatId,
					templateId,
					IM_MESSAGE_VOTE_DATA: votePreparedData,
				},
			})
				.catch((errors) => Logger.error('VoteService.send error: ', errors))
			;
		}

		#prepareVoteDataToSend(data)
		{
			const questions = data.questions.map((question) => {
				const answers = Object.values(question.answers).map((answer) => ({
					MESSAGE: answer,
					MESSAGE_TYPE: 'text',
					FIELD_TYPE: '0',
				}));

				return {
					QUESTION: question.text,
					QUESTION_TYPE: 'text',
					FIELD_TYPE: question.isMultipleChoice ? 1 : 0,
					ANSWERS: answers,
				};
			});

			return {
				QUESTIONS: questions,
				ANONYMITY: data.settings.isAnonymous ? 2 : 1,
				OPTIONS: data.settings.isRevoteEnabled ? ['1'] : [],
			};
		}

		vote(messageId, ballot)
		{
			const actionUuid = UuidManager.getInstance().getActionUuid();

			this.votesInActionProcess.set(actionUuid, Number(messageId));

			runAction(RestMethod.voteAttachedVoteVote, {
				data: {
					moduleId: 'im',
					entityType: 'Bitrix\\Vote\\Attachment\\ImMessageConnector',
					entityId: messageId,
					ballot,
					actionUuid,
				},
			})
				.then((response) => this.#handleVotingResponse(response, messageId, actionUuid))
				.catch((errors) => Logger.error('VoteService.vote error: ', errors))
				.finally(() => this.votesInActionProcess.delete(actionUuid))
			;
		}

		revote(messageId)
		{
			const actionUuid = UuidManager.getInstance().getActionUuid();

			this.votesInActionProcess.set(actionUuid, Number(messageId));

			runAction(RestMethod.voteAttachedVoteRecall, {
				data: {
					moduleId: 'im',
					entityType: 'Bitrix\\Vote\\Attachment\\ImMessageConnector',
					entityId: messageId,
					actionUuid,
				},
			})
				.then((response) => this.#handleVotingResponse(response, messageId, actionUuid))
				.catch((errors) => Logger.error('VoteService.revote error: ', errors))
				.finally(() => this.votesInActionProcess.delete(actionUuid))
			;
		}

		#handleVotingResponse(response, messageId, actionUuid)
		{
			if (!response.attach || this.#isVoteChangedByAnotherAction(messageId, actionUuid))
			{
				return;
			}

			const messageHelper = MessageHelper.createById(messageId);
			if (!messageHelper?.isVoteModelExist)
			{
				return;
			}

			void this.store.dispatch('messagesModel/voteModel/setFromResponse', {
				votes: [
					{
						...messageHelper.voteModel,
						...this.#prepareVoteModelData({ ...response.attach, messageId }),
					},
				],
			});
		}

		finishVote(messageId)
		{
			const actionUuid = UuidManager.getInstance().getActionUuid();

			this.votesInActionProcess.set(actionUuid, Number(messageId));

			runAction(RestMethod.voteAttachedVoteStop, {
				data: {
					moduleId: 'im',
					entityType: 'Bitrix\\Vote\\Attachment\\ImMessageConnector',
					entityId: messageId,
					actionUuid,
				},
			})
				.then(() => {
					if (this.#isVoteChangedByAnotherAction(messageId, actionUuid))
					{
						return;
					}

					const messageHelper = MessageHelper.createById(messageId);
					if (!messageHelper?.isVoteModelExist)
					{
						return;
					}

					void this.store.dispatch('messagesModel/voteModel/setFromResponse', {
						votes: [
							{
								...messageHelper.voteModel,
								isFinished: true,
							},
						],
					});
				})
				.catch((errors) => Logger.error('VoteService.finishVote error: ', errors))
				.finally(() => this.votesInActionProcess.delete(actionUuid))
			;
		}

		#isVoteChangedByAnotherAction(messageId, currentActionUuid)
		{
			const changedVotes = new Map(this.votesInActionProcess);
			changedVotes.delete(currentActionUuid);

			return [...changedVotes.values()].includes(Number(messageId));
		}

		async getVotesData(messageIds, chatId)
		{
			const response = await runAction(RestMethod.voteAttachedVoteGetMany, {
				data: {
					moduleId: 'im',
					entityType: 'Bitrix\\Vote\\Attachment\\ImMessageConnector',
					entityIds: messageIds,
				},
			})
				.catch((errors) => Logger.error('VoteService.getVotesData error: ', errors))
			;
			if (!response)
			{
				return [];
			}

			const { items } = response;
			if (!Type.isArrayFilled(items))
			{
				return [];
			}

			const votes = items.map((vote) => ({
				...this.store.getters['messagesModel/voteModel/getByMessageId'](vote.messageId),
				...this.#prepareVoteModelData(vote),
				chatId,
			}));
			if (Type.isArrayFilled(votes))
			{
				void this.store.dispatch(
					'messagesModel/voteModel/setFromResponse',
					{ votes },
				);
			}

			return votes;
		}

		#prepareVoteModelData(rawVote)
		{
			return {
				messageId: Number(rawVote.messageId || rawVote.entityId),
				voteId: String(rawVote.VOTE_ID),
				isFinished: Boolean(rawVote.isFinished),
				isVoted: Boolean(rawVote.isVoted),
				votedAnswerIds: new Set(
					Object.values(rawVote.userAnswerMap).flatMap((answers) => Object.keys(answers)),
				),
				votedCounter: Number(rawVote.COUNTER),
				questionVotedCounter: new Map(
					Object.values(rawVote.QUESTIONS).map((question) => [String(question.ID), Number(question.COUNTER)]),
				),
				answerVotedCounter: new Map(
					Object.values(rawVote.QUESTIONS).flatMap((question) => (
						Object.values(question.ANSWERS).map((answer) => [String(answer.ID), Number(answer.COUNTER)])
					)),
				),
				resultUrl: rawVote.resultUrl,
			};
		}

		async getVoteResult(messageId, signedAttachId)
		{
			if (!messageId && !signedAttachId)
			{
				return null;
			}

			const data = (
				signedAttachId
					? { signedAttachId }
					: {
						moduleId: 'im',
						entityType: 'Bitrix\\Vote\\Attachment\\ImMessageConnector',
						entityId: messageId,
					}
			);

			const response = await runAction(RestMethod.voteAttachedVoteGetWithVoted, {
				data: {
					...data,
					pageSize: 3,
					userForMobileFormat: true,
				},
			})
				.catch((errors) => Logger.error('VoteService.getVoteResult error: ', errors))
			;
			if (!response)
			{
				return null;
			}

			const { attach, voted } = response;
			if (!attach || !voted)
			{
				return null;
			}

			if (Object.keys(voted).length > 0)
			{
				await dispatch(
					usersUpserted([...new Map(Object.values(voted).flat().map((item) => [item.id, item])).values()]),
				);
			}

			const voteResultData = this.#prepareVoteResultData(attach, voted);
			const voteModel = this.store.getters['messagesModel/voteModel/getByMessageId'](voteResultData.messageId);
			const chatId = await this.#getChatId(voteModel?.chatId, voteResultData.messageId);

			void this.store.dispatch('messagesModel/voteModel/setFromResponse', {
				votes: [
					{
						chatId,
						...voteModel,
						...voteResultData,
					},
				],
			});

			return voteResultData;
		}

		#prepareVoteResultData(vote, voted)
		{
			if (!vote || !voted)
			{
				return null;
			}

			const votedAnswerIds = new Set(
				Object.values(vote.userAnswerMap).flatMap((answers) => Object.keys(answers)),
			);
			const answerVotedUserIds = new Map(
				Object.entries(voted).map(([answerId, users]) => ([answerId, users.map((user) => Number(user.id))])),
			);
			const questions = this.#sort(vote.QUESTIONS).map((question) => {
				const answers = this.#sort(question.ANSWERS).map((answer) => ({
					id: Number(answer.ID),
					text: String(answer.MESSAGE),
					votedCounter: Number(answer.COUNTER),
					votedPercent: Math.round(answer.PERCENT),
					votedUserIds: answerVotedUserIds.get(answer.ID) ?? [],
					isVotedByMe: votedAnswerIds.has(answer.ID),
				}));

				return {
					answers,
					id: Number(question.ID),
					text: question.QUESTION,
					votedCounter: Number(question.COUNTER),
				};
			});

			return {
				...this.#prepareVoteModelData(vote),
				questions,
				answerVotedUserIds,
				isAnonymous: vote.ANONYMITY === 2,
				isRevoteEnabled: vote.OPTIONS === 1,
			};
		}

		#sort(obj)
		{
			return Object.values(obj).sort((a, b) => Number(a.C_SORT) - Number(b.C_SORT));
		}

		async #getChatId(chatId, messageId)
		{
			if (!chatId)
			{
				const message = await this.messageRepository.getMessageById(messageId);
				if (message)
				{
					return message.chatId;
				}

				return 0;
			}

			return chatId;
		}

		getAnswerVotedUsers(messageId, answerIds)
		{
			if (answerIds.length <= 0)
			{
				return;
			}

			BX.rest.callBatch(
				Object.fromEntries(
					answerIds.map((answerId) => ([
						answerId,
						{
							method: RestMethod.voteAttachedVoteGetAnswerVoted,
							params: {
								moduleId: 'im',
								entityType: 'Bitrix\\Vote\\Attachment\\ImMessageConnector',
								entityId: messageId,
								userForMobileFormat: true,
								answerId,
							},
						},
					])),
				),
				async (result) => {
					const users = new Map();
					const answerVotedUserIds = new Map();

					Object.entries(result).forEach(([answerId, data]) => {
						const items = data?.answer?.result?.items;
						if (!Type.isArrayFilled(items))
						{
							return;
						}

						items.forEach((user) => users.set(user.id, user));
						answerVotedUserIds.set(String(answerId), items.map((item) => item.id));
					});

					await dispatch(
						usersUpserted([...users.values()]),
					);

					/** @type {VoteModelState} */
					const voteModel = this.store.getters['messagesModel/voteModel/getByMessageId'](messageId);

					void this.store.dispatch('messagesModel/voteModel/setFromResponse', {
						votes: [
							{
								...voteModel,
								answerVotedUserIds: new Map([
									...voteModel.answerVotedUserIds,
									...answerVotedUserIds,
								]),
							},
						],
					});
				},
			);
		}
	}

	module.exports = { VoteService };
});
