this.BX = this.BX || {};
(function (exports,market_ratingStore,ui_vue3_pinia) {
	'use strict';

	const RatingItem = {
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
	    },
	    ...ui_vue3_pinia.mapActions(market_ratingStore.ratingStore, ['isActiveStar'])
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
				<div class="market-detail__feedback-item_stars-container --feedback">
					<svg class="market-rating__app-rating_star"
						 :class="{'--active': isActiveStar(1, review.RATING)}"
						 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
					</svg>
					<svg class="market-rating__app-rating_star"
						 :class="{'--active': isActiveStar(2, review.RATING)}"
						 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
					</svg>
					<svg class="market-rating__app-rating_star"
						 :class="{'--active': isActiveStar(3, review.RATING)}"
						 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
					</svg>
					<svg class="market-rating__app-rating_star"
						 :class="{'--active': isActiveStar(4, review.RATING)}"
						 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
					</svg>
					<svg class="market-rating__app-rating_star"
						 :class="{'--active': isActiveStar(5, review.RATING)}"
						 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
					</svg>
				</div>
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
			<div class="market-detail__feedback-item_text">
				{{ getReviewText }} 
				<span class="market-detail__feedback-item_link-btn"
					  v-if="this.review.REVIEW_TEXT_SHORT"
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
						  v-if="this.review.REVIEW_ANSWER_TEXT_SHORT"
						  @click="showFullReview"
					>{{ $Bitrix.Loc.getMessage('MARKET_RATING_JS_REVIEW_MORE') }}</span>
				</div>
			</template>
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
						<div class="market-detail__feedback-item_stars-container --feedback1">
							<svg class="market-rating__app-rating_star"
								 :class="{'--active': isActiveStar(1, review.RATING)}"
								 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
							</svg>
							<svg class="market-rating__app-rating_star"
								 :class="{'--active': isActiveStar(2, review.RATING)}"
								 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
							</svg>
							<svg class="market-rating__app-rating_star"
								 :class="{'--active': isActiveStar(3, review.RATING)}"
								 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
							</svg>
							<svg class="market-rating__app-rating_star"
								 :class="{'--active': isActiveStar(4, review.RATING)}"
								 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
							</svg>
							<svg class="market-rating__app-rating_star"
								 :class="{'--active': isActiveStar(5, review.RATING)}"
								 width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
							</svg>
						</div>
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

	exports.RatingItem = RatingItem;

}((this.BX.Market = this.BX.Market || {}),BX.Market,BX.Vue3.Pinia));
