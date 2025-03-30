import { Cache, Dom, Event, Loc, Tag, Type, Extension } from 'main.core';
import { Popup } from 'main.popup';
import { Api } from 'sign.v2.api';
import { SignDropdown } from 'sign.v2.b2e.sign-dropdown';
import { FeatureStorage } from 'sign.feature-storage';
import { DocumentCounters } from 'sign.v2.b2e.document-counters';
import { type BlankSelectorConfig } from 'sign.v2.blank-selector';
import { type DocumentInitiatedType, DocumentInitiated } from 'sign.type';
import { type DocumentDetails, DocumentSetup as BaseDocumentSetup } from 'sign.v2.document-setup';
import { Helpdesk, Hint } from 'sign.v2.helper';
import { isTemplateMode } from 'sign.v2.sign-settings';
import { DateSelector } from './date-selector';
import './style.css';

type RegionDocumentType = {
	code: string,
	description: string,
};

const HelpdeskCodes = Object.freeze({
	HowToWorkWithTemplates: '23174934',
});

export class DocumentSetup extends BaseDocumentSetup
{
	#cache = new Cache.MemoryCache();
	#api: Api;
	#region: string;
	#regionDocumentTypes: Array<RegionDocumentType>;
	#senderDocumentTypes: DocumentInitiatedType[];
	#documentTypeDropdown: HTMLElement;
	#documentSenderTypeDropdown: HTMLElement;
	#documentNumberInput: HTMLInputElement | null = null;
	#documentTitleInput: HTMLInputElement;
	#dateSelector: DateSelector | null = null;
	headerLayout: HTMLElement;
	documentCounters: DocumentCounters | null = null;
	#b2eDocumentLimitCount: number;
	editMode: boolean;
	#currentEditedId: number;
	#currentEditButton: HTMLElement;
	#currentEditBlock: HTMLElement;
	documentSectionLayout: HTMLElement;
	documentSectionInnerLayout: HTMLElement;

