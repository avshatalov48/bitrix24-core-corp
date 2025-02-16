import { mapGetters } from 'ui.vue3.vuex';
import { Model } from 'booking.const';
import type { PopupOptions } from 'main.popup';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';
import 'ui.icon-set.actions';
import { mainPageService } from 'booking.provider.service.main-page-service';
import { Popup } from 'booking.component.popup';
import { Button, ButtonColor, ButtonSize } from 'booking.component.button';
import './promo-banner.css';

export const PromoBanner = {
	emits: ['setShown', 'close'],
	data(): Object
	{
		return {
			IconSet,
			ButtonSize,
			ButtonColor,
			btnClocking: false,
		};
	},
	computed: {
		...mapGetters({
			canTurnOnDemo: `${Model.Interface}/canTurnOnDemo`,
		}),
		popupId(): string
		{
			return 'booking-promo-banner-popup';
		},
		config(): PopupOptions
		{
			return {
				width: 760,
				padding: 0,
				autoHide: false,
				overlay: true,
				animation: 'fading-slide',
				borderRadius: 'var(--ui-border-radius-3xl)',
			};
		},
		videoSrc(): string
		{
			return '/bitrix/js/booking/component/promo-banner/videos/booking.webm';
		},
		listItems(): string[]
		{
			return [
				this.loc('BOOKING_PROMO_BANNER_ITEM_1'),
				this.loc('BOOKING_PROMO_BANNER_ITEM_2'),
				this.loc('BOOKING_PROMO_BANNER_ITEM_3'),
				this.loc('BOOKING_PROMO_BANNER_ITEM_4'),
				this.loc('BOOKING_PROMO_BANNER_ITEM_5'),
			];
		},
		startBtnText(): string
		{
			return (
				this.canTurnOnDemo
					? this.loc('BOOKING_PROMO_BANNER_BUTTON_START_DEMO').replace('#days#', 15)
					: this.loc('BOOKING_PROMO_BANNER_BUTTON_START')
			);
		},
		buttonClickHandler(): Function
		{
			return this.canTurnOnDemo ? this.activateDemo : this.close;
		},
		iconClickHandler(): Function
		{
			return this.canTurnOnDemo ? this.closeDemo : this.close;
		},
	},
	methods: {
		async activateDemo(): void
		{
			this.btnClocking = true;

			await mainPageService.activateDemo();

			this.$emit('setShown');

			// waiting notification about new demo
			setTimeout(() => {
				window.location.reload();
			}, 4000);
		},
		async closeDemo(): void
		{
			window.location.href = '/';
		},
		async close(): void
		{
			this.$emit('close');
		},
	},
	components: {
		Popup,
		Button,
		Icon,
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
	`,
};
