import { Dom, Loc, Tag, Type } from 'main.core';
import { MemoryCache } from 'main.core.cache';
import { type AnalyticsOptions, Analytics } from 'sign.v2.analytics';
import type { Template, TemplateField } from 'sign.v2.api';
import { StartProcess } from 'sign.v2.b2e.start-process';
import { DocumentSendedSuccessFullyEvent, SubmitDocumentInfo } from 'sign.v2.b2e.submit-document-info';
import { Helpdesk, SignSettingsItemCounter } from 'sign.v2.helper';
import { type Metadata, Wizard } from 'ui.wizard';
import { Loader } from 'main.loader';
import { EventEmitter } from 'main.core.events';

import noTemplatesStateImage from './images/no-templates-state-image.svg';
import './style.css';
import './../../sign-settings/src/style.css';
import './../../../sign-settings/src/style.css';

type StepsContext = {
	selectedTemplateUid?: string;
	templatesList?: Template[];
	fields?: TemplateField[];
}

const emptyStateHelpdeskCode = '23174934';

export class B2EEmployeeSignSettings
{
	#cache: MemoryCache<any> = new MemoryCache();
	#containerId: string;
	#wizard: Wizard;
	#startProcess: StartProcess;
	#stepsContext: StepsContext = {};
	#analytics: Analytics;

