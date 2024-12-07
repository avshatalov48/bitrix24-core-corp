import { Loc, Tag, Type } from 'main.core';
import { FeatureStorage } from 'sign.feature-storage';
import './style.css';
import { Api, MemberRole, type SetupMember } from 'sign.v2.api';
import { ProviderCode } from 'sign.v2.b2e.company-selector';
import { DocumentSend } from 'sign.v2.b2e.document-send';
import { DocumentSetup } from 'sign.v2.b2e.document-setup';
import { Parties as CompanyParty } from 'sign.v2.b2e.parties';
import { UserParty } from 'sign.v2.b2e.user-party';
import { DocumentInitiated, DocumentInitiatedType } from 'sign.v2.document-setup';
import { SectionType } from 'sign.v2.editor';
import { SignSettingsItemCounter } from 'sign.v2.helper';
import { isTemplateMode, type SignOptions, type SignOptionsConfig, SignSettings } from 'sign.v2.sign-settings';
import type { Metadata } from 'ui.wizard';

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
		const { blankSelectorConfig, documentSendConfig, userPartyConfig } = this.#prepareConfig(signOptions);
		blankSelectorConfig.hideValidationParty = isTemplateMode(this.documentMode);

		this.documentSetup = new DocumentSetup(blankSelectorConfig);
		this.documentSend = new DocumentSend(documentSendConfig);
		this.#companyParty = new CompanyParty(blankSelectorConfig);
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
	}

	async #setupParties(): Array
	{
		const uid = this.#documentUid;
		const { representative } = this.#companyParty.getParties();
		const members = this.#makeSetupMembers();

		await this.#api.setupB2eParties(uid, representative.entityId, members);
		const membersData = await this.#api.loadMembers(uid);
		if (!Type.isArrayFilled(membersData))
		{
			throw new Error('Members are empty');
		}

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

	#getSigner(currentParty: number, userId: number): SetupMember
	{
		return {
			entityType: 'user',
			entityId: userId,
			party: currentParty,
			role: MemberRole.signer,
		};
	}

	#makeSetupMembers(): Array<SetupMember>
	{
		const { company, validation } = this.#companyParty.getParties();

		const userPartyIds = this.#userParty.getUserIds();
		let members = [];

		let currentParty = this.#isDocumentInitiatedByEmployee ? 2 : 1;

		members = validation.map((item): SetupMember => {
			const result = { ...item, party: currentParty };
			currentParty++;

			return result;
		});
		members.push(this.#getAssignee(currentParty, company.entityId));

		if (!this.isTemplateMode())
		{
			const signerParty = this.#isDocumentInitiatedByEmployee ? 1 : currentParty + 1;
			const signers = userPartyIds.map((userId) => this.#getSigner(signerParty, userId));
			members.push(...signers);
		}

		return members;
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

		const { entityId, representativeId, companyUid } = setupData;
		this.documentSend.documentData = setupData;
		this.editor.documentData = setupData;
		this.#companyParty.setEntityId(entityId);
		if (companyUid)
		{
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

	#getSetupStep(signSettings: B2ESignSettings): $Values<typeof Metadata>
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

	#getCompanyStep(signSettings: B2ESignSettings): $Values<typeof Metadata>
	{
		return {
			get content(): HTMLElement
			{
				const layout = signSettings.#companyParty.getLayout();
				SignSettingsItemCounter.numerate(layout);

				return layout;
			},
			title: Loc.getMessage('SIGN_SETTINGS_B2E_COMPANY'),
			beforeCompletion: async () => {
				const { uid } = this.documentSetup.setupData;
				try
				{
					await this.#companyParty.save(uid);
					this.#setSecondPartySectionVisibility();

					if (this.isTemplateMode())
					{
						const entityData = await this.#setupParties();
						this.editor.entityData = entityData;

						const { title, isTemplate, entityId, externalId, templateUid } = this.documentSetup.setupData;
						const blocks = await this.documentSetup.loadBlocks(uid);

						const documentSendData = { uid, title, blocks, externalId, templateUid };
						this.#executeDocumentSendActions(documentSendData, entityData);

						const editorData = { isTemplate, uid, blocks, entityId };
						this.#executeEditorActions(editorData);
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

	#getEmployeeStep(signSettings: B2ESignSettings): $Values<typeof Metadata>
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

					const entityData = await this.#setupParties();
					this.editor.entityData = entityData;

					const { uid, title, isTemplate, externalId, entityId } = this.documentSetup.setupData;
					const blocks = await this.documentSetup.loadBlocks(uid);

					const documentSendData = { uid, title, blocks, externalId };
					this.#executeDocumentSendActions(documentSendData, entityData);

					const editorData = { isTemplate, uid, blocks, entityId };
					this.#executeEditorActions(editorData);

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

	async #executeDocumentSendActions(documentSendData, entityData): void
	{
		const partiesData = this.#companyParty.getParties();
		Object.assign(partiesData, {
			employees: this.#userParty.getUserIds().map((userId) => {
				return {
					entityType: 'user',
					entityId: userId,
				};
			}),
		});
		this.documentSend.documentData = documentSendData;
		this.documentSend.setPartiesData(partiesData);
		this.documentSend.members = entityData;
	}

	async #executeEditorActions(editorData): void
	{
		this.editor.documentData = editorData;
		await this.editor.waitForPagesUrls();
		await this.editor.renderDocument();
		this.wizard.toggleBtnLoadingState('next', false);
		await this.editor.show();
	}

	#getSendStep(signSettings: B2ESignSettings): $Values<typeof Metadata>
	{
		return {
			get content(): HTMLElement
			{
				const layout = signSettings.documentSend.getLayout();
				SignSettingsItemCounter.numerate(layout);

				return layout;
			},
			title: Loc.getMessage('SIGN_SETTINGS_SEND_DOCUMENT'),
			beforeCompletion: () => this.documentSend.sendForSign(),
		};
	}

	getStepsMetadata(signSettings: B2ESignSettings): Metadata
	{
		const steps = {
			setup: this.#getSetupStep(signSettings),
			company: this.#getCompanyStep(signSettings),
		};

		if (!this.isTemplateMode())
		{
			steps.employees = this.#getEmployeeStep(signSettings);
		}

		steps.send = this.#getSendStep(signSettings);

		return steps;
	}

	onComplete(): void
	{
		if (this.isTemplateMode())
		{
			BX.SidePanel.Instance.close();

			BX.SidePanel.Instance.open('sign-settings-template-created', {
				contentCallback: () => {
					return Promise.resolve(this.getTemplateStatusLayout());
				},
			});

			return;
		}
		super.onComplete();
	}

	getTemplateStatusLayout(): HTMLElement
	{
		return Tag.render`
			<div class="sign-b2e-template-status">
				<div class="sign-b2e-template-status-inner">
					<div class="sign-b2e-template-status-img"></div>
					<div class="sign-b2e-template-status-title">${Loc.getMessage('SIGN_SETTINGS_TEMPLATE_CREATED')}</div>
					<button class="ui-btn ui-btn-light-border ui-btn-round" onclick="BX.SidePanel.Instance.close();">${Loc.getMessage('SIGN_SETTINGS_TEMPLATES_LIST')}</button>
				</div>
			 </div>
		`;
	}

	#setSecondPartySectionVisibility(): void
	{
		const selectedProvider = this.#companyParty.getSelectedProvider();
		const isSecondPartySectionVisible = selectedProvider.code !== ProviderCode.sesRu
			|| isTemplateMode(this.documentMode)
		;

		this.editor.setSectionVisibilityByType(
			SectionType.SecondParty,
			isSecondPartySectionVisible,
		);
	}
}
