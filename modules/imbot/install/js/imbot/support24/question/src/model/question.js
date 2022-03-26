import { VuexBuilderModel } from 'ui.vue.vuex';
import { Type } from 'main.core';

export class QuestionModel extends VuexBuilderModel
{
	getName()
	{
		return 'question';
	}

	getState()
	{
		return {
			history: [],
			searchResult: [],
		};
	}

	getActions()
	{
		return {
			trimHistory: (store, count) =>
			{
				store.commit('trimHistory', count);
			},
			setHistory: (store, payload) =>
			{
				store.commit('setHistory', this.validateQuestionList(payload));
			},
			addHistory: (store, payload) =>
			{
				store.commit('addHistory', this.validateQuestionList(payload));
			},
			setSearchResult: (store, payload) =>
			{
				store.commit('setSearchResult', this.validateQuestionList(payload));
			},
			addSearchResult: (store, payload) =>
			{
				store.commit('addSearchResult', this.validateQuestionList(payload));
			},
			setQuestionTitleById: (store, payload) =>
			{
				store.commit('setQuestionTitleById', payload);
			},
		};
	}

	getMutations()
	{
		return {
			trimHistory: (state, count) =>
			{
				state.history = state.history.filter((question, questionIndex) => questionIndex < count);

				super.saveState(state);
			},
			setHistory: (state, payload) =>
			{
				state.history = payload;

				super.saveState(state);
			},
			addHistory: (state, payload) =>
			{
				state.history = state.history.concat(payload);

				super.saveState(state);
			},
			setSearchResult: (state, payload) =>
			{
				state.searchResult = payload;

				super.saveState(state);
			},
			addSearchResult: (state, payload) =>
			{
				state.searchResult = state.searchResult.concat(payload);

				super.saveState(state);
			},
			setQuestionTitleById: (state, payload) =>
			{
				const question = state.history.find(question => question.id === payload.id);

				question.title = payload.title;

				super.saveState(state);
			},
		};
	}

	validateQuestionList(questions)
	{
		if (!Type.isArrayFilled(questions))
		{
			return [];
		}

		return questions.filter(question => this.isQuestion(question));
	}

	isQuestion(question)
	{
		return (
			Object.keys(question).length === 2
			&& Type.isInteger(question.id)
			&& Type.isString(question.title)
		);
	}
}
