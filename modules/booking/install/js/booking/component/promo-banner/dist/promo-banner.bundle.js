/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_vue3_vuex,booking_const,ui_iconSet_api_vue,ui_iconSet_main,ui_iconSet_actions,booking_provider_service_mainPageService,booking_component_popup,booking_component_button) {
	'use strict';

	const PromoBanner = {
	  emits: ['setShown', 'close'],
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      btnClocking: false
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      canTurnOnDemo: `${booking_const.Model.Interface}/canTurnOnDemo`
	    }),
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
	    listItems() {
	      return [this.loc('BOOKING_PROMO_BANNER_ITEM_1'), this.loc('BOOKING_PROMO_BANNER_ITEM_2'), this.loc('BOOKING_PROMO_BANNER_ITEM_3'), this.loc('BOOKING_PROMO_BANNER_ITEM_4'), this.loc('BOOKING_PROMO_BANNER_ITEM_5')];
	    },
	    startBtnText() {
	      return this.canTurnOnDemo ? this.loc('BOOKING_PROMO_BANNER_BUTTON_START_DEMO').replace('#days#', 15) : this.loc('BOOKING_PROMO_BANNER_BUTTON_START');
	    },
	    buttonClickHandler() {
	      return this.canTurnOnDemo ? this.activateDemo : this.close;
	    },
	    iconClickHandler() {
	      return this.canTurnOnDemo ? this.closeDemo : this.close;
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
					{{ loc('BOOKING_PROMO_BANNER_TITLE') }}
				</div>
				<div class="booking-promo-banner-popup-body">
					<div class="booking-promo-banner-popup-info">
						<div class="booking-promo-banner-popup-subtitle">
							{{ loc('BOOKING_PROMO_BANNER_SUBTITLE') }}
						</div>
						<template v-for="(item, index) of listItems" :key="index">
							<div class="booking-promo-banner-popup-item">
								<Icon :name="IconSet.CIRCLE_CHECK"/>
								<span>{{ item }}</span>
							</div>
						</template>
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

}((this.BX.Booking.Component = this.BX.Booking.Component || {}),BX.Vue3.Vuex,BX.Booking.Const,BX.UI.IconSet,BX,BX,BX.Booking.Provider.Service,BX.Booking.Component,BX.Booking.Component));
//# sourceMappingURL=promo-banner.bundle.js.map
