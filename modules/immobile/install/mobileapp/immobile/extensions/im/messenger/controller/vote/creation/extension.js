/**
 * @module im/messenger/controller/vote/creation
 */
jn.define('im/messenger/controller/vote/creation', (require, exports, module) => {
	const { BottomSheet } = require('bottom-sheet');
	const { Area } = require('ui-system/layout/area');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { Card } = require('ui-system/layout/card');
	const { ButtonSize, Button } = require('ui-system/form/buttons/button');
	const { Text4 } = require('ui-system/typography/text');
	const { AddButton } = require('layout/ui/fields/theme/air/elements/add-button');
	const { SettingSelector } = require('ui-system/blocks/setting-selector');
	const { Color, Indent } = require('tokens');
	const { Loc } = require('im/messenger/loc');
	const { Haptics } = require('haptics');
	const { confirmClosing } = require('alert');
	const { guid } = require('utils/guid');
	const { debounce, useCallback } = require('utils/function');
	const { Type } = require('type');
	const { TextItem } = require('im/messenger/controller/vote/creation/text-item');
	const { VoteService } = require('im/messenger/provider/services/vote');

	const MAX_ANSWERS_COUNT = 10;
	const MAX_QUESTION_LENGTH = 250;
	const MAX_ANSWER_LENGTH = 100;

	class VoteCreation extends LayoutComponent
	{
		/**
		 * @param {number} chatId
		 * @param {function} [dialogCallback]
		 * @param {PageManager} [parentWidget = PageManager]
		 */
		static open({ chatId, dialogCallback, parentWidget = PageManager })
		{
			void new BottomSheet({
				titleParams: {
					text: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_CREATION_TITLE'),
					type: 'dialog',
				},
				component: (layout) => {
					const props = {
						chatId,
						parentWidget: layout,
					};

					if (Type.isFunction(dialogCallback))
					{
						props.dialogCallback = dialogCallback;
					}

					return new VoteCreation(props);
				},
			})
				.setParentWidget(parentWidget)
				.alwaysOnTop()
				.setBackgroundColor(Color.bgContentPrimary.toHex())
				.setNavigationBarColor(Color.bgContentPrimary.toHex())
				.open()
			;
		}

		/**
		 * @private
		 * @param props {{
		 *     chatId: number,
		 *     dialogCallback: function,
		 *     parentWidget: PageManager,
		 * }}
		 */
		constructor(props)
		{
			super(props);

			this.answerRefs = new Map();
			this.questionRefs = new Map();
			this.answerInputRefs = new Map();
			this.questionInputRefs = new Map();
			this.questionMultipleOption = new Map();

			this.focusedInputId = '';
			this.focusedInputRef = null;

			this.blur = this.blur.bind(this);
			this.scrollToRef = debounce(this.scrollToRef, 200, this);
			this.handlePreventBottomSheetDismiss = debounce(this.handlePreventBottomSheetDismiss, 100, this);

			this.cursorPositionY = 0;
			this.voteSettings = {
				isAnonymous: false,
				isRevoteEnabled: true,
			};

			this.state = {
				canSave: false,
				questions: [
					{
						id: guid(),
						text: '',
						isMultipleChoice: false,
						answers: {
							[guid()]: '',
							[guid()]: '',
						},
					},
				],
			};
			this.state.questions.forEach((question) => {
				this.questionMultipleOption.set(question.id, question.isMultipleChoice);
			});
		}

		componentDidMount()
		{
			Keyboard.on(Keyboard.Event.WillHide, this.blur);

			this.parentWidget.on('preventDismiss', () => this.showConfirmOnFormClosing());
		}

		componentWillUnmount()
		{
			Keyboard.off(Keyboard.Event.WillHide, this.blur);
		}

		render()
		{
			return Box(
				{
					safeArea: {
						bottom: true,
					},
					footer: BoxFooter(
						{
							keyboardButton: {
								text: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_CREATION_BUTTON_SUBMIT'),
								testId: `${this.testId}-button-submit`,
								onClick: () => this.onInputSubmit(),
							},
						},
						Button({
							testId: `${this.testId}-button-save`,
							disabled: !this.state.canSave,
							stretched: true,
							size: ButtonSize.L,
							text: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_CREATION_BUTTON_SAVE'),
							onClick: () => this.save(),
						}),
					),
					withScroll: true,
					scrollProps: {
						ref: (ref) => {
							this.boxScrollRef = ref;
						},
					},
					backgroundColor: Color.bgContentPrimary,
				},
				this.renderQuestions(),
				this.renderSettings(),
			);
		}

		renderQuestions()
		{
			return Area(
				{
					isFirst: true,
					excludePaddingSide: {
						bottom: true,
					},
				},
				...this.state.questions.map((question, index) => this.renderQuestion(question, index)),
			);
		}

		renderQuestion(question, questionIndex)
		{
			const canAddAnswer = Object.keys(question.answers).length < MAX_ANSWERS_COUNT;

			return View(
				{},
				Text4({
					style: {
						marginVertical: Indent.L.toNumber(),
					},
					text: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_CREATION_QUESTION_HEADER'),
				}),
				TextItem({
					text: question.text,
					placeholder: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_CREATION_QUESTION_PLACEHOLDER'),
					maxLength: MAX_QUESTION_LENGTH,
					testId: `${this.testId}-question-${questionIndex}`,
					ref: (ref) => this.questionRefs.set(questionIndex, ref),
					onRef: (ref) => this.questionInputRefs.set(questionIndex, ref),
					onFocus: () => this.onFocus(question.id, this.getQuestionInputRef(question.id)),
					onSubmit: () => this.onInputSubmit(),
					onErase: () => this.focus(question.id),
					onChange: this.handlePreventBottomSheetDismiss,
					onCursorPositionChange: (y) => {
						if (this.focusedInputId === question.id)
						{
							this.scrollToRef(this.getQuestionInputRef(question.id)?.getRef?.(), y);
						}
					},
				}),
				Text4({
					style: {
						marginTop: Indent.XL2.toNumber(),
					},
					text: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_CREATION_ANSWER_HEADER'),
				}),
				View(
					{
						style: {
							marginVertical: Indent.M.toNumber(),
						},
					},
					...Object.entries(question.answers).map(([id, text], index) => {
						return TextItem({
							text,
							placeholder: Loc.getMessage(
								'IMMOBILE_MESSENGER_VOTE_CREATION_ANSWER_PLACEHOLDER',
								{ '#INDEX#': index + 1 },
							),
							maxLength: MAX_ANSWER_LENGTH,
							canRemove: Object.keys(question.answers).length > 2,
							testId: `${this.testId}-answer-${index}`,
							ref: (ref) => this.answerRefs.set(index, ref),
							onRef: (ref) => this.answerInputRefs.set(index, ref),
							onFocus: () => this.onFocus(id, this.getAnswerInputRef(id)),
							onSubmit: () => this.onInputSubmit(),
							onErase: () => this.focus(id),
							onChange: this.handlePreventBottomSheetDismiss,
							onRemove: () => this.removeAnswer(id),
							onCursorPositionChange: (y) => {
								if (this.focusedInputId === id)
								{
									this.scrollToRef(this.getAnswerInputRef(id)?.getRef?.(), y);
								}
							},
						});
					}),
					canAddAnswer && AddButton({
						style: {
							paddingTop: Indent.L.toNumber(),
							paddingBottom: Indent.XS.toNumber(),
						},
						text: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_CREATION_ANSWER_BUTTON_ADD'),
						testId: this.testId,
						onClick: () => this.addAnswer(question.id),
					}),
				),
				SettingSelector({
					style: {
						paddingBottom: Indent.XL3.toNumber(),
						borderBottomWidth: 1,
						borderBottomColor: Color.bgSeparatorSecondary.toHex(),
					},
					title: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_CREATION_ANSWER_TOGGLE_IS_MULTIPLE'),
					testId: `${this.testId}-question-multiple-option`,
					checked: question.isMultipleChoice,
					locked: false,
					onClick: useCallback((checked) => {
						Haptics.impactLight();
						this.questionMultipleOption.set(question.id, checked);
					}),
				}),
			);
		}

		renderSettings()
		{
			const { isAnonymous, isRevoteEnabled } = this.voteSettings;

			return Area(
				{},
				Text4({
					style: {
						paddingBottom: Indent.L.toNumber(),
					},
					text: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_CREATION_SETTINGS_HEADER'),
				}),
				Card(
					{
						style: {
							marginTop: Indent.M.toNumber(),
						},
						testId: `${this.testId}-settings-container`,
						border: true,
					},
					SettingSelector({
						style: {
							paddingTop: Indent.S.toNumber(),
							paddingBottom: Indent.XL.toNumber(),
							borderBottomWidth: 1,
							borderBottomColor: Color.bgSeparatorSecondary.toHex(),
						},
						title: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_CREATION_SETTINGS_TOGGLE_IS_ANONYMOUS'),
						testId: `${this.testId}-settings-anonymous`,
						checked: isAnonymous,
						locked: false,
						onClick: useCallback((checked) => {
							Haptics.impactLight();
							this.voteSettings.isAnonymous = checked;
						}),
					}),
					SettingSelector({
						style: {
							paddingTop: Indent.XL.toNumber(),
							paddingBottom: Indent.S.toNumber(),
						},
						title: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_CREATION_SETTINGS_TOGGLE_IS_REVOTE_ENABLED'),
						testId: `${this.testId}-settings-revote`,
						checked: isRevoteEnabled,
						locked: false,
						onClick: useCallback((checked) => {
							Haptics.impactLight();
							this.voteSettings.isRevoteEnabled = checked;
						}),
					}),
				),
			);
		}

		addAnswer(questionId)
		{
			let answerIdToFocus = guid();
			const newQuestions = this.state.questions.map((question) => {
				if (question.id === questionId)
				{
					const filledAnswers = this.fillAnswersWithTextItemValues(question.answers);
					const lastAnswerId = Object.keys(filledAnswers).pop();

					if (filledAnswers[lastAnswerId].trim() === '')
					{
						answerIdToFocus = lastAnswerId;
					}

					return {
						...question,
						text: this.getQuestionRef(question.id)?.getText(),
						isMultipleChoice: this.questionMultipleOption.get(question.id),
						answers: {
							...filledAnswers,
							[answerIdToFocus]: '',
						},
					};
				}

				return {
					...question,
					text: this.getQuestionRef(question.id)?.getText(),
					isMultipleChoice: this.questionMultipleOption.get(question.id),
				};
			});

			this.setState({ questions: newQuestions }, () => this.focus(answerIdToFocus));
		}

		removeAnswer(answerId)
		{
			this.setState(
				{
					questions: this.state.questions.map((question) => {
						if (Object.keys(question.answers).includes(answerId))
						{
							const newAnswers = this.fillAnswersWithTextItemValues(question.answers);
							delete newAnswers[answerId];

							return {
								...question,
								text: this.getQuestionRef(question.id)?.getText(),
								isMultipleChoice: this.questionMultipleOption.get(question.id),
								answers: newAnswers,
							};
						}

						return {
							...question,
							text: this.getQuestionRef(question.id)?.getText(),
							isMultipleChoice: this.questionMultipleOption.get(question.id),
						};
					}),
				},
				() => this.blur(),
			);
		}

		fillAnswersWithTextItemValues(answers)
		{
			return Object.fromEntries(
				Object.entries(answers).map(([id]) => [id, this.getAnswerRef(id)?.getText()]),
			);
		}

		onInputSubmit()
		{
			/** @type {Object<string, string>} */
			const inputFlatMap = this.state.questions.reduce(
				(result, question) => ({
					...result,
					[question.id]: this.getQuestionRef(question.id)?.getText(),
					...this.fillAnswersWithTextItemValues(question.answers),
				}),
				{},
			);
			const inputIds = Object.keys(inputFlatMap);
			const startIndex = inputIds.indexOf(this.focusedInputId) + 1;

			for (let i = startIndex; i < inputIds.length; i++)
			{
				const inputId = inputIds[i];
				const inputText = inputFlatMap[inputId];

				if (inputText.trim() === '')
				{
					this.focus(inputId);

					return;
				}
			}

			this.blur();
		}

		focus(id)
		{
			setTimeout(() => {
				this.getQuestionInputRef(id)?.focus();
				this.getAnswerInputRef(id)?.focus();
			}, 100);
		}

		blur()
		{
			const blur = () => {
				this.focusedInputId = '';
				this.focusedInputRef?.blur?.();
				Keyboard.dismiss();
			};
			const canSave = this.canSave();

			if (canSave === this.state.canSave)
			{
				blur();

				return;
			}

			this.setState(
				{
					canSave,
					questions: this.state.questions.map((question) => ({
						...question,
						text: this.getQuestionRef(question.id)?.getText(),
						isMultipleChoice: this.questionMultipleOption.get(question.id),
						answers: this.fillAnswersWithTextItemValues(question.answers),
					})),
				},
				() => blur(),
			);
		}

		onFocus(id, ref)
		{
			this.focusedInputId = id;
			this.focusedInputRef = ref;

			this.scrollToRef(ref?.getRef?.());
		}

		scrollToRef(inputRef, cursorPositionY = -1)
		{
			const inputPositionY = this.boxScrollRef?.getPosition(inputRef)?.y;

			if (
				inputPositionY
				&& (inputPositionY + cursorPositionY !== this.cursorPositionY)
			)
			{
				this.cursorPositionY = inputPositionY + cursorPositionY;

				setTimeout(
					() => {
						this.boxScrollRef?.scrollTo({
							y: inputPositionY + cursorPositionY - 180,
							animated: true,
						});
						this.cursorPositionY = 0;
					},
					Application.getPlatform() === 'ios' ? 0 : 300,
				);
			}
		}

		/**
		 * @param {string} questionId
		 * @return {TextItem}
		 */
		getQuestionRef(questionId)
		{
			return this.getRefByQuestionId(questionId, this.questionRefs);
		}

		getQuestionInputRef(questionId)
		{
			return this.getRefByQuestionId(questionId, this.questionInputRefs);
		}

		getRefByQuestionId(questionId, refs)
		{
			const questionIds = this.state.questions.map((question) => question.id);
			const index = questionIds.indexOf(questionId);

			if (index !== -1)
			{
				return refs.get(index);
			}

			return null;
		}

		/**
		 * @param {string} answerId
		 * @return {TextItem}
		 */
		getAnswerRef(answerId)
		{
			return this.getRefByAnswerId(answerId, this.answerRefs);
		}

		getAnswerInputRef(answerId)
		{
			return this.getRefByAnswerId(answerId, this.answerInputRefs);
		}

		getRefByAnswerId(answerId, refs)
		{
			for (const question of this.state.questions)
			{
				const answerKeys = Object.keys(question.answers);
				const index = answerKeys.indexOf(answerId);

				if (index !== -1)
				{
					return refs.get(index);
				}
			}

			return null;
		}

		save()
		{
			Haptics.notifySuccess();

			this.dialogCallback({
				voteData: {
					questions: this.state.questions.map((question) => ({
						...question,
						isMultipleChoice: this.questionMultipleOption.get(question.id),
						text: this.getQuestionRef(question.id)?.getText(),
						answers: this.getFilledQuestionAnswers(question),
					})),
					settings: this.voteSettings,
				},
				chatId: this.props.chatId,
			});
			this.parentWidget.close();
		}

		canSave()
		{
			return this.state.questions.every((question) => {
				const isQuestionFilled = this.getQuestionRef(question.id)?.getText().trim().length > 0;
				const isEnoughAnswersFilled = Object.keys(this.getFilledQuestionAnswers(question)).length >= 2;

				return isQuestionFilled && isEnoughAnswersFilled;
			});
		}

		getFilledQuestionAnswers(question)
		{
			return Object.fromEntries(
				Object.entries(this.fillAnswersWithTextItemValues(question.answers))
					.filter(([, answer]) => answer.trim().length > 0)
				,
			);
		}

		handlePreventBottomSheetDismiss()
		{
			const inputValues = Object.values(
				this.state.questions.reduce(
					(result, question) => ({
						...result,
						[question.id]: this.getQuestionRef(question.id)?.getText(),
						...this.fillAnswersWithTextItemValues(question.answers),
					}),
					{},
				),
			);

			this.parentWidget.preventBottomSheetDismiss(inputValues.some((value) => value.trim() !== ''));
		}

		showConfirmOnFormClosing()
		{
			Haptics.impactLight();

			confirmClosing({
				hasSaveAndClose: false,
				title: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_CREATION_CLOSING_ALERT_TITLE'),
				description: Loc.getMessage('IMMOBILE_MESSENGER_VOTE_CREATION_CLOSING_ALERT_DESCRIPTION'),
				onClose: () => this.parentWidget.close(),
			});
		}

		get dialogCallback()
		{
			return this.props.dialogCallback;
		}

		get parentWidget()
		{
			return this.props.parentWidget;
		}

		get testId()
		{
			return 'vote-creation';
		}
	}

	VoteCreation.defaultProps = {
		dialogCallback: ({ chatId, voteData }) => VoteService.getInstance().send(chatId, voteData),
	};

	VoteCreation.propTypes = {
		chatId: PropTypes.number.isRequired,
		dialogCallback: PropTypes.func,
		parentWidget: PropTypes.object,
	};

	module.exports = { VoteCreation };
});
