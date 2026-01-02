import { Dom, Loc, Tag, Type, Text, Extension } from 'main.core';
import { MemoryCache } from 'main.core.cache';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { DateTimeFormat, Timezone } from 'main.date';
import { BitrixVue } from 'ui.vue3';
import { createPinia } from 'ui.vue3.pinia';
import { Slider } from 'main.sidepanel';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { Mapper } from 'humanresources.hcmlink.data-mapper';
import { Button, ButtonSize, ButtonColor } from 'ui.buttons';

import { HcmLinkVacancyChooser } from 'sign.v2.b2e.hcm-link-employee-selector';
import { DocumentTemplateSend } from 'sign.v2.b2e.document-template-send';
import { DocumentTemplateUserParty } from 'sign.v2.b2e.document-template-user-party';
import { Api, HcmLinkMultipleVacancyEmployee } from 'sign.v2.api';
import { DocumentTemplateFilling } from 'sign.v2.b2e.document-template-filling';
import { useDocumentTemplateFillingStore } from './store';

import type { PopupOptions } from 'main.popup';
import type {
	LoadedDocumentData,
	TemplateCreatedDocument,
	HcmLinkNotMappedUsersData,
	HcmLinkMultipleVacancyEmployeesLoadData,
} from 'sign.v2.api';
import { type Metadata, Wizard } from 'ui.wizard';
import type { DocumentSettings, DocumentSettingsByTemplateDocumentUid } from './types';

import './style.css';
import './../../sign-settings/src/style.css';
import './../../../sign-settings/src/style.css';

export { useDocumentTemplateFillingStore };
export type { DocumentSettings, DocumentSettingsByTemplateDocumentUid };

export class B2ETemplatesSignSettings
{
	#cache: MemoryCache<any> = new MemoryCache();
	#documentFilling: DocumentTemplateFilling;
	#documentUserParty: DocumentTemplateUserParty;
	#documentSend: DocumentTemplateSend;
	#templateIds: number[];
	#store: Object;
	#api: Api;
	#sendLayout: HTMLElement;
	#wizardLayout: HTMLElement;
	#piniaInitStubApp: Object;
	#sliderUrl: string;
	#bypassSliderCloseCheck: boolean = false;
	#container: ?HTMLElement = null;
	#confirmPopup: ?MessageBox = null;
	#region: string;

	constructor(
		templateIds: number[] = [],
		sliderUrl: string = '',
	)
	{
		this.#region = Extension.getSettings('sign.v2.b2e.sign-settings-templates').get('region');
		this.#templateIds = templateIds;
		this.#store = createPinia();
		this.#api = new Api();
		this.#piniaInitStubApp = BitrixVue.createApp({});
		this.#piniaInitStubApp.use(this.#store);
		useDocumentTemplateFillingStore().setRuRegionFieldsVisible(this.#isRuRegionFieldsVisible());
		this.#documentSend = new DocumentTemplateSend(this.#store);
		this.#documentSend.subscribe('close', () => this.#closeSlider());

		this.#documentFilling = new DocumentTemplateFilling({
			store: this.#store,
		});

