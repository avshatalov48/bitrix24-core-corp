import { Dom, Loc, Tag, Type } from 'main.core';
import { Popup } from 'main.popup';
import { Guide, Backend, type StepOption } from 'sign.tour';
import { B2EOnboardingSignSettings } from 'sign.v2.b2e.sign-settings-onboarding';
import { Api } from 'sign.v2.api';
import { BannerDispatcher } from 'ui.banner-dispatcher';
import { Icon, Actions as ActionsIconSet } from 'ui.icon-set.api.core';
import { Button } from 'ui.buttons';
import 'ui.design-tokens';
import './style.css';
import b2eWelcomeGif from './video/b2e_welcome.gif';

type WelcomeGuideOptions = {
	region: string,
	byEmployeeEnabled: boolean,
	hasCompanies?: boolean,
	showTariffSlider?: boolean,
	canEditDocument?: boolean,
	canCreateDocument?: boolean,
}

type BannerOptions = {
	hasCompanies: boolean,
	showTariffSlider: boolean,
}

const b2bHelpdeskCode = 16571388;
const b2eCreateHelpdeskCode = 20338910;
const b2eTemplatesHelpdeskCode = 24354462;

const b2eWelcomeTourId = 'sign-b2e-onboarding-tour-id';
const b2eTestSigningWelcomeTourId = 'sign-b2e-onboarding-tour-id-test-signing';

export class Onboarding
{
	#api: Api = new Api();
	#backend: Backend = new Backend();

	async startB2eWelcomeOnboarding(options: WelcomeGuideOptions): Promise<void>
	{
		const tourId = b2eWelcomeTourId;

		const startOnboarding = await this.#shouldStartB2eOnboarding(tourId);
		if (!startOnboarding)
		{
			return;
		}

		BannerDispatcher.high.toQueue((onDone) => {
			const guide = this.#getB2eWelcomeGuide(tourId, options, onDone);
			const welcomePopup = this.#createB2eWelcomePopup(guide);
			this.#backend.saveVisit(tourId);
			welcomePopup.show();
		});
	}

	async startB2eWelcomeOnboardingWithTestSigning(options: WelcomeGuideOptions): Promise<void>
	{
		const tourId = b2eTestSigningWelcomeTourId;

		const startOnboarding = await this.#shouldStartB2eOnboarding(tourId, true);
		if (!startOnboarding)
		{
			return;
		}

		BannerDispatcher.high.toQueue((onDone) => {
			const guide = this.#getB2eWelcomeGuide(tourId, options, onDone);
			const welcomePopup = this.#createB2eWelcomePopupWithTestSigning(guide, options);
			this.#backend.saveVisit(tourId);
			welcomePopup.show();
		});
	}

	showTestSigningBanner(options: BannerOptions): void
	{
		const header = document.querySelector('header.page__header');
		if (header)
		{
			const signButton = new Button({
				color: Button.Color.PRIMARY,
				size: Button.Size.MEDIUM,
				round: true,
				noCaps: true,
				useAirDesign: true,
				style: Button.AirStyle.FILL,
				text: Loc.getMessage('SIGN_ONBOARDING_B2E_BANNER_BTN_SIGN_TEST_TEXT'),
				className: `sign__b2e-onboarding-signing-test-banner-button ${options.showTariffSlider
					? 'sign-b2e-js-tarriff-slider-trigger'
					: ''
				}`,
				events: options.showTariffSlider ? {} : {
					click: () => {
						this.#openSigningSlider(options);
					},
				},
			});

			const onboardingBanner = Tag.render`
				<div class="sign__b2e-onboarding-signing-test-banner">
					<div class="sign__onboarding-banner-content_img"></div>
					<div class="sign__b2e-onboarding-signing-test-banner_content">
						<div class="sign__b2e-onboarding-signing-test-banner-title-container">
							<div class="sign__b2e-onboarding-signing-test-banner-title">
								${Loc.getMessage('SIGN_ONBOARDING_B2E_BANNER_TITLE_SIGN_TEST_TEXT')}
							</div>
							<button
									class="sign__b2e-onboarding-signing-test-banner_close_btn"
									onclick="${() => this.#showCloseOnboardingSigningWarningPopup()}">
							</button>
						</div>
						<div class="sign__b2e-onboarding-signing-test-banner-title-description">
						${Loc.getMessage('SIGN_ONBOARDING_B2E_BANNER_DESCRIPTION_SIGN_TEST_TEXT')}
						</div>
					</div>
				</div>
			`;

			Dom.append(signButton.render(), onboardingBanner.querySelector('.sign__b2e-onboarding-signing-test-banner_content'));
			header.insertAdjacentElement('afterend', onboardingBanner);
		}
	}

