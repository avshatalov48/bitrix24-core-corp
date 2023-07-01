import {ratingStore} from "market.rating-store";
import {MenuManager} from "main.popup";

import { mapActions } from 'ui.vue3.pinia';
import "./my-reviews-component.css";

export const MyReviewsComponent = {
	components: {

	},
	props: [
		'params', 'result',
	],
	data() {
		return {
			filterMenu: null,
			page: 1,
			bottomLoader: null,
			nextPageLoadWait: false,
			filterName: '',
			filterValue: '',
			filterLoader: null,
		};
	},
	computed: {
		isEmpty: function () {
			return !this.result.REVIEWS.ALL_ITEMS || this.result.REVIEWS.ALL_ITEMS.length <= 0
		},
		isFilterEmpty: function () {
			return !this.result.REVIEWS.ITEMS || this.result.REVIEWS.ITEMS.length <= 0
		},
		showNextPageButton: function () {
			if (this.result.REVIEWS.CUR_PAGE && this.result.REVIEWS.PAGES) {
				if (this.result.REVIEWS.CUR_PAGE < this.result.REVIEWS.PAGES) {
					return true;
				}
			}

			return false;
		},
	},
	mounted () {
		this.bindNextPageEvent();
		this.initLoaders();
		this.initMenu();
		this.initFilter();
	},
	methods: {
		getDetailUri: function (appCode) {
			return '/market/detail/' + appCode + '/?from=reviews';
		},
		initFilter: function () {
			if (this.result.REVIEWS.FILTER_CURRENT) {
				this.filterName = this.result.REVIEWS.FILTER_CURRENT.NAME;
				this.filterValue = this.result.REVIEWS.FILTER_CURRENT.VALUE;
			}
		},
		initMenu: function () {
			let menu = [];
			for (let filterValue in this.result.REVIEWS.FILTER) {
				menu.push({
					text: this.result.REVIEWS.FILTER[filterValue],
					onclick: (event) => {
						this.filterValue = filterValue;
						this.page = 1;
						this.moreReviews();
					},
				});
			}

			this.filterMenu = MenuManager.create(
				'reviews-popup-menu',
				this.$refs.myReviewsMenu,
				menu,
				{
					closeByEsc : true,
					autoHide : true,
					angle: false,
					offsetTop: 10,
				}
			);
		},
		showMenu: function () {
			this.filterMenu.show();
		},
		initLoaders: function () {
			this.bottomLoader = new BX.Loader({
				target: this.$refs.marketReviewsBottomLoader,
				mode: 'inline',
				size: 100,
			});

			this.filterLoader = new BX.Loader({
				target: this.$refs.marketReviewsLoader,
				size: 100,
			});
		},
		bindNextPageEvent: function () {
			BX.bind(document, 'scroll', (event) => {
				if (this.needLoadNextPage(event.currentTarget)) {
					this.nextPage();
				}
			});
		},
		needLoadNextPage: function(document) {
			if (
				!document ||
				!document.scrollingElement ||
				!document.scrollingElement.scrollHeight ||
				!this.showNextPageButton ||
				this.nextPageLoadWait
			) {
				return false;
			}

			const doc = document.scrollingElement;

			return doc.scrollTop >= doc.scrollHeight - (doc.offsetHeight * 1.5);
		},
		nextPage: function () {
			if (this.nextPageLoadWait) {
				return;
			}

			this.nextPageLoadWait = true;

			this.page = parseInt(this.result.REVIEWS.CUR_PAGE, 10) + 1;

			this.bottomLoader.show();
			this.moreReviews(true);
		},
		moreReviews: function (append) {
			append = append || false;

			const isFilter = !append;
			if (isFilter) {
				this.filterMenu.close();

				if (this.filterValue === this.result.REVIEWS.FILTER_CURRENT.VALUE) {
					return;
				}

				this.filterLoader.show();
			}

			BX.ajax.runComponentAction(this.params.COMPONENT_NAME, 'getReviewPage', {
				mode: 'class',
				signedParameters: [],
				data: {
					page: this.page,
					filter: this.filterValue,
				},
				analyticsLabel: {
					page: this.page,
					filter: this.filterValue,
				},
			}).then(
				response => {
					this.nextPageLoadWait = false;
					this.bottomLoader.hide();
					this.filterLoader.hide();

					if (response.data && response.data.reviews && BX.type.isArray(response.data.reviews.ITEMS)) {
						if (isFilter) {
							this.result.REVIEWS.ITEMS = response.data.reviews.ITEMS;
						} else {
							this.result.REVIEWS.ITEMS = this.result.REVIEWS.ITEMS.concat(response.data.reviews.ITEMS);
						}

						this.result.REVIEWS.CUR_PAGE = response.data.reviews.CUR_PAGE;
						this.result.REVIEWS.PAGES = response.data.reviews.PAGES;
						this.result.REVIEWS.FILTER_CURRENT = response.data.reviews.FILTER_CURRENT;
						this.filterName = response.data.reviews.FILTER_CURRENT.NAME;
					}
				},
				response => {
					this.nextPageLoadWait = false;
					this.bottomLoader.hide();
				}
			);
		},
		...mapActions(ratingStore, ['isActiveStar', 'getAppRating',]),
	},
	template: `
		<div class="market-reviews__wrapper">
			<div class="market-reviews__title">
				{{ $Bitrix.Loc.getMessage('MARKET_MY_REVIEWS') }}
			</div>
			<div class="market-reviews__container">
				<div class="market-reviews__empty"
					 v-if="isEmpty"
				>
					<div class="market-reviews__empty-icon">
						<svg width="178" height="145" viewBox="0 0 178 145" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path opacity="0.3" fill-rule="evenodd" clip-rule="evenodd" d="M9.99838 62.6071C13.1559 62.6071 15.7155 60.0475 15.7155 56.89C15.7155 53.7325 13.1559 51.1729 9.99838 51.1729C6.8409 51.1729 4.28125 53.7325 4.28125 56.89C4.28125 60.0475 6.8409 62.6071 9.99838 62.6071ZM9.99838 59.6233C11.508 59.6233 12.7317 58.3996 12.7317 56.89C12.7317 55.3804 11.508 54.1566 9.99838 54.1566C8.48879 54.1566 7.26502 55.3804 7.26502 56.89C7.26502 58.3996 8.48879 59.6233 9.99838 59.6233Z" fill="#C7C7C7"/>
							<path opacity="0.3" fill-rule="evenodd" clip-rule="evenodd" d="M31.5612 21.1682C33.9055 21.1682 35.8059 19.2678 35.8059 16.9235C35.8059 14.5792 33.9055 12.6787 31.5612 12.6787C29.2169 12.6787 27.3164 14.5792 27.3164 16.9235C27.3164 19.2678 29.2169 21.1682 31.5612 21.1682ZM31.5611 18.8015C32.5984 18.8015 33.4392 17.9607 33.4392 16.9235C33.4392 15.8863 32.5984 15.0454 31.5611 15.0454C30.5239 15.0454 29.6831 15.8863 29.6831 16.9235C29.6831 17.9607 30.5239 18.8015 31.5611 18.8015Z" fill="#C7C7C7"/>
							<path opacity="0.3" d="M130.139 2.29497C130.139 3.37637 129.262 4.25303 128.181 4.25303C127.099 4.25303 126.223 3.37637 126.223 2.29497C126.223 1.21357 127.099 0.336914 128.181 0.336914C129.262 0.336914 130.139 1.21357 130.139 2.29497Z" fill="#C7C7C7"/>
							<path opacity="0.3" fill-rule="evenodd" clip-rule="evenodd" d="M155.148 134.545C157.396 134.545 159.219 132.723 159.219 130.475C159.219 128.227 157.396 126.404 155.148 126.404C152.9 126.404 151.078 128.227 151.078 130.475C151.078 132.723 152.9 134.545 155.148 134.545ZM155.148 132.427C156.227 132.427 157.1 131.553 157.1 130.475C157.1 129.397 156.227 128.523 155.148 128.523C154.07 128.523 153.196 129.397 153.196 130.475C153.196 131.553 154.07 132.427 155.148 132.427Z" fill="#C7C7C7"/>
							<path opacity="0.3" d="M159.891 75C159.891 113.66 128.551 145 89.8906 145C51.2307 145 19.8906 113.66 19.8906 75C19.8906 36.3401 51.2307 5 89.8906 5C128.551 5 159.891 36.3401 159.891 75Z" fill="#C7C7C7"/>
							<g opacity="0.7">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M40 57C40 50.3726 45.3726 45 52 45H129C135.627 45 141 50.3726 141 57V89C141 95.6274 135.627 101 129 101H101L104 114C104 114 82.8333 104.167 81 101H52C45.3726 101 40 95.6274 40 89V57ZM67.0132 59.7159C66.6943 58.7614 65.3057 58.7614 64.9868 59.7159L62.4122 67.4219C62.2696 67.8488 61.8605 68.1378 61.399 68.1378H53.0674C52.0354 68.1378 51.6063 69.4222 52.4412 70.0121L59.1816 74.7747C59.555 75.0385 59.7112 75.5062 59.5686 75.9331L56.994 83.6391C56.6751 84.5936 57.7985 85.3874 58.6334 84.7974L65.3738 80.0349C65.7472 79.771 66.2528 79.771 66.6262 80.0349L73.3666 84.7974C74.2015 85.3874 75.3249 84.5936 75.006 83.6391L72.4314 75.9331C72.2888 75.5062 72.445 75.0385 72.8184 74.7747L79.5588 70.0121C80.3937 69.4222 79.9646 68.1378 78.9326 68.1378H70.601C70.1395 68.1378 69.7304 67.8488 69.5878 67.4219L67.0132 59.7159Z" fill="#D1D1D1"/>
								<rect x="89" y="60" width="36" height="4" rx="2" fill="#BDBDBD"/>
								<rect x="89" y="67" width="36" height="4" rx="2" fill="#BDBDBD"/>
								<rect x="89" y="74" width="29" height="4" rx="2" fill="#BDBDBD"/>
								<rect x="89" y="81" width="23" height="4" rx="2" fill="#BDBDBD"/>
							</g>
							<path opacity="0.3" fill-rule="evenodd" clip-rule="evenodd" d="M19.9662 140.107H6.35804C6.2416 140.107 6.12616 140.102 6.01188 140.094C2.9364 140.022 0.464999 137.437 0.464844 134.259C0.465738 132.711 1.06528 131.226 2.13157 130.131C2.67814 129.57 3.3244 129.135 4.02751 128.845C4.01575 128.687 4.00976 128.528 4.00976 128.367C4.01072 126.733 4.64335 125.166 5.76846 124.011C6.89357 122.856 8.41901 122.208 10.0092 122.209C12.0462 122.212 13.8446 123.258 14.9254 124.856C15.4386 124.669 15.9911 124.568 16.5667 124.568C19.1095 124.571 21.1979 126.563 21.44 129.111C23.8763 129.656 25.6996 131.887 25.6974 134.554C25.6949 137.627 23.2692 140.117 20.2786 140.116C20.1737 140.116 20.0696 140.113 19.9662 140.107Z" fill="#C7C7C7"/>
							<path opacity="0.3" fill-rule="evenodd" clip-rule="evenodd" d="M173.84 31.8544H164.182C164.1 31.8544 164.018 31.8514 163.937 31.8454C161.754 31.7964 160 30.0342 160 27.8676C160.001 26.8116 160.426 25.7991 161.183 25.0528C161.571 24.6703 162.029 24.3738 162.528 24.1757C162.52 24.0682 162.516 23.9596 162.516 23.8501C162.516 22.7358 162.965 21.6675 163.764 20.8801C164.562 20.0927 165.645 19.6507 166.773 19.6514C168.219 19.6531 169.495 20.3669 170.262 21.4563C170.627 21.3288 171.019 21.2596 171.427 21.2598C173.232 21.2619 174.714 22.6201 174.886 24.357C176.615 24.7292 177.909 26.25 177.907 28.0684C177.905 30.1639 176.184 31.8615 174.061 31.8607C173.987 31.8606 173.913 31.8585 173.84 31.8544Z" fill="#C7C7C7"/>
						</svg>
					</div>
					<div class="market-reviews__empty-title">
						{{ $Bitrix.Loc.getMessage('MARKET_DONT_HAVE_REVIEWS') }}
					</div>
					<div class="market-reviews__empty-description">
						{{ $Bitrix.Loc.getMessage('MARKET_NO_REVIEW_APPS') }}
					</div>
				</div>
				<div class="market-reviews__content"
					 v-else
				>
					<div class="market-reviews__sort">
						<div class="market-reviews__sort-btn" 
							 ref="myReviewsMenu"
							 @click="showMenu"
						>
							<svg class="market-reviews__sort-btn-icon" width="18" height="15" viewBox="0 0 18 15" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M1.13244 6.51895L4.3419 3.18492V10.3125H5.94372V3.18492L9.15318 6.51895L10.2857 5.34246L5.14286 0L0 5.34246L1.13244 6.51895Z" fill="#515E68"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M16.8676 8.48105L13.6581 11.8151L13.6581 4.6875L12.0563 4.6875L12.0563 11.8151L8.84682 8.48105L7.71429 9.65754L12.8571 15L18 9.65754L16.8676 8.48105Z" fill="#515E68"/>
							</svg>
							<span>{{ filterName }}</span>
						</div>
					</div>
					<div class="market-reviews__empty"
						 v-if="isFilterEmpty"
					>
						<div class="market-reviews__empty-icon">
							<svg width="178" height="145" viewBox="0 0 178 145" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path opacity="0.3" fill-rule="evenodd" clip-rule="evenodd" d="M9.99838 62.6071C13.1559 62.6071 15.7155 60.0475 15.7155 56.89C15.7155 53.7325 13.1559 51.1729 9.99838 51.1729C6.8409 51.1729 4.28125 53.7325 4.28125 56.89C4.28125 60.0475 6.8409 62.6071 9.99838 62.6071ZM9.99838 59.6233C11.508 59.6233 12.7317 58.3996 12.7317 56.89C12.7317 55.3804 11.508 54.1566 9.99838 54.1566C8.48879 54.1566 7.26502 55.3804 7.26502 56.89C7.26502 58.3996 8.48879 59.6233 9.99838 59.6233Z" fill="#C7C7C7"/>
								<path opacity="0.3" fill-rule="evenodd" clip-rule="evenodd" d="M31.5612 21.1682C33.9055 21.1682 35.8059 19.2678 35.8059 16.9235C35.8059 14.5792 33.9055 12.6787 31.5612 12.6787C29.2169 12.6787 27.3164 14.5792 27.3164 16.9235C27.3164 19.2678 29.2169 21.1682 31.5612 21.1682ZM31.5611 18.8015C32.5984 18.8015 33.4392 17.9607 33.4392 16.9235C33.4392 15.8863 32.5984 15.0454 31.5611 15.0454C30.5239 15.0454 29.6831 15.8863 29.6831 16.9235C29.6831 17.9607 30.5239 18.8015 31.5611 18.8015Z" fill="#C7C7C7"/>
								<path opacity="0.3" d="M130.139 2.29497C130.139 3.37637 129.262 4.25303 128.181 4.25303C127.099 4.25303 126.223 3.37637 126.223 2.29497C126.223 1.21357 127.099 0.336914 128.181 0.336914C129.262 0.336914 130.139 1.21357 130.139 2.29497Z" fill="#C7C7C7"/>
								<path opacity="0.3" fill-rule="evenodd" clip-rule="evenodd" d="M155.148 134.545C157.396 134.545 159.219 132.723 159.219 130.475C159.219 128.227 157.396 126.404 155.148 126.404C152.9 126.404 151.078 128.227 151.078 130.475C151.078 132.723 152.9 134.545 155.148 134.545ZM155.148 132.427C156.227 132.427 157.1 131.553 157.1 130.475C157.1 129.397 156.227 128.523 155.148 128.523C154.07 128.523 153.196 129.397 153.196 130.475C153.196 131.553 154.07 132.427 155.148 132.427Z" fill="#C7C7C7"/>
								<path opacity="0.3" d="M159.891 75C159.891 113.66 128.551 145 89.8906 145C51.2307 145 19.8906 113.66 19.8906 75C19.8906 36.3401 51.2307 5 89.8906 5C128.551 5 159.891 36.3401 159.891 75Z" fill="#C7C7C7"/>
								<g opacity="0.7">
									<path fill-rule="evenodd" clip-rule="evenodd" d="M40 57C40 50.3726 45.3726 45 52 45H129C135.627 45 141 50.3726 141 57V89C141 95.6274 135.627 101 129 101H101L104 114C104 114 82.8333 104.167 81 101H52C45.3726 101 40 95.6274 40 89V57ZM67.0132 59.7159C66.6943 58.7614 65.3057 58.7614 64.9868 59.7159L62.4122 67.4219C62.2696 67.8488 61.8605 68.1378 61.399 68.1378H53.0674C52.0354 68.1378 51.6063 69.4222 52.4412 70.0121L59.1816 74.7747C59.555 75.0385 59.7112 75.5062 59.5686 75.9331L56.994 83.6391C56.6751 84.5936 57.7985 85.3874 58.6334 84.7974L65.3738 80.0349C65.7472 79.771 66.2528 79.771 66.6262 80.0349L73.3666 84.7974C74.2015 85.3874 75.3249 84.5936 75.006 83.6391L72.4314 75.9331C72.2888 75.5062 72.445 75.0385 72.8184 74.7747L79.5588 70.0121C80.3937 69.4222 79.9646 68.1378 78.9326 68.1378H70.601C70.1395 68.1378 69.7304 67.8488 69.5878 67.4219L67.0132 59.7159Z" fill="#D1D1D1"/>
									<rect x="89" y="60" width="36" height="4" rx="2" fill="#BDBDBD"/>
									<rect x="89" y="67" width="36" height="4" rx="2" fill="#BDBDBD"/>
									<rect x="89" y="74" width="29" height="4" rx="2" fill="#BDBDBD"/>
									<rect x="89" y="81" width="23" height="4" rx="2" fill="#BDBDBD"/>
								</g>
								<path opacity="0.3" fill-rule="evenodd" clip-rule="evenodd" d="M19.9662 140.107H6.35804C6.2416 140.107 6.12616 140.102 6.01188 140.094C2.9364 140.022 0.464999 137.437 0.464844 134.259C0.465738 132.711 1.06528 131.226 2.13157 130.131C2.67814 129.57 3.3244 129.135 4.02751 128.845C4.01575 128.687 4.00976 128.528 4.00976 128.367C4.01072 126.733 4.64335 125.166 5.76846 124.011C6.89357 122.856 8.41901 122.208 10.0092 122.209C12.0462 122.212 13.8446 123.258 14.9254 124.856C15.4386 124.669 15.9911 124.568 16.5667 124.568C19.1095 124.571 21.1979 126.563 21.44 129.111C23.8763 129.656 25.6996 131.887 25.6974 134.554C25.6949 137.627 23.2692 140.117 20.2786 140.116C20.1737 140.116 20.0696 140.113 19.9662 140.107Z" fill="#C7C7C7"/>
								<path opacity="0.3" fill-rule="evenodd" clip-rule="evenodd" d="M173.84 31.8544H164.182C164.1 31.8544 164.018 31.8514 163.937 31.8454C161.754 31.7964 160 30.0342 160 27.8676C160.001 26.8116 160.426 25.7991 161.183 25.0528C161.571 24.6703 162.029 24.3738 162.528 24.1757C162.52 24.0682 162.516 23.9596 162.516 23.8501C162.516 22.7358 162.965 21.6675 163.764 20.8801C164.562 20.0927 165.645 19.6507 166.773 19.6514C168.219 19.6531 169.495 20.3669 170.262 21.4563C170.627 21.3288 171.019 21.2596 171.427 21.2598C173.232 21.2619 174.714 22.6201 174.886 24.357C176.615 24.7292 177.909 26.25 177.907 28.0684C177.905 30.1639 176.184 31.8615 174.061 31.8607C173.987 31.8606 173.913 31.8585 173.84 31.8544Z" fill="#C7C7C7"/>
							</svg>
						</div>
						<div class="market-reviews__empty-title">
							{{ $Bitrix.Loc.getMessage('MARKET_DONT_HAVE_REVIEWS_FILTER') }}
						</div>
						<div class="market-reviews__empty-description">
							{{ $Bitrix.Loc.getMessage('MARKET_NO_REVIEW_APPS_FILTER') }}
						</div>
					</div>
					<div class="market-reviews__section"
						 v-else
					>
						<div class="market-reviews__item"
							 v-for="review in result.REVIEWS.ITEMS"
						>
							<div class="market-reviews__item-label"
								 :class="{'--checked': review.BLOCKED !== 'Y' && review.PUBLISHED === 'Y'}"
							>
								<template v-if="review.BLOCKED !== 'Y'">
									<template v-if="review.PUBLISHED === 'N'">
										<svg class="market-reviews__item-label_icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M7.3281 5.32531H8.66143V7.32531H10.6614V8.65864H7.3281V5.32531Z" fill="#FFA900"/>
											<path fill-rule="evenodd" clip-rule="evenodd" d="M3.15497 10.1787C4.04408 12.1558 6.04538 13.3944 8.21152 13.308C11.0853 13.2492 13.3681 10.8734 13.3122 7.99963C13.312 5.83177 11.9946 3.88148 9.98361 3.07196C7.97256 2.26243 5.67138 2.75611 4.16936 4.3193C2.66734 5.88249 2.26586 8.20154 3.15497 10.1787ZM4.39167 9.62258C5.05385 11.0951 6.54435 12.0175 8.15762 11.9532C10.2979 11.9094 11.998 10.14 11.9564 7.99969C11.9563 6.38514 10.9752 4.93263 9.47741 4.32972C7.97965 3.72681 6.26581 4.09449 5.14715 5.2587C4.02849 6.42291 3.72949 8.15006 4.39167 9.62258Z" fill="#FFA900"/>
										</svg>
										<span class="market-reviews__item-label_">
											{{ $Bitrix.Loc.getMessage('MARKET_REVIEW_SENT_TO_DEVELOPER') }}
										</span>
									</template>
									<template v-if="review.PUBLISHED === 'Y'">
										<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path fill-rule="evenodd" clip-rule="evenodd" d="M6.64089 12.0003L3.33203 8.62202L4.49013 7.43961L6.64089 9.63551L11.5073 4.66699L12.6654 5.8494L6.64089 12.0003Z" fill="#7FA800"/>
										</svg>
										<span class="market-reviews__item-label_">
											{{ $Bitrix.Loc.getMessage('MARKET_REVIEW_PUBLISHED') }}
										</span>
									</template>
								</template>
								<template v-if="review.BLOCKED === 'Y'" >
									<svg class="market-detail__feedback-item_addition--icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M7.22356 5.1732H8.78796L8.60745 8.55271H7.40407L7.22356 5.1732Z" fill="#FF5752"/>
										<path d="M8.00561 10.9167C8.53657 10.9167 8.96699 10.4863 8.96699 9.95535C8.96699 9.42439 8.53657 8.99396 8.00561 8.99396C7.47465 8.99396 7.04422 9.42439 7.04422 9.95535C7.04422 10.4863 7.47465 10.9167 8.00561 10.9167Z" fill="#FF5752"/>
										<path fill-rule="evenodd" clip-rule="evenodd" d="M8.00052 13.1334C10.8356 13.1334 13.1339 10.8351 13.1339 8.00003C13.1339 5.16497 10.8356 2.8667 8.00052 2.8667C5.16546 2.8667 2.86719 5.16497 2.86719 8.00003C2.86719 10.8351 5.16546 13.1334 8.00052 13.1334ZM8.00052 11.9139C10.1621 11.9139 11.9143 10.1616 11.9143 8.00004C11.9143 5.83849 10.1621 4.08621 8.00052 4.08621C5.83897 4.08621 4.08669 5.83849 4.08669 8.00004C4.08669 10.1616 5.83897 11.9139 8.00052 11.9139Z" fill="#FF5752"/>
									</svg>
									<span class="market-reviews__item-label_">
										{{ $Bitrix.Loc.getMessage('MARKET_REVIEW_NO_PUBLISHED') }}
									</span>
								</template>
							</div>
							<div class="market-reviews__item-content">
								<a class="market-reviews__item-logo"
								   :href="getDetailUri(review.APP_CODE)"
								>
									<img class="market-reviews__item-logo-img"
										 :src="review.APP_LOGO"
										 alt="img"
									>
								</a>
								<div class="market-reviews__item-main-content">
									<a class="market-reviews__item-title"
									   :href="getDetailUri(review.APP_CODE)"
									>{{ review.APP_NAME }}</a>
									<div class="market-reviews__item-description">
										{{ review.REVIEW_TEXT_FULL }}
									</div>
									<div class="market-reviews__item-answer"
										 v-if="review.REVIEW_ANSWER_TEXT_FULL"
									>
										<div class="market-reviews__item-answer-content">
											<div class="market-reviews__item-answer-content_date">
												{{ $Bitrix.Loc.getMessage('MARKET_REVIEW_ANSWER') }}
												<a class="market-reviews__item-answer-content_team --link"
												   v-if="review.PARTNER_URL"
												   :href="review.PARTNER_URL"
												   target="_blank"
												>
													{{ review.PARTNER_NAME }}
												</a>
												<span class="market-reviews__item-answer-content_team"
													  v-else
												>
													{{ review.PARTNER_NAME }}
												</span>
												{{ review.REVIEW_ANSWER_DATE }}
											</div>
											<div class="market-reviews__item-answer-content_text">
												{{ review.REVIEW_ANSWER_TEXT_FULL }}
											</div>
										</div>
									</div>
								</div>
								<div class="market-reviews__item-rating">
									<div class="market-detail__feedback-item_stars-container --popup-feedback">
										<svg class="market-rating__app-rating_star"
											 :class="{'--active': isActiveStar(1, getAppRating(review.RATING))}"
											 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
										</svg>
										<svg class="market-rating__app-rating_star"
											 :class="{'--active': isActiveStar(2, getAppRating(review.RATING))}"
											 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
										</svg>
										<svg class="market-rating__app-rating_star"
											 :class="{'--active': isActiveStar(3, getAppRating(review.RATING))}"
											 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
										</svg>
										<svg class="market-rating__app-rating_star "
											 :class="{'--active': isActiveStar(4, getAppRating(review.RATING))}"
											 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
										</svg>
										<svg class="market-rating__app-rating_star"
											 :class="{'--active': isActiveStar(5, getAppRating(review.RATING))}"
											 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
										</svg>
									</div>
									<div class="market-reviews__item-date">
										{{ review.DATE_CREATE }}
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="market-reviews__bottom-loader"
						 ref="marketReviewsBottomLoader"
						 v-show="showNextPageButton"
					></div>
					<div ref="marketReviewsLoader"></div>
				</div>
			</div>
		</div>
	`,
}