		this.#documentUserParty = new DocumentTemplateUserParty();
		this.#sliderUrl = sliderUrl;
		this.#subscribeSliderCloseEvent();
	}

	#getDocumentFillingStep(): Metadata[string]
	{
		const documentFilling = this.#documentFilling;

		return {
			get content(): HTMLElement
			{
				return documentFilling.getLayout();
			},
			title: Loc.getMessage('SIGN_SETTINGS_TEMPLATES_STEP_DOCUMENT_TITLE'),
			beforeCompletion: (): boolean => {
				return documentFilling.validate();
			},
		};
	}

	async #createDocumentsAndSaveToStorageIfNotCreated(): Promise<void>
	{
		const store = useDocumentTemplateFillingStore();
		if (store.createdDocuments.length > 0)
		{
			return;
		}

		const documents: LoadedDocumentData[] = store.documents;
		const templateIds = documents.map((document: LoadedDocumentData) => document.templateId);
		const { items } = await this.#api.template.registerDocuments(
			templateIds,
			this.#documentUserParty.isRejectExcludedEnabled(),
		);
		store.setCreatedDocuments(items);
	}

	async #updateCreatedDocumentsSettings(): Promise<void>
	{
		const store = useDocumentTemplateFillingStore();
		const templateDocuments: LoadedDocumentData[] = store.documents;
		const settings: DocumentSettingsByTemplateDocumentUid = store.settings;
		const createdDocuments: TemplateCreatedDocument[] = store.createdDocuments;
		for (const [templateDocumentUid, documentSettings] of Object.entries(settings))
		{
			const templateDocument: LoadedDocumentData = templateDocuments.find(
				(document: LoadedDocumentData) => document.uid === templateDocumentUid,
			);

			const templateId = templateDocument?.templateId;
			if (!templateId)
			{
				throw new Error('templateId not found');
			}

			const templateCreatedDocument: TemplateCreatedDocument = createdDocuments.find(
				(value: TemplateCreatedDocument) => value.templateId === templateId,
			);
			if (!templateCreatedDocument)
			{
				throw new Error('templateCreatedDocument not found');
			}

			// @TODO make mass update
			// eslint-disable-next-line no-await-in-loop
			await this.#updateDocumentSettings(templateCreatedDocument.document, documentSettings);
		}
	}

	async #updateDocumentSettings(document: LoadedDocumentData, settings: DocumentSettings): Promise<void>
	{
		const uid = document.uid;
		if (this.#isRuRegionFieldsVisible()
			&& settings.registrationNumber.length > 0
			&& document.externalIdSourceType !== 'hcmlink'
		)
		{
			await this.#api.changeExternalId(uid, settings.registrationNumber);
		}

		if (this.#isRuRegionFieldsVisible() && document.externalDateCreateSourceType !== 'hcmlink')
		{
			const formattedDate = DateTimeFormat.format(
				DateTimeFormat.getFormat('SHORT_DATE_FORMAT'),
				settings.creationDate,
			);

			await this.#api.changeExternalDate(uid, formattedDate);
		}

		const tsFromUserTime = Timezone.UserTime.toUTCTimestamp(settings.signingDate);
		await this.#api.modifyDateSignUntil(uid, tsFromUserTime);
	}

	#getEmployeeSelectionStep(): Metadata[string]
	{
		const userParty = this.#documentUserParty;

		return {
			get content(): HTMLElement
			{
				return userParty.getLayout();
			},
			title: Loc.getMessage('SIGN_SETTINGS_TEMPLATES_STEP_EMPLOYEES_TITLE'),
			beforeCompletion: async (): Promise<boolean> => {
				if (!userParty.validate())
				{
					return false;
				}

				try
				{
					this.#showSendLayout();
					await this.#createDocumentsAndSaveToStorageIfNotCreated();
					await this.#updateCreatedDocumentsSettings();
					await this.#documentUserParty.syncMembers();
					await this.#waitAllIntegrationMapped();
					await this.#waitAllIntegrationEmployeesSelected();
					await this.#configure();
					this.#setConfigured();
					await this.#waitForFillAndStartComplete();

					this.#closeTemplateGridSlider();
					this.#showCompleteNotification();

					return true;
				}
				catch (e)
				{
					if (e)
					{
						console.error(e);
					}

					if (this.#isConfigured())
					{
						return true;
					}

					this.#hideSendLayout();

					return false;
				}
			},
		};
	}

	#getStepsMetadata(): Metadata
	{
		return {
			documentFillingStep: this.#getDocumentFillingStep(),
			employeeSelectionStep: this.#getEmployeeSelectionStep(),
		};
	}

	#getLayout(wizard: Wizard): HTMLElement
	{
		this.#wizardLayout = wizard.getLayout();
		this.#sendLayout = this.#documentSend.getLayout();
		Dom.hide(this.#sendLayout);

		return Tag.render`
			<div class="sign-settings__scope sign-settings --no-background --b2e --templates">
				<div class="sign-settings__sidebar">
					${this.#createHead()}
					${this.#wizardLayout}
					${this.#sendLayout}
				</div>
			</div>
		`;
	}

	#createHead(): HTMLElement
	{
		return this.#cache.remember('headLayout', (): HTMLElement => {
			return Tag.render`
				<div class="sign-settings__head">
					<div>
						<p class="sign-settings__head_title">
							${Loc.getMessage('SIGN_SETTINGS_TEMPLATES_HEAD_TITLE')}
						</p>
					</div>
				</div>
			`;
		});
	}

	async renderToContainer(container: HTMLElement | null): void
	{
		if (Type.isNull(container))
		{
			return;
		}
		this.#container = container;

		await this.#loadTemplates();
		const wizard = new Wizard(this.#getStepsMetadata(), {
			back: { className: 'ui-btn-light-border' },
			next: { className: 'ui-btn-success' },
			complete: {
				className: 'ui-btn-success',
				title: Loc.getMessage('SIGN_SETTINGS_TEMPLATES_COMPLETE_TITLE'),
				onComplete: () => this.#closeSlider(),
			},
			cancel: {
				className: 'ui-btn-light-border',
				onCancel: () => this.#closeSlider(),
			},
			swapButtons: true,
		});

		Dom.append(this.#getLayout(wizard), container);
		wizard.moveOnStep(0);
	}

	#showSendLayout(): void
	{
		this.#setSendProgress(0);
		Dom.style(this.#wizardLayout, 'display', 'none');
		Dom.show(this.#sendLayout);
	}

	#hideSendLayout(): void
	{
		Dom.hide(this.#sendLayout);
		Dom.style(this.#wizardLayout, 'display', 'block');
	}

	async #configure(): Promise<void>
	{
		const createdDocuments = useDocumentTemplateFillingStore().createdDocuments;
		for (const createdDocument: TemplateCreatedDocument of createdDocuments)
		{
			// eslint-disable-next-line no-await-in-loop
			await this.#api.configureDocument(createdDocument.document.uid);
		}
	}

	async #waitForFillAndStartComplete(): Promise<void>
	{
		const createdDocuments = useDocumentTemplateFillingStore().createdDocuments;
		const ids = createdDocuments.map((value: TemplateCreatedDocument) => value.document.id);
		let completed = false;
		while (!completed)
		{
			// eslint-disable-next-line no-await-in-loop
			const result = await this.#api.getManyDocumentFillAndStartProgress(ids);
			completed = result.completed;
			this.#setSendProgress(Math.round(result.progress));
			// eslint-disable-next-line no-await-in-loop
			await this.#sleep(1000);
		}
	}

	#sleep(ms: Number): Promise
	{
		return new Promise((resolve): void => {
			setTimeout(resolve, ms);
		});
	}

	async #loadTemplates(): Promise<void>
	{
		const templateDocuments = await this.#getDocumentsByTemplateIds(this.#templateIds);

		useDocumentTemplateFillingStore().setDocuments(templateDocuments);
	}

	async #getDocumentsByTemplateIds(templateIds: number[]): Promise<Array>
	{
		return this.#api.loadDocumentsByTemplateIds(templateIds);
	}

	#closeTemplateGridSlider(): void
	{
		const prevSlider = window.top.BX.SidePanel.Instance.getPreviousSlider();
		if (prevSlider && prevSlider.getUrl().indexOf('templates/folder/?folderId='))
		{
			prevSlider.close();
		}
	}

	#showCompleteNotification(): void
	{
		const notificationText = this.#templateIds.length > 1
			? Loc.getMessage('SIGN_SETTINGS_COMPLETE_NOTIFICATION_TEXT_GROUP')
			: Loc.getMessage('SIGN_SETTINGS_COMPLETE_NOTIFICATION_TEXT')
		;

		window.top.BX.UI.Notification.Center.notify({
			content: notificationText,
			autoHideDelay: 4000,
		});
	}

	async #waitAllIntegrationMapped(): Promise
	{
		// eslint-disable-next-line no-constant-condition
		while (true)
		{
			// eslint-disable-next-line no-await-in-loop
			const notMapped = await this.#getFirstNotMappedIntegration();
			if (notMapped)
			{
				// eslint-disable-next-line no-await-in-loop
				await this.#waitIntegrationSync(notMapped);
			}
			else
			{
				break;
			}
		}
	}

	async #getFirstNotMappedIntegration(): ?HcmLinkNotMappedUsersData
	{
		if (!this.#isCreatedDocumentsHasIntegration())
		{
			return null;
		}

		const createdDocumentUids = this.#getCreateDocumentsUids();
		const integrations = await this.#api.checkNotMappedMembersHrIntegrationByDocuments(createdDocumentUids);

		return integrations.find((integration: HcmLinkNotMappedUsersData): boolean => integration.userIds.length > 0);
	}

	#waitIntegrationSync(integration: HcmLinkNotMappedUsersData): Promise<void>
	{
		return new Promise((resolve, reject): void => {
			this.#showNotMappedPopup(resolve, reject, integration);
		});
	}

	#setSendProgress(value: number): void
	{
		useDocumentTemplateFillingStore().setSendProgress(value);
	}

	#closeSlider(): void
	{
		const slider = BX.SidePanel.Instance.getTopSlider();
		if (slider && this.#isMasterSlider(slider))
		{
			slider.close();
		}
	}

	#isMasterSlider(slider: Slider): boolean
	{
		return /sign-b2e-templates-settings-\d+-(template|folder)/.test(slider.getUrl());
	}

	#setConfigured(value: boolean = true): void
	{
		useDocumentTemplateFillingStore().setConfigured(value);
	}

	#showNotMappedPopup(resolve: function, reject: function, integration: HcmLinkNotMappedUsersData): void
	{
		let shouldPopupCloseReject = true;

		const popup = MessageBox.create({
			message: this.#getHcmPopupLayout(
				Loc.getMessage('SIGN_SETTINGS_TEMPLATES_NOT_MAPPED_POPUP_TITLE'),
				Loc.getMessage('SIGN_SETTINGS_TEMPLATES_NOT_MAPPED_POPUP_DESCRIPTION'),
			),
			buttons: [
				new Button({
					text: Loc.getMessage('SIGN_SETTINGS_TEMPLATES_NOT_MAPPED_POPUP_OK'),
					size: ButtonSize.S,
					color: ButtonColor.PRIMARY,
					round: true,
					onclick: (): void => {
						shouldPopupCloseReject = false;
						popup.close();

						Mapper.openSlider({
							companyId: integration.integrationId,
							userIds: new Set(integration.allUserIds),
							mode: Mapper.MODE_DIRECT,
						}, {
							onCloseHandler: () => resolve(),
						});
					},
				}),
				new Button({
					text: Loc.getMessage('SIGN_SETTINGS_TEMPLATES_NOT_MAPPED_POPUP_CANCEL'),
					size: ButtonSize.S,
					color: ButtonColor.LIGHT_BORDER,
					round: true,
					onclick: (): void => popup.close(),
				}),
			],
			modal: false,
			popupOptions: {
				events: {
					onPopupClose: (): void => {
						if (shouldPopupCloseReject)
						{
							reject();
						}
					},
				},
				...this.#getHcmPopupOptions(),
			},
		});

		popup.show();
	}

	#isConfigured(): boolean
	{
		return useDocumentTemplateFillingStore().configured;
	}

	async #waitAllIntegrationEmployeesSelected(): Promise
	{
		// eslint-disable-next-line no-constant-condition
		while (true)
		{
			// eslint-disable-next-line no-await-in-loop
			const notSelected = await this.#getFirstNotEmployeesSelectedIntegration();
			if (notSelected)
			{
				// eslint-disable-next-line no-await-in-loop
				await this.#waitEmployeeSelect(notSelected);
			}
			else
			{
				break;
			}
		}
	}

	async #getFirstNotEmployeesSelectedIntegration(): ?HcmLinkMultipleVacancyEmployeesLoadData
	{
		if (!this.#isCreatedDocumentsHasIntegration())
		{
			return null;
		}

		const createdDocumentUids = this.#getCreateDocumentsUids();

		const integrations = await this.#api.loadBulkMultipleVacancyMemberHrIntegrations(createdDocumentUids);

		return integrations.find(
			(integration: HcmLinkMultipleVacancyEmployeesLoadData): boolean => integration.employees.length > 0,
		);
	}

	#isCreatedDocumentsHasIntegration(): boolean
	{
		const store = useDocumentTemplateFillingStore();
		const createdDocuments: TemplateCreatedDocument[] = store.createdDocuments;

		return createdDocuments.some((value: TemplateCreatedDocument): boolean => value.document.hcmLinkCompanyId > 0);
	}

	#getCreateDocumentsUids(): string[]
	{
		const store = useDocumentTemplateFillingStore();
		const createdDocuments: TemplateCreatedDocument[] = store.createdDocuments;

		return createdDocuments.map((value: TemplateCreatedDocument): string => value.document.uid);
	}

	#waitEmployeeSelect(integration: HcmLinkMultipleVacancyEmployeesLoadData): Promise<void>
	{
		return new Promise((resolve, reject): void => {
			this.#showNotSelectedEmployeePopup(resolve, reject, integration);
		});
	}

	#showNotSelectedEmployeePopup(
		resolve: function,
		reject: function,
		integration: HcmLinkMultipleVacancyEmployeesLoadData,
	): void
	{
		let shouldPopupCloseReject = true;

		const popup = MessageBox.create({
			message: this.#getHcmPopupLayout(
				Loc.getMessage('SIGN_SETTINGS_TEMPLATES_EMPLOYEES_NOT_SELECTED_POPUP_TITLE'),
				Loc.getMessage('SIGN_SETTINGS_TEMPLATES_EMPLOYEES_NOT_SELECTED_POPUP_DESCRIPTION'),
			),
			buttons: [
				new Button({
					text: Loc.getMessage('SIGN_SETTINGS_TEMPLATES_EMPLOYEES_NOT_SELECTED_POPUP_OK'),
					size: ButtonSize.S,
					color: ButtonColor.PRIMARY,
					round: true,
					onclick: (): void => {
						shouldPopupCloseReject = false;
						popup.close();

						HcmLinkVacancyChooser.openSlider({
							api: this.#api,
							documentGroupUids: this.#getCreatedDocumentUidsByHcmLinkId(integration.company.id),
							employees: this.#convertEmployeesToUserMap(integration.employees),
							companyTitle: integration.company.title,
						}, {
							onCloseHandler: () => resolve(),
						});
					},
				}),
				new Button({
					text: Loc.getMessage('SIGN_SETTINGS_TEMPLATES_EMPLOYEES_NOT_SELECTED_POPUP_CANCEL'),
					size: ButtonSize.S,
					color: ButtonColor.LIGHT_BORDER,
					round: true,
					onclick: (): void => popup.close(),
				}),
			],
			modal: false,
			popupOptions: {
				events: {
					onPopupClose: (): void => {
						if (shouldPopupCloseReject)
						{
							reject();
						}
					},
				},
				...this.#getHcmPopupOptions(),
			},
		});

		popup.show();
	}

	#convertEmployeesToUserMap(employees: HcmLinkMultipleVacancyEmployee[]): Map<number, HcmLinkMultipleVacancyEmployee>
	{
		const employeesMap = new Map();
		employees.forEach((value: HcmLinkMultipleVacancyEmployee): void => {
			employeesMap.set(value.userId, value);
		});

		return employeesMap;
	}

	#getCreatedDocumentUidsByHcmLinkId(companyId: number): string[]
	{
		const store = useDocumentTemplateFillingStore();
		const createdDocuments: TemplateCreatedDocument[] = store.createdDocuments;

		return createdDocuments
			.filter((value: TemplateCreatedDocument): boolean => value.document.hcmLinkCompanyId === companyId)
			.map((value: TemplateCreatedDocument): string => value.document.uid)
		;
	}

	#getHcmPopupOptions(): PopupOptions
	{
		return {
			targetContainer: this.#container,
			borderRadius: '20px',
			padding: 0,
			contentPadding: 24,
		};
	}

	#getHcmPopupLayout(title: string, description: string): HTMLElement
	{
		return Tag.render`
			<div>
				<div class="sign-settings-templates-hcm-popup-warning"></div>
				<div class="sign-settings-templates-hcm-popup-title">
					${Text.encode(title)}			
				</div>
				<div class="sign-settings-templates-hcm-popup-description">
					${Text.encode(description)}			
				</div>
			</div>
		`;
	}

	#subscribeSliderCloseEvent(): void
	{
		if (!this.#sliderUrl)
		{
			return;
		}

		const onClose = (event: BaseEvent): void => {
			const [eventDataItem] = event.getData();
			const slider = eventDataItem?.getSlider();
			if (slider.getUrl() !== this.#sliderUrl)
			{
				return;
			}

			if (this.#isNeedShowCloseConfirm())
			{
				eventDataItem.denyAction();
				this.#showCloseConfirm(slider);

				return;
			}

			EventEmitter.unsubscribe('SidePanel.Slider:onClose', onClose);
			this.#unmountVueApps();
			this.#closeConfimPopup();
		};

		EventEmitter.subscribe('SidePanel.Slider:onClose', onClose);
	}

	#unmountVueApps(): void
	{
		this.#documentFilling.unmount();
		this.#documentSend.unmount();
		this.#documentUserParty.unmount();
	}

	#isNeedShowCloseConfirm(): boolean
	{
		if (this.#bypassSliderCloseCheck)
		{
			return false;
		}

		return this.#getCreateDocumentsUids().length === 0;
	}

	#showCloseConfirm(slider: Slider): void
	{
		if (this.#confirmPopup === null)
		{
			this.#confirmPopup = new MessageBox({
				message: Loc.getMessage('SIGN_SETTINGS_TEMPLATES_CLOSE_CONFIRM'),
				buttons: MessageBoxButtons.OK_CANCEL,
				okCaption: Loc.getMessage('SIGN_SETTINGS_TEMPLATES_CLOSE_CONFIRM_OK'),
				onOk: (messageBox: MessageBox): void => {
					messageBox.close();
					this.#bypassSliderCloseCheck = true;
					slider.close();
					this.#bypassSliderCloseCheck = false;
				},
			});
		}

		this.#confirmPopup.show();
	}

	#closeConfimPopup(): void
	{
		this.#confirmPopup?.close();
	}

	#isRuRegionFieldsVisible(): boolean
	{
		return this.#region === 'ru';
	}
}
