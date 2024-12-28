import { Dom, Loc, Tag, Type, Extension } from 'main.core';
import { Api } from 'sign.v2.api';
import { SignDropdown } from 'sign.v2.b2e.sign-dropdown';
import { type BlankSelectorConfig } from 'sign.v2.blank-selector';
import { DocumentInitiated, type DocumentInitiatedType, DocumentSetup as BaseDocumentSetup } from 'sign.v2.document-setup';
import { Helpdesk, Hint } from 'sign.v2.helper';
import type { DocumentModeType } from 'sign.v2.sign-settings';
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
	#api: Api;
	#region: string;
	#regionDocumentTypes: Array<RegionDocumentType>;
	#senderDocumentTypes: DocumentInitiatedType[];
	#documentTypeDropdown: HTMLElement;
	#documentSenderTypeDropdown: HTMLElement;
	#documentNumberInput: HTMLInputElement | null = null;
	#documentTitleInput: HTMLInputElement;
	#dateSelector: DateSelector | null = null;
	#documentMode: DocumentModeType;

	constructor(blankSelectorConfig: BlankSelectorConfig)
	{
		super(blankSelectorConfig);
		const { region, regionDocumentTypes, documentMode } = blankSelectorConfig;
		this.#api = new Api();
		this.#region = region;
		this.#documentMode = documentMode;
		this.#regionDocumentTypes = regionDocumentTypes;
		this.#senderDocumentTypes = Object.values(DocumentInitiated);
		this.#documentTitleInput = Tag.render`
			<input
				type="text"
				class="ui-ctl-element"
				maxlength="255"
				oninput="${({ target }) => this.setDocumentTitle(target.value)}"
			/>
		`;
		if (this.#isRuRegion() && !isTemplateMode(documentMode))
		{
			this.#documentNumberInput = Tag.render`<input type="text" class="ui-ctl-element" maxlength="255" />`;
			this.#dateSelector = new DateSelector();
		}

		this.blankSelector.subscribe('toggleSelection', ({ data }) => {
			this.setDocumentTitle(data.title);
		});
		this.blankSelector.subscribe('addFile', ({ data }) => {
			this.setDocumentTitle(data.title);
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
		Hint.create(this.layout);
	}

	#isDocumentTypeVisible(): boolean
	{
		return this.#regionDocumentTypes?.length;
	}

	#isRuRegion(): boolean
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
		if (!this.#isRuRegion() || this.isTemplateMode())
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

	#getDocumentHintLayout(): HTMLElement | null
	{
		if (this.isTemplateMode())
		{
			return null;
		}

		return Tag.render`
			<p class="sign-b2e-document-setup__title-text">
				${Loc.getMessage('SIGN_DOCUMENT_SETUP_TITLE_HINT')}
			</p>
		`;
	}

	#getDocumentTitleFullClass(): string
	{
		if (this.isTemplateMode())
		{
			return '--full';
		}

		return this.#isRuRegion() ? '' : '--full';
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
		if (!this.#isRuRegion() || this.isTemplateMode())
		{
			return Promise.resolve();
		}

		return this.#api.changeExternalId(uid, this.#documentNumberInput.value);
	}

	#sendDocumentDate(uid: string): Promise<void>
	{
		if (!this.#isRuRegion() || this.isTemplateMode())
		{
			return Promise.resolve();
		}

		return this.#api.changeExternalDate(uid, this.#dateSelector.getSelectedDate());
	}

	#setDocumentNumber(number: string): void
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
		const headerText = this.isTemplateMode()
			? Loc.getMessage('SIGN_DOCUMENT_SETUP_TEMPLATE_HEADER')
			: Loc.getMessage('SIGN_DOCUMENT_SETUP_HEADER')
		;
		const itemTitleText = this.isTemplateMode()
			? Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_TEMPLATE_TITLE')
			: Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_TITLE')
		;
		this.layout = Tag.render`
			<div class="sign-document-setup">
				<h1 class="sign-b2e-settings__header">${headerText}</h1>
				<div class="sign-b2e-settings__item">
					<p class="sign-b2e-settings__item_title">
						${itemTitleText}
					</p>
					${this.blankSelector.getLayout()}
				</div>
			</div>
		`;
	}

	async setup(uid: ?string): Promise<void>
	{
		try
		{
			await super.setup(uid, this.isTemplateMode());
			if (!this.setupData)
			{
				return;
			}

			if (uid)
			{
				const { title, externalId, externalDateCreate, initiatedByType, regionDocumentType } = this.setupData;
				this.setDocumentTitle(title);
				this.setDocumentSenderType(initiatedByType);
				this.setDocumentType(regionDocumentType);

				if (this.#isRuRegion() && !this.isTemplateMode())
				{
					this.#setDocumentNumber(externalId);
					this.#dateSelector.setDateInCalendar(new Date(externalDateCreate));
				}

				return;
			}

			const { uid: documentUid, templateUid } = this.setupData;
			const { value: title } = this.#documentTitleInput;
			const externalId = this.#documentNumberInput?.value;
			this.ready = false;
			await Promise.all([
				this.#sendDocumentType(documentUid),
				this.#sendDocumentSenderType(documentUid),
				this.#sendDocumentNumber(documentUid),
				this.#sendDocumentDate(documentUid),
			]);

			const modifyDocumentTitleResponse = await this.#api.modifyTitle(documentUid, title);
			const { blankTitle } = modifyDocumentTitleResponse;
			if (blankTitle)
			{
				const { blankId } = this.setupData;
				this.blankSelector.modifyBlankTitle(blankId, blankTitle);
			}

			this.setupData = { ...this.setupData, title, externalId, templateUid };
		}
		catch
		{
			const { blankId } = this.setupData;
			this.handleError(blankId);
		}

		this.ready = true;
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
}
