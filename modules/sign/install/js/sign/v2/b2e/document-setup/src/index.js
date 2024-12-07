import { Dom, Loc, Tag, Type } from 'main.core';
import { Api } from 'sign.v2.api';
import { SignDropdown } from 'sign.v2.b2e.sign-dropdown';
import { type BlankSelectorConfig } from 'sign.v2.blank-selector';
import { DocumentSetup as BaseDocumentSetup } from 'sign.v2.document-setup';
import { Hint } from 'sign.v2.helper';
import type { DocumentModeType } from 'sign.v2.sign-settings';
import { isTemplateMode } from 'sign.v2.sign-settings';
import { DateSelector } from './date-selector';
import './style.css';

type RegionDocumentType = {
	code: string,
	description: string,
};

export class DocumentSetup extends BaseDocumentSetup
{
	#api: Api;
	#region: string;
	#regionDocumentTypes: Array<RegionDocumentType>;
	#documentTypeDropdown: HTMLElement;
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
		const documentTypeLayout = this.#getDocumentTypeLayout();
		const title = this.#isTemplateMode()
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

	#getDocumentNumberLayout(): HTMLElement | null
	{
		if (!this.#isRuRegion() || this.#isTemplateMode())
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
		if (this.#isTemplateMode())
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
		if (this.#isTemplateMode())
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

	#sendDocumentNumber(uid: string): Promise<void>
	{
		if (!this.#isRuRegion() || this.#isTemplateMode())
		{
			return Promise.resolve();
		}

		return this.#api.changeExternalId(uid, this.#documentNumberInput.value);
	}

	#sendDocumentDate(uid: string): Promise<void>
	{
		if (!this.#isRuRegion() || this.#isTemplateMode())
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

	initLayout(): void
	{
		this.layout = Tag.render`
			<div class="sign-document-setup">
				<h1 class="sign-b2e-settings__header">${Loc.getMessage('SIGN_DOCUMENT_SETUP_HEADER')}</h1>
				<div class="sign-b2e-settings__item">
					<p class="sign-b2e-settings__item_title">
						${Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_TITLE')}
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
			await super.setup(uid, this.#isTemplateMode());
			if (!this.setupData)
			{
				return;
			}

			if (uid)
			{
				const { title, externalId, externalDateCreate } = this.setupData;
				this.setDocumentTitle(title);

				if (this.#isRuRegion() && !this.#isTemplateMode())
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

	#isTemplateMode(): boolean
	{
		return isTemplateMode(this.#documentMode);
	}

	validate(): boolean
	{
		const isValidTitle = this.#validateInput(this.#documentTitleInput);
		const isValidNumber = this.#validateInput(this.#documentNumberInput);

		return isValidTitle && isValidNumber;
	}
}
