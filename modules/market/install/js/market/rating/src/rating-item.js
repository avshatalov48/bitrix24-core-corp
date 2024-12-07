import { Stars } from './stars';

export const RatingItem = {
	components: {
		Stars,
	},
	props: [
		'review',
	],
	data() {
		return {

		};
	},
	computed: {
		activeReview: function () {
			return (false) ? 'ui-ears-active' : '';
		},
		getReviewText: function () {
			return (this.review.REVIEW_TEXT_SHORT) ? this.review.REVIEW_TEXT_SHORT + '...' : this.review.REVIEW_TEXT_FULL;
		},
		getAnswerText: function () {
			return (this.review.REVIEW_ANSWER_TEXT_SHORT) ? this.review.REVIEW_ANSWER_TEXT_SHORT + '...' : this.review.REVIEW_ANSWER_TEXT_FULL;
		},
	},
	mounted() {
		setTimeout(
			() => this.$refs.marketReviewItem.removeAttribute('data-role'),
			3000
		);
	},
	methods: {
		showFullReview: function () {
			(new BX.Main.Popup({
				content: this.$refs.marketFullReview,
				overlay: true,
				closeIcon: true,
				autoHide: true,
				closeByEsc: true,
				width: 492,
				borderRadius: 12,
				padding: 17,
				className: 'market-popup__full-review',
			})).show();
		},
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
	`,
}