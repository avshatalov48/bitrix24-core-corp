(function(){

	this.votePanelRef = new VoteDataElementsManager();

	this.VotePanel = ({
		onSetVoteData,
		onAddVoteQuestion,
		onSetVoteQuestionMultiple,
		onFocus,
		onLayout,
		voteData,
		inputTextColor,
		menuCancelTextColor,
		placeholderTextColor,
		postFormData,
		rootScrollRef,
	}) => {

		if (
			!Array.isArray(voteData.questions)
			|| voteData.questions.length <= 0
		)
		{
			return null;
		}

		const layout = [];
		voteData.questions.forEach((question, questionIndex) => {

			layout.push(renderSeparator({
				voteData,
				questionIndex,
				onSetVoteData,
				onAddVoteQuestion,
				onSetVoteQuestionMultiple,
				menuCancelTextColor,
				postFormData
			}));
			layout.push(renderQuestionRow({
				voteData,
				question,
				questionIndex,
				onSetVoteData,
				inputTextColor,
				placeholderTextColor,
				onFocus,
			}));

			if (
				!question.answers
				|| question.answers.length <= 0
			)
			{
				return;
			}

			question.answers.forEach((answer, answerIndex) => {
				layout.push(renderAnswerRow({
					voteData,
					questionIndex,
					answer,
					answerIndex,
					onSetVoteData,
					menuCancelTextColor,
					inputTextColor,
					placeholderTextColor,
					postFormData,
					onFocus,
					rootScrollRef,
				}))
			});

		});

		if (layout.length == 0)
		{
			return null;
		}

		return View(
			{
				style: {
					paddingTop: 15,
					paddingBottom: 15
				},
				onLayout: ({ height }) => {
					onLayout({ height });
				},
			},
			...layout
		);
	};

	renderSeparator = ({
		voteData,
		questionIndex,
		onSetVoteData,
		onAddVoteQuestion,
		onSetVoteQuestionMultiple,
		menuCancelTextColor,
		postFormData,
	}) => {
		return Separator({
			clickCallback: () => {
				onVoteSeparatorClick({
					voteData,
					questionIndex,
					onSetVoteData,
					onAddVoteQuestion,
					onSetVoteQuestionMultiple,
					menuCancelTextColor,
					postFormData,
				})
			}
		})
	};

	renderQuestionRow = ({
		voteData,
		question,
		questionIndex,
		onSetVoteData,
		inputTextColor,
		placeholderTextColor,
		onFocus,
	}) => {
		return View(
			{
				style: {
					marginLeft: 12,
					marginRight: 12,
					marginTop: 15,
					marginBottom: 5,
					alignItems: 'center',
					flexDirection: 'row',
				}
			},
			TextField({
				ref: ref => {
					setTimeout(() => {
						this.votePanelRef.setQuestionElement(questionIndex, ref);
					}, 100);

				},
				value: question.value,
				multiline: false,
				placeholder: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_VOTEPANEL_PLACEHOLDER_QUESTION'),
				placeholderTextColor: placeholderTextColor,
				returnKeyType: 'next',
				style: {
					flex: 1,
					borderWidth: 1,
					borderRadius: 4,
					borderColor: '#DBDDE0',
					color: inputTextColor,
					fontSize: 16,
					fontWeight: 'normal',
					margin: 5,
					backgroundColor: '#00000000',
					paddingTop: 8,
					paddingBottom: 1,
					paddingLeft: 5,
					paddingRight: 5
				},
				onFocus,
				onChangeText: (text) => {
					onQuestionTextChange({
						voteData,
						text,
						questionIndex,
						onSetVoteData
					})
				},
				onSubmitEditing: () => {
					if (
						typeof this.votePanelRef.questions[questionIndex] === 'undefined'
						|| typeof this.votePanelRef.questions[questionIndex].answers[0] === 'undefined'
						|| !this.votePanelRef.questions[questionIndex].answers[0].element
					)
					{
						return;
					}

					this.votePanelRef.questions[questionIndex].answers[0].element.focus();
				},
				autoCapitalize: 'sentences',
			})
		)
	};

	renderAnswerRow = ({
		voteData,
		questionIndex,
		answer,
		answerIndex,
		onSetVoteData,
		menuCancelTextColor,
		inputTextColor,
		placeholderTextColor,
		postFormData,
		onFocus,
		rootScrollRef,
	}) => {
		return View(
			{
				style: {
					marginLeft: 35,
					marginRight: 15,
					marginBottom: 5,
					flexDirection: 'row',
					alignItems: 'center',
				}
			},
			TextField({
				ref: ref => {
					setTimeout(() => { // wait for setState callback
						this.votePanelRef.setAnswerElement(questionIndex, answerIndex, ref);
					}, 1000);
				},
				value: answer.value,
				multiline: false,
				placeholder: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_VOTEPANEL_PLACEHOLDER_ANSWER'),
				placeholderTextColor: placeholderTextColor,
				returnKeyType: 'next',
				style: {
					borderWidth: 1,
					borderRadius: 4,
					borderColor: '#DBDDE0',
					color: inputTextColor,
					fontSize: 16,
					fontWeight: 'normal',
					margin: 5,
					backgroundColor: '#00000000',
					flex: 1,
					paddingTop: 8,
					paddingBottom: 1,
					paddingLeft: 5,
					paddingRight: 5
				},
				onFocus,
				onChangeText: (text) => {
					onAnswerTextChange({
						voteData,
						text,
						questionIndex,
						answerIndex,
						onSetVoteData
					})
				},
				onSubmitEditing: () => {
					if (
						typeof this.votePanelRef.questions[questionIndex] === 'undefined'
						|| typeof this.votePanelRef.questions[questionIndex].answers[answerIndex + 1] === 'undefined'
						|| !this.votePanelRef.questions[questionIndex].answers[answerIndex + 1].element
					)
					{
						return;
					}

					this.votePanelRef.questions[questionIndex].answers[answerIndex + 1].element.focus();

					if (
						parseInt(questionIndex) === parseInt(this.votePanelRef.questions.length) - 1
						&& parseInt(answerIndex) === parseInt(this.votePanelRef.questions[questionIndex].answers.length) - 2
						&& rootScrollRef
					)
					{
						rootScrollRef.scrollToEnd(true);
					}
				},
				autoCapitalize: 'sentences',
			}),
			View(
				{
					style: {
						alignItems: 'center',
						flex: 0
					},
					onClick: () => {
						onOpenAnswerMenu({
							voteData,
							questionIndex,
							answerIndex,
							onSetVoteData,
							menuCancelTextColor,
							postFormData
						})
					}
				},
				Image({
					named: 'icon_threedots',
					style: {
						width: 28,
						height: 28
					}
				})
			)
		)
	};

	onVoteSeparatorClick = ({
		voteData,
		questionIndex,
		onSetVoteData,
		onAddVoteQuestion,
		onSetVoteQuestionMultiple,
		menuCancelTextColor,
		postFormData,
	}) => {

		const menu = dialogs.createPopupMenu();
		const allowMultipleCurrentValue = voteData.questions[questionIndex].allowMultiSelect;

		menu.setData([
				{
					id: 'add',
					title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_VOTEPANEL_MENU_ADD'),
					iconUrl: currentDomain + postFormData.menuPlusIcon,
					sectionCode: '0'
				},
				{
					id: 'multiple',
					title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_VOTEPANEL_MENU_MULTIPLE_' + (allowMultipleCurrentValue ? 'N' : 'Y')),
					iconUrl: currentDomain + postFormData.menuMultiCheckIcon,
					sectionCode: '0'
				},
				{
					id: 'delete',
					title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_VOTEPANEL_MENU_DELETE'),
					iconName: 'delete',
					sectionCode: '0'
				},
				{
					id: 'cancel',
					title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_VOTEPANEL_MENU_CANCEL'),
					textColor: menuCancelTextColor,
					sectionCode: '0'
				}
			],
			[
				{ id: '0' }
			],
			(eventName, item) => {
				if (eventName === 'onItemSelected')
				{
					const voteDataInstance = new VoteDataStateManager(voteData);

					if (item.id === 'add')
					{
						onAddVoteQuestion();
					}
					else if (item.id === 'delete')
					{
						this.votePanelRef.deleteQuestion(questionIndex);
						voteDataInstance.deleteQuestion(questionIndex);
						onSetVoteData(voteDataInstance.get());
					}
					else if (item.id === 'multiple')
					{
						onSetVoteQuestionMultiple(voteData, questionIndex, !allowMultipleCurrentValue);
					}
				}
			}
		);

		menu.setPosition('center');
		menu.show();
	};

	onOpenAnswerMenu = ({
		voteData,
		questionIndex,
		answerIndex,
		onSetVoteData,
		menuCancelTextColor,
		postFormData
	}) => {

		let menu = dialogs.createPopupMenu();

		const arrayMove = (array, from, to) => {
			array.splice(to, 0, array.splice(from, 1)[0]);
			return array;
		};

		menu.setData([
				{
					id: 'up',
					title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_VOTEPANEL_ANSWER_MENU_UP'),
					sectionCode: '0',
					iconUrl: currentDomain + (answerIndex === 0 ? postFormData.menuUpIconDisabled : postFormData.menuUpIcon),
					disable: (answerIndex === 0)
				},
				{
					id: 'down',
					title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_VOTEPANEL_ANSWER_MENU_DOWN'),
					sectionCode: '0',
					iconUrl: currentDomain + (answerIndex === 0 ? postFormData.menuDownIconDisabled : postFormData.menuDownIcon),
					disable: (answerIndex === (voteData.questions[questionIndex].answers.length-1))
				},
				{
					id: 'delete',
					title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_VOTEPANEL_ANSWER_MENU_DELETE'),
					iconName: 'delete',
					sectionCode: '0',
					disable: (voteData.questions[questionIndex].answers[answerIndex].value === '')
				},
				{
					id: 'cancel',
					title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_VOTEPANEL_ANSWER_MENU_CANCEL'),
					textColor: menuCancelTextColor,
					sectionCode: '0'
				}
			],
			[
				{ id: '0' }
			],
			(eventName, item) => {

				if (eventName === 'onItemSelected')
				{
					const voteDataInstance = new VoteDataStateManager(voteData);

					if (item.id === 'delete')
					{
						this.votePanelRef.deleteAnswer(questionIndex, answerIndex);
						voteDataInstance.deleteAnswer(questionIndex, answerIndex);
						onSetVoteData(voteDataInstance.get());
					}
					else if (
						item.id === 'up'
						|| item.id === 'down'
					)
					{
						this.votePanelRef.moveAnswer(questionIndex, answerIndex, item.id);
						voteDataInstance.moveAnswer(questionIndex, answerIndex, item.id);
						onSetVoteData(voteDataInstance.get());
					}
				}
			}
		);

		menu.setPosition('center');
		menu.show();

	};

	onAnswerTextChange = ({
		voteData,
		text,
		questionIndex,
		answerIndex,
		onSetVoteData
	}) => {

		if (
			!Array.isArray(voteData.questions)
			|| voteData.questions.length <= 0
		)
		{
			return;
		}

		const voteDataInstance = new VoteDataStateManager(voteData);
		voteDataInstance.setAnswerText(questionIndex, answerIndex, text);

		if (text.length === 0)
		{
			if (
				answerIndex === voteData.questions[questionIndex].answers.length-2  // before last answer
				&& voteData.questions[questionIndex].answers[answerIndex+1].value.length <= 0 // empty last item
			)
			{
				this.votePanelRef.deleteLastAnswer(questionIndex);
				voteDataInstance.deleteLastAnswer(questionIndex);
			}
			else if (
				answerIndex < voteData.questions[questionIndex].answers.length-2  // before before last answer
			)
			{
				this.votePanelRef.deleteAnswer(questionIndex, answerIndex);
				voteDataInstance.deleteAnswer(questionIndex, answerIndex);
			}

			onSetVoteData(voteDataInstance.get());
		}
		else if (answerIndex === voteData.questions[questionIndex].answers.length-1)  // last answer
		{
			this.votePanelRef.addAnswer(questionIndex);
			voteDataInstance.addAnswer(questionIndex);

			onSetVoteData(voteDataInstance.get());
		}
	};

	onQuestionTextChange = ({
		voteData,
		text,
		questionIndex,
		onSetVoteData
	}) => {

		if (
			!Array.isArray(voteData.questions)
			|| voteData.questions.length <= 0
		)
		{
			return;
		}

		const voteDataInstance = new VoteDataStateManager(voteData);
		voteDataInstance.setQuestionText(questionIndex, text);

		if (text.length <= 0)
		{
			if (
				questionIndex === voteData.questions.length-2  // before last question
				&& voteData.questions[questionIndex+1].value.length <= 0 // empty last item
			)
			{
				this.votePanelRef.deleteLastQuestion();
				voteDataInstance.deleteLastQuestion();
			}
			else if (questionIndex < voteData.questions.length-2)  // before before last question
			{
				this.votePanelRef.deleteQuestion(questionIndex);
				voteDataInstance.deleteQuestion(questionIndex);
			}

			onSetVoteData(voteDataInstance.get());
		}
	};

})();