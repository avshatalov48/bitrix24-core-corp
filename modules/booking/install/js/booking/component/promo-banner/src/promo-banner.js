import type { PopupOptions } from 'main.popup';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';
import 'ui.icon-set.actions';
import { mainPageService } from 'booking.provider.service.main-page-service';
import { Popup } from 'booking.component.popup';
import { Button, ButtonColor, ButtonSize } from 'booking.component.button';
import './promo-banner.css';

export const PromoBanner = {
	emits: ['setShown', 'close', 'buttonClick'],
	props: {
		canTurnOnDemo: {
			type: Boolean,
			default: false,
		},
		type: {
			type: String,
		},
	},
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
		title(): string
		{
			if (this.type === 'crm')
			{
				return this.loc('BOOKING_PROMO_BANNER_TITLE_CRM');
			}

			return this.loc('BOOKING_PROMO_BANNER_TITLE');
		},
		intro(): string
		{
			if (this.type === 'crm')
			{
				return this.loc('BOOKING_PROMO_BANNER_SUBTITLE_CRM');
			}

			return this.loc('BOOKING_PROMO_BANNER_SUBTITLE');
		},
		listItems(): string[]
		{
			if (this.type === 'crm')
			{
				return [
					this.loc('BOOKING_PROMO_BANNER_ITEM_1_CRM'),
					this.loc('BOOKING_PROMO_BANNER_ITEM_2_CRM'),
				];
			}

			return [
				this.loc('BOOKING_PROMO_BANNER_ITEM_1'),
				this.loc('BOOKING_PROMO_BANNER_ITEM_2'),
				this.loc('BOOKING_PROMO_BANNER_ITEM_3'),
				this.loc('BOOKING_PROMO_BANNER_ITEM_4'),
				this.loc('BOOKING_PROMO_BANNER_ITEM_5'),
			];
		},
		callText(): string
		{
			if (this.type === 'crm')
			{
				return this.loc('BOOKING_PROMO_BANNER_CALL_TEXT_CRM');
			}

			return null;
		},
		startBtnText(): string
		{
			if (this.type === 'crm')
			{
				return this.loc('BOOKING_PROMO_BANNER_BUTTON_START_CRM');
			}

			return (
				this.canTurnOnDemo
					? this.loc('BOOKING_PROMO_BANNER_BUTTON_START_DEMO').replace('#days#', 15)
					: this.loc('BOOKING_PROMO_BANNER_BUTTON_START')
			);
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
		buttonClickHandler(): Function
		{
			this.$emit('buttonClick');

			return this.canTurnOnDemo ? this.activateDemo() : this.close();
		},
		iconClickHandler(): Function
		{
			return this.canTurnOnDemo ? this.closeDemo() : this.close();
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
	`,
};