	#showCloseOnboardingSigningWarningPopup(): void
	{
		const popupContent = Tag.render`
			<div class="sign__b2e-close-onboarding-signing-warning-popup-content">
				${Loc.getMessage('SIGN_ONBOARDING_B2E_CLOSE_BANNER_WARNING_POPUP_CONTENT')}
			</div>
		`;

		const popup = new Popup({
			id: 'sign__b2e-close-onboarding-signing-banner-warning-popup',
			content: popupContent,
			minHeigh: 180,
			width: 480,
			padding: 20,
			contentColor: 'white',
			overlay: true,
			closeByEsc: true,
			closeIcon: true,
			buttons: [
				new Button({
					id: 'sign__b2e-close-onboarding-signing-banner-warning-popup-confirm-button',
					text: Loc.getMessage('SIGN_ONBOARDING_B2E_CLOSE_BANNER_WARNING_POPUP_CONFIRM_BUTTON'),
					color: Button.Color.PRIMARY,
					events: {
						click: () => {
							popup.close();
							const banner = document.querySelector('.sign__b2e-onboarding-signing-test-banner');
							if (banner)
							{
								banner.remove();
								this.#api.hideOnboardingSigningBanner();
							}
						},
					},
				}),
				new Button({
					id: 'sign__b2e-close-onboarding-signing-banner-warning-popup-cancel-button',
					text: Loc.getMessage('SIGN_ONBOARDING_B2E_CLOSE_BANNER_WARNING_POPUP_CANCEL_BUTTON'),
					color: Button.Color.LINK,
					events: {
						click() {
							popup.close();
						},
					},
				}),
			],
		});
		popup.show();
	}

