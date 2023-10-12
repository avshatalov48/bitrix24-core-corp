this.BX = this.BX || {};
(function (exports,market_ratingStore,ui_vue3_pinia) {
	'use strict';

	const CollectionTop = {
	  props: ['item', 'collectionIndex'],
	  mounted: function () {
	    new BX.UI.Ears({
	      container: document.querySelector('#item_' + this.item.CAROUSEL_ID),
	      smallSize: true,
	      noScrollbar: true,
	      className: "market-topset-inner-carousel-container",
	      touchScroll: true
	    }).init();
	    BX.addCustomEvent("SidePanel.Slider:onMessage", this.onMessageSlider);
	  },
	  methods: {
	    onMessageSlider: function (event) {
	      if (event.eventId === 'total-fav-number') {
	        this.item.APPS.forEach((app, index) => {
	          if (app.CODE === event.data.appCode) {
	            this.item.APPS[index]['IS_FAVORITE'] = event.data.currentValue;
	          }
	        });
	      }
	    },
	    isSiteTemplate: function (appItem) {
	      return appItem.IS_SITE_TEMPLATE === 'Y';
	    },
	    getBackgroundPath: function (appItem, index) {
	      if (this.isSiteTemplate(appItem)) {
	        return appItem.SITE_PREVIEW;
	      }
	      return "/bitrix/js/market/images/backgrounds/" + this.getIndex(index) + ".png";
	    },
	    getIndex: function (index) {
	      return parseInt(this.offsetIndex(index), 10) % 30 + 1;
	    },
	    offsetIndex: function (index) {
	      return index + this.collectionIndex * 5;
	    },
	    adjustMouseClick: function (element) {
	      if (!element) {
	        return;
	      }
	      let timer;
	      let adjustPointerEvents = () => {
	        window.removeEventListener('mouseup', adjustPointerEvents);
	        element.removeEventListener('mousemove', adjustMouseMove);
	        clearTimeout(timer);
	        setTimeout(() => {
	          element.style.removeProperty('pointer-events');
	        }, 150);
	      };
	      let adjustMouseMove = () => {
	        timer = setTimeout(() => {
	          if (window.getComputedStyle(element).pointerEvents !== 'none') {
	            element.style.setProperty('pointer-events', 'none');
	          }
	        }, 100);
	      };
	      window.addEventListener('mouseup', adjustPointerEvents);
	      element.addEventListener('mousemove', adjustMouseMove);
	    },
	    ...ui_vue3_pinia.mapActions(market_ratingStore.ratingStore, ['isActiveStar', 'getAppRating'])
	  },
	  template: `
		<div class="market-topset-container">
			<div class="market-topset-header-container">
				<div class="market-topset-header-block" style="flex: 1">
					<h2 class="market-topset-title-text">
						<a class="market-topset-title-text-link"
						   :href="$root.getCollectionUri(item.COLLECTION_ID, item.SHOW_ON_PAGE)"
						   data-slider-ignore-autobinding="true"
						   data-load-content="list"
						   @click.prevent="$root.emitLoadContent"
						   :title="item.NAME"
						>
							{{ item.NAME }}
						</a>
					</h2>
					<div class="market-topset-title-counter"
						 v-if="parseInt(item.NUMBER_APPS, 10) > 0"
					>
						{{ item.NUMBER_APPS }}
					</div>
				</div>
				<div class="market-topset-header-block">
					<a class="market-topset-more-btn"
					   :href="$root.getCollectionUri(item.COLLECTION_ID, item.SHOW_ON_PAGE)"
					   data-slider-ignore-autobinding="true"
					   data-load-content="list"
					   @click.prevent="$root.emitLoadContent"
					>
						{{ $Bitrix.Loc.getMessage('MARKET_COLLECTIONS_TOP_JS_SHOW_AS_A_LIST') }}
						<svg width="6" height="9" viewBox="0 0 6 9" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M0 0.990883L3.06862 3.79917L3.86345 4.49975L3.06862 5.20075L0 8.00904L1.08283 9L6 4.5L1.08283 0L0 0.990883Z" fill="#B9BFC3"/>
						</svg>
					</a>
				</div>
			</div>
			<div :id="'item_' + item.CAROUSEL_ID">
				<div class=""
					 :style="{'min-width': 'calc(' + item.NUMBER_SHOW_APPS + ' * var(--market-top-preview-size))',}"
				>
					<div class="market-topset-inner-carousel-block"
						 v-for="(appItem, index) in item.APPS"
						 :style="{
							 'min-width': 'calc(' + item.STYLE_FOR_TOP + '% - var(--market-topset-carousel-block-gap-x) + (var(--market-topset-carousel-block-gap-x) / ' + item.STYLE_FOR_TOP + '))',
							 'width': 'calc(' + item.STYLE_FOR_TOP + '% - var(--market-topset-carousel-block-gap-x) + (var(--market-topset-carousel-block-gap-x) / ' + item.STYLE_FOR_TOP + '))',
							 'max-width': 'calc(' + item.STYLE_FOR_TOP + '% - var(--market-topset-carousel-block-gap-x) + (var(--market-topset-carousel-block-gap-x) / ' + item.STYLE_FOR_TOP + '))',
						 }"
						 :data-market-app-code="appItem.CODE"
					>
						<a class="market-topset-item-cover"
						   :href="$root.getDetailUri(appItem.CODE, isSiteTemplate(appItem), 'main')"
						   @click="$root.openSiteTemplate($event, isSiteTemplate(appItem))"
						   @mousedown="adjustMouseClick($event.target)"
						   :title="appItem.NAME"
						   :draggable="false"
						>
							<div class="market-topset-item-labels"
								 v-if="appItem.LABELS"
							>
								<div class="market-topset-item-label-item"
									 v-for="label in appItem.LABELS"
									 :style="{'background': label.COLOR_2}"
								>
									{{ label.TEXT }}
								</div>
							</div>

							<div class="market-topset-item-cover-inner"
								 :style="{'background-image': 'url(\\'' + getBackgroundPath(appItem, index) + '\\')'}"
							>
								<img :src="appItem.ICON" 
									 v-if="!isSiteTemplate(appItem)"
									 alt=""
								>
							</div>
							<span class="market-topset-item-labels-status" >
								<span class="market-topset-item-label-status"
									  :class="{'--blue': appItem.PRICE_POLICY_BLUE}"
								>
									{{ appItem.PRICE_POLICY_NAME }}
								</span>
							</span>
						</a>
						<div class="market-topset-item-info-container">
							<h3 class="market-topset-item-info-title" :title="appItem.NAME">
								<a class="market-topset-item-info-title-text"
								   :href="$root.getDetailUri(appItem.CODE, isSiteTemplate(appItem), 'main')"
								   @click="$root.openSiteTemplate($event, isSiteTemplate(appItem))"
								   @mousedown="adjustMouseClick($event.target)"
								   :title="appItem.NAME"
								   :draggable="false"
								>
									{{ appItem.NAME }}
								</a>
								<div class="market-topset-item-info-favorites"
									 :class="{'--selected': appItem.IS_FAVORITE === 'Y'}"
								>
									<svg xmlns="http://www.w3.org/2000/svg" width="17" height="16" viewBox="0 0 17 16" fill="none">
										<path d="M2.06479 2.77573L2.06478 2.77574C1.30373 3.51052 0.94519 4.29791 0.898939 5.26294L0.898938 5.26295C0.845606 6.37547 1.29931 7.62581 2.51765 9.15845C2.93744 9.68652 4.06258 10.8338 4.58536 11.2667C5.09058 11.685 6.1463 12.4694 7.11223 13.1591C7.5919 13.5016 8.03948 13.8136 8.37453 14.0381C8.41818 14.0674 8.45939 14.0948 8.49807 14.1203C8.53705 14.0945 8.57861 14.0668 8.62262 14.0373C8.95825 13.8118 9.40665 13.4987 9.88677 13.1555C10.8537 12.4644 11.9091 11.68 12.4082 11.2667L12.4082 11.2667C12.9308 10.834 14.056 9.68667 14.4761 9.15838L14.4761 9.15837C15.4027 7.99295 15.8938 6.96575 16.0594 6.01422C16.1927 5.2479 16.0297 4.33737 15.6507 3.68805L15.6507 3.68804C14.8535 2.32233 13.4589 1.6101 12.0616 1.76241C11.4906 1.82466 11.0592 1.95681 10.6701 2.17688C10.2735 2.40112 9.87318 2.74355 9.40037 3.29085L9.40033 3.29089L9.17381 3.55306L8.49678 4.33665L7.81975 3.55306L7.59323 3.29089L7.5932 3.29086C6.70885 2.26722 6.01623 1.89315 5.03305 1.77207L5.03301 1.77207C4.61762 1.72089 4.37316 1.72575 4.01486 1.78417L2.06479 2.77573ZM2.06479 2.77573C2.63117 2.22887 3.27616 1.90467 4.0148 1.78418L2.06479 2.77573ZM8.84241 14.3362C8.8422 14.3361 8.84196 14.336 8.84169 14.3359L8.84241 14.3362Z" stroke="#C7CCD0" stroke-width="1.78947"/>
									</svg>
								</div>
							</h3>
							<div class="market-topset-item-info-description"
								 :title="appItem.SHORT_DESC"
								 v-html="appItem.SHORT_DESC"
							></div>
							<div class="market-topset-item-info">
								<div class="market-rating__container">
									<div class="market-rating__stars" v-if="!isSiteTemplate(appItem)">
										<svg class="market-rating__star"
											 :class="{'--active': isActiveStar(1, getAppRating(appItem.RATING))}"
											 width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M7.53505 3.17539C7.70176 2.75395 8.29824 2.75395 8.46495 3.17539L9.55466 5.93021C9.62451 6.1068 9.78837 6.22857 9.97761 6.24452L12.8494 6.4866C13.2857 6.52338 13.4673 7.06336 13.142 7.35636L10.9179 9.35965C10.7833 9.48081 10.7248 9.66523 10.7649 9.84179L11.4379 12.8084C11.5369 13.2448 11.0566 13.5815 10.6801 13.3397L8.27019 11.792C8.10558 11.6863 7.89442 11.6863 7.72981 11.792L5.31993 13.3397C4.94338 13.5815 4.46312 13.2448 4.56213 12.8084L5.23514 9.84179C5.27519 9.66523 5.21667 9.48081 5.08215 9.35965L2.85797 7.35636C2.53266 7.06336 2.71434 6.52338 3.15059 6.4866L6.02239 6.24452C6.21163 6.22857 6.37549 6.1068 6.44534 5.93021L7.53505 3.17539Z"/>
										</svg>
										<svg class="market-rating__star"
											 :class="{'--active': isActiveStar(2, getAppRating(appItem.RATING))}"
											 width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M7.53505 3.17539C7.70176 2.75395 8.29824 2.75395 8.46495 3.17539L9.55466 5.93021C9.62451 6.1068 9.78837 6.22857 9.97761 6.24452L12.8494 6.4866C13.2857 6.52338 13.4673 7.06336 13.142 7.35636L10.9179 9.35965C10.7833 9.48081 10.7248 9.66523 10.7649 9.84179L11.4379 12.8084C11.5369 13.2448 11.0566 13.5815 10.6801 13.3397L8.27019 11.792C8.10558 11.6863 7.89442 11.6863 7.72981 11.792L5.31993 13.3397C4.94338 13.5815 4.46312 13.2448 4.56213 12.8084L5.23514 9.84179C5.27519 9.66523 5.21667 9.48081 5.08215 9.35965L2.85797 7.35636C2.53266 7.06336 2.71434 6.52338 3.15059 6.4866L6.02239 6.24452C6.21163 6.22857 6.37549 6.1068 6.44534 5.93021L7.53505 3.17539Z"/>
										</svg>
										<svg class="market-rating__star"
											 :class="{'--active': isActiveStar(3, getAppRating(appItem.RATING))}"
											 width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M7.53505 3.17539C7.70176 2.75395 8.29824 2.75395 8.46495 3.17539L9.55466 5.93021C9.62451 6.1068 9.78837 6.22857 9.97761 6.24452L12.8494 6.4866C13.2857 6.52338 13.4673 7.06336 13.142 7.35636L10.9179 9.35965C10.7833 9.48081 10.7248 9.66523 10.7649 9.84179L11.4379 12.8084C11.5369 13.2448 11.0566 13.5815 10.6801 13.3397L8.27019 11.792C8.10558 11.6863 7.89442 11.6863 7.72981 11.792L5.31993 13.3397C4.94338 13.5815 4.46312 13.2448 4.56213 12.8084L5.23514 9.84179C5.27519 9.66523 5.21667 9.48081 5.08215 9.35965L2.85797 7.35636C2.53266 7.06336 2.71434 6.52338 3.15059 6.4866L6.02239 6.24452C6.21163 6.22857 6.37549 6.1068 6.44534 5.93021L7.53505 3.17539Z"/>
										</svg>
										<svg class="market-rating__star"
											 :class="{'--active': isActiveStar(4, getAppRating(appItem.RATING))}"
											 width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M7.53505 3.17539C7.70176 2.75395 8.29824 2.75395 8.46495 3.17539L9.55466 5.93021C9.62451 6.1068 9.78837 6.22857 9.97761 6.24452L12.8494 6.4866C13.2857 6.52338 13.4673 7.06336 13.142 7.35636L10.9179 9.35965C10.7833 9.48081 10.7248 9.66523 10.7649 9.84179L11.4379 12.8084C11.5369 13.2448 11.0566 13.5815 10.6801 13.3397L8.27019 11.792C8.10558 11.6863 7.89442 11.6863 7.72981 11.792L5.31993 13.3397C4.94338 13.5815 4.46312 13.2448 4.56213 12.8084L5.23514 9.84179C5.27519 9.66523 5.21667 9.48081 5.08215 9.35965L2.85797 7.35636C2.53266 7.06336 2.71434 6.52338 3.15059 6.4866L6.02239 6.24452C6.21163 6.22857 6.37549 6.1068 6.44534 5.93021L7.53505 3.17539Z"/>
										</svg>
										<svg class="market-rating__star"
											 :class="{'--active': isActiveStar(5, getAppRating(appItem.RATING))}"
											 width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M7.53505 3.17539C7.70176 2.75395 8.29824 2.75395 8.46495 3.17539L9.55466 5.93021C9.62451 6.1068 9.78837 6.22857 9.97761 6.24452L12.8494 6.4866C13.2857 6.52338 13.4673 7.06336 13.142 7.35636L10.9179 9.35965C10.7833 9.48081 10.7248 9.66523 10.7649 9.84179L11.4379 12.8084C11.5369 13.2448 11.0566 13.5815 10.6801 13.3397L8.27019 11.792C8.10558 11.6863 7.89442 11.6863 7.72981 11.792L5.31993 13.3397C4.94338 13.5815 4.46312 13.2448 4.56213 12.8084L5.23514 9.84179C5.27519 9.66523 5.21667 9.48081 5.08215 9.35965L2.85797 7.35636C2.53266 7.06336 2.71434 6.52338 3.15059 6.4866L6.02239 6.24452C6.21163 6.22857 6.37549 6.1068 6.44534 5.93021L7.53505 3.17539Z"/>
										</svg>
										<span class="market-rating__stars-amount"
											  v-if="appItem.REVIEWS_NUMBER"
										>({{ appItem.REVIEWS_NUMBER }})</span>
									</div>
									<div class="market-rating__download">
										<span class="market-rating__download-icon"></span>
										<div class="market-rating__download-amount">{{ appItem.NUM_INSTALLS }}</div>
									</div>
								</div>
							</div>

							<template v-if="false">
								<a class="market-topset-item-info-owner"
								   v-if="appItem.PARTNER_URL"
								   :href="appItem.PARTNER_URL"
								   :title="appItem.PARTNER_NAME"
								   target="_blank"
								>
									{{ appItem.PARTNER_NAME }}
								</a>
								<span class="market-topset-item-info-owner"
									  :title="appItem.PARTNER_NAME"
									  v-else
								>
								{{ appItem.PARTNER_NAME }}
							</span>
							</template>
						</div>
					</div>
				</div>
			</div>
		</div>
	`
	};

	exports.CollectionTop = CollectionTop;

}((this.BX.Market = this.BX.Market || {}),BX.Market,BX.Vue3.Pinia));
