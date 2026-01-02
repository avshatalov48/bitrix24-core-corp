import { Builder, Dictionary } from 'crm.integration.analytics';
import { BannerDispatcher, Priority } from 'crm.integration.ui.banner-dispatcher';
import { Dom, Extension, Loc, Tag } from 'main.core';
import type { PopupOptions } from 'main.popup';
import { Popup } from 'main.popup';
import { sendData } from 'ui.analytics';
import { AirButtonStyle, Button as UiButton } from 'ui.buttons';
import { Icon, Outline as OutlineIconSet } from 'ui.icon-set.api.core';
import 'ui.icon-set.actions';
import 'ui.design-tokens';
import 'ui.design-tokens.air';
import './onboarding-popup.css';

const MAX_STEP_NUMBER = 3;

type OnboardingPopupOptions = {
	closeOptionName: string;
	closeOptionCategory: string;
	analytics?: Object;
};

export class OnboardingPopup
{
	#bannerDispatcher: ?BannerDispatcher = null;
	#popup: ?Popup = null;
	#originalOverflowValue: ?string = null;
	#targetContainer: ?HTMLElement = null;
	#step: number = 0;
	#closeOptionName: ?string = null;
	#closeOptionCategory: ?string = null;
	#analytics: ?Object = null;

	constructor(params: OnboardingPopupOptions)
	{
		this.#closeOptionName = params?.closeOptionName ?? null;
		this.#closeOptionCategory = params?.closeOptionCategory ?? null;
		this.#analytics = params?.analytics ?? {};
	}

	show(): void
	{
		if (this.#getPopup().isShown())
		{
			return;
		}

		this.#bannerDispatcher = new BannerDispatcher();

		this.#bannerDispatcher.toQueue(
			(onDone: Function): void => {
				this.#setTargetOverflow('hidden');
				this.#getPopup().subscribe('onAfterClose', onDone);
				this.#getPopup().show();
			},
			Priority.CRITICAL,
		);
	}

