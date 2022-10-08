import 'ui.design-tokens';
import 'ui.fonts.opensans';
import { BitrixVue } from 'ui.vue';
import { UI } from 'ui.notification';
import 'main.loader';
import 'ui.info-helper';

import { Search } from './search/search';
import { SearchEvent } from './search/search-event';
import { ButtonAsk, ButtonAskProps } from './button-ask/button-ask';
import { Question } from './question/question';
import { Theme } from '../../mixin/theme';
import { Permissions } from '../../lib/permissions';

import './question-list.css';

const QuestionListState = Object.freeze({
	DEFAULT: 'default',
	SEARCH: 'search',
});

export const QuestionList = BitrixVue.localComponent('imbot-support24-question-component-question-list',{
	components:
	{
		Search,
		ButtonAsk,
		Question,
	},
	directives:
	{
		'bx-imbot-directive-question-list-observer':
		{
			inserted(element, bindings, vnode)
			{
				vnode.context.loaderObserver = vnode.context.getLoaderObserver();
				vnode.context.loaderObserver.observe(element);

				return true;
			},
			unbind(element, bindings, vnode)
			{
				if (vnode.context.loaderObserver)
				{
					vnode.context.loaderObserver.unobserve(element);
				}

				return true;
			}
		},
	},
	mixins: [Theme],
	data: function() {
		return {
			state: QuestionListState.DEFAULT,
			permissions: null,
			itemsPerPage: 50,
			historyPageNumber: 0,
			searchResultPageNumber: 0,
			hasHistoryToLoad: false,
			hasSearchResultToLoad: false,
			searchQuery: '',
			searchRequestCount: 0,
		};
	},
	computed:
	{
		QuestionListState: () => QuestionListState,
		ButtonAskProps: () => ButtonAskProps,
		items()
		{
			if (this.state === QuestionListState.DEFAULT)
			{
				return this.$store.state.question.history;
			}

			if (this.isSearchFromCache)
			{
				return this.$store.state.question.history
					.filter(question => question.title.toLowerCase().includes(this.searchQuery))
				;
			}

			return this.$store.state.question.searchResult;
		},
		isEmpty()
		{
			return this.items.length === 0;
		},
		isSearchFromCache()
		{
			return (
				this.state === QuestionListState.SEARCH
				&& this.searchQuery !== ''
				&& this.searchQuery.length < 3
			);
		},
		isLoadingInProgress()
		{
			return this.searchRequestCount > 0;
		},
		historyNavigationParams()
		{
			return {
				limit: this.itemsPerPage,
				offset: this.itemsPerPage * this.historyPageNumber,
			};
		},
		searchNavigationParams()
		{
			return {
				limit: this.itemsPerPage,
				offset: this.itemsPerPage * this.searchResultPageNumber,
			};
		},
		showTariffLock()
		{
			return this.permissions && (!this.permissions.isAdmin || !this.permissions.canAskQuestion);
		},
		showLoader()
		{
			if (this.state === QuestionListState.DEFAULT)
			{
				return this.hasHistoryToLoad;
			}

			if (this.isSearchFromCache)
			{
				return false;
			}

			return this.hasSearchResultToLoad;
		},
		searchFieldBorderClass()
		{
			return this.getClassWithTheme('bx-imbot-support24-question-list-search-field-border');
		},
		listItemsClass()
		{
			return this.getClassWithTheme('bx-imbot-support24-question-list-items');
		},
		emptyTitleClass()
		{
			return this.getClassWithTheme('bx-imbot-support24-question-list-empty-title');
		},
		emptyDescriptionClass()
		{
			return this.getClassWithTheme('bx-imbot-support24-question-list-empty-description');
		},
		placeholderTextClass()
		{
			return this.getClassWithTheme('bx-imbot-support24-question-list-placeholder-text');
		},
		notFoundIconClass()
		{
			return this.getClassWithTheme('bx-imbot-support24-question-list-not-found-icon');
		},
		questionListLoaderClass()
		{
			return this.getClassWithTheme('bx-imbot-support24-question-list-items-loader-svg-circle');
		},
		questionListSearchLoaderClass()
		{
			return this.getClassWithTheme('bx-imbot-support24-question-list-items-search-loader-svg-circle');
		},
	},
	created()
	{
		this.$store.dispatch('question/trimHistory', this.itemsPerPage).then(() => {
			const initRequests = {
				config: {
					method: 'imbot.support24.question.config.get',
				},
				questions: {
					method: 'imbot.support24.question.list',
					params: this.historyNavigationParams,
				},
			};

			const initCallback = (response) => {
				this.permissions = new Permissions(response.config.data());

				this.afterHistoryPageLoaded(response.questions);
			};

			this.getRestClient().callBatch(initRequests, initCallback);
		});
	},
	methods:
	{
		getRestClient()
		{
			return this.$Bitrix.RestClient.get();
		},
		searchQuestions(event: SearchEvent)
		{
			this.searchQuery = event.getData().searchQuery.toLowerCase();

			const truncatedSearchQuery = this.searchQuery.trim();

			if (truncatedSearchQuery === '')
			{
				this.state = QuestionListState.DEFAULT;
				return;
			}

			this.state = QuestionListState.SEARCH;

			if (truncatedSearchQuery.length < 3)
			{
				return;
			}

			this.searchRequestCount++;

			const searchParams = {
				searchQuery: this.searchQuery,
				limit: this.itemsPerPage,
			};

			this.getRestClient().callMethod('imbot.support24.question.search', searchParams)
				.then(response => {
					const questions = response.data();

					if (this.searchRequestCount === 1)
					{
						this.$store.dispatch('question/setSearchResult', questions).then(() => {
							this.searchResultPageNumber = 1;
							this.hasSearchResultToLoad = questions.length >= this.itemsPerPage;

							this.searchRequestCount--;
						});
					}
					else
					{
						this.searchRequestCount--;
					}
				})
				.catch(() => {
					this.searchRequestCount--;
				})
			;
		},
		loadNextPage()
		{
			if (this.state === QuestionListState.DEFAULT)
			{
				this.loadNextHistoryPage();
				return;
			}

			this.loadNextSearchPage();
		},
		loadNextHistoryPage()
		{
			this.getRestClient().callMethod('imbot.support24.question.list', this.historyNavigationParams)
				.then(response => this.afterHistoryPageLoaded(response))
			;
		},
		loadNextSearchPage()
		{
			if (this.searchQuery === '')
			{
				return;
			}

			const params = {
				searchQuery: this.searchQuery,
				...this.searchNavigationParams,
			};

			this.getRestClient().callMethod('imbot.support24.question.search', params)
				.then(response => this.afterSearchPageLoaded(response))
			;
		},
		afterHistoryPageLoaded(response)
		{
			const questions = response.data();

			this.hasHistoryToLoad = questions.length >= this.itemsPerPage;

			const addMethod =
				this.historyPageNumber === 0
					? 'question/setHistory'
					: 'question/addHistory'
			;

			this.$store.dispatch(addMethod, questions).then(() => {
				this.historyPageNumber++;
			});
		},
		afterSearchPageLoaded(response)
		{
			const questions = response.data();

			this.hasSearchResultToLoad = questions.length >= this.itemsPerPage;

			const addMethod =
				this.searchResultPageNumber === 0
					? 'question/setSearchResult'
					: 'question/addSearchResult'
			;

			this.$store.dispatch(addMethod, questions).then(() => {
				this.searchResultPageNumber++;
			});
		},
		onAskQuestion()
		{
			if (!this.permissions)
			{
				//Access rights are not yet known
				this.addQuestion();

				return;
			}

			if (!this.permissions.isAdmin)
			{
				this.sendRestrictionNotification();

				return;
			}

			if (!this.permissions.canAskQuestion && this.permissions.canImproveTariff)
			{
				this.openTariffSlider();

				return;
			}

			this.addQuestion();
		},
		addQuestion()
		{
			this.getRestClient().callMethod('imbot.support24.question.add')
				.then(response => {
					const dialogId = response.data();
					this.openDialog(dialogId);
				})
				.catch(response => {
					if (!response.answer || !response.answer.error)
					{
						console.error(response);
					}

					const errorCode = response.answer.error;
					switch (errorCode)
					{
						case 'ACCESS_DENIED':
							this.sendRestrictionNotification();
							break;

						case 'QUESTION_LIMIT_EXCEEDED':
							this.openTariffSlider();
							break;
					}
				})
			;
		},
		sendRestrictionNotification()
		{
			UI.Notification.Center.notify({
				id: 'imbot_support24_question_list_restriction_not_admin',
				content: this.$Bitrix.Loc.getMessage('IMBOT_SUPPORT24_QUESTION_LIST_RESTRICTION_NOT_ADMIN'),
				autoHideDelay: 5000,
			});
		},
		openTariffSlider()
		{
			BX.UI.InfoHelper.show('limit_admin_multidialogues');
		},
		openDialog(dialogId)
		{
			const popupContext = this.$Bitrix.Data.get('popupContext');
			if (popupContext)
			{
				popupContext.closePopup();
			}

			BXIM.openMessenger('chat' + dialogId);
		},
		getLoaderObserver()
		{
			const options = {
				root: document.querySelector('.bx-imbot-support24-question-list-items'),
				threshold: 0.01,
			};

			const callback = (entries, observer) => {
				entries.forEach(entry => {
					if (
						entry.isIntersecting
						&& entry.intersectionRatio > 0.01
					)
					{
						this.loadNextPage();
					}
				});
			};

			return new IntersectionObserver(callback, options);
		},
	},
	// language=Vue
	template: `
		<div class="bx-imbot-support24-question-list">
			<template v-if="isLoadingInProgress">
				<div class="bx-imbot-support24-question-list-search-field">
					<div class="bx-imbot-support24-question-list-search-container">
						<Search
							@search="searchQuestions"
						/>
					</div>

					<div class="bx-imbot-support24-question-list-ask-container">
						<ButtonAsk
							:type="ButtonAskProps.Type.SECONDARY"
							@askQuestion="onAskQuestion"
						/>
					</div>
				</div>
				<div class="bx-imbot-support24-question-list-placeholder">
					<div class="bx-imbot-support24-question-list-loading-icon">
						<div class="main-ui-loader main-ui-show" style="width: 45px; height: 45px;" data-is-shown="true">
							<svg class="main-ui-loader-svg" viewBox="25 25 50 50">
								<circle
									class="main-ui-loader-svg-circle"
									:class="questionListSearchLoaderClass"
									cx="50"
									cy="50"
									r="20"
									fill="none"
									stroke-miterlimit="10"
								/>
							</svg>
						</div>
					</div>
					<div :class="placeholderTextClass">
						{{ $Bitrix.Loc.getMessage('IMBOT_SUPPORT24_QUESTION_LIST_SEARCHING') }}
					</div>
				</div>
			</template>

			<template v-else-if="isEmpty && state === QuestionListState.SEARCH">
				<div class="bx-imbot-support24-question-list-search-field">
					<div class="bx-imbot-support24-question-list-search-container">
						<Search
							@search="searchQuestions"
						/>
					</div>

					<div class="bx-imbot-support24-question-list-ask-container">
						<ButtonAsk
							:type="ButtonAskProps.Type.SECONDARY"
							@askQuestion="onAskQuestion"
						/>
					</div>
				</div>

				<div class="bx-imbot-support24-question-list-placeholder">
					<div :class="notFoundIconClass">:(</div>
					<div :class="placeholderTextClass">
						{{ $Bitrix.Loc.getMessage('IMBOT_SUPPORT24_QUESTION_LIST_SEARCH_NOT_FOUND') }}
					</div>
				</div>
			</template>

			<template v-else-if="!isEmpty">
				<div 
					class="bx-imbot-support24-question-list-search-field"
					:class="searchFieldBorderClass"
				>
					<div class="bx-imbot-support24-question-list-search-container">
						<Search
							@search="searchQuestions"
						/>
					</div>
					
					<div class="bx-imbot-support24-question-list-ask-container">
						<ButtonAsk
							:type="ButtonAskProps.Type.SECONDARY"
							@askQuestion="onAskQuestion"
						/>
					</div>
				</div>

				<div :class="listItemsClass">
					<Question
						v-for="item of items"
						:key="item.id"
						:id="item.id"
						:title="item.title"
						@click="openDialog"
					/>
					
					<div 
						class="bx-imbot-support24-question-list-items-loader"
						v-if="showLoader" 
						:key="'question-list-items-loader'" 
						v-bx-imbot-directive-question-list-observer
					>
						<div class="main-ui-loader main-ui-show" style="width: 23px; height: 23px;" data-is-shown="true">
							<svg class="main-ui-loader-svg" viewBox="25 25 50 50">
								<circle 
									class="main-ui-loader-svg-circle"
									:class="questionListLoaderClass"
									cx="50"
									cy="50"
									r="20"
									fill="none"
									stroke-miterlimit="10"
								/>
							</svg>
						</div>
					</div>
				</div>
			</template>
			
			<div
				class="bx-imbot-support24-question-list-empty"
				v-else-if="isEmpty && state === QuestionListState.DEFAULT"
			>
				<div :class="emptyTitleClass">
					{{ $Bitrix.Loc.getMessage('IMBOT_SUPPORT24_QUESTION_LIST_EMPTY_TITLE') }}
				</div>
				<div :class="emptyDescriptionClass">
					{{ $Bitrix.Loc.getMessage('IMBOT_SUPPORT24_QUESTION_LIST_EMPTY_DESCRIPTION') }}
				</div>
				<div class="bx-imbot-support24-question-list-button-ask-container">
					<ButtonAsk
						:type="ButtonAskProps.Type.PRIMARY"
						@askQuestion="onAskQuestion"
					/>
					<span 
						class="bx-imbot-support24-question-list-tariff-lock"
						v-if="showTariffLock"
					/>
				</div>
			</div>
		</div>
	`
});