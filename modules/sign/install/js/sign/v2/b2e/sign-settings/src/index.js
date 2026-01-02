import { Dom, Loc, Tag, Text, Type } from 'main.core';
import { MemoryCache } from 'main.core.cache';
import { type BaseEvent } from 'main.core.events';
import { FeatureStorage } from 'sign.feature-storage';
import { DocumentInitiated, type DocumentInitiatedType, MemberRole, ProviderCode, ProviderCodeType } from 'sign.type';
import { Api, type SetupMember } from 'sign.v2.api';
import type { ProviderSelectedEvent, CompanySelectedEvent } from 'sign.v2.b2e.company-selector';
import { DocumentSend } from 'sign.v2.b2e.document-send';
import { DocumentSetup } from 'sign.v2.b2e.document-setup';
import { Parties as CompanyParty } from 'sign.v2.b2e.parties';
import { RegionalSettings } from 'sign.v2.b2e.regional-settings';
import { type CardItem, UserParty } from 'sign.v2.b2e.user-party';
import { type DocumentDetails } from 'sign.v2.document-setup';
import { SectionType } from 'sign.v2.editor';
import { SignSettingsItemCounter } from 'sign.v2.helper';
import {
	decorateResultBeforeCompletion,
	isTemplateMode,
	type SignOptions,
	type SignOptionsConfig,
	SignSettings,
} from 'sign.v2.sign-settings';
import { type SaveButton } from 'ui.buttons';
import { Layout } from 'ui.sidepanel.layout';
import { Uploader, UploaderEvent, UploaderFile } from 'ui.uploader.core';
import type { Metadata } from 'ui.wizard';
import { B2EFeatureConfig } from './type';
import { EntityType } from 'sign.type';
import { SignDropdown } from 'sign.v2.b2e.sign-dropdown';

import './style.css';

export type { B2EFeatureConfig };

type SetupMembersResponse = {
	members: Array<SetupMember>,
	currentParty: number,
};

type PartiesDetails = {
	entityId: number,
	entityTypeId: number,
	part: number,
	presetId: number,
	role: string,
	uid: string
}

const acceptedUploaderFileTypes: Set<string> = new Set([
	'jpg',
	'jpeg',
	'png',
	'pdf',
	'doc',
	'docx',
	'rtf',
	'odt',
]);

export class B2ESignSettings extends SignSettings
{
	#companyParty: CompanyParty;
	#regionalSettings: RegionalSettings;
	#userParty: UserParty;
	#api: Api;
	documentSetup: DocumentSetup;
	editedDocument: DocumentDetails | null;
	#maxDocumentCount: boolean;
	#cache: MemoryCache<any> = new MemoryCache();
	#saveButton: SaveButton;
	#isMultiDocumentSaveProcessGone: boolean = false;
	#needSkipEditorStep: boolean;
	#previewDocumentDropdown: HTMLElement | null = null;

