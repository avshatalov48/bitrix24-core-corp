import { Loc, Type } from 'main.core';
import './style.css';
import { type BaseEvent } from 'main.core.events';
import { Api, MemberRole, type SetupMember } from 'sign.v2.api';
import { ProviderCode } from 'sign.v2.b2e.company-selector';
import { DocumentSend } from 'sign.v2.b2e.document-send';
import { DocumentSetup } from 'sign.v2.b2e.document-setup';
import { Parties as CompanyParty } from 'sign.v2.b2e.parties';
import { type CardItem, UserParty } from 'sign.v2.b2e.user-party';
import { DocumentInitiated, DocumentInitiatedType } from 'sign.v2.document-setup';
import { SectionType } from 'sign.v2.editor';
import { SignSettingsItemCounter } from 'sign.v2.helper';
import {
	decorateResultBeforeCompletion,
	isTemplateMode,
	type SignOptions,
	type SignOptionsConfig,
	SignSettings,
} from 'sign.v2.sign-settings';
import type { Metadata } from 'ui.wizard';
import { B2EFeatureConfig } from './type';

export type { B2EFeatureConfig };

type SetupMembersResponse = {
	members: Array<SetupMember>,
	currentParty: number,
};

export class B2ESignSettings extends SignSettings
{
	#companyParty: CompanyParty;
	#userParty: UserParty;
	#api: Api;
	documentSetup: DocumentSend;

