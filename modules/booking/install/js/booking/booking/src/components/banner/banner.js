import { Runtime } from 'main.core';
import { shallowRef } from 'ui.vue3';
import { mapGetters } from 'ui.vue3.vuex';
import { BannerDispatcher } from 'ui.banner-dispatcher';

import { AhaMoment, Model } from 'booking.const';
import { Resolvable } from 'booking.lib.resolvable';
import { ahaMoments } from 'booking.lib.aha-moments';
import { BannerAnalytics } from 'booking.lib.analytics';

export const Banner = {
	data(): Object
	{
		return {
			isBannerShown: false,
			bannerComponent: null,
		};
	},
	computed: {
		...mapGetters({
			canTurnOnDemo: `${Model.Interface}/canTurnOnDemo`,
		}),
	},
	mounted(): void
	{
		if (ahaMoments.shouldShow(AhaMoment.Banner))
		{
			void this.showBanner();
		}
	},
	methods: {
		async showBanner(): Promise<void>
		{
			BannerDispatcher.critical.toQueue(async (onDone) => {
				const { PromoBanner } = await Runtime.loadExtension('booking.component.promo-banner');

				this.bannerComponent = shallowRef(PromoBanner);
				this.isBannerShown = true;
				this.setShown();

				this.bannerClosed = new Resolvable();
				await this.bannerClosed;

				onDone();
			});
		},
		closeBanner(): void
		{
			this.isBannerShown = false;
			this.bannerClosed.resolve();
		},
		setShown(): void
		{
			ahaMoments.setShown(AhaMoment.Banner);
			BannerAnalytics.sendShowPopup();
		},
		buttonClick(): void
		{
			BannerAnalytics.sendClickEnable();
		},
	},
	template: `
		<component
			v-if="isBannerShown"
			:is="bannerComponent"
			:canTurnOnDemo="canTurnOnDemo"
			@buttonClick="buttonClick"
			@close="closeBanner"
		/>
	`,
};