	constructor(containerId: string, signOptions: SignOptions)
	{
		super(containerId, signOptions, {
			next: {
				className: 'ui-btn-success',
			},
			complete: { className: 'ui-btn-success' },
			swapButtons: true,
		});
		const {
			b2eFeatureConfig,
			blankSelectorConfig,
			documentSendConfig,
			userPartyConfig,
		} = this.#prepareConfig(signOptions);

		this.documentSetup = new DocumentSetup(blankSelectorConfig);
		this.documentSend = new DocumentSend(documentSendConfig);
		this.#companyParty = new CompanyParty({
			...blankSelectorConfig,
			documentInitiatedType: signOptions.initiatedByType,
			documentMode: signOptions.documentMode,
		}, b2eFeatureConfig.hcmLinkAvailable);
		this.#regionalSettings = new RegionalSettings({
			templateMode: this.isTemplateMode(),
			regionDocumentTypes: blankSelectorConfig.regionDocumentTypes,
		});

		this.#regionalSettings.isIntegrationVisible = !(this.isTemplateMode()
			&& signOptions.initiatedByType === DocumentInitiated.employee);

		this.#api = new Api();
		this.#maxDocumentCount = signOptions.b2eDocumentLimitCount;
		this.#userParty = new UserParty({ mode: 'edit', ...userPartyConfig });
		this.subscribeOnEvents();
		this.#needSkipEditorStep = documentSendConfig.needSkipEditorStep;

		this.editor.setIsB2eDocumentSectionDisabled(!this.documentSetup.isRuRegion());
		this.editor.setSectionVisibilityByType(SectionType.HcmLinkIntegration, false);

		this.#companyParty.subscribe('onCompanySelect', (event: CompanySelectedEvent) => {
			this.documentSend.setProvider(event.data.provider);
			this.#regionalSettings.isIntegrationEnabled = this.#isIntegrationEnabled(event.data?.provider?.code);
		});
		this.#companyParty.subscribe('onProviderSelect', (event: ProviderSelectedEvent) => {
			this.documentSend.setProvider(event.data.provider);
			this.#regionalSettings.isIntegrationEnabled = this.#isIntegrationEnabled(event.data?.provider?.code);
		});
		this.#createPreviewDocumentDropdown();
	}

	#prepareConfig(signOptions: SignOptions): SignOptionsConfig
	{
		const {
			config,
			documentMode,
			fromRobot,
			fromTemplateFolder,
			b2eDocumentLimitCount,
			templateFolderId,
			isOpenedAsFolder,
			initiatedByType,
		} = signOptions;

		const { blankSelectorConfig, documentSendConfig } = config;
		blankSelectorConfig.documentMode = documentMode;
		blankSelectorConfig.isOpenedFromRobot = fromRobot;
		blankSelectorConfig.b2eDocumentLimitCount = b2eDocumentLimitCount;
		blankSelectorConfig.isOpenedFromTemplateFolder = fromTemplateFolder;
		blankSelectorConfig.templateFolderId = templateFolderId;
		blankSelectorConfig.isOpenedAsFolder = isOpenedAsFolder;
		blankSelectorConfig.initiatedByType = initiatedByType;
		documentSendConfig.documentMode = documentMode;
		documentSendConfig.isOpenedFromRobot = fromRobot;
		documentSendConfig.templateFolderId = templateFolderId;

		return config;
	}

	subscribeOnEvents(): void
	{
		super.subscribeOnEvents();
		this.documentSend.subscribe('changeTitle', ({ data }) => {
			const title = data?.title;
			if (!title)
			{
				return;
			}

			const uid = data?.uid;
			if (!uid)
			{
				return;
			}

			const encodedTitle = Text.encode(title);

			this.documentSetup.setDocumentTitle(encodedTitle);
			if (!this.isGroupDocuments())
			{
				return;
			}

			if (this.documentsGroup.has(uid) === false)
			{
				return;
			}

			const documentDetails = this.documentsGroup.get(uid);
			documentDetails.title = encodedTitle;
			this.documentsGroup.set(uid, documentDetails);

			this.documentSetup.updateDocumentBlock(documentDetails.id);
			this.addInDocumentsGroupUids(documentDetails.uid);
			this.#regionalSettings.documentsGroup = this.documentsGroup;
			const selectedItemUid = this.#previewDocumentDropdown.getSelectedId();
			this.#setPreviewDocumentDropdownItems();
			if (!selectedItemUid)
			{
				return;
			}

			this.#previewDocumentDropdown.setItemSelected(selectedItemUid);
		});
		this.documentSend.subscribe('disableBack', () => {
			this.wizard.toggleBtnActiveState('back', true);
		});
		this.documentSend.subscribe('enableBack', () => {
			this.wizard.toggleBtnActiveState('back', false);
		});
		this.documentSetup.subscribe('addDocument', () => {
			this.setDocumentsGroup();
		});
		this.documentSetup.subscribe('deleteDocument', ({ data }) => {
			this.#deleteDocument(data);
		});
		this.documentSetup.subscribe('editDocument', ({ data }) => {
			this.#editDocumentData(data.uid);
		});
		this.documentSend.subscribe('enableComplete', () => {
			this.wizard.toggleBtnActiveState('complete', false);
		});
		this.documentSend.subscribe('disableComplete', () => {
			this.wizard.toggleBtnActiveState('complete', true);
		});
		this.documentSetup.subscribe('documentsLimitExceeded', () => {
			this.documentSetup.setAvailabilityDocumentSection(false);
		});
		this.documentSetup.subscribe('documentsLimitNotExceeded', () => {
			this.documentSetup.setAvailabilityDocumentSection(true);
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
		this.editor.subscribe('save', ({ data }) => {
			const uid = data.uid;

			if (this.documentsGroup.has(uid) === false)
			{
				return;
			}

			this.#previewDocumentDropdown.selectItem(uid);
		});
	}

	async #editDocumentData(uid: string): Promise<void>
	{
		if (this.documentsGroup.has(uid) === false)
		{
			return;
		}

		if (!this.documentSetup.editMode)
		{
			this.#disableDocumentSectionIfLimitReached();
			this.#resetDocument();

			return;
		}

		if (this.editedDocument && this.isGroupDocuments())
		{
			this.#setPreviewDocumentDropdownItems();
		}

		this.editedDocument = this.documentsGroup.get(uid);
		this.documentSetup.setAvailabilityDocumentSection(true);
		this.#scrollToDown();
		this.documentSetup.setDocumentTitle(this.editedDocument.title);

		if (this.isGroupDocuments() === false)
		{
			return;
		}

		await this.renderPages(this.editedDocument, true, false);
		this.#previewDocumentDropdown.setItemSelected(uid);
	}

	getLayoutTemplate(header: HTMLElement, wizard: HTMLElement, preview: HTMLElement): HTMLElement
	{
		const previewDocumentDropdown = this.#previewDocumentDropdown.getLayout();
		this.#togglePreviewDocumentDropdown();

		return Tag.render`
			<div class="sign-settings__scope sign-settings --b2e">
				<div class="sign-settings__sidebar">
					${header}
					${wizard}
				</div>
				<div class="sing-settings-preview-block">
					${previewDocumentDropdown}
					${preview}
				</div>
			</div>
		`;
	}

	#createPreviewDocumentDropdown(): void
	{
		this.#previewDocumentDropdown = new SignDropdown({
			tabs: [{ id: 'sign-preview-document-dropdown', title: ' ' }],
			entities: [],
			className: 'sign-preview-document-dropdown',
			withCaption: true,
			isEnableSearch: false,
			width: 444,
		});

		this.#togglePreviewDocumentDropdown();

		this.#previewDocumentDropdown.subscribe(
			'onSelect',
			(item: BaseEvent) => {
				const uidFromEvent = item.data?.item?.id;

				if (uidFromEvent === null)
				{
					return;
				}

				if (this.documentsGroup.has(uidFromEvent) === false)
				{
					return;
				}
				const documentDetails = this.documentsGroup.get(uidFromEvent);
				this.documentSetup.resetEditMode();
				this.#resetDocument();
				this.documentSetup.setDocumentTitle(documentDetails.title);

				this.renderPages(documentDetails, true, false);
			},
		);
	}

	#setPreviewDocumentDropdownItems(): void
	{
		const selectedUid = this.documentSetup?.setupData?.uid;

		if (selectedUid === null)
		{
			return;
		}

		this.#previewDocumentDropdown.removeItems();
		this.documentsGroupUids.forEach((uid) => {
			if (this.documentsGroup.has(uid) === false)
			{
				return;
			}

			const documentDetails = this.documentsGroup.get(uid);
			this.#previewDocumentDropdown.addItem({
				id: documentDetails.uid,
				title: documentDetails.title,
				entityId: 'b2e-document-detail',
				tabs: 'sign-preview-document-dropdown',
				deselectable: false,
			});
		});

		this.#previewDocumentDropdown.setItemSelected(selectedUid);
	}

	async setDocumentsGroup(): Promise<void>
	{
		if (this.documentSetup.blankIsNotSelected || !this.documentSetup.validate())
		{
			return;
		}

		this.documentSetup.switchAddDocumentButtonLoadingState(true);
		try
		{
			const documentData = await this.setupDocument();
			this.documentsGroup.set(documentData.uid, documentData);
			this.addInDocumentsGroupUids(documentData.uid);

			this.documentSetup.blankSelector.disableSelectedBlank(documentData.blankId);

			await this.#attachGroupToDocument(documentData);
			this.documentSetup.switchAddDocumentButtonLoadingState(false);

			if (this.editedDocument)
			{
				let uid = documentData.uid;
				if (
					(this.documentSetup.blankIsNotSelected || documentData.blankId === this.editedDocument.blankId)
					&& !this.documentSetup.isFileAdded
				)
				{
					uid = this.editedDocument.uid;
				}
				await this.#handleEditedDocument(documentData).then(() => {
					this.#previewDocumentDropdown.selectItem(uid);
				}).catch(() => {});
			}
			else
			{
				this.documentSetup.renderDocumentBlock(documentData);
				if (this.isGroupDocuments())
				{
					this.#setPreviewDocumentDropdownItems();
				}

				this.#togglePreviewDocumentDropdown();
			}
		}
		catch
		{
			this.documentSetup.switchAddDocumentButtonLoadingState(false);
		}

		this.#scrollToTop();
		this.documentSetup.documentCounters.update(this.documentsGroup.size);
		this.#resetDocument();
		this.wizard.toggleBtnActiveState('next', false);
	}

	addInDocumentsGroupUids(uid: string): void
	{
		if (!this.documentsGroupUids.includes(uid))
		{
			this.documentsGroupUids.push(uid);
		}
	}

	async #handleEditedDocument(documentData): Promise<void>
	{
		if (
			(this.documentSetup.blankIsNotSelected || documentData.blankId === this.editedDocument.blankId)
			&& !this.documentSetup.isFileAdded
		)
		{
			this.documentSetup.resetEditMode();
			await this.#saveUpdatedDocumentData(this.editedDocument.uid);
			if (this.isGroupDocuments())
			{
				await this.#setPreviewDocumentDropdownItems();
			}

			return;
		}

		this.deleteFromDocumentsGroupUids(documentData.uid);
		this.replaceInDocumentsGroupUids(this.editedDocument.uid, documentData.uid);
		this.documentSetup.replaceDocumentBlock(this.editedDocument, documentData);
		await this.#deleteDocument(this.editedDocument);
		await this.#attachGroupToDocument(documentData);

		this.editor.setUrls([]);
	}

	replaceInDocumentsGroupUids(oldUid: string, newUid: string): void
	{
		const index = this.documentsGroupUids.indexOf(oldUid);
		if (index !== -1)
		{
			this.documentsGroupUids.splice(index, 1, newUid);
		}
	}

	deleteFromDocumentsGroupUids(uid: string)
	{
		const index = this.documentsGroupUids.indexOf(uid);

		if (index === -1)
		{
			return;
		}

		this.documentsGroupUids.splice(index, 1);
	}

	#removeDocumentElement(documentId): void
	{
		const deletedElement = this.documentSetup.layout.querySelector(`[data-id="document-id-${documentId}"]`);
		deletedElement?.remove();
	}

	async #attachGroupToDocument(documentData): Promise<void>
	{
		if (!this.groupId)
		{
			const { groupId } = await this.#api.createDocumentsGroup();
			this.groupId = groupId;
		}

		try
		{
			const targetDocument = this.documentsGroup.get(documentData.uid);

			if (targetDocument && !targetDocument.groupId)
			{
				await this.#api.attachGroupToDocument(documentData.uid, this.groupId);
				targetDocument.groupId = this.groupId;
			}
		}
		catch (error)
		{
			console.error(error);
		}
	}

	async #saveUpdatedDocumentData(uid: string): Promise<void>
	{
		const updatedDocumentData = await this.documentSetup.updateDocumentData(this.editedDocument);
		this.documentSetup.updateDocumentBlock(this.editedDocument.id);

		if (uid)
		{
			this.documentsGroup.set(this.editedDocument.uid, updatedDocumentData);
			this.addInDocumentsGroupUids(this.editedDocument.uid);
			this.documentSend.setDocumentsBlock(this.documentsGroup);
			this.#regionalSettings.documentsGroup = this.documentsGroup;
		}
	}

	#togglePreviewDocumentDropdown(): void
	{
		if (this.isGroupDocuments())
		{
			this.#previewDocumentDropdown.show();

			return;
		}

		this.#previewDocumentDropdown.hide();
	}

	async #deleteDocument(data: { blankId: number, deletedButton: HTMLElement, id: number, uid: string }): Promise<void>
	{
		const { id, uid, blankId, deleteButton } = data;
		this.documentSetup.ready = false;

		if (this.documentsGroup.has(uid) === false)
		{
			return;
		}

		try
		{
			await this.#api.removeDocument(uid);
			this.documentSetup.ready = true;

			if (this.isFirstDocumentSelected(uid))
			{
				this.resetPreview();
				this.hasPreviewUrls = false;
			}
			this.#removeDocumentElement(id);
			this.documentSend.deleteDocument(uid);
			this.documentsGroup.delete(uid);
			this.deleteFromDocumentsGroupUids(uid);
			this.documentSetup.blankSelector.enableSelectedBlank(blankId);
			this.documentSetup.deleteDocumentFromList(blankId);
			this.documentSetup.documentCounters?.update(this.documentsGroup.size);
			this.documentSetup.resetEditMode();

			if (this.documentsGroup.size === 0)
			{
				this.wizard.toggleBtnActiveState('next', true);
			}
			else
			{
				this.documentSetup.setupData = this.getFirstDocumentDataFromGroup();
			}

			if (this.documentsGroup.size === 0)
			{
				return;
			}

			const lastDocumentDetails = [...this.documentsGroup.values()].pop();
			await this.renderPages(lastDocumentDetails, false, false);

			if (this.isGroupDocuments())
			{
				this.#setPreviewDocumentDropdownItems();
			}

			this.#togglePreviewDocumentDropdown();

			this.#resetDocument();
		}
		catch
		{
			this.documentSetup.toggleDeleteBtnLoadingState(deleteButton);
			this.documentSetup.ready = true;
		}
	}

	async #setupParties(): Promise<Array<PartiesDetails>>
	{
		const { representative } = this.#companyParty.getParties();
		const { members, signerParty } = this.#makeSetupMembers();

		const documentUids = [...this.documentsGroup.keys()];
		for (const documentUid of documentUids)
		{
			// eslint-disable-next-line no-await-in-loop
			await this.#api.setupB2eParties(
				documentUid,
				representative.entityId,
				members,
				this.#userParty.isRejectExcludedEnabled(),
			);
		}
		const uid = this.#documentUid;
		const membersData = await this.#api.loadMembers(uid);
		if (!Type.isArrayFilled(membersData))
		{
			throw new Error('Members are empty');
		}

		const syncMemberPromises = documentUids.map((uid) => this.#syncMembersWithDepartments(uid, signerParty));
		await Promise.all(syncMemberPromises);

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

	#isTemplateModeForCompany(): boolean
	{
		const isInitiatedByCompany = this.documentSetup.setupData.initiatedByType === DocumentInitiated.company;

		return isTemplateMode(this.documentMode) && isInitiatedByCompany;
	}

	async #syncMembersWithDepartments(uid: string, signerParty: number): Promise<void>
	{
		let syncFinished = false;
		while (!syncFinished)
		{
			// eslint-disable-next-line no-await-in-loop
			const response = await this.#api.syncB2eMembersWithDepartments(
				uid,
				signerParty,
				this.#userParty.isRejectExcludedEnabled(),
			);
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
		const { representative } = this.#companyParty.getParties();

		let type = EntityType.COMPANY;
		if (representative.entityType !== EntityType.USER)
		{
			type = representative.entityType;
		}

		return {
			entityType: type,
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
			if (entityType !== EntityType.USER && entityType !== EntityType.STRUCTURE_NODE_ROLE)
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

		if (setupData.groupId)
		{
			const documentsGroupData = await this.#api.getDocumentListInGroup(setupData.groupId);
			for (const item of documentsGroupData)
			{
				this.addInDocumentsGroupUids(item.uid);

				const blocks = await this.#api.loadBlocksByDocument(item.uid);
				const updatedItem = { ...item, blocks };
				this.documentsGroup.set(item.uid, updatedItem);
				this.documentSetup.renderDocumentBlock(updatedItem);
				this.documentSetup.blankSelector.disableSelectedBlank(updatedItem.blankId);
			}
			this.groupId = setupData.groupId;
			this.documentSetup.documentCounters?.update(this.documentsGroup.size);
			this.#resetDocument();
		}
		else
		{
			this.documentsGroup.set(setupData.uid, setupData);
			this.addInDocumentsGroupUids(setupData.uid);
		}

		const firstDocument = this.getFirstDocumentDataFromGroup();
		const { entityId, representativeId, companyUid, companyEntityId, hcmLinkCompanyId } = firstDocument;
		this.documentSend.documentData = this.documentsGroup;

		this.#disableDocumentSectionIfLimitReached();

		if (this.isSingleDocument())
		{
			this.editor.documentData = firstDocument;
		}

		const members = await this.#api.loadMembers(uid);

		if (representativeId)
		{
			const memberEntityType = this.#getMemberEntityTypeByRole(members, MemberRole.assignee);
			this.#companyParty.loadRepresentative(representativeId, memberEntityType);
		}

		const parsedMembers = this.#parseMembers(members);
		const { signers = [], assignees = [] } = parsedMembers;

		this.#companyParty.setEntityId(entityId);
		if (companyUid)
		{
			this.#regionalSettings.setLastSavedHcmLinkCompanyId(hcmLinkCompanyId);
			this.#companyParty.loadCompany(
				companyUid,
				// workaround (assignee member) for old drafts/templates
				companyEntityId ?? assignees[0] ?? null,
			);
		}

		if (signers.length > 0)
		{
			this.#userParty.load(signers);
		}

		this.#companyParty.loadValidator(members);

		return true;
	}

	#getMemberEntityTypeByRole(members: SetupMember[], role: MemberRole): string
	{
		const member: SetupMember = members.find(
			(setupMemberItem: SetupMember) => setupMemberItem.role === role,
		);

		return member?.entityType === EntityType.STRUCTURE_NODE_ROLE ? EntityType.STRUCTURE_NODE_ROLE : EntityType.USER;
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

				if (!Type.isNull(signSettings.getAfterPreviewLayout()))
				{
					BX.show(signSettings.getAfterPreviewLayout());
				}

				return layout;
			},
			title: Loc.getMessage('SIGN_SETTINGS_B2B_LOAD_DOCUMENT'),
			beforeCompletion: async () => {
				const blankIsSelected = this.documentSetup.blankSelector.selectedBlankId !== 0;
				if (blankIsSelected || this.documentSetup.isFileAdded)
				{
					const isValid = this.documentSetup.validate();
					if (!isValid)
					{
						return false;
					}
				}

				const setupData = await this.setupDocument();
				if (!setupData)
				{
					return false;
				}

				await this.#addDocumentInGroup(setupData).then(() => {
					if (this.isGroupDocuments())
					{
						this.#setPreviewDocumentDropdownItems();
					}

					this.#togglePreviewDocumentDropdown();
				}).catch(() => {});
				if (this.editedDocument)
				{
					let uid = setupData.uid;
					if (
						(this.documentSetup.blankIsNotSelected || setupData.blankId === this.editedDocument.blankId)
						&& !this.documentSetup.isFileAdded
					)
					{
						uid = this.editedDocument.uid;
					}

					await this.#handleEditedDocument(setupData).then(() => {
						this.#previewDocumentDropdown.selectItem(uid);
					}).catch(() => {});
				}

				this.#resetDocument();
				this.#processSetupData();
				this.#disableDocumentSectionIfLimitReached();

				if (!Type.isNull(this.getAfterPreviewLayout()))
				{
					BX.hide(this.getAfterPreviewLayout());
				}

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
				const isTemplateModeForCompany = signSettings.#isTemplateModeForCompany();

				if (signSettings.isTemplateMode())
				{
					signSettings.#companyParty.setEditorAvailability(isTemplateModeForCompany);
				}
				SignSettingsItemCounter.numerate(layout);

				if (!signSettings.isEditMode())
				{
					if (!signSettings.#companyParty.isRepresentativeSelected())
					{
						signSettings.#companyParty.loadFirstRepresentative();
					}

					if (!signSettings.#companyParty.isProviderSelected())
					{
						signSettings.#companyParty.loadFirstCompany();
					}
				}

				return layout;
			},
			title: Loc.getMessage(titleLocCode),
			beforeCompletion: async () => {
				const { initiatedByType } = this.documentSetup.setupData;
				try
				{
					for (const [uid] of this.documentsGroup)
					{
						// eslint-disable-next-line no-await-in-loop
						await this.#companyParty.save(uid);
					}
					this.#regionalSettings.companyId = this.#companyParty.getSelectedCompanyId();
					this.#regionalSettings.documentsGroup = this.documentsGroup;

					this.editor.setSenderType(initiatedByType);

					this.#setSecondPartySectionVisibility();

					if (this.isTemplateMode())
					{
						this.#executeDocumentSendActions();
						this.#regionalSettings.isIntegrationVisible = !this.#isInitiatedByEmployee();

						if (!this.#isRegionalSettingsStepEnabled())
						{
							await this.#executeTemplateSetupPartyActions();
						}
					}
				}
				catch (e)
				{
					console.warn(e);

					return false;
				}

				return true;
			},
		};
	}

	#getRegionalSettingsStep(signSettings: B2ESignSettings, documentUid: string): $Values<Metadata>
	{
		return {
			get content(): HTMLElement
			{
				return signSettings.#regionalSettings.getLayout();
			},
			title: Loc.getMessage('SIGN_SETTINGS_B2E_REGIONAL_SETTINGS_SHORT'),
			beforeCompletion: async () => {
				try
				{
					await this.#regionalSettings.save();
				}
				catch
				{
					return false;
				}

				this.documentSetup.setupData.hcmLinkCompanyId = this.#regionalSettings.getSelectedHcmLinkCompanyId();
				this.documentSend.hcmLinkEnabled = this.documentSetup.setupData.hcmLinkCompanyId > 0;

				await this.#setHcmLinkIntegrationSectionVisibility();

				if (this.isTemplateMode())
				{
					await this.#executeTemplateSetupPartyActions();
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

					const { uid, isTemplate, entityId } = this.documentSetup.setupData;
					const blocks = await this.documentSetup.loadBlocks(uid);

					this.#executeDocumentSendActions();

					const editorData = { isTemplate, uid, blocks, entityId };
					await this.#executeEditorActions(editorData);

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

	async #executeDocumentSendActions(): Promise<void>
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
		this.documentSend.documentData = this.documentsGroup;
		this.documentSend.resetUserPartyPopup();
		this.documentSend.setPartiesData(partiesData);
	}

	async #executeEditorActions(editorData: {
		isTemplate: boolean,
		uid: string,
		blocks: Array,
		entityId: number
	}): Promise<void>
	{
		if (this.isTemplateCreateMode())
		{
			this.editor.setAnalytics(this.getAnalytics());
		}
		this.wizard.toggleBtnLoadingState('next', false);
		if (this.isSingleDocument())
		{
			this.editor.documentData = editorData;
			await this.editor.renderDocument();
			if (!this.#needSkipEditorStep || this.isTemplateMode())
			{
				await this.editor.show();
			}
		}
	}

	#getSendStep(signSettings: B2ESignSettings): Metadata[string]
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

				if (!signSettings.documentSend.isDateTimeLimitSelectorValid())
				{
					signSettings.wizard.toggleBtnActiveState('complete', true);
				}

				return layout;
			},
			title: Loc.getMessage(titleLocCode),
			beforeCompletion: async () => {
				return this.documentSend.sendForSign();
			},
		};
	}

	getStepsMetadata(signSettings: this, documentUid: ?string, templateUid?: string): Metadata
	{
		this.#sendAnalyticsOnStart(documentUid);
		const steps = {
			setup: this.#getSetupStep(signSettings, documentUid),
			company: this.#getCompanyStep(signSettings),
		};

		if (this.#isRegionalSettingsStepEnabled())
		{
			steps.regionalSettings = this.#getRegionalSettingsStep(signSettings, documentUid);
		}

		if (this.isDocumentMode())
		{
			steps.employees = this.#getEmployeeStep(signSettings);
		}

		steps.send = this.#getSendStep(signSettings);

		this.#decorateStepsBeforeCompletionWithAnalytics(steps, documentUid);

		return steps;
	}

	#isRegionalSettingsStepEnabled(): boolean
	{
		return this.documentSetup.isRuRegion();
	}

	async init(uid: ?string, templateUid: string)
	{
		await super.init(uid, templateUid);
		this.#setPreviewDocumentDropdownItems();

		if (this.isEditMode() && !Type.isNull(this.getAfterPreviewLayout()))
		{
			BX.hide(this.getAfterPreviewLayout());
		}

		if (this.isEditMode())
		{
			this.documentSetup.setDocumentTitle(this.documentSetup.setupData.title);
			this.documentSetup.enableDocumentInputs();
		}
	}

	onComplete(showNotification: boolean = true): void
	{
		if (this.isTemplateMode())
		{
			return;
		}
		super.onComplete(showNotification);
	}

	isTemplateCreateMode(): boolean
	{
		return isTemplateMode(this.documentMode) && !this.isEditMode();
	}

	#setSecondPartySectionVisibility(): void
	{
		const selectedProvider = this.#companyParty.getSelectedProvider();
		const isNotSesRuProvider = selectedProvider.code !== ProviderCode.sesRu;

		const isSecondPartySectionVisible = isNotSesRuProvider
			|| (isTemplateMode(this.documentMode) && this.#isInitiatedByEmployee())
		;

		this.editor.setSectionVisibilityByType(
			SectionType.SecondParty,
			isSecondPartySectionVisible,
		);
	}

	async #setHcmLinkIntegrationSectionVisibility(): Promise<void>
	{
		const isInitiatedByCompany = this.documentSetup.setupData.initiatedByType === DocumentInitiated.company;

		if (this.#isInitiatedByEmployee() && this.documentSetup.isRuRegion())
		{
			await this.#api.changeIntegrationId(this.documentSetup.setupData.uid, null);
		}

		const isHcmLinkIntegrationSectionVisible = this.documentSetup.setupData.hcmLinkCompanyId > 0
			&& isInitiatedByCompany
		;

		this.editor.setSectionVisibilityByType(
			SectionType.HcmLinkIntegration,
			isHcmLinkIntegrationSectionVisible,
		);
	}

	#isInitiatedByEmployee(): boolean
	{
		return this.documentSetup.setupData.initiatedByType === DocumentInitiated.employee;
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

	async #processSetupData(): Promise<void>
	{
		const setupData = this.getDocumentSetupData();
		this.#companyParty.setInitiatedByType(setupData.initiatedByType);
		this.#companyParty.setEntityId(setupData.entityId);
		if (this.isTemplateMode())
		{
			this.#companyParty.reloadCompanyProviders();
		}
		this.editedDocument = null;

		if (this.hasPreviewUrls)
		{
			this.wizard.toggleBtnActiveState('next', false);
		}
	}

	async #addDocumentInGroup(setupData: DocumentDetails): Promise<void>
	{
		if (this.documentSetup.blankIsNotSelected)
		{
			return;
		}

		if (this.documentsGroup.has(setupData.uid))
		{
			return;
		}

		if (this.isTemplateMode() || !FeatureStorage.isGroupSendingEnabled())
		{
			this.setSingleDocument(setupData);

			return;
		}

		this.documentsGroup.set(setupData.uid, setupData);
		this.addInDocumentsGroupUids(setupData.uid);
		this.documentSetup.blankSelector.disableSelectedBlank(setupData.blankId);

		if (!setupData.groupId && !this.editedDocument)
		{
			await this.#attachGroupToDocument(setupData);
		}

		if (!this.isTemplateMode())
		{
			this.documentSetup.documentCounters.update(this.documentsGroup.size);
		}

		if (!this.editedDocument)
		{
			this.documentSetup.renderDocumentBlock(setupData);
		}
	}

	#scrollToTop(): void
	{
		window.scrollTo({
			top: 0,
			behavior: 'smooth',
		});
	}

	#scrollToDown(): void
	{
		window.scrollTo({
			top: document.body.scrollHeight,
			behavior: 'smooth',
		});
	}

	#resetDocument(): void
	{
		if (this.isTemplateMode() || !FeatureStorage.isGroupSendingEnabled())
		{
			return;
		}

		this.documentSetup.resetDocument();

		this.editedDocument = null;
	}

	#disableDocumentSectionIfLimitReached(): void
	{
		if (this.documentsGroup.size >= this.#maxDocumentCount)
		{
			this.documentSetup.setAvailabilityDocumentSection(false);
		}
	}

	getAfterPreviewLayout(): HTMLElement | null
	{
		if (!this.isDocumentMode() || !FeatureStorage.isMultiDocumentLoadingEnabled())
		{
			return null;
		}

		return this.#cache.remember('beforePreviewLayout', () => Tag.render`
			<button class="ui-btn ui-btn-light-border ui-btn-md" style="margin-top: 20px;" onclick="${() => this.#onBeforePreviewBtnClick()}">
				${Loc.getMessage('SIGN_SETTINGS_B2E_BEFORE_PREVIEW')}
			</button>
		`);
	}

	#onBeforePreviewBtnClick(): void
	{
		// eslint-disable-next-line unicorn/no-this-assignment
		const self = this;
		BX.SidePanel.Instance.open('sign-settings:afterPreviewSidePanel', {
			cacheable: false,
			width: 750,
			contentCallback: () => {
				self.#resetAfterPreviewSidePanel();

				return Layout.createContent({
					extensions: ['ui.forms'],
					title: 'Добавить папку с файлами',
					content(): void
					{
						self.#getUploader();

						return self.#getMultiDocumentAddSidePanelContent();
					},
					buttons({ cancelButton, SaveButton })
					{
						self.#saveButton = new SaveButton({
							onclick: () => self.#onBeforePreviewSaveBtnClick(),
						});

						return [
							self.#saveButton,
						];
					},
				});
			},
			events: {
				onClose: (event) => {
					if (this.#isMultiDocumentSaveProcessGone)
					{
						event.denyAction();
					}
					this.#resetAfterPreviewSidePanel();
				},
			},
		});
	}

	#getMultiDocumentAddSidePanelContent(): HTMLElement
	{
		return this.#cache.remember('multiDocumentAddSidePanelContent', () => Tag.render`
			<div id="multiple-document-add-container" style="display: flex; flex-direction: column;">
				<div style="flex-direction: row;">
					${this.#getUploadFileFromDirButton().root}
					${this.#getUploadFileButton()}
				</div>
			</div>
		`);
	}

	#getUploader(): Uploader
	{
		return this.#cache.remember('uploader', () => {
			return new Uploader({
				id: 'sign-settings-uploader',
				controller: 'sign.upload.blankUploadController',
				acceptedFileTypes: [...acceptedUploaderFileTypes.values()].map((a) => `.${a}`),
				multiple: true,
				autoUpload: false,
				maxFileSize: 52_428_800,
				imageMaxFileSize: 10_485_760,
				maxTotalFileSize: 52_428_800,
				events: {
					[UploaderEvent.BEFORE_FILES_ADD]: (event) => this.#onBeforeFilesAdd(event),
					[UploaderEvent.FILE_ADD]: (event) => this.#onFileAdd(event.getData().file),
					[UploaderEvent.UPLOAD_COMPLETE]: (event) => this.#onUploadComplete(event),
				},
			});
		});
	}

	#resetAfterPreviewSidePanel(): void
	{
		this.#cache.delete('uploader');
		this.#cache.delete('uploadButton');
		this.#cache.delete('uploadFromDirButton');
		this.#cache.delete('multiDocumentAddSidePanelContent');
	}

	#getUploadFileButton(): HTMLElement
	{
		return this.#cache.remember('uploadButton', () => {
			const layout = Tag.render`
				<div>
					<button class="ui-btn ui-btn-light-border" style="margin-top: 15px;" onclick="${() => layout.fileInput.click()}">${Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_ADD_FILE')}</button>
					<input ref="fileInput" hidden type="file" multiple ref="fileInput" onchange="${(event) => {
							this.#onInputFileChange(event);
							event.target.value = '';
						}}"
						accept="${[...acceptedUploaderFileTypes.values()].map((n) => `.${n}`).join(', ')}"
					>
				</div>
			`;

			return layout.root;
		});
	}

	#getUploadFileFromDirButton(): { root: HTMLElement, fileInput: HTMLInputElement }
	{
		return this.#cache.remember('uploadFromDirButton', () => {
			const layout = Tag.render`
				<div>
					<button class="ui-btn ui-btn-primary" style="margin-top: 15px;" onclick="${() => layout.fileInput.click()}">${Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_LOAD_FROM_DIRS')}</button>
					<input hidden type="file" webkitdirectory multiple ref="fileInput" onchange="${(event) => {
						this.#onInputFileChange(event);
						event.target.value = '';
					}}">
				</div>
			`;

			return layout;
		});
	}

	#onInputFileChange(event: Event): void
	{
		const target: HTMLInputElement = event.target;
		const files = target.files;
		const validatedFiles = [...files].filter((f: File) => acceptedUploaderFileTypes.has(
			f.name.split('.').at(-1),
		));
		this.#getUploader().addFiles(validatedFiles);
	}

	#onFileAdd(file: UploaderFile): void
	{
		Dom.insertAfter(
			Tag.render`<p style="color: #666">${Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_LOADED_FILE_NAME', { '#FILENAME#': Text.encode(file.getName()) })}</p>`,
			this.#getUploadFileButton(),
		);
	}

	#onBeforePreviewSaveBtnClick(): void
	{
		if (this.#getUploader().getFiles().length === 0)
		{
			alert(Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_NO_FILES_SELECTED'));

			return;
		}

		Dom.style(this.#getMultiDocumentAddSidePanelContent(), { opacity: 0.6, 'pointer-events': 'none' });
		this.#isMultiDocumentSaveProcessGone = true;
		this.#getUploader().start();
		this.#saveButton.setClocking(true);
	}

	async #onUploadComplete(event: BaseEvent): void
	{
		const uploader = this.#getUploader();
		for (const file of uploader.getFiles())
		{
			try
			{
				const blankId = await this.documentSetup.blankSelector.createBlankFromOuterUploaderFiles([file]);
				this.documentSetup.blankSelector.selectBlank(blankId);
				await this.setDocumentsGroup();
			}
			catch (e)
			{
				console.error(`Error while add file with name ${file.getName()}`, e);
			}
		}

		Dom.style(this.#getMultiDocumentAddSidePanelContent(), { opacity: 1, 'pointer-events': 'auto' });
		this.#isMultiDocumentSaveProcessGone = false;
		this.#saveButton.setClocking(false);
		BX.SidePanel.Instance.close();
	}

	#onBeforeFilesAdd(event: BaseEvent<{ files: Array<UploaderFile> }>): void
	{
		const uploaderConfig = {
			maxFileSize: 52_428_800,
			imageMaxFileSize: 10_485_760,
			maxTotalFileSize: 52_428_800,
		};
		const data = event.getData();
		const files: UploaderFile[] = data.files;
		const uploader = this.#getUploader();
		const allFilesWithNew: UploaderFile[] = [...files, ...uploader.getFiles()];

		if ((allFilesWithNew.length + this.documentsGroup.size) > this.#maxDocumentCount)
		{
			alert(Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_MAX_DOCUMENTS_COUNT_EXCEEDED_NOTICE'));
			event.preventDefault();

			return;
		}

		for (const file of files)
		{
			if (file.getSize() > uploaderConfig.maxFileSize)
			{
				alert(Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_INVALID_FILE_SIZE'));
				event.preventDefault();

				return;
			}

			if (file.isImage() && file.getSize() > uploaderConfig.imageMaxFileSize)
			{
				alert(Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_INVALID_IMAGE_FILE_SIZE'));
				event.preventDefault();

				return;
			}
		}

		const totalFileSize = allFilesWithNew.reduce((acc, file) => acc + file.getSize(), 0);
		if (totalFileSize > uploaderConfig.maxTotalFileSize)
		{
			alert(Loc.getMessage('SIGN_V2_B2E_SIGN_SETTINGS_MAX_TOTAL_FILE_SIZE_EXCEEDED'));
			event.preventDefault();
		}
	}

	#isIntegrationEnabled(code: ProviderCodeType): boolean
	{
		return code && code !== ProviderCode.sesRu;
	}

	async #executeTemplateSetupPartyActions(): Promise<void>
	{
		const { uid } = this.documentSetup.setupData;
		const isB2eDocumentSectionDisabled = !this.documentSetup.isRuRegion() || this.#isInitiatedByEmployee();
		this.editor.setIsB2eDocumentSectionDisabled(isB2eDocumentSectionDisabled);
		this.editor.entityData = await this.#setupParties();
		const { isTemplate, entityId } = this.documentSetup.setupData;
		const blocks = await this.documentSetup.loadBlocks(uid);

		const editorData = { isTemplate, uid, blocks, entityId };
		this.#executeEditorActions(editorData);
	}
}
