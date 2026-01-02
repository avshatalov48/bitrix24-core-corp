import { Dom, Event, Extension, Loc, Tag, Type } from 'main.core';
import { MemoryCache } from 'main.core.cache';
import { BaseEvent } from 'main.core.events';
import { Popup } from 'main.popup';
import { FeatureStorage } from 'sign.feature-storage';
import { DocumentInitiated, type DocumentInitiatedType } from 'sign.type';
import { Api } from 'sign.v2.api';
import { DocumentCounters } from 'sign.v2.b2e.document-counters';
import { SignDropdown } from 'sign.v2.b2e.sign-dropdown';
import { type BlankSelectorConfig, type ToggleEvent } from 'sign.v2.blank-selector';
import { type DocumentDetails, DocumentSetup as BaseDocumentSetup } from 'sign.v2.document-setup';
import { Helpdesk, Hint } from 'sign.v2.helper';
import { NewBlankForTemplatePopupManager } from './components/template/new-blank-for-template-popup-manager';
import 'sign.v2.ui.notice';
import './style.css';

const HelpdeskCodes = Object.freeze({
	HowToWorkWithTemplates: '23174934',
});

export class DocumentSetup extends BaseDocumentSetup
{
	#cache: MemoryCache<any> = new MemoryCache();
	#api: Api;
	#region: string;
	#senderDocumentTypes: DocumentInitiatedType[];
	#documentSenderTypeDropdown: HTMLElement;
	#documentTitleInput: HTMLInputElement;
	headerLayout: HTMLElement;
	documentCounters: DocumentCounters | null = null;
	#b2eDocumentLimitCount: number;
	editMode: boolean;
	#currentEditedId: number;
	#currentEditButton: HTMLElement;
	#currentEditBlock: HTMLElement;
	#isOpenedFromRobot: boolean = false;
	#isOpenedFromTemplateFolder: boolean = false;
	#isOpenedAsFolder: boolean = false;
	documentSectionLayout: HTMLElement;
	documentSectionInnerLayout: HTMLElement;
	#initiatedByType: DocumentInitiatedType;

