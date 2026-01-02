/**
 * @module im/messenger/controller/dialog/lib/vote-manager
 */
jn.define('im/messenger/controller/dialog/lib/vote-manager', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { EventType, MessageParams, OwnMessageStatus } = require('im/messenger/const');
	const { voteDefaultElement } = require('im/messenger/model/messages/vote/default-element');
	const { VoteService } = require('im/messenger/provider/services/vote');
	const { AnalyticsService } = require('im/messenger/provider/services/analytics');
	const { Type } = require('type');
	const { Uuid } = require('utils/uuid');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { MessageHelper } = require('im/messenger/lib/helper');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { Feature } = require('im/messenger/lib/feature');
	const { VoteResult } = require('im/messenger/controller/vote/result');

	const logger = LoggerManager.getInstance().getLogger('dialog--vote-manager');

	class VoteManager
	{
		/**
		 * @param {IServiceLocator<DialogLocatorServices>} dialogLocator
		 * @param {number} chatId
		 */
		constructor(dialogLocator, chatId)
		{
			this.dialogLocator = dialogLocator;
			this.chatId = chatId;

			/** @type {MessengerCoreStore} */
			this.store = serviceLocator.get('core').getStore();

			this.loadedVotes = new Map();
			this.pendingVotes = new Set();

			this.visibleMessagesChangedHandler = this.visibleMessagesChangedHandler.bind(this);
			this.resendHandler = this.resendHandler.bind(this);

			if (Feature.isVoteMessageAvailable)
			{
				this.#subscribeEvents();
			}
		}

		/**
		 * @return {DialogId}
		 */
		get dialogId()
		{
			return this.dialogLocator.get('dialogId');
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
		 * @return {MessageRenderer}
		 */
		get messageRenderer()
		{
			return this.dialogLocator.get('message-renderer');
		}

		destructor()
		{
			this.#unsubscribeEvents();

			this.loadedVotes.forEach((voteId) => BX.PULL.clearWatch(`VOTE_${voteId}`));

			this.loadedVotes.clear();
			this.pendingVotes.clear();
		}

		#subscribeEvents()
		{
			this.view
				.on(EventType.dialog.visibleMessagesChanged, this.visibleMessagesChangedHandler)
				.on(EventType.dialog.resend, this.resendHandler)
			;

			BX.addCustomEvent(EventType.dialog.external.resend, this.resendHandler);
		}

		#unsubscribeEvents()
		{
			BX.removeCustomEvent(EventType.dialog.external.resend, this.resendHandler);
		}

		async visibleMessagesChangedHandler({ messageList = [] })
		{
			const voteMessageIds = (
				messageList
					.filter((message) => (
						!Uuid.isV4(message.id)
						&& !this.loadedVotes.has(Number(message.id))
						&& !this.pendingVotes.has(Number(message.id))
						&& MessageHelper.createById(message.id)?.isVote
					))
					.map((message) => Number(message.id))
			);

			if (voteMessageIds.length > 0)
			{
				logger.log('VoteManager.visibleMessagesChangedHandler', voteMessageIds);

				this.pendingVotes = new Set([...this.pendingVotes, ...voteMessageIds]);

				const loadedVotes = await VoteService.getInstance().getVotesData(voteMessageIds, this.chatId);

				loadedVotes.forEach((vote) => this.loadedVotes.set(vote.messageId, vote.voteId));
				this.pendingVotes.clear();

				this.loadedVotes.forEach((voteId) => BX.PULL.extendWatch(`VOTE_${voteId}`));
			}
		}

		/**
		 * @param {number|Object} initialIndex
		 * @param {MessagesModelState|Message} initialMessage
		 * @returns {Promise<void>}
		 */
		async resendHandler(initialIndex, initialMessage)
		{
			logger.log('VoteManager.resendHandler', { initialIndex, initialMessage });

			let message = initialMessage;
			let isBottomMessage = true;

			if (Type.isObject(initialIndex))
			{
				message = initialIndex.message;

				const bottomMessage = this.view.getBottomMessage();

				if (message.id !== bottomMessage.id)
				{
					isBottomMessage = false;
				}
			}
			else
			{
				isBottomMessage = initialIndex === 0;
			}

			const messageId = message.id;
			const messageHelper = MessageHelper.createById(messageId);
			if (!messageHelper?.isVoteModelExist)
			{
				return;
			}

			const { messageModel } = messageHelper;
			const { chatId, params, templateId } = messageModel;

			if (!isBottomMessage)
			{
				await this.messageRenderer.delete([messageId]);
				await this.messageRenderer.render([messageModel]);
				await this.view.scrollToBottomSmoothly();
			}

			await this.store.dispatch('messagesModel/update', {
				id: messageId,
				fields: {
					error: false,
					errorReason: OwnMessageStatus.sending,
				},
			});
			await this.sendVoteToServer(chatId, params.COMPONENT_PARAMS.data.voteDataForServer, templateId);
		}

		async onVoteCreate({ chatId, voteData })
		{
			logger.log('VoteManager.onVoteCreate', { chatId, voteData });

			if (chatId <= 0)
			{
				return;
			}

			const messageUuid = Uuid.getV4();
			const message = this.getMessageModelToRender(chatId, voteData, messageUuid);

			await this.store.dispatch('messagesModel/add', message);
			await this.view.scrollToBottomSmoothly();
			await this.sendVoteToServer(chatId, voteData, messageUuid);
		}

		getMessageModelToRender(chatId, voteData, messageUuid)
		{
			return {
				chatId,
				authorId: MessengerParams.getUserId(),
				text: 'Vote',
				unread: false,
				templateId: messageUuid,
				date: new Date(),
				sending: true,
				params: {
					componentId: MessageParams.ComponentId.VoteMessage,
					COMPONENT_PARAMS: {
						data: {
							questions: voteData.questions.map((question) => ({
								...question,
								question: question.text,
								answers: Object.fromEntries(
									Object.entries(question.answers).map(([answerId, answerText]) => ([
										answerId,
										{
											id: answerId,
											message: answerText,
										},
									])),
								),
								fieldType: question.isMultipleChoice ? 1 : 0,
							})),
							anonymity: voteData.settings.isAnonymous ? 2 : 1,
							options: voteData.settings.isRevoteEnabled ? 1 : 0,
							voteDataForServer: voteData,
						},
					},
				},
				vote: {
					...voteDefaultElement,
					chatId,
					messageId: messageUuid,
				},
			};
		}

		async sendVoteToServer(chatId, voteData, messageUuid)
		{
			logger.log('VoteManager.sendVoteToServer', chatId, voteData, messageUuid);

			try
			{
				const response = await VoteService.getInstance().send(chatId, voteData, messageUuid);

				logger.log('VoteManager.sendVoteToServer response', response);

				if (response.messageId)
				{
					this.loadedVotes.set(response.messageId, String(response.voteId));

					await this.store.dispatch('messagesModel/updateWithId', {
						id: messageUuid,
						fields: {
							id: response.messageId,
							templateId: messageUuid,
							error: false,
							params: {
								componentId: MessageParams.ComponentId.VoteMessage,
							},
							vote: {
								...voteDefaultElement,
								chatId,
								messageId: response.messageId,
								voteId: response.voteId,
							},
						},
					});
					AnalyticsService.getInstance().sendOnVotePublished(this.dialogId, response.voteId, voteData);
				}
			}
			catch (response)
			{
				logger.warn('VoteManager.sendVoteToServer catch', response);

				void this.store.dispatch('messagesModel/update', {
					id: messageUuid,
					fields: {
						error: true,
					},
				});
			}
		}

		async vote(messageId, updatedVoteModel, ballot)
		{
			logger.log('VoteManager.vote', messageId, updatedVoteModel, ballot);

			const messageHelper = MessageHelper.createById(messageId);
			if (!messageHelper?.isVoteModelExist || messageHelper?.isFinishedVote)
			{
				return;
			}

			await this.store.dispatch('messagesModel/voteModel/update', {
				vote: {
					...updatedVoteModel,
					answerLocalSelection: new Map(),
				},
			});

			VoteService.getInstance().vote(messageId, ballot);
			AnalyticsService.getInstance().sendVoteVoted(this.dialogId, updatedVoteModel.voteId);
		}

		async revote(messageId)
		{
			logger.log('VoteManager.revote', messageId);

			const messageHelper = MessageHelper.createById(messageId);
			if (!messageHelper?.isVoteModelExist)
			{
				return;
			}

			const { messageModel, voteModel } = messageHelper;
			const { questions } = messageModel.params.COMPONENT_PARAMS.data;
			const vote = {
				...voteModel,
				isVoted: false,
				votedAnswerIds: new Set(),
				votedCounter: voteModel.votedCounter - 1,
				questionVotedCounter: new Map([
					...voteModel.questionVotedCounter,
					...[...voteModel.votedAnswerIds].map((answerId) => {
						const questionId = String(
							Object.values(questions)
								.find((question) => Object.keys(question.answers).includes(answerId))
								.id,
						);

						return [questionId, voteModel.questionVotedCounter.get(questionId) - 1];
					}),
				]),
				answerVotedCounter: new Map([
					...voteModel.answerVotedCounter,
					...[...voteModel.votedAnswerIds].map((answerId) => (
						[answerId, voteModel.answerVotedCounter.get(answerId) - 1]
					)),
				]),
				answerLocalSelection: new Map(),
			};

			await this.store.dispatch('messagesModel/voteModel/update', { vote });

			VoteService.getInstance().revote(messageId);
			AnalyticsService.getInstance().sendVoteCancelled(this.dialogId, voteModel.voteId);
		}

		async finishVote(messageId)
		{
			logger.log('VoteManager.finishVote', messageId);

			const messageHelper = MessageHelper.createById(messageId);
			if (!messageHelper?.isVoteModelExist)
			{
				return;
			}

			await this.store.dispatch('messagesModel/voteModel/update', {
				vote: {
					...messageHelper.voteModel,
					isFinished: true,
					answerLocalSelection: new Map(),
				},
			});

			VoteService.getInstance().finishVote(messageId);
			AnalyticsService.getInstance().sendVoteFinished(this.dialogId, messageHelper.voteModel.voteId);
		}

		openVoteResult(messageId)
		{
			VoteResult.open({ messageId, dialogId: this.dialogId });
		}

		toggleAnswerLocalSelection(messageId, questionId, answerId)
		{
			logger.log('VoteManager.toggleAnswerLocalSelection', messageId, questionId, answerId);

			const messageHelper = MessageHelper.createById(messageId);
			if (!messageHelper?.isVoteModelExist)
			{
				return;
			}

			const { answerLocalSelection = new Map() } = messageHelper.voteModel;

			const selectionByDialog = answerLocalSelection.get(this.dialogCode) ?? {};
			const oldSelectedAnswers = selectionByDialog[questionId] ?? [];
			const newSelectedAnswers = (
				oldSelectedAnswers.includes(answerId)
					? oldSelectedAnswers.filter((id) => id !== answerId)
					: [...oldSelectedAnswers, answerId]
			);

			void this.store.dispatch('messagesModel/voteModel/update', {
				vote: {
					messageId,
					answerLocalSelection: new Map([
						...answerLocalSelection,
						[this.dialogCode, { ...selectionByDialog, [questionId]: newSelectedAnswers }],
					]),
				},
			});
		}
	}

	module.exports = { VoteManager };
});