	constructor(blankSelectorConfig: BlankSelectorConfig)
	{
		super(blankSelectorConfig);
		const { region, regionDocumentTypes, documentMode, b2eDocumentLimitCount } = blankSelectorConfig;
		this.#api = new Api();
		this.#region = region;
		this.#regionDocumentTypes = regionDocumentTypes;
		this.#senderDocumentTypes = Object.values(DocumentInitiated);
		this.#b2eDocumentLimitCount = b2eDocumentLimitCount;
		this.editMode = false;
		this.onClickShowHintPopup = this.showHintPopup.bind(this);

		this.#documentTitleInput = Tag.render`
			<input
				type="text"
				class="ui-ctl-element"
				maxlength="255"
				oninput="${({ target }) => this.setDocumentTitle(target.value)}"
			/>
		`;
		if (this.isRuRegion() && !isTemplateMode(documentMode))
		{
			this.#documentNumberInput = Tag.render`<input type="text" class="ui-ctl-element" maxlength="255" />`;
			this.#dateSelector = new DateSelector();
		}

		this.#disableDocumentInputs();
		this.disableAddButton();

		this.blankSelector.subscribe('toggleSelection', ({ data }) => {
			this.setDocumentTitle(data.title);
			if (data.selected)
			{
				this.#enableDocumentInputs();
				this.enableAddButton();
			}
		});
		this.blankSelector.subscribe('addFile', ({ data }) => {
			this.isFileAdded = true;
			this.setDocumentTitle(data.title);
			this.#enableDocumentInputs();
			this.enableAddButton();
		});
		this.#init();
	}

	#init(): void
	{
		this.#initDocumentType();
		this.#initDocumentSenderType();
		const documentTypeLayout = this.#getDocumentTypeLayout();
		const title = this.isTemplateMode()
			? Loc.getMessage('SIGN_DOCUMENT_SETUP_TITLE_TEMPLATE_HEAD_LABEL')
			: Loc.getMessage('SIGN_DOCUMENT_SETUP_TITLE_HEAD_LABEL');

		const titleLayout = Tag.render`
			<div class="sign-b2e-settings__item">
				<p class="sign-b2e-settings__item_title">
					${title}
				</p>
				${this.#getDocumentTitleLayout()}
			</div>
		`;

		Dom.append(documentTypeLayout, this.layout);
		Dom.append(this.#getDocumentSenderTypeLayout(), this.layout);
		Dom.append(titleLayout, this.layout);

		if (!this.isTemplateMode() && FeatureStorage.isGroupSendingEnabled())
		{
			this.documentCounters = new DocumentCounters({
				documentCountersLimit: this.#b2eDocumentLimitCount,
			});
			this.documentCounters.subscribe('limitNotExceeded', () => {
				this.enableAddButton();
				this.#setAddDocumentNoticeText();
				this.emit('documentsLimitNotExceeded');
			});
			this.documentCounters.subscribe('limitExceeded', () => {
				this.disableAddButton();
				this.#setDocumentLimitNoticeText();
				this.emit('documentsLimitExceeded');
			});
			Dom.append(this.documentCounters.getLayout(), this.layout);

			const addDocumentLayout = this.#getAddDocumentLayout();
			Dom.append(addDocumentLayout, this.layout);
		}
		Hint.create(this.layout);
	}

	#isDocumentTypeVisible(): boolean
	{
		return this.#regionDocumentTypes?.length;
	}

	isRuRegion(): boolean
	{
		return this.#region === 'ru';
	}

	#initDocumentType(): void
	{
		if (!this.#isDocumentTypeVisible())
		{
			return;
		}

		this.#documentTypeDropdown = new SignDropdown({
			tabs: [{ id: 'b2e-document-codes', title: ' ' }],
			entities: [
				{ id: 'b2e-document-code', searchFields: [{ name: 'caption', system: true }] },
			],
			className: 'sign-b2e-document-setup__type-selector',
			withCaption: true,
			isEnableSearch: true,
		});
		this.#regionDocumentTypes.forEach((item) => {
			if (Type.isPlainObject(item)
				&& Type.isStringFilled(item.code)
				&& Type.isStringFilled(item.description))
			{
				const { code, description } = item;
				this.#documentTypeDropdown.addItem({
					id: code,
					title: code,
					caption: `(${description})`,
					entityId: 'b2e-document-code',
					tabs: 'b2e-document-codes',
					deselectable: false,
				});
			}
		});
		this.#documentTypeDropdown.selectItem(this.#regionDocumentTypes[0].code);
	}

	#getDocumentTypeLayout(): HTMLElement | null
	{
		if (!this.#isDocumentTypeVisible())
		{
			return null;
		}

		return Tag.render`
			<div class="sign-b2e-settings__item">
				<p class="sign-b2e-settings__item_title">
					<span>${Loc.getMessage('SIGN_DOCUMENT_SETUP_TYPE')}</span>
					<span
						data-hint="${Loc.getMessage('SIGN_DOCUMENT_SETUP_TYPE_HINT')}"
					></span>
				</p>
				${this.#documentTypeDropdown.getLayout()}
			</div>
		`;
	}

	#initDocumentSenderType(): void
	{
		if (!this.isTemplateMode() || !this.isSenderTypeAvailable())
		{
			return;
		}

		this.#documentSenderTypeDropdown = new SignDropdown({
			tabs: [{ id: 'b2e-document-sender-types', title: ' ' }],
			entities: [
				{ id: 'b2e-document-sender-type', searchFields: [{ name: 'caption', system: true }] },
			],
			className: 'sign-b2e-document-setup__sender-type-selector',
			withCaption: true,
			isEnableSearch: false,
			height: 110,
			width: 350,
		});
		this.#senderDocumentTypes.forEach((item) => {
			if (Type.isStringFilled(item))
			{
				const langPhraseCode = `SIGN_DOCUMENT_SETUP_SENDER_TYPE_${item.toUpperCase()}`;
				this.#documentSenderTypeDropdown.addItem({
					id: item,
					title: Loc.getMessage(langPhraseCode),
					entityId: 'b2e-document-sender-type',
					tabs: 'b2e-document-sender-types',
					deselectable: false,
				});
			}
		});
		this.#documentSenderTypeDropdown.selectItem(this.#senderDocumentTypes[0]);
	}

	#getDocumentSenderTypeLayout(): HTMLElement | null
	{
		if (!this.isTemplateMode() || !this.isSenderTypeAvailable())
		{
			return null;
		}

		return Tag.render`
			<div class="sign-b2e-settings__item">
				<p class="sign-b2e-settings__item_title">
					<span>${Loc.getMessage('SIGN_DOCUMENT_SETUP_SENDER_TYPE_TITLE')}</span>
				</p>
				${this.#documentSenderTypeDropdown.getLayout()}
				${this.#getHelpLink()}
			</div>
		`;
	}

	#getHelpLink(): HTMLElement
	{
		return Helpdesk.replaceLink(
			Loc.getMessage('SIGN_DOCUMENT_SETUP_SENDER_TYPE_HELP_LINK'),
			HelpdeskCodes.HowToWorkWithTemplates,
			'detail',
			['ui-link'],
		);
	}

	#getDocumentNumberLayout(): HTMLElement | null
	{
		if (!this.isRuRegion() || this.isTemplateMode())
		{
			return null;
		}

		return Tag.render`
			<div class="sign-b2e-document-setup__title-item --num">
				<p class="sign-b2e-document-setup__title-text">
					<span>${Loc.getMessage('SIGN_DOCUMENT_SETUP_NUM_LABEL')}</span>
					<span
						data-hint="${Loc.getMessage('SIGN_DOCUMENT_SETUP_NUM_LABEL_HINT')}"
					></span>
				</p>
				<div class="ui-ctl ui-ctl-textbox">
					${this.#documentNumberInput}
				</div>
			</div>
		`;
	}

	#getDocumentTitleLayout(): HTMLElement
	{
		return Tag.render`
			<div>
				<div class="sign-b2e-document-setup__title-item ${this.#getDocumentTitleFullClass()}">
					<p class="sign-b2e-document-setup__title-text">
						${Loc.getMessage('SIGN_DOCUMENT_SETUP_TITLE_LABEL')}
					</p>
					<div class="ui-ctl ui-ctl-textbox">
						${this.#documentTitleInput}
					</div>
				</div>
				${this.#getDocumentNumberLayout()}
				${this.#getDocumentHintLayout()}
				${this.#dateSelector?.getLayout()}
			</div>
		`;
	}

	#getAddDocumentLayout(): HTMLElement
	{
		return this.#cache.remember('addDocumentLayout', () => {
			return Tag.render`
				<div class="sign-b2e-settings__item --add">
					<div class="sign-b2e-settings__item_title">
						<span>${Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_DOCUMENT')}</span>
						${this.documentCounters.getLayout()}
					</div>
					${this.getAddDocumentButton()}
					${this.getAddDocumentNotice()}
				</div>
			`;
		});
	}

	getAddDocumentButton(): HTMLElement
	{
		return this.#cache.remember('addDocumentButton', () => {
			return Tag.render`
				<button type="button" class="sign-b2e-document-setup__add-button" onclick="${this.#onClickAddDocument.bind(this)}">
					${this.#getAddDocumentButtonText()}
				</button>
			`;
		});
	}

	getAddDocumentNotice(): HTMLElement
	{
		return this.#cache.remember('addDocumentNotice', () => {
			return Tag.render`
				<p class="sign-b2e-document-setup__add-notice">${Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_DOCUMENT_NOTICE')}</p>
			`;
		});
	}

	#getAddDocumentButtonText(): HTMLElement
	{
		return this.#cache.remember('addDocumentButtonText', () => {
			return Tag.render`
				<span class="sign-b2e-document-setup__add-button_text">${Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_ANOTHER_DOCUMENT')}</span>
			`;
		});
	}

	switchAddDocumentButtonLoadingState(loading: boolean): void
	{
		if (loading)
		{
			Dom.addClass(this.getAddDocumentButton(), 'ui-btn-wait');
		}
		else
		{
			Dom.removeClass(this.getAddDocumentButton(), 'ui-btn-wait');
		}
	}

	disableAddButton(): void
	{
		Dom.addClass(this.getAddDocumentButton(), '--disabled');
	}

	enableAddButton(): void
	{
		Dom.removeClass(this.getAddDocumentButton(), '--disabled');
	}

	#setDocumentLimitNoticeText(): void
	{
		Dom.addClass(this.getAddDocumentNotice(), '--warning');
		this.getAddDocumentNotice().textContent = Loc.getMessage('SIGN_DOCUMENT_SETUP_DOCUMENT_LIMIT_NOTICE');
	}

	#setAddDocumentNoticeText(): void
	{
		Dom.removeClass(this.getAddDocumentNotice(), '--warning');
		this.getAddDocumentNotice().textContent = Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_DOCUMENT_NOTICE');
	}

	toggleDeleteBtnLoadingState(deleteButton: HTMLElement): void
	{
		Dom.toggleClass(deleteButton, 'ui-btn-wait');
	}

	#onClickAddDocument(): void
	{
		this.emit('addDocument');
	}

	renderDocumentBlock(documentData: Object): void
	{
		if (!documentData)
		{
			return;
		}

		Dom.append(this.#createDocumentBlock(documentData), this.headerLayout);
	}

	#createDocumentBlock(documentData: Object): HTMLElement
	{
		const deleteButton = Tag.render`
			<button class="sign-b2e-document-setup__document-block_delete" type="button"></button>
		`;
		const editButton = Tag.render`
			<button class="ui-btn ui-btn-round ui-btn-sm ui-btn-light-border" type="button">
				${Loc.getMessage('SIGN_DOCUMENT_SETUP_DOCUMENT_EDIT_BUTTON')}
			</button>
		`;

		Event.bind(deleteButton, 'click', (event) => {
			this.#onClickDeleteDocument(documentData, event);
		});
		Event.bind(editButton, 'click', (event) => {
			this.#onClickEditDocument(documentData, event);
		});
		const documentTypeDropdownLayout = this.#isDocumentTypeVisible() ? this.#getDocumentTypeDropdownLayout() : '';

		return Tag.render`
			<div class="sign-b2e-document-setup__document-block" data-id="document-id-${documentData.id}">
				<div class="sign-b2e-document-setup__document-block_inner">
					<div class="sign-b2e-document-setup__document-block_title">${documentData.title}</div>
					${documentTypeDropdownLayout}
				</div>
				<div class="sign-b2e-document-setup__document-block_btn">
					${editButton}
					${deleteButton}
				</div>
				<div class="sign-b2e-document-setup__document-block_hint">
					${Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_DOCUMENT_HINT')}
				</div>
			</div>
		`;
	}

	#getDocumentTypeDropdownLayout(): HTMLElement
	{
		return Tag.render`
			<div class="sign-b2e-document-setup__document-block_info">${this.#documentTypeDropdown.getSelectedCaption()}</div>
		`;
	}

	updateDocumentBlock(id: number): void
	{
		const editedBlock = this.layout.querySelector(`[data-id="document-id-${id}"]`);
		const titleNode = editedBlock.querySelector('.sign-b2e-document-setup__document-block_title');
		titleNode.textContent = this.#documentTitleInput.title;

		if (this.#isDocumentTypeVisible())
		{
			const infoNode = editedBlock.querySelector('.sign-b2e-document-setup__document-block_info');
			infoNode.textContent = this.#documentTypeDropdown.getSelectedCaption();
		}
	}

	replaceDocumentBlock(oldDocument, newDocument): void
	{
		const editedBlock = this.layout.querySelector(`[data-id="document-id-${oldDocument.id}"]`);
		Dom.replace(editedBlock, this.#createDocumentBlock(newDocument));
	}

	#getDocumentHintLayout(): HTMLElement | null
	{
		if (this.isTemplateMode())
		{
			return null;
		}

		return this.#cache.remember('documentHintLayout', () => {
			return Tag.render`
				<p class="sign-b2e-document-setup__title-text">
					${Loc.getMessage('SIGN_DOCUMENT_SETUP_TITLE_HINT')}
				</p>
			`;
		});
	}

	#onClickDeleteDocument(documentData: DocumentDetails, event: PointerEvent): void
	{
		this.setupData = null;
		const { id, uid, blankId } = documentData;
		const deleteButton = event.target;
		this.toggleDeleteBtnLoadingState(deleteButton);
		this.emit('deleteDocument', { id, uid, blankId, deleteButton });
	}

	#onClickEditDocument(documentData: DocumentDetails, event: PointerEvent): void
	{
		const { id, uid } = documentData;
		this.toggleEditMode(id, event.target);
		this.emit('editDocument', { uid });
	}

	toggleEditMode(id: number, editButton: HTMLElement): void
	{
		if (this.#currentEditedId !== id)
		{
			this.resetEditMode();
		}

		const documentBlock = editButton.closest(`[data-id="document-id-${id}"]`);
		Dom.toggleClass(documentBlock, '--edit');

		if (this.editMode)
		{
			// eslint-disable-next-line no-param-reassign
			editButton.textContent = Loc.getMessage('SIGN_DOCUMENT_SETUP_DOCUMENT_EDIT_BUTTON');
			this.#disableDocumentInputs();
			this.disableAddButton();
			this.editMode = false;
		}
		else
		{
			// eslint-disable-next-line no-param-reassign
			editButton.textContent = Loc.getMessage('SIGN_DOCUMENT_SETUP_DOCUMENT_CANCEL_BUTTON');
			this.editMode = true;
			this.#enableDocumentInputs();
			this.enableAddButton();
			this.#currentEditedId = id;
			this.#currentEditButton = editButton;
			this.#currentEditBlock = documentBlock;
		}
	}

	resetEditMode(): void
	{
		if (!this.#currentEditedId)
		{
			return;
		}

		this.#currentEditButton.textContent = Loc.getMessage('SIGN_DOCUMENT_SETUP_DOCUMENT_EDIT_BUTTON');
		Dom.removeClass(this.#currentEditBlock, '--edit');
		this.editMode = false;

		this.#currentEditButton = null;
		this.#currentEditBlock = null;
		this.#currentEditedId = null;
	}

	getHeaderLayout(): HTMLElement
	{
		const headerText = this.isTemplateMode()
			? Loc.getMessage('SIGN_DOCUMENT_SETUP_TEMPLATE_HEADER')
			: Loc.getMessage('SIGN_DOCUMENT_SETUP_HEADER')
		;

		this.headerLayout = Tag.render`
			<h1 class="sign-b2e-settings__header">${headerText}</h1>
		`;

		return this.headerLayout;
	}

	#getDocumentTitleFullClass(): string
	{
		if (this.isTemplateMode())
		{
			return '--full';
		}

		return this.isRuRegion() ? '' : '--full';
	}

	#sendDocumentType(uid: string): Promise<void>
	{
		if (!this.#isDocumentTypeVisible())
		{
			return Promise.resolve();
		}

		const type = this.#documentTypeDropdown.getSelectedId();

		return this.#api.changeRegionDocumentType(uid, type);
	}

	#sendDocumentSenderType(uid: string): Promise<void>
	{
		if (!this.isTemplateMode() || !this.isSenderTypeAvailable())
		{
			return Promise.resolve();
		}

		const senderType = this.#documentSenderTypeDropdown.getSelectedId();
		this.setupData.initiatedByType = senderType;

		return this.#api.changeSenderDocumentType(uid, senderType);
	}

	#sendDocumentNumber(uid: string): Promise<void>
	{
		if (!this.isRuRegion() || this.isTemplateMode())
		{
			return Promise.resolve();
		}

		return this.#api.changeExternalId(uid, this.#documentNumberInput.value);
	}

	#sendDocumentDate(uid: string): Promise<void>
	{
		if (!this.isRuRegion() || this.isTemplateMode())
		{
			return Promise.resolve();
		}

		return this.#api.changeExternalDate(uid, this.#dateSelector.getSelectedDate());
	}

	setDocumentNumber(number: string): void
	{
		this.#documentNumberInput.value = number;
	}

	setDocumentTitle(title: string = ''): void
	{
		this.#documentTitleInput.value = title;
		this.#documentTitleInput.title = title;
	}

	setDocumentType(regionDocumentType: string = ''): void
	{
		if (!this.#isDocumentTypeVisible())
		{
			return;
		}

		const isDocumentTypeExist = this.#regionDocumentTypes.some((item) => item.code === regionDocumentType);
		const documentType = isDocumentTypeExist ? regionDocumentType : this.#regionDocumentTypes[0].code;

		this.#documentTypeDropdown.selectItem(documentType);
	}

	setDocumentSenderType(initiatedByType: string): void
	{
		if (!this.isTemplateMode() || !this.isSenderTypeAvailable())
		{
			return;
		}
		const senderType = this.#senderDocumentTypes.includes(initiatedByType) ? initiatedByType : 'employee';
		this.#documentSenderTypeDropdown.selectItem(senderType);
	}

	initLayout(): void
	{
		this.layout = Tag.render`
			<div class="sign-document-setup">
				${this.getHeaderLayout()}
				${this.getDocumentSectionLayout()}
			</div>
		`;
	}

	getDocumentSectionLayout(): HTMLElement
	{
		if (!this.documentSectionLayout)
		{
			this.documentSectionLayout = Tag.render`
				<div class="sign-b2e-settings__item">
					${this.getDocumentSectionInnerLayout()}
				</div>
			`;
			this.createHintPopup();
		}

		return this.documentSectionLayout;
	}

	getDocumentSectionInnerLayout(): HTMLElement
	{
		const itemTitleText = this.isTemplateMode()
			? Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_TEMPLATE_TITLE')
			: Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_TITLE')
		;

		this.documentSectionInnerLayout = Tag.render`
			<div class="sign-b2e-settings__item-inner">
				<p class="sign-b2e-settings__item_title">
					${itemTitleText}
				</p>
				${this.blankSelector.getLayout()}
			</div>
		`;

		return this.documentSectionInnerLayout;
	}

	createHintPopup(): void
	{
		this.hintPopup = new Popup({
			content: Loc.getMessage('SIGN_DOCUMENT_SETUP_DOCUMENT_LIMIT_POPUP'),
			autoHide: true,
			darkMode: true,
		});
	}

	setAvailabilityDocumentSection(isAvailable: boolean): void
	{
		if (isAvailable)
		{
			Dom.removeClass(this.documentSectionInnerLayout, '--disabled');
			Event.unbind(this.documentSectionLayout, 'click', this.onClickShowHintPopup);
			this.hintPopup.close();

			return;
		}

		Dom.addClass(this.documentSectionInnerLayout, '--disabled');
		Event.bind(this.documentSectionLayout, 'click', this.onClickShowHintPopup);
	}

	showHintPopup(event): void
	{
		this.hintPopup.setBindElement(event);
		this.hintPopup.show();
	}

	async setup(uid: ?string): Promise<void>
	{
		try
		{
			await super.setup(uid, this.isTemplateMode());
			if (!this.setupData || this.blankIsNotSelected)
			{
				this.ready = true;

				return;
			}

			if (uid)
			{
				const { title, externalId, externalDateCreate, initiatedByType, regionDocumentType } = this.setupData;
				this.setDocumentTitle(title);
				this.setDocumentSenderType(initiatedByType);
				this.setDocumentType(regionDocumentType);

				if (this.isRuRegion() && !this.isTemplateMode())
				{
					this.setDocumentNumber(externalId);
					this.#dateSelector.setDateInCalendar(new Date(externalDateCreate));
				}

				return;
			}
			this.ready = false;

			this.setupData = await this.updateDocumentData(this.setupData);
		}
		catch
		{
			const { blankId } = this.setupData;
			this.handleError(blankId);
		}

		this.ready = true;
	}

	async updateDocumentData(documentData: DocumentDetails): Promise<DocumentDetails>
	{
		if (!documentData)
		{
			return;
		}
		await Promise.all([
			this.#sendDocumentType(documentData.uid),
			this.#sendDocumentSenderType(documentData.uid),
			this.#sendDocumentNumber(documentData.uid),
			this.#sendDocumentDate(documentData.uid),
		]);

		const { value: title } = this.#documentTitleInput;
		const { templateUid } = this.setupData;
		const externalId = this.#documentNumberInput?.value;
		let regionDocumentType = null;
		if (this.#isDocumentTypeVisible())
		{
			regionDocumentType = this.#documentTypeDropdown.getSelectedId();
		}
		const modifyDocumentTitleResponse = await this.#api.modifyTitle(documentData.uid, title);
		const { blankTitle } = modifyDocumentTitleResponse;
		if (blankTitle)
		{
			const { blankId } = documentData;
			this.blankSelector.modifyBlankTitle(blankId, blankTitle);
		}

		documentData = { ...documentData, title, externalId, regionDocumentType, templateUid };

		return documentData;
	}

	#validateInput(input: HTMLElement): boolean
	{
		if (!input)
		{
			return true;
		}

		const { parentNode, value } = input;
		if (value.trim() !== '')
		{
			Dom.removeClass(parentNode, 'ui-ctl-warning');

			return true;
		}

		Dom.addClass(parentNode, 'ui-ctl-warning');
		input.focus();

		return false;
	}

	validate(): boolean
	{
		const isValidTitle = this.#validateInput(this.#documentTitleInput);
		const isValidNumber = this.#validateInput(this.#documentNumberInput);

		return isValidTitle && isValidNumber;
	}

	isSenderTypeAvailable(): boolean
	{
		const settings = Extension.getSettings('sign.v2.b2e.document-setup');

		return settings.get('isSenderTypeAvailable');
	}

	resetDocument(): void
	{
		this.blankSelector.resetSelectedBlank();
		this.setDocumentTitle('');

		if (this.isRuRegion())
		{
			this.setDocumentNumber('');
		}

		this.isFileAdded = false;
		this.#disableDocumentInputs();
		this.disableAddButton();
	}

	#enableDocumentInputs(): void
	{
		this.#documentTitleInput.disabled = false;
		if (this.#documentNumberInput)
		{
			this.#documentNumberInput.disabled = false;
		}
		this.blankIsNotSelected = false;
	}

	#disableDocumentInputs(): void
	{
		this.#documentTitleInput.disabled = true;
		if (this.#documentNumberInput)
		{
			this.#documentNumberInput.disabled = true;
		}
		this.blankIsNotSelected = true;
	}
}
