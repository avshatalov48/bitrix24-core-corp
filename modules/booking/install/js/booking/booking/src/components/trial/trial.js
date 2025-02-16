import { AutoLauncher } from 'ui.auto-launch';
import { mapGetters } from 'ui.vue3.vuex';
import { Runtime } from 'main.core';
import { shallowRef } from 'ui.vue3';
import { BannerDispatcher } from 'ui.banner-dispatcher';

import { Resolvable } from 'booking.lib.resolvable';
import { AhaMoment, Model } from 'booking.const';
import { ahaMoments } from 'booking.lib.aha-moments';

export const Trial = {
	data(): Object
	{
		return {
			isBannerShown: false,
			bannerComponent: null,
		};
	},
	watch: {
		isShownTrialPopup(): void
		{
			if (ahaMoments.shouldShow(AhaMoment.TrialBanner))
			{
				if (!AutoLauncher.isEnabled())
				{
					AutoLauncher.enable();
				}

				void this.showBanner();
			}
		},
	},
	computed: {
		...mapGetters({
			isShownTrialPopup: `${Model.Interface}/isShownTrialPopup`,
		}),
	},
	methods: {
		async showBanner(): Promise<void>
		{
			BannerDispatcher.critical.toQueue(async (onDone) => {
				const { TrialBanner } = await Runtime.loadExtension('booking.component.trial-banner');

				this.bannerComponent = shallowRef(TrialBanner);
				this.isBannerShown = true;

				this.bannerClosed = new Resolvable();
				await this.bannerClosed;

				onDone();
			});
		},
		closeBanner(): void
		{
			this.isBannerShown = false;

			ahaMoments.setShown(AhaMoment.TrialBanner);

			this.bannerClosed.resolve();
		},
	},
	template: `
		<component v-if="isBannerShown" :is="bannerComponent" @close="closeBanner"/>
	`,
};
