import {createPinia} from 'ui.vue3.pinia';
import {BitrixVue, nextTick} from "ui.vue3";
import {Toolbar} from "market.toolbar";
import {Main} from "market.main";
import {ListApps} from "market.list-apps";

import "./market.css";
import {EventEmitter} from "main.core.events";

export class Market
{
	constructor(params = {})
	{
		this.params = params.params;
		this.result = params.result;

		(BitrixVue.createApp({
			name: 'Market',
			components: {
				Toolbar, Main, ListApps,
			},
			data: () => {
				return {
					params: this.params,
					result: this.result,
					categories: [],
					favNumbers: 0,
					numUpdates: 0,
					totalApps: 0,
					skeleton: '',
					marketSlider: '',

					mainUri: '',
					siteTemplateUri: '',

					currentUri: '',

					hideToolbar: false,
					hideCategories: false,
					hideBreadcrumbs: false,
					showTitle: true,
					changeHistory: true,

					firstPageHistory: false,
					isHistoryMoving: false,
				};
			},
			computed: {
				isMainPage: function () {
					return this.params.COMPONENT_NAME === 'bitrix:market.main'
				},
				isListPage: function () {
					return this.params.COMPONENT_NAME === 'bitrix:market.list'
				},
				getMainDir: function () {
					return '/market/';
				},
				getMainUri: function () {
					return this.getMainDir;
				},
				getFavoritesUri: function () {
					return this.getMainDir + 'favorites/';
				},
				getInstalledUri: function () {
					return this.getMainDir + 'installed/';
				},
				getUpdatesUri: function () {
					return this.getMainDir + 'installed/?updates=Y';
				},
				getReviewsUri: function () {
					return this.getMainDir + 'reviews/';
				},
				getFavNumbers: function () {
					return this.favNumbers > 99 ? '99+' : this.favNumbers;
				},
				getNumUpdates: function () {
					return this.numUpdates > 99 ? '99+' : this.numUpdates;
				},
				showSkeleton: function () {
					return this.skeleton.length > 0
				},
				getSkeletonPath: function () {
					return "/bitrix/images/market/slider/" + this.skeleton + ".svg"
				},
			},
			created() {
				this.categories = this.result.CATEGORIES;
				this.favNumbers = this.result.FAV_NUMBERS;
				this.numUpdates = this.result.NUM_UPDATES;
				this.totalApps = this.result.TOTAL_APPS;
				this.marketSlider = this.result.MARKET_SLIDER;
				if (this.params.CREATE_URI_SITE_TEMPLATE && this.params.CREATE_URI_SITE_TEMPLATE.length > 0) {
					this.siteTemplateUri = this.params.CREATE_URI_SITE_TEMPLATE;
				}
				if (this.params.HIDE_CATEGORIES && this.params.HIDE_CATEGORIES === 'Y') {
					this.hideCategories = true;
				}
				if (this.params.HIDE_TOOLBAR && this.params.HIDE_TOOLBAR === 'Y') {
					this.hideToolbar = true;
				}
				if (this.params.HIDE_BREADCRUMBS && this.params.HIDE_BREADCRUMBS === 'Y') {
					this.hideBreadcrumbs = true;
				}
				if (this.params.SHOW_TITLE && this.params.SHOW_TITLE === 'N') {
					this.showTitle = false;
				}
				if (this.params.CHANGE_HISTORY && this.params.CHANGE_HISTORY === 'N') {
					this.changeHistory = false;
				}
				if (this.params.ADDITIONAL_BODY_CLASS && this.params.ADDITIONAL_BODY_CLASS.length > 0) {
					document.body.classList.add(this.params.ADDITIONAL_BODY_CLASS);
				}
			},
			mounted() {
				this.$Bitrix.eventEmitter.subscribe('market:loadContent', this.loadContent);
				EventEmitter.subscribe('market:refreshUri', this.refreshUri);
				BX.addCustomEvent("SidePanel.Slider:onMessage", this.onMessageSlider);

				this.setParamsForFirstHistoryPage();
				BX.bind(top.window, 'popstate', this.onPopState.bind(this));
			},
			methods: {
				onPopState: function (event) {
					if (!event.state.uri || !event.state.skeleton) {
						return;
					}

					this.isHistoryMoving = true;
					this.updatePage(event.state.uri, event.state.skeleton);
				},
				setParamsForFirstHistoryPage: function () {
					if (
						!this.params.HISTORY ||
						!this.params.HISTORY.uri ||
						!this.params.HISTORY.skeleton
					) {
						return;
					}

					setTimeout(() => top.history.replaceState({
						uri: this.params.HISTORY.uri,
						skeleton: this.params.HISTORY.skeleton,
					}, ''), 500);

				},
				getDetailUri: function (appCode, isSiteTemplate, from) {
					isSiteTemplate = isSiteTemplate ?? false;

					if (isSiteTemplate) {
						return this.getSiteTemplateUri(appCode, from);
					}

					return this.getMainDir + 'detail/' + appCode + '/?from=' + from;
				},
				getSiteTemplateUri: function (appCode, from) {
					from = from ?? '';
					let path = '/sites/site/edit/0/?IS_FRAME=Y&tpl=market/' + appCode + '&from=' + from;

					if (this.siteTemplateUri.length > 0) {
						let uri = new URL(this.siteTemplateUri, window.location.href);
						uri.searchParams.append('IS_FRAME', 'Y');
						uri.searchParams.append('tpl', 'market/' + appCode);

						path = uri.pathname + uri.search;
					}

					return path;
				},
				getCategoryUri: function (categoryCode) {
					return this.getMainDir + 'category/' + categoryCode + '/';
				},
				getCollectionUri: function (collectionId, showOnPage) {
					if (showOnPage === 'Y') {
						this.getCollectionPageUri(collectionId);
					}

					return this.getMainDir + 'collection/' + collectionId + '/';
				},
				getCollectionPageUri: function (collectionId) {
					return this.getMainDir + 'collection/page/' + collectionId + '/';
				},
				openSiteTemplate: function (event, isSiteTemplate) {
					if (isSiteTemplate) {
						event.preventDefault();
						BX.SidePanel.Instance.open(event.currentTarget.href, {
							customLeftBoundary: 60,
						});
					}
				},
				emitLoadContent: function (event) {
					event.preventDefault();
					this.$Bitrix.eventEmitter.emit('market:loadContent', {info: event})
				},
				loadContent: function(event) {
					const link = event.data.info.target.closest('[data-load-content]');
					if (!link) {
						return;
					}

					if (link.dataset.loadContent.length <= 0 || link.href.length <= 0) {
						return;
					}

					if (this.result.MAIN_URI && this.result.MAIN_URI.length > 0) {
						this.mainUri = this.result.MAIN_URI;
					}

					this.updatePage(link.href, link.dataset.loadContent);
				},
				refreshUri: function(event) {
					if (!event.data.refreshUri || !event.data.skeleton) {
						return;
					}

					this.updatePage(event.data.refreshUri, event.data.skeleton);
				},
				updatePage: function (uri, skeleton) {
					this.skeleton = skeleton;
					this.$Bitrix.eventEmitter.emit('market:closeToolbarPopup');

					BX.ajax.runAction('market.Content.load', {
						data: {
							page: uri,
						},
						analyticsLabel: {
							page: uri,
						},
					}).then(
						response => {
							if (response.data) {
								if (
									BX.type.isObject(response.data.params) &&
									BX.type.isObject(response.data.result)
								) {
									this.params = response.data.params;
									this.result = response.data.result;

									if (this.changeHistory && !this.isHistoryMoving) {
										top.history.pushState({
											uri: uri,
											skeleton: skeleton,
										}, '', uri);
									}

									if (this.showTitle && response.data.result.hasOwnProperty('TITLE')) {
										top.document.title = response.data.result.TITLE;
									}

									this.$Bitrix.eventEmitter.emit('market:loadContentFinish');

									if (document.querySelector('.market-toolbar')) {
										window.scrollTo({
											top: document.querySelector('.market-toolbar').getBoundingClientRect().top,
											behavior: 'smooth',
										});
									}

									this.currentUri = uri;
								}
							}
							nextTick(() => {
								this.skeleton = '';
								this.isHistoryMoving = false;
							});
						},
						response => {
							this.skeleton = '';
							this.isHistoryMoving = false;
						},
					);
				},
				onMessageSlider: function (event) {
					if (event.eventId === 'total-fav-number') {
						this.favNumbers = event.data.total;
					}
				},
			},
			template: `
				<div class="market-wrapper">
					<Toolbar
						:categories="categories"
						:menuInfo="result.MENU_INFO"
						v-if="!hideToolbar"
					/>
					<Main
						v-if="isMainPage"
						:params="params"
						:result="result"
					/>
					<ListApps
						v-else
						:params="params"
						:result="result"
					/>
				</div>
			`,
		})).use(createPinia()).mount('#market-wrapper-vue');
	}
}