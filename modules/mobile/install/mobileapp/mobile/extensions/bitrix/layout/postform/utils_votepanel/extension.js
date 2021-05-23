(() =>
{
	class VoteDataManager
	{
		constructor()
		{
			this.questions = [];
		};

		load(data)
		{
			if (typeof data.questions !== 'undefined')
			{
				this.questions = data.questions;
			}
		};

		get()
		{
			return {
				questions: this.questions
			};
		};

		clearQuestions()
		{
			this.questions = [];
		};

		addQuestion()
		{
			this.questions.push({
				answers: []
			});
		};

		deleteQuestion(questionIndex)
		{
			this.questions.splice(questionIndex, 1);
		};

		deleteLastQuestion()
		{
			this.questions.pop();
		};

		deleteAnswer(questionIndex, answerIndex)
		{
			this.questions[questionIndex].answers.splice(answerIndex, 1);
		};

		deleteLastAnswer(questionIndex)
		{
			this.questions[questionIndex].answers.pop();
		};

		addAnswer(questionIndex)
		{
			this.questions[questionIndex].answers.push({
			});
		};

		moveAnswer(questionIndex, answerIndex, direction)
		{
			const arrayMove = (array, from, to) => {
				array.splice(to, 0, array.splice(from, 1)[0]);
				return array;
			};

			this.questions[questionIndex].answers = arrayMove(this.questions[questionIndex].answers, answerIndex, (direction === 'up' ? answerIndex - 1 : answerIndex + 1));
		};
	}

	this.VoteDataStateManager = class VoteDataStateManager extends VoteDataManager
	{
		constructor(voteData)
		{
			super();
			this.load(voteData);
		};

		addQuestion()
		{
			this.questions.push({
				answers: [],
				value: '',
				allowMultiSelect: false,
			});
		};

		setQuestionText(questionIndex, text)
		{
			this.questions[questionIndex].value = text;
		}

		setQuestionMultiSelect(questionIndex, value)
		{
			if (
				Array.isArray(this.questions)
				&& this.questions[questionIndex]
			)
			{
				this.questions[questionIndex].allowMultiSelect = value;
			}
		}

		addAnswer(questionIndex)
		{
			this.questions[questionIndex].answers.push({
				value: '',
			});
		};

		setAnswerText(questionIndex, answerIndex, text)
		{
			this.questions[questionIndex].answers[answerIndex].value = text;
		}
	};

	this.VoteDataElementsManager = class VoteDataElementsManager extends VoteDataManager
	{
		constructor()
		{
			super();
		};

		addQuestion()
		{
			this.questions.push({
				answers: [],
				element: null
			});
		};

		setQuestionElement(questionIndex, element)
		{
			if (typeof this.questions[questionIndex] === 'undefined')
			{
				return;
			}

			this.questions[questionIndex].element = element;
		};

		addAnswer(questionIndex)
		{
			this.questions[questionIndex].answers.push({
				element: null
			});
		};

		setAnswerElement(questionIndex, answerIndex, element)
		{
			if (
				typeof this.questions[questionIndex] === 'undefined'
				|| typeof this.questions[questionIndex].answers[answerIndex] === 'undefined'
			)
			{
				return;
			}

			this.questions[questionIndex].answers[answerIndex].element = element;
		};
	}

})();