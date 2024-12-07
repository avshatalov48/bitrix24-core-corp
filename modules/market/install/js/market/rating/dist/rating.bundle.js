this.BX = this.BX || {};
(function (exports,ui_vue3,market_installStore,main_popup,ui_designTokens,ui_vue3_pinia) {
	'use strict';

	const Stars = {
	  props: ['rating'],
	  methods: {
	    isActiveStar: function (currentStar, rating) {
	      return currentStar <= parseInt(rating, 10);
	    }
	  },
	  template: `
		<div class="market-detail__feedback-item_stars-container --feedback">
			<svg class="market-rating__app-rating_star"
				 :class="{'--active': isActiveStar(1, rating)}"
				 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
			</svg>
			<svg class="market-rating__app-rating_star"
				 :class="{'--active': isActiveStar(2, rating)}"
				 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
			</svg>
			<svg class="market-rating__app-rating_star"
				 :class="{'--active': isActiveStar(3, rating)}"
				 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
			</svg>
			<svg class="market-rating__app-rating_star"
				 :class="{'--active': isActiveStar(4, rating)}"
				 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
			</svg>
			<svg class="market-rating__app-rating_star"
				 :class="{'--active': isActiveStar(5, rating)}"
				 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
			</svg>
		</div>
	`
	};

	const RatingItem = {
	  components: {
	    Stars
	  },
	  props: ['review'],
	  data() {
	    return {};
	  },
	  computed: {
	    activeReview: function () {
	      return '';
	    },
	    getReviewText: function () {
	      return this.review.REVIEW_TEXT_SHORT ? this.review.REVIEW_TEXT_SHORT + '...' : this.review.REVIEW_TEXT_FULL;
	    },
	    getAnswerText: function () {
	      return this.review.REVIEW_ANSWER_TEXT_SHORT ? this.review.REVIEW_ANSWER_TEXT_SHORT + '...' : this.review.REVIEW_ANSWER_TEXT_FULL;
	    }
	  },
	  mounted() {
	    setTimeout(() => this.$refs.marketReviewItem.removeAttribute('data-role'), 3000);
	  },
	  methods: {
	    showFullReview: function () {
	      new BX.Main.Popup({
	        content: this.$refs.marketFullReview,
	        overlay: true,
	        closeIcon: true,
	        autoHide: true,
	        closeByEsc: true,
	        width: 492,
	        borderRadius: 12,
	        padding: 17,
	        className: 'market-popup__full-review'
	      }).show();
	    }
	  },
	  template: `
		<!-- data-role="ui-ears-active" -->
		<div class="market-detail__feedback-item"
			 :data-role="activeReview"
			 ref="marketReviewItem"
		>
			<div class="market-detail__feedback-item_row">
				<div class="market-detail__feedback-item_name">{{ review.USER_NAME }}</div>
				<div class="market-detail__feedback-item_addition">{{ review.DATE_CREATE }}</div>
			</div>
			<div class="market-detail__feedback-item_row">
				<Stars :rating="review.RATING"/>

				<template v-if="review.BLOCKED !== 'Y'">
					<div class="market-detail__feedback-item_addition --icon"
						 v-if="review.PUBLISHED === 'N'"
					>
						<svg class="market-detail__feedback-item_addition--icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M7.3281 5.32531H8.66143V7.32531H10.6614V8.65864H7.3281V5.32531Z" fill="#FFA900"/>
							<path fill-rule="evenodd" clip-rule="evenodd" d="M3.15497 10.1787C4.04408 12.1558 6.04538 13.3944 8.21152 13.308C11.0853 13.2492 13.3681 10.8734 13.3122 7.99963C13.312 5.83177 11.9946 3.88148 9.98361 3.07196C7.97256 2.26243 5.67138 2.75611 4.16936 4.3193C2.66734 5.88249 2.26586 8.20154 3.15497 10.1787ZM4.39167 9.62258C5.05385 11.0951 6.54435 12.0175 8.15762 11.9532C10.2979 11.9094 11.998 10.14 11.9564 7.99969C11.9563 6.38514 10.9752 4.93263 9.47741 4.32972C7.97965 3.72681 6.26581 4.09449 5.14715 5.2587C4.02849 6.42291 3.72949 8.15006 4.39167 9.62258Z" fill="#FFA900"/>
						</svg>
						{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_SENT_TO_DEVELOPER') }}
					</div>
					<div class="market-detail__feedback-item_addition --icon"
						 v-if="review.PUBLISHED === 'Y'"
					>
						<svg class="market-detail__feedback-item_addition--icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M6.64089 11.9998L3.33203 8.62153L4.49013 7.43912L6.64089 9.63502L11.5073 4.6665L12.6654 5.84891L6.64089 11.9998Z" fill="#7FA800"/>
						</svg>
						{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_PUBLISHED') }}
					</div>
				</template>
				<div class="market-detail__feedback-item_addition --icon"
					 v-if="review.BLOCKED === 'Y'"
				>
					<svg class="market-detail__feedback-item_addition--icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M7.22356 5.1732H8.78796L8.60745 8.55271H7.40407L7.22356 5.1732Z" fill="#FF5752"/>
						<path d="M8.00561 10.9167C8.53657 10.9167 8.96699 10.4863 8.96699 9.95535C8.96699 9.42439 8.53657 8.99396 8.00561 8.99396C7.47465 8.99396 7.04422 9.42439 7.04422 9.95535C7.04422 10.4863 7.47465 10.9167 8.00561 10.9167Z" fill="#FF5752"/>
						<path fill-rule="evenodd" clip-rule="evenodd" d="M8.00052 13.1334C10.8356 13.1334 13.1339 10.8351 13.1339 8.00003C13.1339 5.16497 10.8356 2.8667 8.00052 2.8667C5.16546 2.8667 2.86719 5.16497 2.86719 8.00003C2.86719 10.8351 5.16546 13.1334 8.00052 13.1334ZM8.00052 11.9139C10.1621 11.9139 11.9143 10.1616 11.9143 8.00004C11.9143 5.83849 10.1621 4.08621 8.00052 4.08621C5.83897 4.08621 4.08669 5.83849 4.08669 8.00004C4.08669 10.1616 5.83897 11.9139 8.00052 11.9139Z" fill="#FF5752"/>
					</svg>
					{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_NO_PUBLISHED') }}
				</div>
			</div>
			<div>
				<template v-if="review.REVIEW_TEXT_FULL">
					<div class="market-detail__feedback-item_text">
						{{ getReviewText }}
						<span class="market-detail__feedback-item_link-btn"
							  v-if="review.REVIEW_TEXT_SHORT"
							  @click="showFullReview"
						>{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_REVIEW_MORE') }}</span>
					</div>
					<template v-if="review.REVIEW_ANSWER_TEXT_FULL">
						<div class="market-detail__feedback-item_row">
							<div class="market-detail__feedback-item_subtitle">{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_REVIEW_ANSWER') }}</div>
							<div class="market-detail__feedback-item_addition">{{ review.REVIEW_ANSWER_DATE }}</div>
						</div>
						<div class="market-detail__feedback-item_text">
							{{ getAnswerText }}
							<span class="market-detail__feedback-item_link-btn"
								  v-if="review.REVIEW_ANSWER_TEXT_SHORT"
								  @click="showFullReview"
							>{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_REVIEW_MORE') }}</span>
						</div>
					</template>
				</template>
				<div class="market-detail__feedback-empty-wrapper"
					 v-else
				>
					<div class="market-detail__feedback-empty-icon"></div>
					<div class="market-detail__feedback-empty-text">{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_EMPTY_TEXT') }}</div>
				</div>
			</div>
			<div ref="marketFullReview" v-show="false">
				<div class="market-detail__feedback-item --popup"
					 :data-role="activeReview"
					 ref="marketReviewItem"
				>
					<div class="market-detail__feedback-item_row --popup">
						<div class="market-detail__feedback-item_name">{{ review.USER_NAME }}</div>
						<div class="market-detail__feedback-item_addition">{{ review.DATE_CREATE }}</div>
					</div>
					<div class="market-detail__feedback-item_row --popup">
						<Stars :rating="review.RATING"/>
						<template v-if="review.BLOCKED !== 'Y'">
							<div class="market-detail__feedback-item_addition --icon"
								 v-if="review.PUBLISHED === 'N'"
							>
								<svg class="market-detail__feedback-item_addition--icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M7.3281 5.32531H8.66143V7.32531H10.6614V8.65864H7.3281V5.32531Z" fill="#FFA900"/>
									<path fill-rule="evenodd" clip-rule="evenodd" d="M3.15497 10.1787C4.04408 12.1558 6.04538 13.3944 8.21152 13.308C11.0853 13.2492 13.3681 10.8734 13.3122 7.99963C13.312 5.83177 11.9946 3.88148 9.98361 3.07196C7.97256 2.26243 5.67138 2.75611 4.16936 4.3193C2.66734 5.88249 2.26586 8.20154 3.15497 10.1787ZM4.39167 9.62258C5.05385 11.0951 6.54435 12.0175 8.15762 11.9532C10.2979 11.9094 11.998 10.14 11.9564 7.99969C11.9563 6.38514 10.9752 4.93263 9.47741 4.32972C7.97965 3.72681 6.26581 4.09449 5.14715 5.2587C4.02849 6.42291 3.72949 8.15006 4.39167 9.62258Z" fill="#FFA900"/>
								</svg>
								{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_SENT_TO_DEVELOPER') }}
							</div>
							<div class="market-detail__feedback-item_addition --icon"
								 v-if="review.PUBLISHED === 'Y'"
							>
								<svg class="market-detail__feedback-item_addition--icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path fill-rule="evenodd" clip-rule="evenodd" d="M6.64089 11.9998L3.33203 8.62153L4.49013 7.43912L6.64089 9.63502L11.5073 4.6665L12.6654 5.84891L6.64089 11.9998Z" fill="#7FA800"/>
								</svg>
								{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_PUBLISHED') }}
							</div>
						</template>
						<div class="market-detail__feedback-item_addition --icon"
							 v-if="review.BLOCKED === 'Y'"
						>
							<svg class="market-detail__feedback-item_addition--icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M7.22356 5.1732H8.78796L8.60745 8.55271H7.40407L7.22356 5.1732Z" fill="#FF5752"/>
								<path d="M8.00561 10.9167C8.53657 10.9167 8.96699 10.4863 8.96699 9.95535C8.96699 9.42439 8.53657 8.99396 8.00561 8.99396C7.47465 8.99396 7.04422 9.42439 7.04422 9.95535C7.04422 10.4863 7.47465 10.9167 8.00561 10.9167Z" fill="#FF5752"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M8.00052 13.1334C10.8356 13.1334 13.1339 10.8351 13.1339 8.00003C13.1339 5.16497 10.8356 2.8667 8.00052 2.8667C5.16546 2.8667 2.86719 5.16497 2.86719 8.00003C2.86719 10.8351 5.16546 13.1334 8.00052 13.1334ZM8.00052 11.9139C10.1621 11.9139 11.9143 10.1616 11.9143 8.00004C11.9143 5.83849 10.1621 4.08621 8.00052 4.08621C5.83897 4.08621 4.08669 5.83849 4.08669 8.00004C4.08669 10.1616 5.83897 11.9139 8.00052 11.9139Z" fill="#FF5752"/>
							</svg>
							{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_NO_PUBLISHED') }}
						</div>
					</div>
					<div class="market-detail__feedback-item_text --popup"
						 v-html="review.REVIEW_TEXT_FULL"
					></div>
					<template v-if="review.REVIEW_ANSWER_TEXT_FULL">
						<div class="market-detail__feedback-item_row --popup">
							<div class="market-detail__feedback-item_subtitle">{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_REVIEW_ANSWER') }}</div>
							<div class="market-detail__feedback-item_addition">{{ review.REVIEW_ANSWER_DATE }}</div>
						</div>
						<div class="market-detail__feedback-item_text --last-element"
							 v-html="review.REVIEW_ANSWER_TEXT_FULL"
						></div>
					</template>
				</div>
			</div>
		</div>
	`
	};

	const POPUP_CONTAINER_PREFIX = '#popup-window-content-';
	const POPUP_ID = 'feedback-popup-wrapper';
	const POPUP_BORDER_RADIUS = '10px';

	// @vue/component
	const PopupWrapper = {
	  name: 'PopupWrapper',
	  emits: ['close'],
	  computed: {
	    popupContainer() {
	      return `${POPUP_CONTAINER_PREFIX}${POPUP_ID}`;
	    }
	  },
	  created() {
	    this.instance = this.getPopupInstance();
	    this.instance.show();
	  },
	  mounted() {
	    this.instance.adjustPosition({
	      forceBindPosition: true,
	      position: this.getConfig().bindOptions.position
	    });
	  },
	  beforeUnmount() {
	    if (!this.instance) {
	      return;
	    }
	    this.closePopup();
	  },
	  methods: {
	    getPopupInstance() {
	      if (!this.instance) {
	        var _PopupManager$getPopu;
	        (_PopupManager$getPopu = main_popup.PopupManager.getPopupById(this.id)) == null ? void 0 : _PopupManager$getPopu.destroy();
	        this.instance = new main_popup.Popup(this.getConfig());
	      }
	      return this.instance;
	    },
	    getConfig() {
	      return {
	        id: POPUP_ID,
	        bindOptions: {
	          position: 'bottom'
	        },
	        width: 463,
	        padding: 32,
	        minHeight: 470,
	        className: 'market-detail__app-rating_feedback-popup',
	        cacheable: false,
	        closeIcon: true,
	        autoHide: true,
	        closeByEsc: true,
	        animation: 'fading',
	        events: {
	          onPopupClose: this.closePopup.bind(this),
	          onPopupDestroy: this.closePopup.bind(this)
	        },
	        overlay: {
	          backgroundColor: '#000',
	          opacity: 50
	        },
	        contentBorderRadius: POPUP_BORDER_RADIUS
	      };
	    },
	    closePopup() {
	      this.$emit('close');
	      this.instance.destroy();
	      this.instance = null;
	    }
	  },
	  template: `
		<Teleport :to="popupContainer">
			<slot></slot>
		</Teleport>
	`
	};

	const Rating = {
	  emits: ['can-review', 'review-info', 'update-rating'],
	  components: {
	    RatingItem,
	    PopupWrapper
	  },
	  props: ['appInfo', 'showNoAccessInstallButton'],
	  data() {
	    return {
	      ratingClickState: false,
	      addingReview: false,
	      policyChecked: false,
	      rulesChecked: false,
	      feedbackBlock: null,
	      reviewText: '',
	      currentRating: 0,
	      starsError: false,
	      sendingReview: false
	    };
	  },
	  computed: {
	    canReview: function () {
	      return this.appInfo.REVIEWS.CAN_REVIEW === 'Y';
	    },
	    showPolicy: function () {
	      return this.appInfo.REVIEWS.SHOW_POLICY_CHECKBOX === 'Y';
	    },
	    showRules: function () {
	      return this.appInfo.REVIEWS.SHOW_RULES_CHECKBOX === 'Y';
	    },
	    allChecked: function () {
	      return this.policyChecked && this.rulesChecked;
	    },
	    appWasInstalled: function () {
	      return this.appInfo.WAS_INSTALLED && this.appInfo.WAS_INSTALLED === 'Y';
	    },
	    showInstallState: function () {
	      return !this.appWasInstalled && (!this.$parent.countReviews || this.addingReview);
	    },
	    canRatingClick: function () {
	      return this.canReview && !this.sendingReview;
	    },
	    ...ui_vue3_pinia.mapState(market_installStore.marketInstallState, ['installStep', 'slider', 'timer'])
	  },
	  mounted() {
	    this.feedbackBlock = this.$refs.marketFeedback;
	    if (this.feedbackBlock) {
	      new BX.UI.Ears({
	        container: this.feedbackBlock,
	        smallSize: true,
	        noScrollbar: true
	      }).init();
	    }
	    if (!this.canReview) {
	      this.currentRating = this.appInfo.REVIEWS.USER_RATING;
	    }
	    if (!this.showPolicy) {
	      this.policyChecked = true;
	    }
	    if (!this.showRules) {
	      this.rulesChecked = true;
	    }
	  },
	  methods: {
	    scrollToUserReview: function () {
	      window.scrollTo({
	        top: this.feedbackBlock.getBoundingClientRect().top,
	        behavior: 'smooth'
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
	      this.currentRating = rating === this.currentRating ? 0 : rating;
	    },
	    addingReviewClick: function () {
	      this.addingReview = true;
	      ui_vue3.nextTick(() => {
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
	      BX.ajax.runAction('market.Application.addReview', {
	        data: {
	          appCode: this.appInfo.CODE,
	          reviewText: this.reviewText,
	          currentRating: this.currentRating
	        },
	        analyticsLabel: {
	          appCode: this.appInfo.CODE,
	          currentRating: this.currentRating
	        }
	      }).then(response => {
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
	      }, response => {
	        this.sendingReview = false;
	        this.showNotify(this.$Bitrix.Loc.getMessage('MARKET_RATING_JS_ADD_REVIEW_ERROR'));
	      });
	    },
	    showNotify: function (text) {
	      BX.UI.Notification.Center.notify({
	        content: text,
	        position: BX.UI.Notification.Position.BOTTOM_LEFT
	      });
	    },
	    getCountStars: function (star) {
	      if (!this.appInfo.REVIEWS.RATING || !this.appInfo.REVIEWS.RATING.RATING_DETAIL) {
	        return 0;
	      }
	      if (this.appInfo.REVIEWS.RATING.RATING_DETAIL[star]) {
	        return this.appInfo.REVIEWS.RATING.RATING_DETAIL[star];
	      }
	      return 0;
	    },
	    getStarWidth: function (star) {
	      if (this.getCountStars(star) === 0 || !this.$parent.countReviews) {
	        return '0%';
	      }
	      return this.getCountStars(star) / this.$parent.countReviews * 100 + '%';
	    },
	    successReviewHandler: function (data) {
	      this.showNotify(this.$Bitrix.Loc.getMessage('MARKET_RATING_JS_ADD_REVIEW_SUCCESS'));
	      if (data && data.can_review) {
	        this.$emit('can-review', data.can_review);
	      }
	      if (data && data.review_info) {
	        this.$emit('review-info', data.review_info);
	      }
	      if (data && data.rating) {
	        this.$emit('update-rating', data.rating);
	      }
	      this.addingReview = false;
	    },
	    ...ui_vue3_pinia.mapActions(market_installStore.marketInstallState, ['showInstallPopup'])
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
								 :class="{'--active': isActiveStar(1, $parent.totalRating)}"
								 @click="ratingClick"
								 width="18" height="18" viewBox="0 0 18 18"
								 fill="none" xmlns="http://www.w3.org/2000/svg"
							>
								<path
									d="M8.53505 1.17539C8.70176 0.753948 9.29824 0.753947 9.46495 1.17539L11.389 6.03945C11.4589 6.21604 11.6227 6.33781 11.812 6.35377L16.8494 6.7784C17.2857 6.81517 17.4673 7.35515 17.142 7.64815L13.2679 11.1376C13.1333 11.2587 13.0748 11.4432 13.1149 11.6197L14.292 16.8084C14.391 17.2448 13.9107 17.5815 13.5342 17.3397L9.27019 14.6013C9.10558 14.4955 8.89442 14.4955 8.72981 14.6013L4.46583 17.3397C4.08928 17.5815 3.60902 17.2448 3.70803 16.8084L4.88514 11.6197C4.92519 11.4432 4.86667 11.2587 4.73215 11.1376L0.857968 7.64815C0.532662 7.35515 0.714337 6.81517 1.15059 6.7784L6.18805 6.35377C6.37728 6.33781 6.54114 6.21604 6.61099 6.03945L8.53505 1.17539Z"/>
							</svg>

							<svg class="market-rating__app-rating_star "
								 :class="{'--active': isActiveStar(2, $parent.totalRating)}"
								 @click="ratingClick"
								 width="18" height="18" viewBox="0 0 18 18"
								 fill="none" xmlns="http://www.w3.org/2000/svg"
							>
								<path
									d="M8.53505 1.17539C8.70176 0.753948 9.29824 0.753947 9.46495 1.17539L11.389 6.03945C11.4589 6.21604 11.6227 6.33781 11.812 6.35377L16.8494 6.7784C17.2857 6.81517 17.4673 7.35515 17.142 7.64815L13.2679 11.1376C13.1333 11.2587 13.0748 11.4432 13.1149 11.6197L14.292 16.8084C14.391 17.2448 13.9107 17.5815 13.5342 17.3397L9.27019 14.6013C9.10558 14.4955 8.89442 14.4955 8.72981 14.6013L4.46583 17.3397C4.08928 17.5815 3.60902 17.2448 3.70803 16.8084L4.88514 11.6197C4.92519 11.4432 4.86667 11.2587 4.73215 11.1376L0.857968 7.64815C0.532662 7.35515 0.714337 6.81517 1.15059 6.7784L6.18805 6.35377C6.37728 6.33781 6.54114 6.21604 6.61099 6.03945L8.53505 1.17539Z"/>
							</svg>

							<svg class="market-rating__app-rating_star"
								 :class="{'--active': isActiveStar(3, $parent.totalRating)}"
								 @click="ratingClick"
								 width="18" height="18" viewBox="0 0 18 18"
								 fill="none" xmlns="http://www.w3.org/2000/svg"
							>
								<path
									d="M8.53505 1.17539C8.70176 0.753948 9.29824 0.753947 9.46495 1.17539L11.389 6.03945C11.4589 6.21604 11.6227 6.33781 11.812 6.35377L16.8494 6.7784C17.2857 6.81517 17.4673 7.35515 17.142 7.64815L13.2679 11.1376C13.1333 11.2587 13.0748 11.4432 13.1149 11.6197L14.292 16.8084C14.391 17.2448 13.9107 17.5815 13.5342 17.3397L9.27019 14.6013C9.10558 14.4955 8.89442 14.4955 8.72981 14.6013L4.46583 17.3397C4.08928 17.5815 3.60902 17.2448 3.70803 16.8084L4.88514 11.6197C4.92519 11.4432 4.86667 11.2587 4.73215 11.1376L0.857968 7.64815C0.532662 7.35515 0.714337 6.81517 1.15059 6.7784L6.18805 6.35377C6.37728 6.33781 6.54114 6.21604 6.61099 6.03945L8.53505 1.17539Z"/>
							</svg>

							<svg class="market-rating__app-rating_star"
								 :class="{'--active': isActiveStar(4, $parent.totalRating)}"
								 @click="ratingClick"
								 width="18" height="18" viewBox="0 0 18 18"
								 fill="none" xmlns="http://www.w3.org/2000/svg"
							>
								<path
									d="M8.53505 1.17539C8.70176 0.753948 9.29824 0.753947 9.46495 1.17539L11.389 6.03945C11.4589 6.21604 11.6227 6.33781 11.812 6.35377L16.8494 6.7784C17.2857 6.81517 17.4673 7.35515 17.142 7.64815L13.2679 11.1376C13.1333 11.2587 13.0748 11.4432 13.1149 11.6197L14.292 16.8084C14.391 17.2448 13.9107 17.5815 13.5342 17.3397L9.27019 14.6013C9.10558 14.4955 8.89442 14.4955 8.72981 14.6013L4.46583 17.3397C4.08928 17.5815 3.60902 17.2448 3.70803 16.8084L4.88514 11.6197C4.92519 11.4432 4.86667 11.2587 4.73215 11.1376L0.857968 7.64815C0.532662 7.35515 0.714337 6.81517 1.15059 6.7784L6.18805 6.35377C6.37728 6.33781 6.54114 6.21604 6.61099 6.03945L8.53505 1.17539Z"/>
							</svg>

							<svg class="market-rating__app-rating_star"
								 :class="{'--active': isActiveStar(5, $parent.totalRating)}"
								 @click="ratingClick"
								 width="18" height="18" viewBox="0 0 18 18"
								 fill="none" xmlns="http://www.w3.org/2000/svg"
							>
								<path
									d="M8.53505 1.17539C8.70176 0.753948 9.29824 0.753947 9.46495 1.17539L11.389 6.03945C11.4589 6.21604 11.6227 6.33781 11.812 6.35377L16.8494 6.7784C17.2857 6.81517 17.4673 7.35515 17.142 7.64815L13.2679 11.1376C13.1333 11.2587 13.0748 11.4432 13.1149 11.6197L14.292 16.8084C14.391 17.2448 13.9107 17.5815 13.5342 17.3397L9.27019 14.6013C9.10558 14.4955 8.89442 14.4955 8.72981 14.6013L4.46583 17.3397C4.08928 17.5815 3.60902 17.2448 3.70803 16.8084L4.88514 11.6197C4.92519 11.4432 4.86667 11.2587 4.73215 11.1376L0.857968 7.64815C0.532662 7.35515 0.714337 6.81517 1.15059 6.7784L6.18805 6.35377C6.37728 6.33781 6.54114 6.21604 6.61099 6.03945L8.53505 1.17539Z"/>
							</svg>
						</div>

						<div class="market-rating__app-rating-info_stars-number">
							{{ $parent.totalRating }}
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
							 v-if="$parent.countReviews"
							 @click="backToReviews"
						>{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_REVIEWS_TOTAL', {'#NUMBER#': $parent.countReviews}) }}
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
									@click="$parent.installApp"
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
									v-for="review in appInfo.REVIEWS.ITEMS"
									:review="review"
								/>
							</div>
						</div>
					</div>
				</div>
			</div>
			<PopupWrapper v-if="addingReview && !showInstallState" @close="cancelAddingReviewClick">
				<div class="market-detail__app-rating_feedback-form">
					<div class="market-detail__app-rating_feedback-img">
						<img :src="appInfo.ICON" alt="icon">
					</div>
					<div class="market-detail__app-rating_feedback-subtitle"
						 v-html="$Bitrix.Loc.getMessage('MARKET_RATING_JS_ADD_REVIEW_TITLE', {'#APP_NAME#' : appInfo.NAME})"
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
								  v-html="$Bitrix.Loc.getMessage('MARKET_RATING_JS_ADD_REVIEW_POLICY', {'#POLICY_URL#': appInfo.REVIEWS.POLICY_URL})"
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
								  v-html="$Bitrix.Loc.getMessage('MARKET_RATING_JS_ADD_REVIEW_POSTING_GUIDELINES', {'#RULES_URL#': appInfo.REVIEWS.POSTING_GUIDELINES_URL})"
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
	`
	};

	exports.Rating = Rating;

}((this.BX.Market = this.BX.Market || {}),BX.Vue3,BX.Market,BX.Main,BX,BX.Vue3.Pinia));
