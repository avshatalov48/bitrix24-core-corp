import { Dom, Loc, Tag } from 'main.core';
import type { Template } from 'sign.v2.api';
import { StartProcess } from 'sign.v2.b2e.start-process';
import { SubmitDocumentInfo } from 'sign.v2.b2e.submit-document-info';
import { type Metadata, Wizard } from 'ui.wizard';
import { SignSettingsItemCounter } from 'sign.v2.helper';

import './style.css';
import './../../sign-settings/src/style.css';
import './../../../sign-settings/src/style.css';

type StepsContext = {
	selectedTemplateUid?: string;
	templatesList?: Template[];
}

export class B2EEmployeeSignSettings
{
	#containerId: string;
	#wizard: Wizard;
	#startProcess: StartProcess;
	#stepsContext: StepsContext = {};

	constructor(containerId: string)
	{
		this.#containerId = containerId;
		const currentSlider = BX.SidePanel.Instance.getTopSlider();
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
		this.#startProcess = new StartProcess();
	}

	#createHead(): HTMLElement
	{
		return Tag.render`
			<div class="sign-settings__head">
				<div>
					<p class="sign-settings__head_title">
						${Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_TITLE')}
					</p>
					<p class="sign-settings__head_title --sub">
						<span>${Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_TITLE_SUB')}</span>
						<a class="sign-settings__head_title-help">
							${Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_TITLE_SUB_HELP')}
						</a>
					</p>
				</div>
			</div>
		`;
	}

	#getStartProcessStep(signSettings: B2EEmployeeSignSettings): $Values<Metadata>
	{
		return {
			get content(): HTMLElement
			{
				const layout = signSettings.#startProcess.getLayout();
				SignSettingsItemCounter.numerate(layout);

				return layout;
			},
			title: Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_START_PROCESS'),
			beforeCompletion: async () => {
				this.#stepsContext.selectedTemplateUid = signSettings.#startProcess.getSelectedTemplateUid();
				this.#stepsContext.templatesList = await signSettings.#startProcess.getTemplates();
			},
		};
	}

	#getSubmitDocumentInfoStep(signSettings: B2EEmployeeSignSettings): $Values<Metadata>
	{
		let submitDocumentInfo = null;

		return {
			get content(): HTMLElement
			{
				const currentTemplateSelected: Template = signSettings.#stepsContext.templatesList
					.find((template) => template.uid === signSettings.#stepsContext.selectedTemplateUid)
				;

				submitDocumentInfo = new SubmitDocumentInfo({
					template: {
						uid: currentTemplateSelected.uid,
					},
					company: currentTemplateSelected.company,
					fields: currentTemplateSelected.fields,
				});

				const layout = submitDocumentInfo.getLayout();
				SignSettingsItemCounter.numerate(layout);

				return layout;
			},
			title: Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_SUBMIT_INFO'),
			beforeCompletion: () => {
				return submitDocumentInfo.sendForSign();
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
		return Tag.render`
			<div class="sign-settings__scope sign-settings --b2e --employee">
				<div class="sign-settings__sidebar">
					${this.#createHead()}
					${this.#wizard.getLayout()}
				</div>
			</div>
		`;
	}

	render(): void
	{
		const container = document.getElementById(this.#containerId);
		Dom.append(this.#getLayout(), container);
		this.#wizard.moveOnStep(0);
	}
}
