import { Dom, Loc, Tag, Type } from 'main.core';
import { MemoryCache } from 'main.core.cache';
import { Loader } from 'main.loader';
import { SubmitDocumentInfo } from 'sign.v2.b2e.submit-document-info';
import { Helpdesk } from 'sign.v2.helper';
import type { Metadata } from 'ui.wizard';
import { Wizard } from 'ui.wizard';
import { Api } from 'sign.v2.api';
import documentImage from './images/document-img.svg';
import './style.css';
import './../../sign-settings/src/style.css';
import './../../../sign-settings/src/style.css';

const emptyStateHelpdeskCode = 26311976;

export class B2EOnboardingSignSettings
{
	#cache: MemoryCache<any> = new MemoryCache();
	#containerId: string;
	#wizard: Wizard;
	#template: Object = {};
	#api = new Api();
	#hasCompanies: boolean;

	constructor(containerId: string = '', hasCompanies: boolean = false)
	{
		this.#hasCompanies = hasCompanies;
		this.#containerId = containerId;
		const currentSlider = BX.SidePanel.Instance.getTopSlider();
		this.#wizard = new Wizard(this.#getStepsMetadata(this), {
			back: { className: 'ui-btn-light-border' },
			next: { className: 'ui-btn-success' },
			complete: {
				className: 'ui-btn-success',
				title: Loc.getMessage('SIGN_SETTINGS_ONBOARDING_COMPLETE_TITLE'),
				onComplete: () => currentSlider?.close(),
			},
			swapButtons: true,
		});
	}

	async renderToContainer(container: HTMLElement): void
	{
		if (Type.isNull(container))
		{
			return;
		}

		const loader = new Loader({ target: container });
		void loader.show();
		if (!this.#hasCompanies)
		{
			Dom.append(this.#getCompaniesNotFoundEmptyState(), container);
			void loader.hide();

			return;
		}

		let response = null;
		try
		{
			response = await this.#api.template.installOnboardingTemplate();
		}
		catch
		{
			Dom.append(this.#getCompaniesNotFoundEmptyState(), container);
			void loader.hide();

			return;
		}

		this.#template = response.template;
		void loader.hide();

		Dom.append(this.#getLayout(), container);
		this.#wizard.moveOnStep(0);
	}

	#getCompaniesNotFoundEmptyState(): HTMLElement
	{
		return Tag.render`
			<div class="sign-settings__scope sign-settings --b2e --employee">
				<div class="sign-settings__sidebar">
					<div class="sign-settings__empty-state">
						<div class="sign-settings__empty-state_icon">
							<img src="${documentImage}" alt="">
						</div>
						<p class="sign-settings__empty-state_title">
							${Loc.getMessage('SIGN_SETTINGS_ONBOARDING_EMPTY_STATE_TITLE')}
						</p>
						<p class="sign-settings__empty-state_text">
							${Helpdesk.replaceLink(
								Loc.getMessage('SIGN_SETTINGS_ONBOARDING_EMPTY_STATE_DESCRIPTION'),
								emptyStateHelpdeskCode,
								Helpdesk.defaultRedirectValue,
					['sign-settings__empty-state_link'],
							)}
						</p>
					</div>
				</div>
			</div>
		`;
	}

	#getStepsMetadata(signSettings: B2EEmployeeSignSettings): Metadata
	{
		return {
			submitDocumentInfo: this.#getSubmitDocumentInfoStep(signSettings),
		};
	}

	#getSubmitDocumentInfoStep(signSettings: B2EOnboardingSignSettings): Metadata[string]
	{
		let submitDocumentInfo: SubmitDocumentInfo = null;

		return {
			get content(): HTMLElement
			{
				submitDocumentInfo = new SubmitDocumentInfo({
					template: {
						uid: signSettings.#template.uid,
						title: signSettings.#template.title,
					},
					company: signSettings.#template.company,
					fields: [],
					isOnboarding: true,
				});

				signSettings.#enableNoStepMode();

				return submitDocumentInfo.getLayout();
			},
			beforeCompletion: async () => {
				let result: boolean = false;
				try
				{
					result = await submitDocumentInfo.sendForSign();
				}
				catch (error)
				{
					console.error(error);
					result = false;
				}

				return result;
			},
		};
	}

	#enableNoStepMode(): void
	{
		Dom.addClass(this.#getLayout(), 'no-step-mode');
	}

	#getLayout(): HTMLElement
	{
		return this.#cache.remember('headLayout', () => {
			return Tag.render`
				<div class="sign-settings__scope sign-settings --b2e">
					<div class="sign-settings__sidebar">
						${this.#wizard.getLayout()}
					</div>
				</div>
			`;
		});
	}
}
