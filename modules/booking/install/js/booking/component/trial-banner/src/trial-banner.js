import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import { Lottie } from 'ui.lottie';
import type { PopupOptions } from 'main.popup';
import { Button, ButtonColor, ButtonSize } from 'booking.component.button';
import { Popup } from 'booking.component.popup';
import Animation from './animation.json';
import './trial-banner.css';

export const TrialBanner = {
	emits: ['close'],
	components: {
		Popup,
		Button,
		Icon,
	},
	data(): Object
	{
		return {
			IconSet,
			ButtonSize,
			ButtonColor,
		};
	},
	mounted()
	{
		Lottie.loadAnimation({
			animationData: Animation,
			container: this.$refs.animationContainer,
			renderer: 'svg',
			loop: false,
			autoplay: true,
		});
	},
	computed: {
		popupId(): string
		{
			return 'booking-trial-banner-popup';
		},
		config(): PopupOptions
		{
			return {
				className: 'booking-trial-banner-popup',
				width: 560,
				height: 330,
				padding: 48,
				autoHide: false,
				overlay: true,
				animation: 'fading-slide',
				borderRadius: '12px',
				closeIcon: true,
			};
		},
	},
	methods: {
		close(): void
		{
			this.$emit('close');
		},
	},
	template: `
		<Popup
			:id="popupId"
			:config="config"
			@close="close"
		>
			<div class="booking-trial-banner-popup-container">
				<div class="booking-trial-banner-popup-content">
					<div
						class="booking-trial-banner-popup-title"
						v-html="loc('BOOKING_TRIAL_BANNER_TITLE')"
					>
					</div>
					<div
						class="booking-trial-banner-popup-text"
						v-html="loc('BOOKING_TRIAL_BANNER_TEXT')"
					>
					</div>
					<div
						class="booking-trial-banner-popup-text-trial"
						v-html="loc('BOOKING_TRIAL_BANNER_TRIAL_INFO').replace('#days#', '30')"
					>
					</div>
					<div class="booking-trial-banner-popup-button-container">
						<Button
							class="booking-trial-banner-popup-button"
							:text="loc('BOOKING_TRIAL_BANNER_BUTTON')"
							:size="ButtonSize.SMALL"
							:color="ButtonColor.PRIMARY"
							:round="true"
							@click="close"
						/>
					</div>
				</div>
				<div
					ref="animationContainer"
					class="booking-trial-banner-popup-image"
				></div>
			</div>
		</Popup>
	`,
};
