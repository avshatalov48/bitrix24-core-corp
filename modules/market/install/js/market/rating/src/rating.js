import {nextTick} from "ui.vue3";
import { Ears } from 'ui.ears'
import { RatingItem } from "./rating-item";
import { PopupWrapper } from './popup-wrapper';
import 'ui.design-tokens';

import "./rating.css"

export const Rating = {
	components: {
		RatingItem, PopupWrapper
	},
	props: [
		'appInfo', 'isSite', 'showNoAccessInstallButton',
	],
	data() {
		return {
			application: {},
			needInstallBeforeAdd: true,
			ratingClickState: false, // клик по звёздам, когда не установлено
			addingReview: false, // клик по кнопке "добавить отзыв"
			policyChecked: false,
			rulesChecked: false,
			feedbackBlock: null, // блок с отзывами, чтобы прикрутить уши для прокрутки
			reviewText: '', // текст отзыва для добавления
			currentRating: 0,
			starsError: false,
			sendingReview: false,
		};
	},
	beforeMount() {
		this.application = this.appInfo;
		this.needInstallBeforeAdd = this.isSite !== true;
	},
	computed: {
		canReview: function () {
			return this.application.REVIEWS.CAN_REVIEW === 'Y';
		},
		showPolicy: function () {
			return this.application.REVIEWS.SHOW_POLICY_CHECKBOX === 'Y';
		},
		showRules: function () {
			return this.application.REVIEWS.SHOW_RULES_CHECKBOX === 'Y';
		},
		allChecked: function() {
			return this.policyChecked && this.rulesChecked;
		},
		appWasInstalled: function () {
			return this.application.WAS_INSTALLED && this.application.WAS_INSTALLED === 'Y';
		},
		showInstallState: function () {
			return this.needInstallBeforeAdd && !this.appWasInstalled && (!this.countReviews || this.addingReview)
		},
		canRatingClick: function () {
			return (this.canReview && !this.sendingReview);
		},
		countReviews: function () {
			return parseInt(this.application.REVIEWS.RATING.COUNT, 10);
		},
		totalRating: function () {
			if (this.application.REVIEWS.RATING && this.application.REVIEWS.RATING.RATING) {
				return this.application.REVIEWS.RATING.RATING
			}

			return 0;
		},
	},
	mounted() {
		this.feedbackBlock = this.$refs.marketFeedback;
		if (this.feedbackBlock) {
			(new Ears({
				container: this.feedbackBlock,
				smallSize: true,
				noScrollbar: true,
			})).init();
		}

		if (!this.canReview) {
			this.currentRating = this.application.REVIEWS.USER_RATING;
		}

		if (!this.showPolicy) {
			this.policyChecked = true;
		}
		if (!this.showRules) {
			this.rulesChecked = true;
		}

		if (false) {
			this.scrollToUserReview();
		}
	},
	methods: {
		scrollToUserReview: function () {
			window.scrollTo({
				top: this.feedbackBlock.getBoundingClientRect().top,
				behavior: 'smooth',
			});
		},
		ratingClick: function () {
			this.ratingClickState = true;
			setTimeout(() => this.ratingClickState = false, 2000);
		},
		isActiveStar: function (currentStar, rating) {
			return currentStar <= parseInt(rating, 10);
		},
		currentRatingClick: function (rating) {
			if (!this.canRatingClick) {
				return;
			}

			this.currentRating = (rating === this.currentRating) ? 0 : rating;
		},
		addingReviewClick: function () {
			this.addingReview = true;

			nextTick(() => {
				if (this.$refs.marketFeedbackText) {
					this.$refs.marketFeedbackText.focus();
				}
			});
		},
		cancelAddingReviewClick: function () {
			this.addingReview = false;
			this.currentRating = 0;
		},
		backToReviews: function () {
			if (!this.appWasInstalled && this.addingReview) {
				this.addingReview = false;
			}
		},
		addReview: function () {
			if (!this.allChecked) {
				return;
			}

			if (this.currentRating <= 0) {
				this.starsError = true;
				setTimeout(() => this.starsError = false, 200);
				this.showNotify(this.$Bitrix.Loc.getMessage('MARKET_RATING_JS_ADD_REVIEW_STARS_ERROR'));
				return;
			}

			this.sendingReview = true;
			const isSiteTemplate = this.isSite === true ? 'Y' : 'N';

			BX.ajax.runAction('market.Application.addReview', {
				data: {
					appCode: this.application.REVIEW_APP_CODE,
					reviewText: this.reviewText,
					currentRating: this.currentRating,
					isSite: isSiteTemplate,
				},
				analyticsLabel: {
					appCode: this.application.REVIEW_APP_CODE,
					currentRating: this.currentRating,
					isSite: isSiteTemplate,
				},
			}).then(
				response => {
					this.sendingReview = false;

					if (response.data && response.data.success === 'Y') {
						this.successReviewHandler(response.data);
					} else if (response.data && response.data.error) {
						const errors = response.data.error.slice(0);
						const firstError = errors.shift();
						if (firstError === 'NOT_FOUND_TEXT') {
							this.showNotify(this.$Bitrix.Loc.getMessage('MARKET_RATING_JS_ADD_REVIEW_TEXT_ERROR'));
							return;
						}

						this.showNotify(this.$Bitrix.Loc.getMessage('MARKET_RATING_JS_ADD_REVIEW_ERROR') + ' (' + response.data.error + ')');
					} else {
						this.showNotify(this.$Bitrix.Loc.getMessage('MARKET_RATING_JS_ADD_REVIEW_ERROR'));
					}
				},
				response => {
					this.sendingReview = false;
					this.showNotify(this.$Bitrix.Loc.getMessage('MARKET_RATING_JS_ADD_REVIEW_ERROR'));
				},
			);
		},
		showNotify: function (text) {
			BX.UI.Notification.Center.notify({
				content: text,
				position: BX.UI.Notification.Position.BOTTOM_LEFT,
			});
		},
		getCountStars: function (star) {
			if (!this.application.REVIEWS.RATING || !this.application.REVIEWS.RATING.RATING_DETAIL) {
				return 0;
			}

			if (this.application.REVIEWS.RATING.RATING_DETAIL[star]) {
				return this.application.REVIEWS.RATING.RATING_DETAIL[star];
			}

			return 0;
		},
		getStarWidth: function (star) {
			if (this.getCountStars(star) === 0 || !this.countReviews) {
				return '0%';
			}

			return (this.getCountStars(star) / this.countReviews) * 100 + '%';
		},
		successReviewHandler: function (data) {
			this.showNotify(this.$Bitrix.Loc.getMessage('MARKET_RATING_JS_ADD_REVIEW_SUCCESS'));

			if (data && data.hasOwnProperty('can_review')) {
				this.application.REVIEWS.CAN_REVIEW = data.can_review
			}

			if (data && data.hasOwnProperty('review_info') && BX.Type.isArray(this.application.REVIEWS.ITEMS)) {
				this.application.REVIEWS.ITEMS.unshift(data.review_info);
			}

			if (data && data.hasOwnProperty('rating') && data.rating.hasOwnProperty('RATING_DETAIL')) {
				this.application.REVIEWS.RATING = data.rating;
			}

			this.addingReview = false;
		},
	},
	template: `
		<div class="market-detail__app-rating-info">
			<div class="market-detail__app-rating-info_container">
				<div class="market-detail__app-rating-info_stars-block">
					<div class="market-rating__app-rating-info_stars">

						<div class="market-rating__app-rating-info_stars-name">
							{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_RATING_TITLE') }}
						</div>

						<div class="market-rating__app-rating-info_stars-container">
							<svg class="market-rating__app-rating_star"
								 :class="{'--active': isActiveStar(1, totalRating)}"
								 @click="ratingClick"
								 width="18" height="18" viewBox="0 0 18 18"
								 fill="none" xmlns="http://www.w3.org/2000/svg"
							>
								<path
									d="M8.53505 1.17539C8.70176 0.753948 9.29824 0.753947 9.46495 1.17539L11.389 6.03945C11.4589 6.21604 11.6227 6.33781 11.812 6.35377L16.8494 6.7784C17.2857 6.81517 17.4673 7.35515 17.142 7.64815L13.2679 11.1376C13.1333 11.2587 13.0748 11.4432 13.1149 11.6197L14.292 16.8084C14.391 17.2448 13.9107 17.5815 13.5342 17.3397L9.27019 14.6013C9.10558 14.4955 8.89442 14.4955 8.72981 14.6013L4.46583 17.3397C4.08928 17.5815 3.60902 17.2448 3.70803 16.8084L4.88514 11.6197C4.92519 11.4432 4.86667 11.2587 4.73215 11.1376L0.857968 7.64815C0.532662 7.35515 0.714337 6.81517 1.15059 6.7784L6.18805 6.35377C6.37728 6.33781 6.54114 6.21604 6.61099 6.03945L8.53505 1.17539Z"/>
							</svg>

							<svg class="market-rating__app-rating_star "
								 :class="{'--active': isActiveStar(2, totalRating)}"
								 @click="ratingClick"
								 width="18" height="18" viewBox="0 0 18 18"
								 fill="none" xmlns="http://www.w3.org/2000/svg"
							>
								<path
									d="M8.53505 1.17539C8.70176 0.753948 9.29824 0.753947 9.46495 1.17539L11.389 6.03945C11.4589 6.21604 11.6227 6.33781 11.812 6.35377L16.8494 6.7784C17.2857 6.81517 17.4673 7.35515 17.142 7.64815L13.2679 11.1376C13.1333 11.2587 13.0748 11.4432 13.1149 11.6197L14.292 16.8084C14.391 17.2448 13.9107 17.5815 13.5342 17.3397L9.27019 14.6013C9.10558 14.4955 8.89442 14.4955 8.72981 14.6013L4.46583 17.3397C4.08928 17.5815 3.60902 17.2448 3.70803 16.8084L4.88514 11.6197C4.92519 11.4432 4.86667 11.2587 4.73215 11.1376L0.857968 7.64815C0.532662 7.35515 0.714337 6.81517 1.15059 6.7784L6.18805 6.35377C6.37728 6.33781 6.54114 6.21604 6.61099 6.03945L8.53505 1.17539Z"/>
							</svg>

							<svg class="market-rating__app-rating_star"
								 :class="{'--active': isActiveStar(3, totalRating)}"
								 @click="ratingClick"
								 width="18" height="18" viewBox="0 0 18 18"
								 fill="none" xmlns="http://www.w3.org/2000/svg"
							>
								<path
									d="M8.53505 1.17539C8.70176 0.753948 9.29824 0.753947 9.46495 1.17539L11.389 6.03945C11.4589 6.21604 11.6227 6.33781 11.812 6.35377L16.8494 6.7784C17.2857 6.81517 17.4673 7.35515 17.142 7.64815L13.2679 11.1376C13.1333 11.2587 13.0748 11.4432 13.1149 11.6197L14.292 16.8084C14.391 17.2448 13.9107 17.5815 13.5342 17.3397L9.27019 14.6013C9.10558 14.4955 8.89442 14.4955 8.72981 14.6013L4.46583 17.3397C4.08928 17.5815 3.60902 17.2448 3.70803 16.8084L4.88514 11.6197C4.92519 11.4432 4.86667 11.2587 4.73215 11.1376L0.857968 7.64815C0.532662 7.35515 0.714337 6.81517 1.15059 6.7784L6.18805 6.35377C6.37728 6.33781 6.54114 6.21604 6.61099 6.03945L8.53505 1.17539Z"/>
							</svg>

							<svg class="market-rating__app-rating_star"
								 :class="{'--active': isActiveStar(4, totalRating)}"
								 @click="ratingClick"
								 width="18" height="18" viewBox="0 0 18 18"
								 fill="none" xmlns="http://www.w3.org/2000/svg"
							>
								<path
									d="M8.53505 1.17539C8.70176 0.753948 9.29824 0.753947 9.46495 1.17539L11.389 6.03945C11.4589 6.21604 11.6227 6.33781 11.812 6.35377L16.8494 6.7784C17.2857 6.81517 17.4673 7.35515 17.142 7.64815L13.2679 11.1376C13.1333 11.2587 13.0748 11.4432 13.1149 11.6197L14.292 16.8084C14.391 17.2448 13.9107 17.5815 13.5342 17.3397L9.27019 14.6013C9.10558 14.4955 8.89442 14.4955 8.72981 14.6013L4.46583 17.3397C4.08928 17.5815 3.60902 17.2448 3.70803 16.8084L4.88514 11.6197C4.92519 11.4432 4.86667 11.2587 4.73215 11.1376L0.857968 7.64815C0.532662 7.35515 0.714337 6.81517 1.15059 6.7784L6.18805 6.35377C6.37728 6.33781 6.54114 6.21604 6.61099 6.03945L8.53505 1.17539Z"/>
							</svg>

							<svg class="market-rating__app-rating_star"
								 :class="{'--active': isActiveStar(5, totalRating)}"
								 @click="ratingClick"
								 width="18" height="18" viewBox="0 0 18 18"
								 fill="none" xmlns="http://www.w3.org/2000/svg"
							>
								<path
									d="M8.53505 1.17539C8.70176 0.753948 9.29824 0.753947 9.46495 1.17539L11.389 6.03945C11.4589 6.21604 11.6227 6.33781 11.812 6.35377L16.8494 6.7784C17.2857 6.81517 17.4673 7.35515 17.142 7.64815L13.2679 11.1376C13.1333 11.2587 13.0748 11.4432 13.1149 11.6197L14.292 16.8084C14.391 17.2448 13.9107 17.5815 13.5342 17.3397L9.27019 14.6013C9.10558 14.4955 8.89442 14.4955 8.72981 14.6013L4.46583 17.3397C4.08928 17.5815 3.60902 17.2448 3.70803 16.8084L4.88514 11.6197C4.92519 11.4432 4.86667 11.2587 4.73215 11.1376L0.857968 7.64815C0.532662 7.35515 0.714337 6.81517 1.15059 6.7784L6.18805 6.35377C6.37728 6.33781 6.54114 6.21604 6.61099 6.03945L8.53505 1.17539Z"/>
							</svg>
						</div>

						<div class="market-rating__app-rating-info_stars-number">
							{{ totalRating }}
							<span class="market-rating__app-rating-info_stars-all-number">/5</span>
						</div>
					</div>

					<div class="market-rating__app-rating-info_scale">
						<div class="market-rating__app-rating-info_scale-item">
							<div class="market-rating__app-rating-info_scale-item-name">
								{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_FIVE_STARS') }}
							</div>
							<div class="market-rating__app-rating-info_scale-item-line-container">
								<div class="market-rating__app-rating-info_scale-item-line-active"
									 :style="{'width': getStarWidth(5)}"
								></div>
							</div>
							<div class="market-rating__app-rating-info_scale-item-number">{{ getCountStars(5) }}</div>
						</div>
						<div class="market-rating__app-rating-info_scale-item">
							<div class="market-rating__app-rating-info_scale-item-name">
								{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_FOUR_STARS') }}
							</div>
							<div class="market-rating__app-rating-info_scale-item-line-container">
								<div class="market-rating__app-rating-info_scale-item-line-active"
									 :style="{'width': getStarWidth(4)}"
								></div>
							</div>
							<div class="market-rating__app-rating-info_scale-item-number">{{ getCountStars(4) }}</div>
						</div>
						<div class="market-rating__app-rating-info_scale-item">
							<div class="market-rating__app-rating-info_scale-item-name">
								{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_THREE_STARS') }}
							</div>
							<div class="market-rating__app-rating-info_scale-item-line-container">
								<div class="market-rating__app-rating-info_scale-item-line-active"
									 :style="{'width': getStarWidth(3)}"
								></div>
							</div>
							<div class="market-rating__app-rating-info_scale-item-number">{{ getCountStars(3) }}</div>
						</div>
						<div class="market-rating__app-rating-info_scale-item">
							<div class="market-rating__app-rating-info_scale-item-name">
								{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_TWO_STARS') }}
							</div>
							<div class="market-rating__app-rating-info_scale-item-line-container">
								<div class="market-rating__app-rating-info_scale-item-line-active"
									 :style="{'width': getStarWidth(2)}"
								></div>
							</div>
							<div class="market-rating__app-rating-info_scale-item-number">{{ getCountStars(2) }}</div>
						</div>
						<div class="market-rating__app-rating-info_scale-item">
							<div class="market-rating__app-rating-info_scale-item-name">
								{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_ONE_STAR') }}
							</div>
							<div class="market-rating__app-rating-info_scale-item-line-container">
								<div class="market-rating__app-rating-info_scale-item-line-active"
									 :style="{'width': getStarWidth(1)}"
								></div>
							</div>
							<div class="market-rating__app-rating-info_scale-item-number">{{ getCountStars(1) }}</div>
						</div>
					</div>

				</div>
				<div class="market-detail__app-rating_feedback-block">
					<div class="market-detail__app-rating_feedback-header">
						<div class="market-detail__app-rating_feedback-title">
							{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_REVIEWS_TITLE') }}
						</div>
	
						<div class="market-detail__app-rating_feedback-info"
							 :class="{'market-detail__app-rating_feedback-info__back' : !appWasInstalled && addingReview}"
							 v-if="countReviews"
							 @click="backToReviews"
						>{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_REVIEWS_TOTAL', {'#NUMBER#': countReviews}) }}
						</div>
					</div>
					<div class="market-detail__app-rating_feedback-content">
						<div class="market-detail__app-rating_feedback-empty"
							 :class="{'--animation': ratingClickState === true}"
							 v-show="showInstallState"
						>
							<div class="market-detail__app-rating_feedback-empty-icon">
								<svg width="79" height="78" viewBox="0 0 79 78" fill="none"
									 xmlns="http://www.w3.org/2000/svg">
									<path fill-rule="evenodd" clip-rule="evenodd"
										  d="M21.3519 39C20.0466 39 18.868 39.7809 18.3591 40.9828L13.5 52.4588H65.5L60.6409 40.9828C60.132 39.7809 58.9534 39 57.6481 39H56.092L59.3331 47.502C59.7385 48.5657 58.953 49.7059 57.8146 49.7059H20.9232C19.7679 49.7059 18.9816 48.5343 19.4194 47.4652L22.8852 39H21.3519ZM65.5 52.4588H13.5V61.7502C13.5 63.5451 14.9551 65.0002 16.75 65.0002H62.25C64.0449 65.0002 65.5 63.5451 65.5 61.7502V52.4588ZM55.75 61.3298C57.5449 61.3298 59 59.9016 59 58.1399C59 56.3781 57.5449 54.95 55.75 54.95C53.9551 54.95 52.5 56.3781 52.5 58.1399C52.5 59.9016 53.9551 61.3298 55.75 61.3298Z"
										  fill="#D5D7DB"/>
									<path
										d="M41 16.25C41 15.4216 40.3284 14.75 39.5 14.75C38.6716 14.75 38 15.4216 38 16.25L41 16.25ZM38.4393 43.3107C39.0251 43.8964 39.9749 43.8964 40.5607 43.3107L50.1066 33.7647C50.6924 33.1789 50.6924 32.2292 50.1066 31.6434C49.5208 31.0576 48.5711 31.0576 47.9853 31.6434L39.5 40.1287L31.0147 31.6434C30.4289 31.0576 29.4792 31.0576 28.8934 31.6434C28.3076 32.2292 28.3076 33.1789 28.8934 33.7647L38.4393 43.3107ZM38 16.25L38 42.25L41 42.25L41 16.25L38 16.25Z"
										fill="#D5D7DB"/>
								</svg>
							</div>
							<div class="market-detail__app-rating_feedback-empty-description"
								 v-html="$Bitrix.Loc.getMessage('MARKET_RATING_JS_INSTALL_TEXT')"
							></div>
							<button class="ui-btn ui-btn-xs ui-btn-success market-detail__app-rating_install-btn"
									:class="{'ui-btn-disabled': showNoAccessInstallButton}"
									@click="$emit('installApp')"
							>
								{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_INSTALL') }}
							</button>
						</div>
						<div class="market-detail__app-rating_feedback-wrapper"
							 v-show="!showInstallState"
						>
							<div class="market-detail__app-rating_feedback-ready"
								 ref="marketFeedback"
							>
								<div class="market-detail__feedback-item market-detail__app-rating_add-feedback-btn"
									 v-if="canReview"
									 @click="addingReviewClick"
								>
										<span class="market-detail__app-rating_add-feedback-btn-icon">
											<svg width="25" height="24" viewBox="0 0 25 24" fill="none"
												 xmlns="http://www.w3.org/2000/svg">
												<path fill-rule="evenodd" clip-rule="evenodd"
													  d="M13.5 6H11.5V11H6.5V13H11.5V18H13.5V13H18.5V11H13.5V6Z"
													  fill="#559BE6"/>
											</svg>
										</span>
									<span class="market-detail__app-rating_add-feedback-btn-text"
										  v-html="$Bitrix.Loc.getMessage('MARKET_RATING_JS_ADD_REVIEW')"
									></span>
								</div>

								<RatingItem
									v-for="review in application.REVIEWS.ITEMS"
									:review="review"
								/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<PopupWrapper 
				v-if="addingReview && !showInstallState"
				@close="cancelAddingReviewClick"
			>
				<div class="market-detail__app-rating_feedback-form">
					<div class="market-detail__app-rating_feedback-img" v-if="isSite !== true">
						<img :src="application.ICON" alt="icon">
					</div>
					<div class="market-detail__app-rating_feedback-subtitle"
						 v-html="$Bitrix.Loc.getMessage('MARKET_RATING_JS_ADD_REVIEW_TITLE', {'#APP_NAME#' : application.NAME})"
					></div>
					<p class="market-detail__app-rating_feedback-text">{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_ADD_REVIEW_FEEDBACK_TEXT') }}</p>
					<div class="market-rating__app-rating-info_stars-container">
						<svg class="market-rating__app-rating_star"
							 :class="{'--active': isActiveStar(1, currentRating), '--pointer': canRatingClick}"
							 @click="currentRatingClick(1)"
							 width="33" height="33" viewBox="0 0 33 33" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path xmlns="http://www.w3.org/2000/svg" d="M15.8607 5.74116C16.0899 5.16168 16.9101 5.16168 17.1393 5.74116L19.7849 12.4292C19.8809 12.6721 20.1062 12.8395 20.3664 12.8614L27.2929 13.4453C27.8928 13.4959 28.1426 14.2383 27.6953 14.6412L22.3683 19.4392C22.1833 19.6058 22.1029 19.8594 22.1579 20.1021L23.7765 27.2365C23.9126 27.8366 23.2522 28.2996 22.7345 27.9671L16.8715 24.2017C16.6452 24.0564 16.3548 24.0564 16.1285 24.2017L10.2655 27.9671C9.74776 28.2996 9.0874 27.8366 9.22354 27.2365L10.8421 20.1021C10.8971 19.8594 10.8167 19.6058 10.6317 19.4392L5.30471 14.6412C4.85741 14.2383 5.10721 13.4959 5.70707 13.4453L12.6336 12.8614C12.8938 12.8395 13.1191 12.6721 13.2151 12.4292L15.8607 5.74116Z"/>
						</svg>
						<svg class="market-rating__app-rating_star"
							 :class="{'--active': isActiveStar(2, currentRating), '--pointer': canRatingClick}"
							 @click="currentRatingClick(2)"
							 width="33" height="33" viewBox="0 0 33 33" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path xmlns="http://www.w3.org/2000/svg" d="M15.8607 5.74116C16.0899 5.16168 16.9101 5.16168 17.1393 5.74116L19.7849 12.4292C19.8809 12.6721 20.1062 12.8395 20.3664 12.8614L27.2929 13.4453C27.8928 13.4959 28.1426 14.2383 27.6953 14.6412L22.3683 19.4392C22.1833 19.6058 22.1029 19.8594 22.1579 20.1021L23.7765 27.2365C23.9126 27.8366 23.2522 28.2996 22.7345 27.9671L16.8715 24.2017C16.6452 24.0564 16.3548 24.0564 16.1285 24.2017L10.2655 27.9671C9.74776 28.2996 9.0874 27.8366 9.22354 27.2365L10.8421 20.1021C10.8971 19.8594 10.8167 19.6058 10.6317 19.4392L5.30471 14.6412C4.85741 14.2383 5.10721 13.4959 5.70707 13.4453L12.6336 12.8614C12.8938 12.8395 13.1191 12.6721 13.2151 12.4292L15.8607 5.74116Z"/>
						</svg>
						<svg class="market-rating__app-rating_star"
							 :class="{'--active': isActiveStar(3, currentRating), '--pointer': canRatingClick}"
							 @click="currentRatingClick(3)"
							 width="33" height="33" viewBox="0 0 33 33" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path xmlns="http://www.w3.org/2000/svg" d="M15.8607 5.74116C16.0899 5.16168 16.9101 5.16168 17.1393 5.74116L19.7849 12.4292C19.8809 12.6721 20.1062 12.8395 20.3664 12.8614L27.2929 13.4453C27.8928 13.4959 28.1426 14.2383 27.6953 14.6412L22.3683 19.4392C22.1833 19.6058 22.1029 19.8594 22.1579 20.1021L23.7765 27.2365C23.9126 27.8366 23.2522 28.2996 22.7345 27.9671L16.8715 24.2017C16.6452 24.0564 16.3548 24.0564 16.1285 24.2017L10.2655 27.9671C9.74776 28.2996 9.0874 27.8366 9.22354 27.2365L10.8421 20.1021C10.8971 19.8594 10.8167 19.6058 10.6317 19.4392L5.30471 14.6412C4.85741 14.2383 5.10721 13.4959 5.70707 13.4453L12.6336 12.8614C12.8938 12.8395 13.1191 12.6721 13.2151 12.4292L15.8607 5.74116Z"/>
						</svg>
						<svg class="market-rating__app-rating_star"
							 :class="{'--active': isActiveStar(4, currentRating), '--pointer': canRatingClick}"
							 @click="currentRatingClick(4)"
							 width="33" height="33" viewBox="0 0 33 33" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path xmlns="http://www.w3.org/2000/svg" d="M15.8607 5.74116C16.0899 5.16168 16.9101 5.16168 17.1393 5.74116L19.7849 12.4292C19.8809 12.6721 20.1062 12.8395 20.3664 12.8614L27.2929 13.4453C27.8928 13.4959 28.1426 14.2383 27.6953 14.6412L22.3683 19.4392C22.1833 19.6058 22.1029 19.8594 22.1579 20.1021L23.7765 27.2365C23.9126 27.8366 23.2522 28.2996 22.7345 27.9671L16.8715 24.2017C16.6452 24.0564 16.3548 24.0564 16.1285 24.2017L10.2655 27.9671C9.74776 28.2996 9.0874 27.8366 9.22354 27.2365L10.8421 20.1021C10.8971 19.8594 10.8167 19.6058 10.6317 19.4392L5.30471 14.6412C4.85741 14.2383 5.10721 13.4959 5.70707 13.4453L12.6336 12.8614C12.8938 12.8395 13.1191 12.6721 13.2151 12.4292L15.8607 5.74116Z"/>
						</svg>
						<svg class="market-rating__app-rating_star"
							 :class="{'--active': isActiveStar(5, currentRating), '--pointer': canRatingClick}"
							 @click="currentRatingClick(5)"
							 width="33" height="33" viewBox="0 0 33 33" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path xmlns="http://www.w3.org/2000/svg" d="M15.8607 5.74116C16.0899 5.16168 16.9101 5.16168 17.1393 5.74116L19.7849 12.4292C19.8809 12.6721 20.1062 12.8395 20.3664 12.8614L27.2929 13.4453C27.8928 13.4959 28.1426 14.2383 27.6953 14.6412L22.3683 19.4392C22.1833 19.6058 22.1029 19.8594 22.1579 20.1021L23.7765 27.2365C23.9126 27.8366 23.2522 28.2996 22.7345 27.9671L16.8715 24.2017C16.6452 24.0564 16.3548 24.0564 16.1285 24.2017L10.2655 27.9671C9.74776 28.2996 9.0874 27.8366 9.22354 27.2365L10.8421 20.1021C10.8971 19.8594 10.8167 19.6058 10.6317 19.4392L5.30471 14.6412C4.85741 14.2383 5.10721 13.4959 5.70707 13.4453L12.6336 12.8614C12.8938 12.8395 13.1191 12.6721 13.2151 12.4292L15.8607 5.74116Z"/>
						</svg>
					</div>
					<div class="ui-ctl ui-ctl-textarea ui-ctl-no-resize ui-ctl-w100 ui-ctl-lg">
							<textarea class="ui-ctl-element market-detail__app-rating_feedback-textarea"
									  ref="marketFeedbackText"
									  :disabled="sendingReview"
									  v-model="reviewText"
							></textarea>
					</div>
					<div class="market-detail__app-rating_feedback-checkbox-wrapper">
						<label
							class="ui-ctl ui-ctl-checkbox ui-ctl-wa market-detail__app-rating_feedback-checkbox"
							v-if="showPolicy"
						>
							<input type="checkbox" class="ui-ctl-element"
								   v-model="policyChecked"
							>
							<span class="ui-ctl-label-text market-detail__app-rating_feedback-label"
								  v-html="$Bitrix.Loc.getMessage('MARKET_RATING_JS_ADD_REVIEW_POLICY', {'#POLICY_URL#': application.REVIEWS.POLICY_URL})"
							>
								</span>
						</label>
						<label
							class="ui-ctl ui-ctl-checkbox ui-ctl-wa market-detail__app-rating_feedback-checkbox"
							v-if="showRules"
						>
							<input type="checkbox" class="ui-ctl-element"
								   v-model="rulesChecked"
							>
							<span class="ui-ctl-label-text market-detail__app-rating_feedback-label"
								  v-html="$Bitrix.Loc.getMessage('MARKET_RATING_JS_ADD_REVIEW_POSTING_GUIDELINES', {'#RULES_URL#': application.REVIEWS.POSTING_GUIDELINES_URL})"
							>
								</span>
						</label>
					</div>
					<div class="market-detail__app-rating_feedback-buttons">
						<button class="ui-btn ui-btn-sm"
								:class="{
											'ui-btn-wait': sendingReview, 
											'ui-btn-primary': allChecked,
											'ui-btn-default': !allChecked,
											'ui-btn-disabled': !allChecked,
										}"
								:disabled="sendingReview || !allChecked"
								@click="addReview"
						>
							{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_ADD_REVIEW_SEND') }}
						</button>
					</div>
				</div>
			</PopupWrapper>
		</div>
	`,
}