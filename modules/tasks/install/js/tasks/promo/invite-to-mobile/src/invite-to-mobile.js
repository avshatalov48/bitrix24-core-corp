import { Loc } from 'main.core';
import { BannerDispatcher } from 'ui.banner-dispatcher';
import { MobilePromoter } from 'ui.mobile-promoter';

type RawInvitationToMobileData = {
	appLink: string,
}

export class InviteToMobile
{
	#appLink: string;

	constructor(data: RawInvitationToMobileData)
	{
		this.#appLink = data.appLink;
	}

	show(): void
	{
		BannerDispatcher.normal.toQueue((onDone) => {
			const promoter = this.#createMobilePromoter();
			const popup = promoter.getPopup();

			popup.subscribe('onAfterClose', (event) => {
				onDone();
			});

			popup.show();
		});
	}

	#createMobilePromoter(): MobilePromoter
	{
		return new MobilePromoter({
			title: Loc.getMessage('TASKS_PROMO_INVITE_TO_MOBILE_TITLE'),
			position: {
				right: 83,
				bottom: 46,
			},
			qrContent: this.#appLink,
			analytics: { c_section: 'tasks' },
		});
	}
}
