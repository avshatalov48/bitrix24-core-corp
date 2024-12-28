import { Tag, Loc, Type, Dom } from 'main.core';
import { EventEmitter, type BaseEvent } from 'main.core.events';
import type { DocumentModeType } from 'sign.v2.sign-settings';
import { isTemplateMode } from 'sign.v2.sign-settings';
import { UserParty } from 'sign.v2.b2e.user-party';
import { ReminderSelector, type Options as ReminderOptions, ReminderType } from 'sign.v2.b2e.reminder-selector';
import { Item, EntityTypes } from './item';
import { Api, type Role, MemberRole } from 'sign.v2.api';
import { DocumentSummary } from 'sign.v2.document-summary';
import { LangSelector } from 'sign.v2.lang-selector';
import type { PartiesData, DocumentData } from './type';
import type { DocumentSendConfig } from 'sign.v2.b2b.document-send';
import { Hint } from 'sign.v2.helper';
import { ProgressBar } from 'ui.progressbar';
import './style.css';
import { HcmLinkMapping } from 'sign.v2.b2e.hcm-link-mapping';
import type { Analytics } from 'sign.v2.analytics';

export type CommunicationType = 'idle' | 'phone' | 'email';

type CommunicationSelectedData = {
	[key: string]: {
		id: string,
		option: CommunicationType,
	}
};
type Member = {
	presetId: number,
	part: number,
	uid: string,
	role: Role,
};

const ReminderSelectorOptionsByRole: Record<Role, ReminderOptions> = {
	[MemberRole.assignee]: { preSelectedType: ReminderType.oncePerDay },
	[MemberRole.signer]: { preSelectedType: ReminderType.twicePerDay },
};

const idleCommunication: CommunicationType = 'idle';

export class DocumentSend extends EventEmitter
{
	events = Object.freeze({
		onTemplateComplete: 'onTemplateComplete',
	});
	// members: Array<Member> | null;

