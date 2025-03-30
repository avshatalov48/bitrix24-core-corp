import { Tag, Dom, Type } from 'main.core';
import { Popup } from 'main.popup';
import { BitrixVue, VueCreateAppResult } from 'ui.vue3';
import { BannerDispatcher, Priority } from 'crm.integration.ui.banner-dispatcher';
import { PopupContent } from './components/popup-content';

declare type OnboardingPopupOptions = {
	closeOptionCategory: string,
	closeOptionName: string,
};

const HELPDESK_CODE = '23240636';

export class OnboardingPopup
{
	#popup: Popup = null;
	#bannerDispatcher: BannerDispatcher;
	#popupContentApp: VueCreateAppResult = null;
	#targetContainer: HTMLElement = null;
	#originalOverflowValue: ?string = null;

	#optionName: string;
	#optionCategory: string;

	#width: number = 868;
	#height: number = 482;

	constructor(options: OnboardingPopupOptions)
	{
		if (!Type.isStringFilled(options.closeOptionCategory))
		{
			throw new Error("Parameter 'options.closeOptionCategory' must be a filled string");
		}

		if (!Type.isStringFilled(options.closeOptionName))
		{
			throw new Error("Parameter 'options.closeOptionName' must be a filled string");
		}

		this.#optionCategory = options.closeOptionCategory;
		this.#optionName = options.closeOptionName;

		this.#bannerDispatcher = new BannerDispatcher();
	}

	show(): void
	{
		this.#setTargetOverflow('hidden');
		this.#bannerDispatcher.toQueue(
			(onDone: Function): void => {
				this.getPopup().subscribe('onAfterClose', onDone);
				this.getPopup().show();
			},
			Priority.CRITICAL,
		);
	}

	close(): void
	{
		this.getPopup().close();
	}

	save(): void
	{
		BX.userOptions.save(this.#optionCategory, this.#optionName, 'closed', 'Y');
	}

	getPopup(): Popup
	{
		if (this.#popup === null)
		{
			this.#popup = new Popup({
				targetContainer: this.#target(),
				className: 'crm-permissions-onboarding-popup',
				closeIcon: true,
				closeByEsc: false,
				cacheable: false,
				padding: 0,
				overlay: {
					opacity: 40,
					backgroundColor: '#000000',
				},
				content: this.#getContent(),
				width: this.#width,
				height: this.#height,
				animation: 'fading-slide',
				borderRadius: '24px',
				events: {
					onAfterClose: () => {
						this.#resetTargetOverflow();
						this.save();
					},
				},
			});
		}

		return this.#popup;
	}

	openHelpdesk(): void
	{
		if (top.BX.Helper)
		{
			top.BX.Helper.show(`redirect=detail&code=${HELPDESK_CODE}`);
		}
	}

	#getContent(): HTMLElement
	{
		const container = Tag.render`<div class="crm-permissions-onboarding-popup-content"></div>`;

		this.#popupContentApp = BitrixVue.createApp(PopupContent, { popup: this });
		this.#popupContentApp.mount(container);

		return container;
	}

	#setTargetOverflow(value: string): void
	{
		this.#originalOverflowValue = Dom.style(this.#target(), 'overflow');
		Dom.style(this.#target(), 'overflow', value);
	}

	#resetTargetOverflow(): void
	{
		if (this.#originalOverflowValue === null)
		{
			return;
		}

		Dom.style(this.#target(), 'overflow', this.#originalOverflowValue);
		this.#originalOverflowValue = null;
	}

	#target(): HTMLElement
	{
		if (this.#targetContainer === null)
		{
			this.#targetContainer = document.body;
		}

		return this.#targetContainer;
	}
}