	#getB2eWelcomeGuide(tourId: string, options: OnboardingOptions, onFinish: ?Function): Guide
	{
		return new Guide({
			id: tourId,
			autoSave: true,
			simpleMode: false,
			events: {
				onFinish,
			},
			steps: [
				this.#createB2eNewDocumentButtonStep('.ui-toolbar-after-title-buttons > .sign-b2e-onboarding-create', options.region),
				...(options.byEmployeeEnabled ? [this.#createB2eKanbanRouteStep('.ui-toolbar-after-title-buttons > .sign-b2e-onboarding-route')] : []),
				this.#createB2eTemplatesStep(
					this.#isTemplateBtnVisible()
						? 'div#sign_sign_b2e_employee_template_list'
						: 'div#sign_more_button',
				),
			],
		});
	}

	getB2bGuide(target: string | HTMLElement): Guide
	{
		return new Guide({
			id: 'sign-tour-guide-sign-start-kanban',
			autoSave: true,
			simpleMode: true,
			steps: [
				{
					target,
					title: Loc.getMessage('SIGN_ONBOARDING_B2B_BTN_TITLE'),
					text: Loc.getMessage('SIGN_ONBOARDING_B2B_BTN_TEXT'),
					article: b2bHelpdeskCode,
				},
			],
		});
	}

	#createB2eWelcomePopupWithTestSigning(guide: Guide, options: OnboardingOptions): Popup
	{
		const popupTitle = Loc.getMessage('SIGN_ONBOARDING_B2E_WELCOME_POPUP_TITLE');
		const buttons = [];

		if (options.canEditDocument && options.canCreateDocument)
		{
			buttons.push(new Button({
				id: 'sign__b2e-onboarding-welcome-popup_sign__onboarding-signing-popup-button',
				color: Button.Color.PRIMARY,
				size: Button.Size.MEDIUM,
				round: true,
				noCaps: true,
				useAirDesign: true,
				style: Button.AirStyle.FILL,
				text: Loc.getMessage('SIGN_ONBOARDING_B2E_WELCOME_POPUP_BTN_SIGN_TEST_TEXT'),
				className: `sign__b2e-onboarding-welcome-popup_sign__onboarding-popup-button ${options.showTariffSlider ? 'sign-b2e-js-tarriff-slider-trigger' : ''}`,
				events: options.showTariffSlider ? {} : {
					click: () => {
						popup.close();
						this.#openSigningSlider(options);
					},
				},
			}));
		}

		buttons.push(new Button({
			id: 'sign__b2e-onboarding-welcome-popup_sign__onboarding-tour-popup-button',
			color: Button.Color.PRIMARY,
			size: Button.Size.MEDIUM,
			round: true,
			noCaps: true,
			useAirDesign: true,
			style: Button.AirStyle.OUTLINE,
			text: Loc.getMessage('SIGN_ONBOARDING_B2E_WELCOME_POPUP_BTN_TEXT_RU'),
			className: 'sign__b2e-onboarding-welcome-popup_sign__onboarding-popup-button',
			events: {
				click() {
					popup.close();
					guide.start();
				},
			},
		}));

		const popup = new Popup({
			id: 'sign__b2e-onboarding-welcome-popup',
			className: 'sign__b2e-onboarding-welcome-popup',
			closeIcon: true,
			width: 690,
			height: 322,
			padding: 20,
			overlay: true,
			buttons,
			content: Tag.render`
				<div class="sign__onboarding-popup-content">
					<div class="sign__onboarding-popup-content_header">
						<div class="sign__onboarding-popup-content_header-title">
							${popupTitle}
						</div>
					</div>
					<div class="sign__onboarding-popup-content_body">
						<div class="sign__onboarding-popup-content_img"></div>
						<div class="sign__onboarding-popup-content_text_container">
							<div class="sign__onboarding-popup-content_text">
								${Loc.getMessage('SIGN_ONBOARDING_B2E_WELCOME_POPUP_TEXT')}
							</div>
						</div>
					</div>
				</div>
			`,
		});

		return popup;
	}

	#createB2eWelcomePopup(guide: Guide): Popup
	{
		const popupTitle = Loc.getMessage('SIGN_ONBOARDING_B2E_WELCOME_POPUP_TITLE_WEST');
		const popup = new Popup({
			className: 'sign__b2e-onboarding-welcome-popup',
			closeIcon: false,
			width: 500,
			height: 517,
			padding: 20,
			buttons: [
				new Button({
					color: Button.Color.PRIMARY,
					size: Button.Size.SMALL,
					round: true,
					noCaps: true,
					text: Loc.getMessage('SIGN_ONBOARDING_B2E_WELCOME_POPUP_BTN_TEXT'),
					className: 'sign__b2e-onboarding-welcome-popup_start-guide',
					events: {
						click() {
							popup.close();
							guide.start();
						},
					},
				}),
			],
			content: Tag.render`
				<div class="sign__onboarding-popup-content">
					<div class="sign__onboarding-popup-content_header">
						<div class="sign__onboarding-popup-content_header-icon">
							${this.#renderIcon()}
						</div>
						<div class="sign__onboarding-popup-content_header-title">
							${popupTitle}
						</div>
					</div>
					<div class="sign__onboarding-popup-content_promo-video-wrapper">
						<img src="${b2eWelcomeGif}" alt="video">
					</div>
					<div class="sign__onboarding-popup-content_footer">
						${Loc.getMessage('SIGN_ONBOARDING_B2E_WELCOME_POPUP_TEXT')}
					</div>
				</div>
			`,
		});

		return popup;
	}

	#renderIcon(): HTMLElement
	{
		const color = getComputedStyle(document.body).getPropertyValue('--ui-color-on-primary');

		const icon = new Icon({
			color,
			size: 18,
			icon: ActionsIconSet.PENCIL_DRAW,
		});

		return icon.render();
	}

	#openSigningSlider(options: OnboardingOptions): void
	{
		BX.SidePanel.Instance.open('onboarding-signing-slider', {
			width: 750,
			contentCallback: () => {
				const containerId = 'onboarding-signing-slider-container';
				const container = Tag.render`<div id="${containerId}"></div>`;
				const onboardingSignSettings = new B2EOnboardingSignSettings(containerId, Boolean(options.hasCompanies));
				onboardingSignSettings.renderToContainer(container);

				return container;
			},
		});
	}

	#createB2eNewDocumentButtonStep(target: string | HTMLElement, region: string): StepOption
	{
		const firstStepMsgTitle = region === 'ru'
			? Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_CREATE_TITLE_RU')
			: Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_CREATE_TITLE')
		;

		const firstStepMsgText = region === 'ru'
			? Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_CREATE_TEXT_RU')
			: Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_CREATE_TEXT')
		;

		return {
			target,
			title: firstStepMsgTitle,
			text: firstStepMsgText,
			article: b2eCreateHelpdeskCode,
		};
	}

	#createB2eTemplatesStep(target: string | HTMLElement): StepOption
	{
		return {
			target,
			title: Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_TEMPLATES_TITLE_V1'),
			text: Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_TEMPLATES_TEXT_V1'),
			article: b2eTemplatesHelpdeskCode,
		};
	}

	#createB2eKanbanRouteStep(target: string | HTMLElement): StepOption
	{
		return {
			target,
			title: Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_ROUTE_TITLE'),
			text: Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_ROUTE_TEXT'),
		};
	}

	async #shouldStartB2eOnboarding(tourId: string, checkDocuments: boolean = false): Promise<boolean>
	{
		const { lastVisitDate } = await this.#backend.getLastVisitDate(tourId);

		if (Type.isNull(lastVisitDate) && checkDocuments)
		{
			const { hasSignedDocuments } = await this.#api.hasSignedDocuments();

			if (hasSignedDocuments)
			{
				return false;
			}
		}

		return Type.isNull(lastVisitDate);
	}

	#isTemplateBtnVisible(): boolean
	{
		return document.querySelector('div#sign_sign_b2e_employee_template_list')?.offsetParent !== null;
	}
}