	#ui = {
		container: HTMLDivElement = null,
		employeesTitle: HTMLParagraphElement,
	};

	#communicationSelectedOption: CommunicationSelectedData = {
		company: {
			id: 'company',
			option: idleCommunication,
		},
		employee: {
			id: 'employee',
			option: idleCommunication,
		},
		validation: {
			id: 'validation',
			option: idleCommunication,
		},
	};

	#items = {
		company: Item,
		representative: Item,
		employees: UserParty,
		reviewer: Item,
		editor: Item,
	};

	#partiesData: ?PartiesData = null;
	#documentSummary: DocumentSummary;
	#documentData: DocumentData;
	#langSelector: LangSelector;
	#progress: ProgressBar;
	#progressOverlay: ?HTMLElement;
	#progressContainer: ?HTMLElement;
	#itemsToHide: Array<HTMLElement> = [];
	#reminderSelectorByRole: Record<Role, ReminderSelector> = {};
	#documentMode: DocumentModeType;
	#isExistingTemplate: boolean = false;

	#hcmLinkMapping: HcmLinkMapping;
	#analytics: ?Analytics;

	constructor(documentSendConfig: DocumentSendConfig)
	{
		super();
		this.setEventNamespace('BX.Sign.V2.B2e.DocumentSend');
		this.#items.company = new Item({ entityType: EntityTypes.Company });
		this.#items.representative = new Item({ entityType: EntityTypes.User });
		this.#items.reviewer = new Item({ entityType: EntityTypes.User });
		this.#items.editor = new Item({ entityType: EntityTypes.User });
		this.#items.employees = new UserParty({ mode: 'view' });
		this.#documentSummary = new DocumentSummary({
			events: {
				changeTitle: (event: BaseEvent) => {
					const data = event.getData();
					this.#documentData.title = data.title;
					this.emit('changeTitle', data);
				},
				showEditor: (event: BaseEvent) => this.emit('showEditor'),
			},
		});
		const { region, languages, documentMode, analytics } = documentSendConfig;
		this.#langSelector = new LangSelector(region, languages);
		this.#documentData = {};
		this.#analytics = analytics;
		this.#documentMode = documentMode;
		this.#ui.employeesTitle = Tag.render`
			<p class="sign-b2e-send__party_signing-employees">
				${Loc.getMessage('SIGN_SEND_SIGNING_EMPLOYEES', { '#CNT#': 0 })}
			</p>
		`;
		this.#progress = new ProgressBar({
			maxValue: 100,
			value: 0,
			colorTrack: '#dfe3e6',
		});

		[MemberRole.assignee, MemberRole.signer].forEach((role: Role) => {
			this.#getOrCreateReminderSelectorForRole(role);
		});

		this.#hcmLinkMapping = new HcmLinkMapping({
			api: new Api(),
		});

		this.#hcmLinkMapping.subscribe('validUpdate', (event) => this.#onHcmLinkMappingValidUpdate(event))
	}

	get documentData(): DocumentData
	{
		return this.#documentData;
	}

	set documentData(documentData: DocumentData)
	{
		const { uid, title, blocks, externalId } = documentData;
		this.#documentSummary.uid = uid;
		this.#documentSummary.title = title;
		this.#documentSummary.blocks = blocks;
		this.#documentSummary.setNumber(externalId);
		this.#documentData = documentData;
		this.#langSelector.setDocumentUid(uid);
		this.#items.employees.setDocumentUid(uid);
		this.#hcmLinkMapping.setEnabled(documentData.integrationId > 0);
		this.#hcmLinkMapping.setDocumentUid(uid);
	}

	#getCompanyCommunication(): CommunicationType | null
	{
		return this.#communicationSelectedOption.company?.option ?? null;
	}

	#getValidationCommunication(): CommunicationType | null
	{
		return this.#communicationSelectedOption.validation?.option ?? null;
	}

	#getEmployeeCommunication(): CommunicationType | null
	{
		return this.#communicationSelectedOption.employee?.option ?? null;
	}

	#getProgressAnimateLayout(): HTMLElement
	{
		const createDocumentOverlapLayout = (docsCount: number) => {
			return Tag.render`
				<div class="sign-b2e-overlay__overlap-docs">
					${Array.from({ length: docsCount }).map(() => {
						return Tag.render`
							<div class="sign-b2e-overlay__overlap-doc"></div>
						`;
					})}
				</div>
			`;
		};

		return Tag.render`
			<div class="sign-b2e-overlay__animate-layout">
				${createDocumentOverlapLayout(4)}
				${createDocumentOverlapLayout(3)}
			</div>
		`;
	}

	getLayout(): HTMLElement
	{
		const layout = Tag.render`
			<div class="sign-b2e-send">
				<h1 class="sign-b2e-settings__header">${Loc.getMessage('SIGN_DOCUMENT_SEND_HEADER_1')}</h1>
			</div>
		`;

		const summaryTitle = this.#isTemplateMode()
			? Loc.getMessage('SIGN_DOCUMENT_SUMMARY_TEMPLATE_TITLE')
			: Loc.getMessage('SIGN_DOCUMENT_SUMMARY_TITLE');

		this.#itemsToHide = [];
		const summaryLayout = Tag.render`
			<div class="sign-b2e-settings__item">
				<p class="sign-b2e-settings__item_title">
					${summaryTitle}
				</p>
				${this.#documentSummary.getLayout()}
				<div class="sign-b2e-send__lang-selector">
					${this.#langSelector.getLayout()}
					<span
						data-hint="${Loc.getMessage('SIGN_DOCUMENT_SEND_LANG_SELECTOR_HINT')}"
					></span>
				</div>
				<span class="sign-b2e-send__deadline">
					${Loc.getMessage('SIGN_DOCUMENT_SEND_DEADLINE')}
				</span>
			</div>
		`;
		this.#itemsToHide.push(summaryLayout);

		let usersLayout = null;
		if (!this.#isTemplateMode())
		{
			usersLayout = Tag.render`
				<div class="sign-b2e-settings__item">
					<p class="sign-b2e-settings__item_title">
						${Loc.getMessage('SIGN_DOCUMENT_SEND_SECOND_PARTY')}
					</p>
					${this.#ui.employeesTitle}
					${this.#items.employees.getLayout()}
					<div class="sign-b2e-send__config-container">
						${this.#getCommunicationsLayout('employee')}
						${this.#getReminderSelectorLayout(MemberRole.signer)}
					</div>
					${this.#hcmLinkMapping.render()}
				</div>
			`;
			this.#itemsToHide.push(usersLayout);
		}
		const itemTitleText = this.#isTemplateMode()
			? Loc.getMessage('SIGN_DOCUMENT_SEND_FIRST_PARTY_TEMPLATE')
			: Loc.getMessage('SIGN_DOCUMENT_SEND_FIRST_PARTY')
		;
		const companyLayout = Tag.render`
			<div class="sign-b2e-settings__item">
				<p class="sign-b2e-settings__item_title">
					${itemTitleText}
				</p>
				<div class="sign-b2e-send__company-items">
					<div class="sign-b2e-send__company-items_flex">
						<p class="sign-b2e-send__company-items_item-title">
							${Loc.getMessage('SIGN_SEND_SIGNING_COMPANY')}
						</p>
						<span class="sign-b2e-send__company-items_shrunk"></span>
						<p class="sign-b2e-send__company-items_item-title">
							${Loc.getMessage('SIGN_SEND_SIGNING_REPRESENTATIVE')}
						</p>
					</div>
					<div class="sign-b2e-send__company-items_flex">
						${this.#items.company.getLayout()}
						<span class="sign-b2e-send__company-items_shrunk sign-b2e-send__party-item-separator">
							&#43;
						</span>
						${this.#items.representative.getLayout()}
					</div>
				</div>
				<div class="sign-b2e-send__config-container">
					${this.#getCommunicationsLayout('company')}
					${this.#getReminderSelectorLayout(MemberRole.assignee)}
				</div>
			</div>
		`;
		this.#itemsToHide.push(companyLayout);
		Dom.append(summaryLayout, layout);
		const reviewerHeaderText = this.#isTemplateMode()
			? Loc.getMessage('SIGN_SEND_SIGNING_VALIDATION_HEAD_REVIEWER_TEMPLATE')
			: Loc.getMessage('SIGN_SEND_SIGNING_VALIDATION_HEAD_REVIEWER')
		;
		const validationTitles = {
			[MemberRole.reviewer]: {
				header: reviewerHeaderText,
				hint: Loc.getMessage('SIGN_SEND_SIGNING_VALIDATION_TITLE_REVIEWER'),
			},
			[MemberRole.editor]: {
				header: Loc.getMessage('SIGN_SEND_SIGNING_VALIDATION_HEAD_EDITOR'),
				hint: Loc.getMessage('SIGN_SEND_SIGNING_VALIDATION_TITLE_EDITOR'),
			},
		};
		this.#partiesData.validation.forEach(({ role }) => {
			const { hint, header } = validationTitles[role];
			const validationLayout = Tag.render`
				<div class="sign-b2e-settings__item">
					<p class="sign-b2e-settings__item_title">
						${header}
					</p>
					<div class="sign-b2e-send__party_item">
						<p class="sign-b2e-send__company-items_item-title">
							${hint}
						</p>
						${this.#items[role].getLayout()}
						${this.#getCommunicationsLayout('validation')}
					</div>
				</div>
			`;
			this.#itemsToHide.push(validationLayout);
			Dom.append(validationLayout, layout);
		});
		Dom.append(companyLayout, layout);
		if (!Type.isNull(usersLayout))
		{
			Dom.append(usersLayout, layout);
		}

		this.#progressContainer = Tag.render`<div class="send-b2e-progress-container"></div>`;
		this.#progress.renderTo(this.#progressContainer);

		this.#progressOverlay = this.#isTemplateMode() ? this.#getTemplateProgressOverlay()
			: this.#getProgressOverlay();

		Dom.style(this.#progressOverlay, 'display', 'none');
		this.emit('appendOverlay', { overlay: this.#progressOverlay });
		Hint.create(layout);

		return layout;
	}

	#getProgressOverlay(): HTMLElement
	{
		const closeDescriptionText = Loc.getMessage('SIGN_SEND_CLOSE_DESCRIPTION');

		return Tag.render`
			<div class="send-b2e-overlay">
				<div class="sign-b2e-overlay-content">
					${this.#getProgressAnimateLayout()}
					<div class="sign-b2e-overlay-progress-title">
						${Loc.getMessage('SIGN_SEND_PROGRESS_TITLE')}
					</div>
					<div class="sign-b2e-overlay-close-description">
						${closeDescriptionText}
					</div>
					<div>
						${this.#getCloseBtn()}
					</div>
				</div>
				${this.#progressContainer}
			</div>
		`;
	}

	#getTemplateProgressOverlay(): HTMLElement
	{
		const templateTitle = this.#isExistingTemplate ? Loc.getMessage('SIGN_SETTINGS_TEMPLATE_CHANGED')
			: Loc.getMessage('SIGN_SETTINGS_TEMPLATE_CREATED');

		return Tag.render`
			<div class="sign-b2e-template-status">
				<div class="sign-b2e-template-status-inner">
					<div class="sign-b2e-template-status-img"></div>
					<div class="sign-b2e-template-status-title">${templateTitle}</div>
					<div class="sign-b2e-template-status-info">${Loc.getMessage('SIGN_SETTINGS_TEMPLATE_CREATED_INFO')}</div>
					<button class="ui-btn ui-btn-light-border ui-btn-round" onclick="BX.SidePanel.Instance.close();">${Loc.getMessage('SIGN_SETTINGS_TEMPLATES_LIST')}</button>
				</div>
			 </div>
		`;
	}

	setExistingTemplate(): void
	{
		this.#isExistingTemplate = true;
	}

	#getCloseBtn(): HTMLElement
	{
		return Tag.render`
			<button
				class="ui-btn ui-btn-light-border ui-btn-round"
				onclick="${() => this.emit('close')}">
				${Loc.getMessage('SIGN_SEND_CLOSE_BTN')}
			</button>
		`;
	}

	resetUserPartyPopup(): DocumentSend
	{
		this.#items.employees.resetUserPartyPopup();
		return this;
	}

	setPartiesData(parties: PartiesData): DocumentSend
	{
		this.#partiesData = parties;
		if (Type.isNumber(parties?.company?.entityId))
		{
			this.#items.company.setItemData({
				entityId: parties?.company?.entityId,
				entityType: parties?.company?.entityType,
			});
		}

		if (Type.isNumber(parties?.representative?.entityId))
		{
			this.#items.representative.setItemData({
				entityId: parties?.representative?.entityId,
				entityType: parties?.representative?.entityType,
			});
		}

		if (Type.isArrayFilled(parties?.employees))
		{
			this.#items.employees.setSignersIds(parties?.employees.map((employee) => {
				return { entityId: employee.entityId, entityType: employee.entityType };
			}));
		}

		if (Type.isArrayFilled(parties?.validation))
		{
			parties.validation.forEach((party) => {
				const { entityId, entityType, role } = party;
				this.#items[role].setItemData({ entityId, entityType });
			});
		}

		this.#refreshView();

		return this;
	}

	async sendForSign(): Promise<boolean>
	{
		const api = new Api();
		const { uid, templateUid } = this.#documentData;

		try
		{
			this.emit('disableBack');
			await this.#saveReminderTypesForRoles();

			this.#showProgressOverlay();
			if (this.#isTemplateMode())
			{
				const { template: { id: templateId } } = await api.template.completeTemplate(templateUid);
				this.emit('onTemplateComplete', { templateId });
			}
			else
			{
				await api.configureDocument(uid);
				await this.#checkFillAndStartProgress(uid);
			}

			return true;
		}
		catch (ex)
		{
			console.error(ex);
			this.#hideProgressOverlay();
			this.emit('enableBack');

			return false;
		}
	}

	async #checkFillAndStartProgress(uid: string): Promise<void>
	{
		const api = new Api();
		let completed = false;
		while (!completed)
		{
			// eslint-disable-next-line no-await-in-loop
			const result = await api.getDocumentFillAndStartProgress(uid);
			completed = result.completed;
			this.#progress.update(Math.round(result.progress));
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

	#refreshView(): void
	{
		this.#ui.employeesTitle.innerText = Loc.getMessage(
			'SIGN_SEND_SIGNING_EMPLOYEES',
			{ '#CNT#': this.#partiesData?.employees?.length ?? 0 },
		);
	}

	#getCommunicationsLayout(communicationChannelId: string): HTMLElement
	{
		return Tag.render`
			<div class="sign-b2e-send__communications">
				<span class="sign-b2e-send__communications_title">
					${Loc.getMessage('SIGN_DOCUMENT_SEND_COMMUNICATION_TITLE')}
				</span>
				<div class="sign-b2e-send__communications_communication-type">
					<span class="sign-b2e-send__communications_communication-type-text">
						${Loc.getMessage('SIGN_DOCUMENT_SEND_COMMUNICATION_CHANEL_IDLE')}
					</span>
					<span
						data-hint="${Loc.getMessage('SIGN_DOCUMENT_SEND_COMMUNICATION_CHANEL_HINT')}"
					></span>
				</div>
			</div>
		`;
	}

	#showProgressOverlay(): void
	{
		this.#progress.update(0);
		this.emit('hidePreview');
		this.#itemsToHide.forEach((item) => Dom.hide(item));
		Dom.style(this.#progressOverlay, 'display', 'flex');
		this.emit('showOverlay');
	}

	#hideProgressOverlay(): void
	{
		this.#itemsToHide.forEach((item) => Dom.show(item));
		this.emit('hideOverlay');
		Dom.style(this.#progressOverlay, 'display', 'none');
		this.emit('showPreview');
	}

	#getReminderSelectorLayout(role: Role): HTMLElement
	{
		return Tag.render`
			<div class="sign-b2e-send__reminder-selector">
				${this.#getOrCreateReminderSelectorForRole(role).getLayout()}
				<span
					data-hint="${Loc.getMessage('SIGN_DOCUMENT_SEND_REMINDER_TYPE_SELECTOR_HINT')}"
				></span>
			</div>
		`;
	}

	#getOrCreateReminderSelectorForRole(role: Role): ReminderSelector
	{
		this.#reminderSelectorByRole[role] ??= new ReminderSelector(ReminderSelectorOptionsByRole[role] ?? {});

		return this.#reminderSelectorByRole[role];
	}

	#saveReminderTypesForRoles(): Promise<void>
	{
		const promises: Array<Promise> = Object.entries(this.#reminderSelectorByRole)
			.map(([role, selector]) => selector.save(this.#documentData.uid, role))
		;

		return Promise.all(promises);
	}

	#isTemplateMode(): boolean
	{
		return isTemplateMode(this.#documentMode);
	}

	#onHcmLinkMappingValidUpdate(event: BaseEvent): void
	{
		const enableComplete = event?.data.value ?? false;

		this.emit(
			enableComplete
				? 'enableComplete'
				: 'disableComplete'
		);
	}
}