	constructor(blankSelectorConfig: BlankSelectorConfig)
	{
		super(blankSelectorConfig);
		const {
			region,
			b2eDocumentLimitCount,
			isOpenedFromRobot,
			isOpenedFromTemplateFolder,
			isOpenedAsFolder,
			initiatedByType,
		} = blankSelectorConfig;
		this.#api = new Api();
		this.#region = region;
		this.#b2eDocumentLimitCount = b2eDocumentLimitCount;
		this.editMode = false;
		this.onClickShowHintPopup = this.showHintPopup.bind(this);
		this.#isOpenedFromRobot = isOpenedFromRobot;
		this.#isOpenedFromTemplateFolder = isOpenedFromTemplateFolder;
		this.#isOpenedAsFolder = isOpenedAsFolder;
		this.#senderDocumentTypes = this.#getSenderDocumentTypes();

		this.#documentTitleInput = Tag.render`
			<input
				type="text"
				class="ui-ctl-element"
				maxlength="255"
				oninput="${({ target }) => this.setDocumentTitle(target.value)}"
			/>
		`;
		this.#initiatedByType = initiatedByType;

		this.#disableDocumentInputs();
		this.disableAddButton();

		this.#init();
	}

	#init(): void
	{
		this.#initDocumentSenderType();
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

		Dom.append(this.#getDocumentSenderTypeLayout(), this.layout);
		Dom.append(titleLayout, this.layout);

		if (!this.isTemplateMode() && FeatureStorage.isGroupSendingEnabled())
		{
			this.documentCounters = new DocumentCounters({
				documentCountersLimit: this.#b2eDocumentLimitCount,
			});
			Dom.append(this.documentCounters.getLayout(), this.layout);

			const addDocumentLayout = this.#getAddDocumentLayout();
			Dom.append(addDocumentLayout, this.layout);
		}
		Hint.create(this.layout);

		this.#subscribeOnEvents();
	}

	#getSenderDocumentTypes(): DocumentInitiatedType[]
	{
		if (this.#isOpenedFromTemplateFolder || this.#isOpenedAsFolder)
		{
			return [DocumentInitiated.company];
		}

		return Object.values(DocumentInitiated);
	}

	#subscribeOnEvents(): void
	{
		const blankSelector = this.blankSelector;
		blankSelector.subscribe(blankSelector.events.toggleSelection, this.#onBlankSelectorToggleSelection.bind(this));
		blankSelector.subscribe(blankSelector.events.addFile, this.#onBlankSelectorAddFile.bind(this));
		blankSelector.subscribe(
			blankSelector.events.beforeAddFileSuccessfully,
			this.#onBlankSelectorBeforeAddFileSuccessfully.bind(this),
		);
		if (!this.isTemplateMode() && FeatureStorage.isGroupSendingEnabled())
		{
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
		}
	}

	#onBlankSelectorAddFile(event: BaseEvent<{ title: string }>)
	{
		const data = event.getData();
		this.isFileAdded = true;
		this.enableDocumentInputs();
		this.enableAddButton();
		if (!this.isTemplateMode() || !this.isEditActionMode())
		{
			this.setDocumentTitle(data.title);
		}
		const popupManager = NewBlankForTemplatePopupManager.getOrCreateForObject(this);
		// Document title will update after popup confirmation
		if (popupManager.isUploadPopupCompletedOnce())
		{
			this.setDocumentTitle(data.title);
		}
	}

	#onBlankSelectorToggleSelection(event: ToggleEvent): void
	{
		if (this.blankIsNotSelected && this.editMode)
		{
			return;
		}

		const data = event.getData();

		const handleEvent = () => {
			this.setDocumentTitle(data.title);

			if (data.selected)
			{
				this.enableDocumentInputs();
				this.enableAddButton();
			}
		};

		if (!this.isEditActionMode() || !this.isTemplateMode())
		{
			handleEvent();

			return;
		}

		const popupManager = NewBlankForTemplatePopupManager.getOrCreateForObject(this);
		if (popupManager.isSelectBlankPopupCompletedOnce())
		{
			handleEvent();

			return;
		}

		const extra = data.extra;
		if (!data.selected || extra?.isInitial || extra?.skipSelectPopupShow)
		{
			return;
		}

		const previousSelectedBlankId = data.previousSelectedBlankId;

		popupManager.showSelectBlankPopup();
		popupManager.subscribeOnce(popupManager.events.selectBlankPopup.onConfirm, handleEvent);
		popupManager.subscribeOnce(
			popupManager.events.selectBlankPopup.onCancel,
			() => {
				popupManager.unsubscribe(
					popupManager.events.selectBlankPopup.onConfirm,
					handleEvent,
				);
				if (Type.isNumber(previousSelectedBlankId) && previousSelectedBlankId > 0)
				{
					this.blankSelector.selectBlank(previousSelectedBlankId, { skipSelectPopupShow: true });
				}
			},
		);
	}

	#onBlankSelectorBeforeAddFileSuccessfully(event: BaseEvent): void
	{
		if (!this.isTemplateMode() || !this.isEditActionMode())
		{
			return;
		}

		const newBlankPopupManager = NewBlankForTemplatePopupManager.getOrCreateForObject(this);
		const lastSelectedBlank = this.blankSelector.selectedBlankId;

		if (newBlankPopupManager.isUploadPopupCompletedOnce())
		{
			return;
		}

		newBlankPopupManager.showUploadPopup();
		const onCancelListener = () => {
			this.blankSelector.clearFiles();
			this.blankSelector.selectBlank(lastSelectedBlank, { skipSelectPopupShow: true });
		};

		const onConfirmListener = () => {
			this.blankSelector.getBlank();
			const title = this.blankSelector.getUploadedFileName(0);
			if (Type.isStringFilled(title))
			{
				this.setDocumentTitle(title);
			}
		};

		const unsubscribeAnotherListenerDecorator = (
			listener: (event?: BaseEvent) => any,
			unsubscribedListener: (event?: BaseEvent) => any,
		) => {
			return (event: BaseEvent) => {
				listener(event);
				newBlankPopupManager.unsubscribe(
					newBlankPopupManager.events.uploadPopup.onCancel,
					unsubscribedListener,
				);
				newBlankPopupManager.unsubscribe(
					newBlankPopupManager.events.uploadPopup.onConfirm,
					unsubscribedListener,
				);
			};
		};
		newBlankPopupManager.subscribeOnce(
			newBlankPopupManager.events.uploadPopup.onCancel,
			unsubscribeAnotherListenerDecorator(onCancelListener, onConfirmListener),
		);
		newBlankPopupManager.subscribeOnce(
			newBlankPopupManager.events.uploadPopup.onConfirm,
			unsubscribeAnotherListenerDecorator(onConfirmListener, onCancelListener),
		);
	}

	isRuRegion(): boolean
	{
		return this.#region === 'ru';
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
		const selectedKey = this.#isOpenedFromRobot ? 1 : 0;
		const selectedItem = this.#senderDocumentTypes[selectedKey] ?? null;
		if (selectedItem)
		{
			this.#documentSenderTypeDropdown.selectItem(selectedItem);
		}
	}

	#getDocumentSenderTypeLayout(): HTMLElement | null
	{
		if (this.#shouldHideSenderTypeLayout())
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

	#shouldHideSenderTypeLayout(): boolean
	{
		return (
			!this.isTemplateMode()
			|| !this.isSenderTypeAvailable()
			|| this.#isOpenedFromRobot
			|| this.#isOpenedFromTemplateFolder
			|| this.#isOpenedAsFolder
		);
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

	#getDocumentTitleLayout(): HTMLElement
	{
		return Tag.render`
			<div>
				<div class="sign-b2e-document-setup__title-item --full">
					<div class="ui-ctl ui-ctl-textbox">
						${this.#documentTitleInput}
					</div>
				</div>
				${this.#getDocumentHintLayout()}
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
				<p class="sign-wizard__notice">${Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_DOCUMENT_NOTICE')}</p>
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
		this.getAddDocumentNotice().textContent = Loc.getMessage(
			'SIGN_DOCUMENT_SETUP_DOCUMENT_GROUP_LIMIT_NOTICE',
			{ '%limit%': this.#b2eDocumentLimitCount },
		);
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

		return Tag.render`
			<div class="sign-b2e-document-setup__document-block" data-id="document-id-${documentData.id}">
				<div class="sign-b2e-document-setup__document-block_inner">
					<div class="sign-b2e-document-setup__document-block_title">${documentData.title}</div>
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

	updateDocumentBlock(id: number): void
	{
		const editedBlock = this.layout.querySelector(`[data-id="document-id-${id}"]`);
		const titleNode = editedBlock.querySelector('.sign-b2e-document-setup__document-block_title');
		titleNode.textContent = this.#documentTitleInput.title;
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
			this.enableDocumentInputs();
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

	#sendDocumentSenderType(uid: string): Promise<void>
	{
		if (!this.isTemplateMode() || !this.isSenderTypeAvailable())
		{
			return Promise.resolve();
		}

		const senderType = this.#getDocumentSenderType();
		this.setupData.initiatedByType = senderType;

		return this.#api.changeSenderDocumentType(uid, senderType);
	}

	#getDocumentSenderType(): ?DocumentInitiatedType
	{
		if (!this.isTemplateMode())
		{
			return null;
		}

		if (!this.isSenderTypeAvailable())
		{
			return this.#initiatedByType;
		}

		return this.#documentSenderTypeDropdown.getSelectedId();
	}

	setDocumentTitle(title: string = ''): void
	{
		this.#documentTitleInput.value = title;
		this.#documentTitleInput.title = title;
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
			const copyBlocksFromPreviousBlank = this.isEditActionMode()
				&& this.isTemplateMode()
				&& this.blankSelector.isFilesReadyForUpload();

			await super.setup(
				uid,
				this.isTemplateMode(),
				copyBlocksFromPreviousBlank,
				this.#getDocumentSenderType(),
			);
			if (!this.setupData || this.blankIsNotSelected)
			{
				this.ready = true;

				return;
			}

			if (uid)
			{
				const { title, initiatedByType } = this.setupData;
				this.setDocumentTitle(title);
				this.setDocumentSenderType(initiatedByType);

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

	async updateDocumentData(documentData: DocumentDetails): Promise<DocumentDetails | undefined>
	{
		if (!documentData)
		{
			return;
		}

		await Promise.all([
			this.#sendDocumentSenderType(documentData.uid),
		]);

		const { value: title } = this.#documentTitleInput;
		const { templateUid } = this.setupData;
		const modifyDocumentTitleResponse = await this.#api.modifyTitle(documentData.uid, title);
		const { blankTitle } = modifyDocumentTitleResponse;
		if (blankTitle)
		{
			const { blankId } = documentData;
			this.blankSelector.modifyBlankTitle(blankId, blankTitle);
		}

		return { ...documentData, title, templateUid };
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
		return this.#validateInput(this.#documentTitleInput);
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

		this.isFileAdded = false;
		this.#disableDocumentInputs();
		this.disableAddButton();
	}

	enableDocumentInputs(): void
	{
		this.#documentTitleInput.disabled = false;
		this.blankIsNotSelected = false;
	}

	#disableDocumentInputs(): void
	{
		this.#documentTitleInput.disabled = true;
		this.blankIsNotSelected = true;
	}
}