	constructor(containerId: string, signOptions: SignOptions)
	{
		super(containerId, signOptions, {
			next: { className: 'ui-btn-success' },
			complete: { className: 'ui-btn-success' },
			swapButtons: true,
		});
		const {
			b2eFeatureConfig,
			blankSelectorConfig,
			documentSendConfig,
			userPartyConfig,
		} = this.#prepareConfig(signOptions);

		blankSelectorConfig.hideValidationParty = isTemplateMode(this.documentMode);

		this.documentSetup = new DocumentSetup(blankSelectorConfig);
		this.documentSend = new DocumentSend(documentSendConfig);
		this.#companyParty = new CompanyParty({
			...blankSelectorConfig,
			documentInitiatedType: signOptions.initiatedByType,
			documentMode: signOptions.documentMode,
		}, b2eFeatureConfig.hcmLinkAvailable);
		this.#api = new Api();
		this.#userParty = new UserParty({ mode: 'edit', ...userPartyConfig });
		this.subscribeOnEvents();
	}

	#prepareConfig(signOptions: SignOptions): SignOptionsConfig
	{
		const { config, documentMode } = signOptions;

		const { blankSelectorConfig, documentSendConfig } = config;
		blankSelectorConfig.documentMode = documentMode;
		documentSendConfig.documentMode = documentMode;

		return config;
	}

	subscribeOnEvents(): void
	{
		super.subscribeOnEvents();
		this.documentSend.subscribe('changeTitle', ({ data }) => {
			this.documentSetup.setDocumentTitle(data.title);
		});
		this.documentSend.subscribe('disableBack', () => {
			this.wizard.toggleBtnActiveState('back', true);
		});
		this.documentSend.subscribe('enableBack', () => {
			this.wizard.toggleBtnActiveState('back', false);
		});
		this.documentSend.subscribe('enableComplete', () => {
			this.wizard.toggleBtnActiveState('complete', false);
		});
		this.documentSend.subscribe('disableComplete', () => {
			this.wizard.toggleBtnActiveState('complete', true);
		});
		this.documentSend.subscribe(
			this.documentSend.events.onTemplateComplete,
			(event: BaseEvent<{ templateId: number }>) => {
				if (this.isTemplateMode() && !this.isEditMode())
				{
					const templateId = event.getData().templateId;
					this.getAnalytics().send({
						event: 'turn_on_off_template',
						type: 'auto',
						c_element: 'on',
						p5: `templateId_${templateId}`,
					});
					this.getAnalytics().send({
						event: 'click_save_template',
						c_element: 'create_button',
						p5: `templateId_${templateId}`,
						status: 'success',
					});
				}
			},
		);
	}

	async #setupParties(): Promise<Array<Object>>
	{
		const uid = this.#documentUid;
		const { representative } = this.#companyParty.getParties();
		const { members, signerParty } = this.#makeSetupMembers();

		await this.#api.setupB2eParties(uid, representative.entityId, members);
		const membersData = await this.#api.loadMembers(uid);
		if (!Type.isArrayFilled(membersData))
		{
			throw new Error('Members are empty');
		}

		await this.#syncMembers(uid, signerParty);

		return membersData.map((memberData) => {
			return {
				presetId: memberData?.presetId,
				part: memberData?.party,
				uid: memberData?.uid,
				entityTypeId: memberData?.entityTypeId ?? null,
				entityId: memberData?.entityId ?? null,
				role: memberData?.role ?? null,
			};
		});
	}

	async #syncMembers(uid: string, signerParty: number): Promise<void>
	{
		let syncFinished = false;
		while (!syncFinished)
		{
			// eslint-disable-next-line no-await-in-loop
			const response = await this.#api.syncB2eMembersWithDepartments(uid, signerParty);
			syncFinished = response.syncFinished;
			// eslint-disable-next-line no-await-in-loop
			await this.#sleep(1000);
		}
	}

	#sleep(ms: Number): Promise
	{
		return new Promise((resolve) => {
			setTimeout(resolve, ms);
		});
	}

	get #documentUid(): string
	{
		return this.documentSetup.setupData.uid;
	}

	get #isDocumentInitiatedByEmployee(): DocumentInitiatedType
	{
		return this.documentSetup.setupData.initiatedByType === DocumentInitiated.employee;
	}

	#getAssignee(currentParty: number, companyId: number): SetupMember
	{
		return {
			entityType: 'company',
			entityId: companyId,
			party: currentParty,
			role: MemberRole.assignee,
		};
	}

	#getSigner(currentParty: number, entity: CardItem): SetupMember
	{
		return {
			entityType: entity.entityType,
			entityId: entity.entityId,
			party: currentParty,
			role: MemberRole.signer,
		};
	}

	#makeSetupMembers(): SetupMembersResponse
	{
		const { company, validation } = this.#companyParty.getParties();

		const userPartyEntities = this.#userParty.getEntities();

		let currentParty = this.#isDocumentInitiatedByEmployee ? 2 : 1;

		const members = validation.map((item): SetupMember => {
			const result = { ...item, party: currentParty };
			currentParty++;

			return result;
		});
		members.push(this.#getAssignee(currentParty, company.entityId));

		let signerParty = currentParty;
		if (this.isDocumentMode())
		{
			signerParty = this.#isDocumentInitiatedByEmployee ? 1 : currentParty + 1;
			const signers = userPartyEntities.map((entity) => this.#getSigner(signerParty, entity));
			members.push(...signers);
		}

		return { members, signerParty };
	}

	#parseMembers(loadedMembers: SetupMember[]): { [$Keys<typeof MemberRole>]: number[]; }
	{
		return loadedMembers.reduce((acc, member) => {
			const { entityType, entityId } = member;
			if (entityType !== 'user')
			{
				return acc;
			}

			const role = `${member.role}s`;

			return {
				...acc,
				[role]: [
					...acc[role] ?? [],
					entityId,
				],
			};
		}, {});
	}

	async applyDocumentData(uid: string): Promise<boolean>
	{
		const setupData = await this.setupDocument(uid, true);
		if (!setupData)
		{
			return false;
		}

		const { entityId, representativeId, companyUid, hcmLinkCompanyId } = setupData;
		this.documentSend.documentData = setupData;
		this.editor.documentData = setupData;
		this.#companyParty.setEntityId(entityId);
		if (companyUid)
		{
			this.#companyParty.setLastSavedIntegrationId(hcmLinkCompanyId);
			this.#companyParty.loadCompany(companyUid);
		}

		if (representativeId)
		{
			this.#companyParty.loadRepresentative(representativeId);
		}

		const members = await this.#api.loadMembers(uid);
		const parsedMembers = this.#parseMembers(members);
		const { signers = [], reviewers = [], editors = [] } = parsedMembers;
		if (signers.length > 0)
		{
			this.#userParty.load(signers);
		}

		if (reviewers.length > 0)
		{
			this.#companyParty.loadValidator(reviewers[0], MemberRole.reviewer);
		}

		if (editors.length > 0)
		{
			this.#companyParty.loadValidator(editors[0], MemberRole.editor);
		}

		return true;
	}

	async applyTemplateData(templateUid: string): Promise<boolean>
	{
		super.applyTemplateData(templateUid);

		this.documentSetup.setupData.templateUid = templateUid;
		this.documentSend.setExistingTemplate();

		return true;
	}

	#getSetupStep(signSettings: B2ESignSettings, documentUid?: string): $Values<Metadata>
	{
		return {
			get content(): HTMLElement
			{
				const layout = signSettings.documentSetup.layout;
				SignSettingsItemCounter.numerate(layout);

				return layout;
			},
			title: Loc.getMessage('SIGN_SETTINGS_B2B_LOAD_DOCUMENT'),
			beforeCompletion: async () => {
				const isValid = this.documentSetup.validate();
				if (!isValid)
				{
					return false;
				}

				const setupDataPromise = this.setupDocument();
				if (!setupDataPromise)
				{
					return false;
				}
				const setupData = await setupDataPromise;
				if (!setupData)
				{
					return false;
				}

				this.#companyParty.setInitiatedByType(setupData.initiatedByType);
				this.#companyParty.setEntityId(setupData.entityId);

				return true;
			},
		};
	}

	#getCompanyStep(signSettings: B2ESignSettings): $Values<Metadata>
	{
		const titleLocCode = this.isTemplateMode()
			? 'SIGN_SETTINGS_B2E_ROUTES'
			: 'SIGN_SETTINGS_B2E_COMPANY'
		;

		return {
			get content(): HTMLElement
			{
				const layout = signSettings.#companyParty.getLayout();
				SignSettingsItemCounter.numerate(layout);

				return layout;
			},
			title: Loc.getMessage(titleLocCode),
			beforeCompletion: async () => {
				const { uid } = this.documentSetup.setupData;
				try
				{
					await this.#companyParty.save(uid);
					this.documentSetup.setupData.integrationId = this.#companyParty.getSelectedIntegrationId();

					this.#setSecondPartySectionVisibility();

					if (this.isTemplateMode())
					{
						this.editor.entityData = await this.#setupParties();
						const { title, isTemplate, entityId, externalId, templateUid } = this.documentSetup.setupData;
						const blocks = await this.documentSetup.loadBlocks(uid);
						this.#executeDocumentSendActions({ uid, title, blocks, externalId, templateUid });

						this.#executeEditorActions({ isTemplate, uid, blocks, entityId });
					}
				}
				catch
				{
					return false;
				}

				return true;
			},
		};
	}

	#getEmployeeStep(signSettings: B2ESignSettings): $Values<Metadata>
	{
		return {
			get content(): HTMLElement
			{
				const layout = signSettings.#userParty.getLayout();
				SignSettingsItemCounter.numerate(layout);

				return layout;
			},
			title: Loc.getMessage('SIGN_SETTINGS_B2E_EMPLOYEES'),
			beforeCompletion: async () => {
				try
				{
					const isValid = this.#userParty.validate();
					if (!isValid)
					{
						return isValid;
					}

					this.editor.entityData = await this.#setupParties();

					const { uid, title, isTemplate, externalId, entityId, integrationId } = this.documentSetup.setupData;
					const blocks = await this.documentSetup.loadBlocks(uid);

					this.#executeDocumentSendActions({ uid, title, blocks, externalId, integrationId });

					this.#executeEditorActions({ isTemplate, uid, blocks, entityId });

					return true;
				}
				catch (e)
				{
					console.error(e);

					return false;
				}
			},
		};
	}

	async #executeDocumentSendActions(documentSendData: Object): Promise<void>
	{
		const partiesData = this.#companyParty.getParties();
		Object.assign(partiesData, {
			employees: this.#userParty.getEntities().map((entity) => {
				return {
					entityType: entity.entityType,
					entityId: entity.entityId,
				};
			}),
		});
		this.documentSend.documentData = documentSendData;
		this.documentSend.resetUserPartyPopup();
		this.documentSend.setPartiesData(partiesData);
	}

	async #executeEditorActions(editorData: Object): Promise<void>
	{
		this.editor.documentData = editorData;
		await this.editor.waitForPagesUrls();
		await this.editor.renderDocument();
		this.wizard.toggleBtnLoadingState('next', false);
		await this.editor.show();
	}

	#getSendStep(signSettings: B2ESignSettings): $Values<Metadata>
	{
		const titleLocCode = this.isTemplateMode()
			? 'SIGN_SETTINGS_SEND_DOCUMENT_CREATE'
			: 'SIGN_SETTINGS_SEND_DOCUMENT'
		;

		return {
			get content(): HTMLElement
			{
				const layout = signSettings.documentSend.getLayout();
				SignSettingsItemCounter.numerate(layout);

				return layout;
			},
			title: Loc.getMessage(titleLocCode),
			beforeCompletion: () => this.documentSend.sendForSign(),
		};
	}

	getStepsMetadata(signSettings: this, documentUid: ?string, templateUid?: string): Metadata
	{
		this.#sendAnalyticsOnStart(documentUid);
		const steps = {
			setup: this.#getSetupStep(signSettings, documentUid),
			company: this.#getCompanyStep(signSettings),
		};

		if (this.isDocumentMode())
		{
			steps.employees = this.#getEmployeeStep(signSettings);
		}

		steps.send = this.#getSendStep(signSettings);

		this.#decorateStepsBeforeCompletionWithAnalytics(steps, documentUid);

		return steps;
	}

	onComplete(): void
	{
		if (this.isTemplateMode())
		{
			return;
		}
		super.onComplete();
	}

	isTemplateCreateMode(): boolean
	{
		return isTemplateMode(this.documentMode) && !this.isEditMode();
	}

	#setSecondPartySectionVisibility(): void
	{
		const selectedProvider = this.#companyParty.getSelectedProvider();

		const isNotSesRuProvider = selectedProvider.code !== ProviderCode.sesRu;
		const isInitiatedByEmployee = this.documentSetup.setupData.initiatedByType === DocumentInitiated.employee;
		const isInitiatedByCompany = this.documentSetup.setupData.initiatedByType === DocumentInitiated.company;

		const isSecondPartySectionVisible =	isNotSesRuProvider
			|| (isTemplateMode(this.documentMode) && isInitiatedByEmployee)
		;

		const isHcmLinkIntegrationSectionVisible = this.documentSetup.setupData.integrationId > 0
			|| isInitiatedByCompany
		;

		this.editor.setSectionVisibilityByType(
			SectionType.HcmLinkIntegration,
			isHcmLinkIntegrationSectionVisible,
		);

		this.editor.setSectionVisibilityByType(
			SectionType.SecondParty,
			isSecondPartySectionVisible,
		);
	}

	#decorateStepsBeforeCompletionWithAnalytics(steps: Metadata, documentUid?: string): void
	{
		const analytics = this.getAnalytics();

		if (Type.isPlainObject(steps.setup))
		{
			steps.setup.beforeCompletion = decorateResultBeforeCompletion(
				steps.setup.beforeCompletion,
				() => this.#sendAnalyticsOnSetupStep(analytics, documentUid),
				() => this.#sendAnalyticsOnSetupError(analytics),
			);
		}

		if (Type.isPlainObject(steps.company))
		{
			steps.company.beforeCompletion = decorateResultBeforeCompletion(
				steps.company.beforeCompletion,
				() => this.#sendAnalyticsOnCompanyStepSuccess(analytics),
				() => this.#sendAnalyticsOnCompanyStepError(analytics),
			);
		}

		if (Type.isPlainObject(steps.send))
		{
			steps.send.beforeCompletion = decorateResultBeforeCompletion(
				steps.send.beforeCompletion,
				() => this.#sendAnalyticsOnSendStepSuccess(analytics, this.documentSetup.setupData.uid),
				() => this.#sendAnalyticsOnSendStepError(analytics, this.documentSetup.setupData.uid),
			);
		}
	}

	#sendAnalyticsOnSetupStep(analytics: Analytics, documentUid?: string): void
	{
		if (this.isTemplateCreateMode())
		{
			analytics.send({ event: 'proceed_step_document', c_element: 'create_button', status: 'success' });
			analytics.send({
				event: 'turn_on_off_template',
				type: 'auto',
				c_element: 'off',
				p5: `templateId_${this.documentSetup.setupData.templateId}`,
			});
		}
	}

	#sendAnalyticsOnSetupError(analytics: Analytics): void
	{
		if (this.isTemplateCreateMode())
		{
			analytics.send({ event: 'proceed_step_document', status: 'error', c_element: 'create_button' });
		}
	}

	#sendAnalyticsOnCompanyStepSuccess(analytics: Analytics): void
	{
		if (this.isTemplateCreateMode())
		{
			analytics.send({ event: 'proceed_step_route', status: 'success', c_element: 'create_button' });
		}
	}

	#sendAnalyticsOnCompanyStepError(analytics: Analytics): void
	{
		if (this.isTemplateCreateMode())
		{
			analytics.send({ event: 'proceed_step_route', status: 'error', c_element: 'create_button' });
		}
	}

	async #sendAnalyticsOnSendStepSuccess(analytics: Analytics, documentUid: string): void
	{
		if (this.isDocumentMode())
		{
			analytics.sendWithProviderTypeAndDocId({
				event: 'sent_document_to_sign',
				c_element: 'create_button',
				status: 'success',
			}, documentUid);
		}
	}

	async #sendAnalyticsOnSendStepError(analytics: Analytics, documentUid: string): void
	{
		if (this.isTemplateCreateMode())
		{
			this.getAnalytics().send({
				event: 'click_save_template',
				status: 'error',
				c_element: 'create_button',
			});
		}

		if (this.isDocumentMode())
		{
			analytics.sendWithProviderTypeAndDocId({
				event: 'sent_document_to_sign',
				c_element: 'create_button',
				status: 'error',
			}, documentUid);
		}
	}

	#sendAnalyticsOnStart(documentUid?: string): void
	{
		const analytics = this.getAnalytics();
		if (this.isTemplateCreateMode())
		{
			analytics.send({ event: 'open_wizard', c_element: 'create_button' });
		}
		else if (this.isDocumentMode())
		{
			const context = { event: 'click_create_document', c_element: 'create_button' };
			if (this.isEditMode() && Type.isStringFilled(documentUid))
			{
				analytics.sendWithDocId(context, documentUid);
			}
			else
			{
				analytics.send(context);
			}
		}
	}
}