	constructor(containerId: string = '', analyticsContext: Partial<AnalyticsOptions> = {})
	{
		this.#containerId = containerId;
		const currentSlider = BX.SidePanel.Instance.getTopSlider();
		this.#startProcess = new StartProcess();
		this.#wizard = new Wizard(this.#getStepsMetadata(this), {
			back: { className: 'ui-btn-light-border' },
			next: { className: 'ui-btn-success' },
			complete: {
				className: 'ui-btn-success',
				title: Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_COMPLETE_TITLE'),
				onComplete: () => currentSlider?.close(),
			},
			swapButtons: true,
		});
		this.#analytics = new Analytics({ contextOptions: analyticsContext });
		this.#subscribeOnEvents();
	}

	#createHead(): HTMLElement
	{
		return this.#cache.remember('headLayout', () => {
			const { root, titleHelp } = Tag.render`
				<div class="sign-settings__head">
					<div>
						<p class="sign-settings__head_title">
							${Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_TITLE')}
						</p>
						<p class="sign-settings__head_title --sub">
							<span>${Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_TITLE_SUB')}</span>
							<a ref="titleHelp" class="sign-settings__head_title-help">
								${Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_TITLE_SUB_HELP')}
							</a>
						</p>
					</div>
				</div>
			`;
			Helpdesk.bindHandler(titleHelp, '23052076');

			return root;
		});
	}

	#getStartProcessStep(signSettings: B2EEmployeeSignSettings): Metadata[string]
	{
		const startProcess = signSettings.#startProcess;

		return {
			get content(): HTMLElement
			{
				if (Type.isUndefined(signSettings.#stepsContext.selectedTemplateUid))
				{
					signSettings.#wizard.toggleBtnActiveState('next', true);
					if (Type.isStringFilled(startProcess.getSelectedTemplateUid()))
					{
						signSettings.#wizard.toggleBtnActiveState('next', false);
					}
					else
					{
						startProcess.subscribe(
							startProcess.events.onProcessTypeSelect,
							() => signSettings.#wizard.toggleBtnActiveState('next', false),
						);
					}
				}

				const layout = startProcess.getLayout();
				SignSettingsItemCounter.numerate(layout);
				signSettings.#disableNoStepMode();

				return layout;
			},
			title: Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_START_PROCESS'),
			beforeCompletion: async () => {
				this.#stepsContext.selectedTemplateUid = startProcess.getSelectedTemplateUid();
				this.#stepsContext.templatesList = await startProcess.getTemplates();
				this.#stepsContext.fields = (await startProcess.getFields(this.#stepsContext.selectedTemplateUid))
					.fields
				;
			},
		};
	}

	#getSubmitDocumentInfoStep(signSettings: B2EEmployeeSignSettings): Metadata[string]
	{
		let submitDocumentInfo: SubmitDocumentInfo = null;

		return {
			get content(): HTMLElement
			{
				const currentTemplateSelected: Template = signSettings.#stepsContext.templatesList
					.find((template) => template.uid === signSettings.#stepsContext.selectedTemplateUid)
				;

				submitDocumentInfo = new SubmitDocumentInfo({
					template: {
						uid: currentTemplateSelected.uid,
						title: currentTemplateSelected.title,
					},
					company: currentTemplateSelected.company,
					fields: signSettings.#stepsContext.fields,
				});

				const layout = submitDocumentInfo.getLayout();
				if (signSettings.#stepsContext.fields.length > 0)
				{
					SignSettingsItemCounter.numerate(layout);
				}
				else
				{
					signSettings.#enableNoStepMode();
				}

				return layout;
			},
			title: Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_SUBMIT_INFO'),
			beforeCompletion: async () => {
				submitDocumentInfo.subscribeOnce(
					submitDocumentInfo.events.documentSendedSuccessFully,
					(event: DocumentSendedSuccessFullyEvent) => {
						const document = event.getData().document;
						this.#analytics.sendWithProviderTypeAndDocId(
							{
								event: 'sent_document_to_sign',
								c_element: 'create_button',
								status: 'success',
							},
							document.id,
							document.providerCode,
						);
					},
				);

				let result: boolean = false;
				try
				{
					result = await submitDocumentInfo.sendForSign();
				}
				catch (error)
				{
					console.error(error);
					result = false;
					this.#analytics.send({
						event: 'sent_document_to_sign',
						c_element: 'create_button',
						status: 'error',
					});
				}

				return result;
			},
		};
	}

	#getStepsMetadata(signSettings: B2EEmployeeSignSettings): Metadata
	{
		return {
			startProcess: this.#getStartProcessStep(signSettings),
			submitDocumentInfo: this.#getSubmitDocumentInfoStep(signSettings),
		};
	}

	#getLayout(): HTMLElement
	{
		return this.#cache.remember('headLayout', () => {
			return Tag.render`
				<div class="sign-settings__scope sign-settings --b2e --employee">
					<div class="sign-settings__sidebar">
						${this.#createHead()}
						${this.#wizard.getLayout()}
					</div>
				</div>
			`;
		});
	}

	async render(): void
	{
		const container = document.getElementById(this.#containerId);
		if (container === null)
		{
			return;
		}

		this.renderToContainer(container);
	}

	async renderToContainer(container: HTMLElement): void
	{
		if (Type.isNull(container))
		{
			return;
		}

		const loader = new Loader({ target: container });
		void loader.show();
		const templates = await this.#startProcess.getTemplates();

		if (templates.length === 0)
		{
			this.#analytics.send({ event: 'show_empty_state', c_element: 'create_button' });
			Dom.append(this.#getZeroTemplatesEmptyState(), container);
			void loader.hide();

			return;
		}

		void loader.hide();
		Dom.append(this.#getLayout(), container);
		this.#wizard.moveOnStep(0);

		this.#analytics.send({ event: 'click_create_document', c_element: 'create_button' });
	}

	#getZeroTemplatesEmptyState(): HTMLElement
	{
		return Tag.render`
			<div class="sign-settings__scope sign-settings --b2e --employee">
				<div class="sign-settings__sidebar">
					<div class="sign-settings__empty-state">
						<div class="sign-settings__empty-state_icon">
							<img src="${noTemplatesStateImage}" alt="${Loc.getMessage('SIGN_SETTINGS_EMPTY_STATE_ICON_ALT')}">
						</div>
						<p class="sign-settings__empty-state_title">
							${Loc.getMessage('SIGN_SETTINGS_EMPTY_STATE_TITLE')}
						</p>
						<p class="sign-settings__empty-state_text">
							${Helpdesk.replaceLink(
								Loc.getMessage('SIGN_SETTINGS_EMPTY_STATE_DESCRIPTION'),
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

	#enableNoStepMode(): void
	{
		Dom.addClass(this.#getLayout(), 'no-step-mode');
	}

	#disableNoStepMode(): void
	{
		Dom.removeClass(this.#getLayout(), 'no-step-mode');
	}

	#subscribeOnEvents(): void
	{
		EventEmitter.subscribe('BX.Sign.SignSettingsEmployee:onBeforeTemplateSend', () => {
			this.#wizard.toggleBtnActiveState('back', true);
		});
		EventEmitter.subscribe('BX.Sign.SignSettingsEmployee:onAfterTemplateSend', () => {
			this.#wizard.toggleBtnActiveState('back', false);
		});
	}

	clearCache(): void
	{
		this.#startProcess.resetCache();
		this.#stepsContext = {};
	}
}
