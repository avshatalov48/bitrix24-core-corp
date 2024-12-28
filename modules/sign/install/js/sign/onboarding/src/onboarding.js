import { Loc, Tag, Type } from 'main.core';
import { Popup } from 'main.popup';
import { Guide, Backend, type StepOption } from 'sign.tour';
import { BannerDispatcher } from 'ui.banner-dispatcher';
import { Button } from 'ui.buttons';
import 'ui.design-tokens';
import { Icon, Actions as ActionsIconSet } from 'ui.icon-set.api.core';
import './style.css';
import b2eWelcomeGif from './video/b2e_welcome.gif';

type OnboardingOptions = {
	region: string,
	tourId: string,
}

const b2bHelpdeskCode = 16571388;
const b2eCreateHelpdeskCode = 20338910;
const b2eTemplatesHelpdeskCode = 23174934;

const b2ePopupTourId = 'sign-b2e-onboarding-tour-id';

export class Onboarding
{
	#backend: Backend = new Backend();

	async startB2eByEmployeeOnboarding(options: OnboardingOptions): Promise<void>
	{
		const startOnboarding = await this.#shouldStartB2eOnboarding();
		if (!startOnboarding)
		{
			return;
		}

		BannerDispatcher.high.toQueue((onDone) => {
			const guide = this.#getB2eByEmployeeGuide(options, onDone);
			const welcomePopup = this.#createB2eByEmployeePopup(guide);

			this.#backend.saveVisit(b2ePopupTourId);

			welcomePopup.show();
		});
	}

	async startB2eWelcomeOnboarding(options: OnboardingOptions): Promise<void>
	{
		const startOnboarding = await this.#shouldStartB2eOnboarding();
		if (!startOnboarding)
		{
			return;
		}

		BannerDispatcher.high.toQueue((onDone) => {
			const guide = this.#getB2eWelcomeGuide(options, onDone);
			const welcomePopup = this.#createB2eWelcomePopup(guide);

			this.#backend.saveVisit(b2ePopupTourId);

			welcomePopup.show();
		});
	}

	async startB2eFallbackOnboarding(options: OnboardingOptions): Promise<void>
	{
		BannerDispatcher.high.toQueue((onDone) => {
			const guide = this.#getB2eFallbackGuide(options, onDone);
			guide.startOnce();
		});
	}

	#getB2eByEmployeeGuide(options: OnboardingOptions, onFinish: ?Function): Guide
	{
		return new Guide({
			id: options.tourId ?? 'sign-tour-guide-sign-start-kanban-b2e-by-employee',
			autoSave: true,
			simpleMode: false,
			events: {
				onFinish,
			},
			steps: [
				this.#createB2eKanbanRouteStep('.ui-toolbar-after-title-buttons > button.sign-b2e-onboarding-route'),
				this.#createB2eTemplatesStep('div#sign_sign_b2e_employee_template_list'),
			],
		});
	}

	#getB2eWelcomeGuide(options: OnboardingOptions, onFinish: ?Function): Guide
	{
		return new Guide({
			id: options.tourId ?? 'sign-tour-guide-sign-start-kanban-b2e-by-employee',
			autoSave: true,
			simpleMode: false,
			events: {
				onFinish,
			},
			steps: [
				this.#createB2eNewDocumentButtonStep('.ui-toolbar-after-title-buttons > .sign-b2e-onboarding-create', options.region),
				this.#createB2eKanbanRouteStep('.ui-toolbar-after-title-buttons > .sign-b2e-onboarding-route'),
				this.#createB2eTemplatesStep('div#sign_sign_b2e_employee_template_list'),
			],
		});
	}

	#getB2eFallbackGuide(options: OnboardingOptions, onFinish: ?Function): Guide
	{
		return new Guide({
			id: options.tourId ?? 'sign-tour-guide-sign-start-kanban-b2e',
			autoSave: true,
			simpleMode: true,
			events: {
				onFinish,
			},
			steps: [
				this.#createB2eNewDocumentButtonStep('.ui-toolbar-after-title-buttons > .sign-b2e-onboarding-create', options.region),
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

	#createB2eByEmployeePopup(guide: Guide): Popup
	{
		const popup = new Popup({
			content: Tag.render`
				<div>
					<div class="sign__b2e_by_employee_onboarding_popup-title">${Loc.getMessage('SIGN_ONBOARDING_B2E_BY_EMPLOYEE_POPUP_TITLE')}</div>
					<div class="sign__b2e_by_employee_onboarding_popup-text">${Loc.getMessage('SIGN_ONBOARDING_B2E_BY_EMPLOYEE_POPUP_TEXT')}</div>
				</div>
			`,
			closeIcon: false,
			width: 371,
			height: 180,
			padding: 20,
			overlay: true,
			className: 'sign__b2e_by_employee_onboarding_popup',
			contentColor: 'white',
			buttons: [
				new Button({
					color: Button.Color.PRIMARY,
					size: Button.Size.SMALL,
					round: true,
					noCaps: true,
					text: Loc.getMessage('SIGN_ONBOARDING_B2E_BY_EMPLOYEE_POPUP_BTN_TEXT'),
					events: {
						click() {
							popup.close();
							guide.start();
						},
					},
				}),
			],
		});

		return popup;
	}

	#createB2eWelcomePopup(guide: Guide): Popup
	{
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
							${Loc.getMessage('SIGN_ONBOARDING_B2E_WELCOME_POPUP_TITLE')}
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
			title: Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_TEMPLATES_TITLE'),
			text: Loc.getMessage('SIGN_ONBOARDING_B2E_STEP_TEMPLATES_TEXT'),
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

	async #shouldStartB2eOnboarding(): Promise<boolean>
	{
		const { lastVisitDate } = await this.#backend.getLastVisitDate(b2ePopupTourId);

		return Type.isNull(lastVisitDate);
	}
}
