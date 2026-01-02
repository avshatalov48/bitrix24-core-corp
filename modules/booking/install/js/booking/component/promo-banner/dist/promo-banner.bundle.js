/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_iconSet_api_vue,ui_iconSet_main,ui_iconSet_actions,booking_provider_service_mainPageService,booking_component_popup,booking_component_button) {
	'use strict';

	const PromoBanner = {
	  emits: ['setShown', 'close', 'buttonClick'],
	  props: {
	    canTurnOnDemo: {
	      type: Boolean,
	      default: false
	    },
	    type: {
	      type: String
	    }
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      btnClocking: false
	    };
	  },
	  computed: {
	    popupId() {
	      return 'booking-promo-banner-popup';
	    },
	    config() {
	      return {
	        width: 760,
	        padding: 0,
	        autoHide: false,
	        overlay: true,
	        animation: 'fading-slide',
	        borderRadius: 'var(--ui-border-radius-3xl)'
	      };
	    },
	    videoSrc() {
	      return '/bitrix/js/booking/component/promo-banner/videos/booking.webm';
	    },
	    title() {
	      if (this.type === 'crm') {
	        return this.loc('BOOKING_PROMO_BANNER_TITLE_CRM');
	      }
	      return this.loc('BOOKING_PROMO_BANNER_TITLE');
	    },
	    intro() {
	      if (this.type === 'crm') {
	        return this.loc('BOOKING_PROMO_BANNER_SUBTITLE_CRM');
	      }
	      return this.loc('BOOKING_PROMO_BANNER_SUBTITLE');
	    },
	    listItems() {
	      if (this.type === 'crm') {
	        return [this.loc('BOOKING_PROMO_BANNER_ITEM_1_CRM'), this.loc('BOOKING_PROMO_BANNER_ITEM_2_CRM')];
	      }
	      return [this.loc('BOOKING_PROMO_BANNER_ITEM_1'), this.loc('BOOKING_PROMO_BANNER_ITEM_2'), this.loc('BOOKING_PROMO_BANNER_ITEM_3'), this.loc('BOOKING_PROMO_BANNER_ITEM_4'), this.loc('BOOKING_PROMO_BANNER_ITEM_5')];
	    },
	    callText() {
	      if (this.type === 'crm') {
	        return this.loc('BOOKING_PROMO_BANNER_CALL_TEXT_CRM');
	      }
	      return null;
	    },
	    startBtnText() {
	      if (this.type === 'crm') {
	        return this.loc('BOOKING_PROMO_BANNER_BUTTON_START_CRM');
	      }
	      return this.canTurnOnDemo ? this.loc('BOOKING_PROMO_BANNER_BUTTON_START_DEMO').replace('#days#', 15) : this.loc('BOOKING_PROMO_BANNER_BUTTON_START');
	    }
	  },
	  methods: {
	    async activateDemo() {
	      this.btnClocking = true;
	      await booking_provider_service_mainPageService.mainPageService.activateDemo();
	      this.$emit('setShown');

	      // waiting notification about new demo
	      setTimeout(() => {
	        window.location.reload();
	      }, 4000);
	    },
	    async closeDemo() {
	      window.location.href = '/';
	    },
	    async close() {
	      this.$emit('close');
	    },
	    buttonClickHandler() {
	      this.$emit('buttonClick');
	      return this.canTurnOnDemo ? this.activateDemo() : this.close();
	    },
	    iconClickHandler() {
	      return this.canTurnOnDemo ? this.closeDemo() : this.close();
	    }
	  },
	  components: {
	    Popup: booking_component_popup.Popup,
	    Button: booking_component_button.Button,
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  template: `
		<Popup
			:id="popupId"
			:config="config"
			@close="close"
		>
			<div class="booking-promo-banner-popup">
				<div class="booking-promo-banner-popup-title">
					{{ title }}
				</div>
				<div class="booking-promo-banner-popup-body">
					<div class="booking-promo-banner-popup-info">
						<div class="booking-promo-banner-popup-subtitle" v-html="intro">
						</div>
						<template v-for="(item, index) of listItems" :key="index">
							<div class="booking-promo-banner-popup-item">
								<Icon :name="IconSet.CIRCLE_CHECK"/>
								<span v-html="item"></span>
							</div>
						</template>
						<div class="booking-promo-banner-popup-item --without-bullet" :if="callText" style="margin-top: 20px;">
							<span>{{ callText }}</span>
						</div>
					</div>
					<div class="booking-promo-banner-popup-video-container">
						<video
							class="booking-promo-banner-popup-video"
							:src="videoSrc"
							muted
							autoplay
							loop
							preload
						></video>
					</div>
				</div>
				<Button
					:class="{'booking-promo-banner-popup-button': !btnClocking}"
					:text="startBtnText"
					:size="ButtonSize.MEDIUM"
					:color="ButtonColor.SUCCESS"
					:clocking="btnClocking"
					@click="buttonClickHandler"
				/>
				<Icon
					class="booking-promo-banner-popup-cross"
					:name="IconSet.CROSS_40"
					@click="iconClickHandler"
				/>
			</div>
		</Popup>
	`
	};

	exports.PromoBanner = PromoBanner;

}((this.BX.Booking.Component = this.BX.Booking.Component || {}),BX.UI.IconSet,BX,BX,BX.Booking.Provider.Service,BX.Booking.Component,BX.Booking.Component));
//# sourceMappingURL=promo-banner.bundle.js.map
