import { Runtime } from 'main.core';
import { shallowRef } from 'ui.vue3';
import { BannerDispatcher } from 'ui.banner-dispatcher';

import { Resolvable } from 'booking.lib.resolvable';
import { AhaMoment } from 'booking.const';
import { ahaMoments } from 'booking.lib.aha-moments';

export const Banner = {
	data(): Object
	{
		return {
			isBannerShown: false,
			bannerComponent: null,
		};
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

				this.bannerClosed = new Resolvable();
				await this.bannerClosed;

				onDone();
			});
		},
		closeBanner(): void
		{
			this.isBannerShown = false;

			this.setShown();

			this.bannerClosed.resolve();
		},
		setShown(): void
		{
			ahaMoments.setShown(AhaMoment.Banner);
		},
	},
	template: `
		<component v-if="isBannerShown" :is="bannerComponent" @setShown="setShown" @close="closeBanner"/>
	`,
};
