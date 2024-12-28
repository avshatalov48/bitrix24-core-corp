import { MenuManager } from "main.popup";
import { Stars } from "./stars";
import { MarketLinks } from "market.market-links";

import { nextTick } from 'ui.vue3';
import 'ui.icon-set.actions';
import "ui.forms";
import "ui.buttons";
import "ui.alerts";
import 'ui.notification';

import "./my-reviews-component.css";

export const ReviewItem = {
	components: {
		Stars,
	},
	emits: ['editedReview'],
	props: ['review', 'reviewIndex'],
	data() {
		return {
			contextMenu: false,
			editing: false,
			savingReview: false,
			newReviewText: '',
			newReviewRating: 0,
			MarketLinks: MarketLinks,
		};
	},
	computed: {
		getReviewId: function() {
			return this.review.ID;
		},
		canEditReview: function() {
			return this.review.CAN_EDIT_REVIEW === 'Y';
		},
		editReviewNotAllowedText: function() {
			return this.review.EDIT_REVIEW_NOT_ALLOWED_TEXT ?? '';
		},
		isSiteTemplate: function () {
			return this.review.IS_SITE_TEMPLATE === 'Y';
		},
		getBackgroundPath: function () {
			if (this.isSiteTemplate) {
				return this.review.SITE_PREVIEW;
			}

			return "/bitrix/js/market/images/backgrounds/" + this.getIndex + ".png";
		},
		getIndex: function () {
			return (parseInt(this.reviewIndex, 10) % 30) + 1;
		},
	},
	mounted: function() {
		this.newReviewText = this.review.REVIEW_FULL_TEXT_EDITING_EDIT;
		this.newReviewRating = this.review.RATING;
	},
	methods: {
		getDetailLink: function(reviewItem) {
			const params = {
				from: 'reviews',
			};
			return MarketLinks.appDetail(reviewItem, params);
		},
		showMenu: function () {
			let menu = [];

			menu.push({
				text: this.$Bitrix.Loc.getMessage('MARKET_REVIEWS_MENU_ITEM_EDITING'),
				onclick: this.showEditing,
			});

			if (menu.length > 0) {
				const menuId = 'review-item-menu-' + this.getReviewId;
				MenuManager.destroy(menuId);

				this.contextMenu = MenuManager.create(
					menuId,
					this.$refs.marketReviewsMenu,
					menu,
					{
						closeByEsc : true,
						autoHide : true,
						angle: true,
						offsetLeft: 20,
					}
				);
			}

			this.contextMenu.show();
		},
		showEditing: function() {
			if (this.contextMenu) {
				this.contextMenu.close();
			}

			if (!this.canEditReview) {
				if (this.editReviewNotAllowedText.length) {
					this.showNotify(this.editReviewNotAllowedText);
					return;
				}

				this.showNotify(this.$Bitrix.Loc.getMessage('MARKET_REVIEW_EDIT_REVIEW_NOT_ALLOWED'))
				return;
			}

			this.editing = true;

			nextTick(() => {
				if (this.$refs.marketReviewEditingText) {
					this.$refs.marketReviewEditingText.focus();
				}
			});
		},
		saveReview: function() {
			this.savingReview = true;

			const isSiteTemplate = this.isSiteTemplate === true ? 'Y' : 'N';
			BX.ajax.runAction('market.Application.editReview', {
				data: {
					reviewId: this.review.ID,
					appCode: this.review.APP_CODE,
					reviewText: this.newReviewText,
					currentRating: this.newReviewRating,
					isSite: isSiteTemplate,
				},
				analyticsLabel: {
					appCode: this.review.APP_CODE,
					currentRating: this.newReviewRating,
					isSite: isSiteTemplate,
				},
			}).then(
				response => {
					this.savingReview = false;

					if (response.data && response.data.success === 'Y') {
						this.successReviewHandler(response.data);
					} else if (response.data && response.data.error) {
						const errors = response.data.error.slice(0);
						const firstError = errors.shift();
						if (firstError === 'NOT_FOUND_TEXT') {
							this.showNotify(this.$Bitrix.Loc.getMessage('MARKET_REVIEW_EDIT_REVIEW_TEXT_ERROR'));
							return;
						}

						this.showNotify(this.$Bitrix.Loc.getMessage('MARKET_REVIEW_EDIT_REVIEW_ERROR') + ' (' + response.data.error + ')');
					} else {
						this.showNotify(this.$Bitrix.Loc.getMessage('MARKET_REVIEW_EDIT_REVIEW_ERROR'));
					}
				},
				response => {
					this.savingReview = false;
					this.showNotify(this.$Bitrix.Loc.getMessage('MARKET_REVIEW_EDIT_REVIEW_ERROR'));
				},
			);
		},
		successReviewHandler: function (data) {
			this.showNotify(this.$Bitrix.Loc.getMessage('MARKET_REVIEW_EDIT_REVIEW_SUCCESS'));

			if (data && data.review_info) {
				this.$emit('editedReview', this.reviewIndex, data.review_info);
			}

			this.editing = false;
		},
		showNotify: function (text) {
			BX.UI.Notification.Center.notify({
				content: text,
				position: BX.UI.Notification.Position.TOP_CENTER,
			});
		},
		cancelEditing: function() {
			this.editing = false;
		},
	},
	template: `
		<div class="market-reviews__item">
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
						<span class="market-reviews__item-menu ui-icon-set --more-information"
							  ref="marketReviewsMenu"
							  @click="showMenu"
						></span>
					</template>
					<template v-if="review.PUBLISHED === 'Y'">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M6.64089 12.0003L3.33203 8.62202L4.49013 7.43961L6.64089 9.63551L11.5073 4.66699L12.6654 5.8494L6.64089 12.0003Z" fill="#7FA800"/>
						</svg>
						<span class="market-reviews__item-label_">
							{{ $Bitrix.Loc.getMessage('MARKET_REVIEW_PUBLISHED') }}
						</span>
						<span class="market-reviews__item-menu ui-icon-set --more-information"
							  ref="marketReviewsMenu"
							  @click="showMenu"
						></span>
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
				<div class="market-reviews__item-content-logo">
					<a class="market-reviews__item-content-logo-link"
					   :style="{'background-image': 'url(\\'' + getBackgroundPath + '\\')'}"
					   :href="getDetailLink(review)"
					   @click="MarketLinks.openSiteTemplate($event, this.isSiteTemplate)"
					>
						<img class="market-reviews__item-content-logo-img"
							 :src="review.APP_LOGO"
							 v-if="!isSiteTemplate"
							 alt="img"
						>
					</a>
				</div>
				<div class="market-reviews__item-main-content">
					<div class="market-reviews__item-title-wrapper">
						<a class="market-reviews__item-title"
						   :href="getDetailLink(review)"
						>{{ review.APP_NAME }}</a>
						<div class="market-reviews__item-rating">
							<Stars :rating="review.RATING"
								   :editable="false"
							/>
							<div class="market-reviews__item-date">
								{{ review.DATE_CREATE }}
							</div>
						</div>
					</div>
					<div class="market-reviews__item-description">
						{{ review.REVIEW_FULL_TEXT_EDITING_SHOW }}
					</div>
					<div class="market-reviews__item-editing-block" v-if="editing">
						<div class="market-reviews__item-editing-header">
							<span class="market-reviews__item-editing-title">
								{{ $Bitrix.Loc.getMessage('MARKET_REVIEW_EDITING_TITLE') }}
							</span>
							
							<Stars :rating="review.RATING"
								   :editable="true"
								   @change-rating="(rating) => this.newReviewRating = rating"
							/>
						</div>
						<div class="market-reviews__item-editing-content">
							<div class="ui-ctl ui-ctl-textarea">
								<textarea class="ui-ctl-element"
										  ref="marketReviewEditingText"
										  :disabled="savingReview"
										  v-model="newReviewText"
								></textarea>
							</div>
							<div class="market-reviews__item-editing-notice">
								<div class="ui-alert ui-alert-icon-info ui-alert-warning">
									<span class="ui-alert-message">
										{{ $Bitrix.Loc.getMessage('MARKET_REVIEW_EDITING_RE_MODERATION_TITLE') }}
									</span>
								</div>
							</div>
							<div class="market-reviews__item-editing-buttons">
								<button class="ui-btn ui-btn-primary ui-btn-xs"
										:class="{'ui-btn-clock': savingReview}"
										@click="saveReview"
								>
									<span class="ui-btn-text">
										{{ $Bitrix.Loc.getMessage('MARKET_REVIEW_EDITING_SAVE') }}
									</span>
								</button>
								<button class="ui-btn ui-btn-link ui-btn-xs"
										@click="cancelEditing"
								>
									<span class="ui-btn-text">
										{{ $Bitrix.Loc.getMessage('MARKET_REVIEW_EDITING_CANCEL') }}
									</span>
								</button>
							</div>
						</div>
					</div>
					<div class="market-reviews__item-answer" v-if="review.REVIEW_ANSWER_TEXT_FULL">
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
								{{ review.REVIEW_FULL_ANSWER_TEXT_EDITING_SHOW }}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	`,
}