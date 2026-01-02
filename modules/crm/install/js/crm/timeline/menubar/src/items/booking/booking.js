import { Dom, Loc, Runtime } from 'main.core';
import { BitrixVue } from 'ui.vue3';
import { BannerDispatcher, Priority } from 'crm.integration.ui.banner-dispatcher';
import { TourManager } from 'crm.tour-manager';
import Item from '../../item';
import Tour from './tour';
import Options from './options';
import { Resolvable } from './util';
import BannerAnalytics from './analytic';

interface AhaParams {
	itemCode: string,
	title: string,
	body: string,
}

export default class Booking extends Item
{
	#bannerDispatcher: BannerDispatcher;

	constructor()
	{
		super();

		this.#bannerDispatcher = new BannerDispatcher();
	}

	showSlider(): void
	{
		const entities = this.getSettings()?.entities;
		const embedEntities = JSON.stringify(entities);

		const url = `/booking/?embed=${embedEntities}`;

		if (BX.SidePanel)
		{
			BX.SidePanel.Instance.open(url, {
				customLeftBoundary: 0,
			});
		}
		else
		{
			window.open(url);
		}
	}

	supportsLayout(): Boolean
	{
		return false;
	}

	showTour(): void
	{
		this.#showBanner();
		this.#showAha();
	}

	#showBanner(): void
	{
		if (!this.#shouldShowBanner())
		{
			return;
		}

		this.#bannerDispatcher.toQueue(
			async (onDone: Function): void => {
				const bannerClosed = this.#renderBannerComponent();
				this.#setBannerShown();
				await bannerClosed;

				onDone();
			},
			Priority.CRITICAL,
		);
	}

	async #renderBannerComponent(): Promise
	{
		const bannerClosed = new Resolvable();
		const app = await this.#createBannerComponent(bannerClosed.resolve);
		const content = Dom.create('div');
		Dom.append(content, document.body);
		app.mount(content);

		return bannerClosed;
	}

	async #createBannerComponent(onBannerClose: Function): Promise
	{
		const { PromoBanner } = await Runtime.loadExtension('booking.component.promo-banner');
		const app = BitrixVue.createApp({
			components: { PromoBanner },
			data(): Object
			{
				return {
					isBannerShown: true,
				};
			},
			methods: {
				onClose(): void
				{
					this.isBannerShown = false;
					onBannerClose();
				},
				buttonClick(): void
				{
					BannerAnalytics.sendClickEnable();
				},
			},
			template: '<PromoBanner v-if="isBannerShown" type="crm" @buttonClick="buttonClick" @close="onClose" />',
		});
		app.mixin({
			methods: {
				loc(name: string, replacements?: { [key: string]: string }): string
				{
					return Loc.getMessage(name, replacements);
				},
			},
		});

		return app;
	}

	#shouldShowBanner(): Boolean
	{
		return this.getSettings()?.shouldShowBanner || false;
	}

	#setBannerShown(): void
	{
		new Options().saveUserOption(Options.START_BANNER);
		BannerAnalytics.sendShowPopup();
	}

	#showAha(): void
	{
		if (!this.#shouldShowAha())
		{
			return;
		}

		const ahaParams = this.#getAhaParams();
		if (!ahaParams)
		{
			return;
		}

		const guideBindElement = document
			.querySelector('.main-buttons-inner-container .main-buttons-item[data-id="booking"]');
		if (!guideBindElement)
		{
			return;
		}

		const tour = new Tour({
			...ahaParams,
			guideBindElement,
		});
		tour.getGuide().getPopup().setAutoHide(true);

		TourManager.getInstance().registerWithLaunch(tour);
	}

	#shouldShowAha(): Boolean
	{
		return Boolean(this.getSettings()?.ahaMoments.length);
	}

	#getAhaParams(): AhaParams
	{
		switch (this.getSettings()?.ahaMoments[0])
		{
			case Options.BEFORE_FIRST_RESOURCE_AHA_OPTION_NAME:
				return this.#getBeforeFirstResourceAhaParams();
			case Options.AFTER_FIRST_RESOURCE_AHA_OPTION_NAME:
				return this.#getAfterFirstResourceAhaParams();
			default:
				return null;
		}
	}

	#getBeforeFirstResourceAhaParams(): AhaParams
	{
		return {
			itemCode: 'booking_before_first_resource',
			title: Loc.getMessage('CRM_TIMELINE_BOOKING_AHA_BEFORE_RESOURCE_TITLE'),
			text: Loc.getMessage('CRM_TIMELINE_BOOKING_AHA_BEFORE_RESOURCE_TEXT'),
			userOptionName: Options.BEFORE_FIRST_RESOURCE_AHA_OPTION_NAME,
			articleCode: 23712054,
		};
	}

	#getAfterFirstResourceAhaParams(): AhaParams
	{
		return {
			itemCode: 'booking_after_first_resource',
			title: Loc.getMessage('CRM_TIMELINE_BOOKING_AHA_AFTER_RESOURCE_TITLE'),
			text: Loc.getMessage('CRM_TIMELINE_BOOKING_AHA_AFTER_RESOURCE_TEXT'),
			userOptionName: Options.AFTER_FIRST_RESOURCE_AHA_OPTION_NAME,
		};
	}
}
