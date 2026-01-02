/**
 * @module im/messenger/controller/vote/result
 */
jn.define('im/messenger/controller/vote/result', (require, exports, module) => {
	const { BottomSheet } = require('bottom-sheet');
	const { LoadingScreen } = require('layout/ui/loading-screen');
	const { StatusBlock } = require('ui-system/blocks/status-block');
	const { Area } = require('ui-system/layout/area');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { ButtonSize, ButtonDesign, Button } = require('ui-system/form/buttons/button');
	const { Icon } = require('ui-system/blocks/icon');
	const { AvatarStack } = require('ui-system/blocks/avatar-stack');
	const { H4 } = require('ui-system/typography/heading');
	const { Text5, Text6 } = require('ui-system/typography/text');
	const { Link4 } = require('ui-system/blocks/link');
	const { Color, Indent, Corner } = require('tokens');
	const { Loc } = require('im/messenger/loc');
	const { makeLibraryImagePath } = require('asset-manager');
	const { copyToClipboard } = require('utils/copy');
	const { useCallback } = require('utils/function');
	const { Type } = require('type');
	const { MessageHelper } = require('im/messenger/lib/helper');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { VoteService } = require('im/messenger/provider/services/vote');
	const { AnalyticsService } = require('im/messenger/provider/services/analytics');
	const { Pull } = require('im/messenger/controller/vote/result/pull');
	const { UserList } = require('im/messenger/controller/vote/result/user-list');

	class VoteResult extends LayoutComponent
	{
		/**
		 * @public
		 * @param {{
		 *     messageId: ?MessageId,
		 *     signedAttachId: ?string,
		 *     dialogId: ?DialogId,
		 *     parentWidget: PageManager,
		 * }} props
		 */
		static open({ messageId, signedAttachId, dialogId, parentWidget = PageManager })
		{
			void new BottomSheet({
				titleParams: {
					type: 'dialog',
					text: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_RESULT_TITLE'),
				},
				component: (layout) => {
					return new VoteResult({
						messageId,
						signedAttachId,
						dialogId,
						parentWidget: layout,
					});
				},
			})
				.setParentWidget(parentWidget)
				.alwaysOnTop()
				.setBackgroundColor(Color.bgContentPrimary.toHex())
				.setNavigationBarColor(Color.bgContentPrimary.toHex())
				.open()
			;
		}

		constructor(props)
		{
			super(props);

			this.onVoteModelUpdate = this.onVoteModelUpdate.bind(this);
			this.renderAvatarStackRestView = this.renderAvatarStackRestView.bind(this);

			/** @type {MessengerCoreStoreManager} */
			this.storeManager = serviceLocator.get('core').getStoreManager();

			this.state = {
				isLoading: true,
			};

			const messageHelper = MessageHelper.createById(this.props.messageId);
			if (messageHelper?.isVoteModelExist)
			{
				this.state = {
					isLoading: false,
					vote: this.#prepareVoteFromStoreModels(messageHelper.messageModel, messageHelper.voteModel),
				};
			}
		}

		/**
		 * @param {MessagesModelState} messageModel
		 * @param {VoteModelState} voteModel
		 * @return {Object}
		 */
		#prepareVoteFromStoreModels(messageModel, voteModel)
		{
			const vote = messageModel.params.COMPONENT_PARAMS.data;

			const questions = Object.values(vote.questions).map((question) => {
				const questionId = String(question.id);
				const questionVotedCounter = voteModel.questionVotedCounter.get(questionId) || 0;

				const answers = Object.values(question.answers).map((answer) => {
					const answerId = String(answer.id);
					const answerVotedCounter = voteModel.answerVotedCounter.get(answerId) || 0;
					const answerVotedUserIds = (
						[...(new Map(voteModel.answerVotedUserIds).get(answerId) || []), 0, 0, 0]
							.slice(0, Math.min(answerVotedCounter, 3))
					);

					return {
						id: answerId,
						text: String(answer.message),
						votedCounter: answerVotedCounter,
						votedPercent: Math.round(answerVotedCounter / questionVotedCounter * 100),
						votedUserIds: answerVotedUserIds,
						isVotedByMe: voteModel.votedAnswerIds.has(answerId),
					};
				});

				return {
					answers,
					id: questionId,
					text: question.question,
					votedCounter: questionVotedCounter,
				};
			});

			return {
				...voteModel,
				questions,
				isAnonymous: vote.anonymity === 2,
				isRevoteEnabled: vote.options === 1,
			};
		}

		componentDidMount()
		{
			void VoteService.getInstance().getVoteResult(this.props.messageId, this.props.signedAttachId)
				.then((vote) => {
					if (!vote)
					{
						this.setState({
							isLoading: false,
							isAccessDenied: true,
						});

						return;
					}

					this.voteMessageId = vote.messageId;

					this.pull = new Pull(this.voteMessageId, vote.isAnonymous);
					this.pull.subscribe();

					this.#subscribeStoreEvents();
					this.setState({
						isLoading: false,
						vote,
					});
				})
			;
		}

		componentWillUnmount()
		{
			this.#unsubscribeStoreEvents();

			this.pull?.unsubscribe();
		}

		#subscribeStoreEvents()
		{
			this.storeManager.on('messagesModel/voteModel/set', this.onVoteModelUpdate);
		}

		#unsubscribeStoreEvents()
		{
			this.storeManager.off('messagesModel/voteModel/set', this.onVoteModelUpdate);
		}

		/**
		 * @param {MutationPayload<VoteSetData, VoteSetActions>} mutation.payload
		 */
		onVoteModelUpdate(mutation)
		{
			const { payload = {} } = mutation;
			const { data = {}, actionName = '' } = payload;

			const updateActions = ['setFromResponse', 'setFromPullEvent'];
			if (
				!updateActions.includes(actionName)
				|| !Type.isArrayFilled(data.voteList)
			)
			{
				return;
			}

			const voteModel = data.voteList.find((vote) => vote.messageId === this.voteMessageId);
			if (voteModel)
			{
				this.#updateVoteFromModel(voteModel);
			}
		}

		#updateVoteFromModel(voteModel)
		{
			this.setState({
				vote: {
					...this.state.vote,
					...voteModel,
					questions: this.state.vote.questions.map((question) => {
						const questionId = String(question.id);
						const questionVotedCounter = voteModel.questionVotedCounter.get(questionId) || 0;

						return {
							...question,
							votedCounter: questionVotedCounter,
							answers: question.answers.map((answer) => {
								const answerId = String(answer.id);
								const answerVotedCounter = voteModel.answerVotedCounter.get(answerId) || 0;
								const answerVotedUserIds = (
									[...(new Map(voteModel.answerVotedUserIds).get(answerId) || []), 0, 0, 0]
										.slice(0, Math.min(answerVotedCounter, 3))
								);

								return {
									...answer,
									votedCounter: answerVotedCounter,
									votedPercent: Math.round(answerVotedCounter / questionVotedCounter * 100),
									votedUserIds: answerVotedUserIds,
									isVotedByMe: voteModel.votedAnswerIds.has(answerId),
								};
							}),
						};
					}),
				},
			});
		}

		render()
		{
			if (this.state.isLoading)
			{
				return LoadingScreen();
			}

			if (this.state.isAccessDenied)
			{
				return this.renderAccessDeniedContent();
			}

			return this.renderVoteResultContent();
		}

		renderAccessDeniedContent()
		{
			return Box(
				{
					safeArea: {
						bottom: true,
					},
					footer: BoxFooter(
						{},
						Button({
							testId: `${this.testId}-button-ok`,
							stretched: true,
							size: ButtonSize.L,
							text: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_RESULT_BUTTON_OK'),
							onClick: () => this.parentWidget.close(),
						}),
					),
					backgroundColor: Color.bgContentPrimary,
				},
				StatusBlock({
					image: Image({
						style: {
							width: 108,
							height: 108,
						},
						svg: {
							uri: makeLibraryImagePath('user-access.svg', 'empty-states'),
						},
					}),
					title: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_RESULT_ACCESS_DENIED_TITLE'),
					description: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_RESULT_ACCESS_DENIED_DESCRIPTION'),
					testId: `${this.testId}-vote-access-denied`,
				}),
			);
		}

		renderVoteResultContent()
		{
			return Box(
				{
					safeArea: {
						bottom: true,
					},
					footer: BoxFooter(
						{},
						Button({
							testId: `${this.testId}-button-copy-link`,
							stretched: true,
							size: ButtonSize.L,
							design: ButtonDesign.OUTLINE_ACCENT_2,
							disabled: !this.vote.resultUrl,
							text: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_RESULT_BUTTON_COPY_LINK'),
							onClick: () => {
								void copyToClipboard(
									`${currentDomain}${this.vote.resultUrl}`,
									Loc.getMessage('IMMOBILE_MESSENGER_VOTE_RESULT_LINK_COPIED'),
								);

								const messageHelper = MessageHelper.createById(this.voteMessageId);
								if (messageHelper?.isVoteModelExist)
								{
									AnalyticsService.getInstance().sendVoteResultLinkCopied(
										this.dialogId,
										this.vote.voteId,
									);
								}
							},
						}),
					),
					withScroll: true,
					backgroundColor: Color.bgContentPrimary,
				},
				Area(
					{
						isFirst: true,
						testId: `${this.testId}-vote-result-container`,
					},
					this.renderVoteInfo(),
					...this.vote.questions.map((question, index) => this.renderQuestion(question, index)),
				),
			);
		}

		renderVoteInfo()
		{
			let text = (
				this.vote.isAnonymous
					? Loc.getMessage('IMMOBILE_MESSENGER_VOTE_RESULT_ANONYMOUS')
					: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_RESULT_PUBLIC')
			);

			if (!this.vote.isRevoteEnabled)
			{
				text += Loc.getMessage('IMMOBILE_MESSENGER_VOTE_RESULT_REVOTE_DISABLED');
			}

			if (this.vote.isFinished)
			{
				text += Loc.getMessage('IMMOBILE_MESSENGER_VOTE_RESULT_FINISHED');
			}

			return Text6({
				text,
				color: Color.base2,
				testId: `${this.testId}-vote-info-field`,
			});
		}

		renderQuestion(question)
		{
			return View(
				{
					style: {
						marginTop: Indent.XL.toNumber(),
					},
					testId: `${this.testId}-question-container`,
				},
				H4({
					style: {
						marginTop: Indent.XS2.toNumber(),
					},
					accent: true,
					text: question.text,
					testId: `${this.testId}-question-text-field`,
				}),
				Text6({
					style: {
						marginTop: Indent.XS.toNumber(),
						marginBottom: Indent.M.toNumber(),
					},
					color: Color.base4,
					text: Loc.getMessagePlural(
						'IMMOBILE_MESSENGER_VOTE_RESULT_VOTED_COUNT',
						question.votedCounter,
						{
							'#COUNT#': question.votedCounter,
						},
					),
					testId: `${this.testId}-question-voted-count-field`,
				}),
				...question.answers.map((answer) => this.renderAnswer(answer)),
			);
		}

		renderAnswer(answer)
		{
			return View(
				{
					style: {
						paddingTop: Indent.XL2.toNumber(),
						paddingBottom: Indent.L.toNumber(),
					},
					testId: `${this.testId}-answer-container`,
				},
				this.renderAnswerProgress(answer),
				answer.votedCounter && this.renderVotedUsers(answer),
			);
		}

		renderAnswerProgress(answer)
		{
			return View(
				{
					style: {
						width: '100%',
						backgroundColor: Color.chatOtherPoll1.toHex(),
						borderRadius: Corner.L.toNumber(),
					},
					testId: `${this.testId}-answer-progress-container`,
				},
				View(
					{
						style: {
							position: 'absolute',
							width: `${answer.votedPercent}%`,
							height: '100%',
							backgroundColor: (answer.isVotedByMe ? Color.chatOtherPoll3 : Color.chatOtherPoll2).toHex(),
							borderRadius: Corner.L.toNumber(),
						},
					},
				),
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'space-between',
							alignItems: 'center',
							marginVertical: Indent.XL2.toNumber(),
							marginLeft: Indent.XL2.toNumber(),
							marginRight: Indent.M.toNumber(),
						},
					},
					Text5({
						style: {
							flex: 1,
						},
						color: Color.chatMyBase0_2,
						accent: true,
						text: answer.text,
						testId: `${this.testId}-answer-progress-text-field`,
					}),
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'flex-end',
							},
						},
						H4({
							color: Color.chatMyBase0_2,
							accent: true,
							text: String(answer.votedPercent),
							testId: `${this.testId}-answer-progress-percent-field`,
						}),
						Text6({
							style: {
								marginLeft: Indent.XS2.toNumber(),
								marginBottom: Indent.XS2.toNumber(),
							},
							color: Color.chatMyBase1_2,
							accent: true,
							text: '%',
						}),
					),
				),
			);
		}

		renderVotedUsers(answer)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingTop: Indent.XL.toNumber(),
					},
					testId: `${this.testId}-voted-users-container`,
					onClick: () => this.openVotedUserList(answer.id, answer.text),
				},
				!this.vote.isAnonymous && AvatarStack({
					style: {
						marginHorizontal: Indent.L.toNumber(),
					},
					entities: answer.votedUserIds,
					visibleEntityCount: 3,
					testId: `${this.testId}-voted-users-avatars`,
					restView: this.renderAvatarStackRestView,
					onClick: useCallback(() => this.openVotedUserList(answer.id, answer.text)),
				}),
				Link4({
					color: Color.base3,
					text: Loc.getMessagePlural(
						'IMMOBILE_MESSENGER_VOTE_RESULT_USERS_COUNT',
						answer.votedCounter,
						{
							'#COUNT#': answer.votedCounter,
						},
					),
					rightIcon: this.vote.isAnonymous ? undefined : Icon.CHEVRON_TO_THE_RIGHT_SIZE_M,
					testId: `${this.testId}-voted-users-field`,
					onClick: () => this.openVotedUserList(answer.id, answer.text),
				}),
			);
		}

		renderAvatarStackRestView()
		{
			return null;
		}

		openVotedUserList(answerId, answerText)
		{
			if (this.vote.isAnonymous)
			{
				return;
			}

			UserList.open({
				answerId,
				answerText,
				messageId: this.voteMessageId,
				parentWidget: this.parentWidget,
			});
		}

		get dialogId()
		{
			return this.props.dialogId;
		}

		get parentWidget()
		{
			return this.props.parentWidget;
		}

		get vote()
		{
			return this.state.vote;
		}

		get testId()
		{
			return 'vote-result';
		}
	}

	VoteResult.propTypes = {
		messageId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
		signedAttachId: PropTypes.string,
		dialogId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
		parentWidget: PropTypes.object,
	};

	module.exports = { VoteResult };
});