	#getPopup(): Popup
	{
		if (this.#popup === null)
		{
			this.#popup = this.#createPopup();
		}

		return this.#popup;
	}

	#createPopup(): Popup
	{
		return new Popup(this.#getPopupParams());
	}

	#getPopupParams(): PopupOptions
	{
		return {
			id: 'crm_repeat_sale_onboarding_popup',
			targetContainer: this.#getTarget(),
			content: this.#getPopupContent(),
			cacheable: true,
			isScrollBlock: false,
			className: 'crm-repeat-sale-onboarding-popup',
			closeByEsc: true,
			closeIcon: true,
			padding: 16,
			width: 733,
			overlay: {
				opacity: 40,
				backgroundColor: '#000000',
			},
			animation: 'fading-slide',
			autoHide: false,
			events: {
				onFirstShow: () => {
					this.#sendViewAnalytics();
				},
				onclose: () => {
					this.#resetTargetOverflow();
					BX.userOptions.save(this.#closeOptionCategory, this.#closeOptionName, 'closed', 'Y');

					this.#sendCloseAnalytics();
				},
			},
		};
	}

	#getPopupContent(): HTMLElement
	{
		const icon = new Icon({
			size: 29,
			color: '#853AF5',
			icon: OutlineIconSet.COPILOT,
		});

		return Tag.render`
			<div class="crm-repeat-sale__onboarding-popup-container">
				<div class="crm-repeat-sale__onboarding-popup-title">
					${Loc.getMessage('CRM_REPEAT_SALE_ONBOARDING_TITLE')}
					${icon.render()}
				</div>
				<div class="crm-repeat-sale__onboarding-popup-video">
					<div>
						${this.#renderVideo()}
					</div>
				</div>
				<div class="crm-repeat-sale__onboarding-popup-content">
					${this.#getStepContent()}
				</div>
			</div>
		`;
	}

	#renderVideo(): HTMLElement
	{
		const videoElement = Tag.render`
			<video
				src="${this.#getVideoPath()}"
				autoplay
				preload
				loop
			></video>
		`;

		// eslint-disable-next-line @bitrix24/bitrix24-rules/no-native-events-binding
		videoElement.addEventListener('canplay', () => {
			videoElement.muted = true;
			videoElement.play();
		});

		return videoElement;
	}

	#getVideoPath(): string
	{
		const region = Extension.getSettings('crm.repeat-sale.onboarding-popup').get('region');

		let name = 'how-it-work-en';
		if (['kz', 'ru', 'by', 'uz'].includes(region))
		{
			name = 'how-it-work-ru';
		}

		return `/bitrix/js/crm/repeat-sale/onboarding-popup/video/${name}.webm`;
	}

	#getStepContent(): HTMLElement
	{
		return Tag.render`
			<div class="crm-repeat-sale__onboarding-popup-step-container">
				<div class="crm-repeat-sale__onboarding-popup-step-text">
					${Loc.getMessage(`CRM_REPEAT_SALE_ONBOARDING_TEXT_STEP_${this.#step}`)}
				</div>
				<div class="crm-repeat-sale__onboarding-popup-button">
					${this.#getButton().render()}
				</div>
			</div>
		`;
	}

	#getButton(): UiButton
	{
		const style = this.#isLastStep() ? AirButtonStyle.TINTED : AirButtonStyle.FILLED_COPILOT;
		const icon = this.#isLastStep() ? OutlineIconSet.CHECK_M : OutlineIconSet.NEXT;

		return new UiButton({
			useAirDesign: true,
			text: this.#getButtonText(),
			round: true,
			size: BX.UI.Button.Size.LARGE,
			icon,
			style,
			onclick: () => {
				if (this.#isLastStep())
				{
					this.#getPopup().close();
					this.#getPopup().destroy();
				}
				else
				{
					this.#goToNextStep();
				}
			},
		});
	}

	#getButtonText(): string
	{
		const code = (
			this.#isLastStep()
				? 'CRM_REPEAT_SALE_ONBOARDING_BUTTON_CLOSE'
				: 'CRM_REPEAT_SALE_ONBOARDING_BUTTON_NEXT'
		);

		return Loc.getMessage(code);
	}

	#isLastStep(): boolean
	{
		return this.#step === MAX_STEP_NUMBER;
	}

	#goToNextStep(): void
	{
		this.#step++;

		Dom.replace(
			document.querySelector('.crm-repeat-sale__onboarding-popup-step-container'),
			this.#getStepContent(),
		);

		this.#sendClickAnalytics();
	}

	#setTargetOverflow(value: string): void
	{
		this.#originalOverflowValue = Dom.style(this.#getTarget(), 'overflow');
		Dom.style(this.#getTarget(), 'overflow', value);
	}

	#resetTargetOverflow(): void
	{
		if (this.#originalOverflowValue === null)
		{
			return;
		}

		Dom.style(this.#getTarget(), 'overflow', this.#originalOverflowValue);
		this.#originalOverflowValue = null;
	}

	#getTarget(): HTMLElement
	{
		if (this.#targetContainer === null)
		{
			this.#targetContainer = document.body;
		}

		return this.#targetContainer;
	}

	#sendViewAnalytics(): void
	{
		this.#sendAnalytics('view');
	}

	#sendCloseAnalytics(): void
	{
		this.#sendAnalytics('close');
	}

	#sendClickAnalytics(): void
	{
		this.#sendAnalytics('click');
	}

	#sendAnalytics(eventName: string): void
	{
		const type = Dictionary.TYPE_REPEAT_SALE_BANNER_NULL;
		const subSection = this.#analytics.c_sub_section ?? Dictionary.SUB_SECTION_KANBAN;

		let instance = null;
		if (eventName === 'view')
		{
			instance = Builder.RepeatSale.Banner.ViewEvent.createDefault(type, subSection);
		}
		else if (eventName === 'close')
		{
			instance = Builder.RepeatSale.Banner.CloseEvent.createDefault(type, subSection);
		}
		else if (eventName === 'click')
		{
			instance = Builder.RepeatSale.Banner.ClickEvent.createDefault(type, subSection);
		}

		if (instance)
		{
			const section = this.#analytics.c_section ?? '';

			sendData(instance.setSection(section).buildData());
		}
	}
}