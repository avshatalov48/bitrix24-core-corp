import { Dom, Loc, Tag, Text as TextFormat, Type } from 'main.core';
import { MemoryCache } from 'main.core.cache';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Loader } from 'main.loader';
import { Menu } from 'main.popup';
import type { DocumentInitiatedType, ProviderCodeType } from 'sign.type';
import { DocumentInitiated } from 'sign.type';
import type { B2eCompanyList, Company, Provider } from 'sign.v2.api';
import { Api } from 'sign.v2.api';
import { HcmLinkCompanySelector } from 'sign.v2.b2e.hcm-link-company-selector';
import { type Scheme, SchemeType } from 'sign.v2.b2e.scheme-selector';
import { CompanyEditor, CompanyEditorMode, DocumentEntityTypeId, EditorTypeGuid } from 'sign.v2.company-editor';
import { Alert, AlertColor, AlertSize } from 'ui.alerts';
import { Dialog } from 'ui.entity-selector';
import { hide, show } from './functions';
import type { ProviderSelectedEvent } from './provider-selector';
import { allowedSignatureProviders, ProviderSelector } from './provider-selector';

import './style.css';

export type CompanySelectedEvent = BaseEvent<{companyId: number, provider: Provider }>;
export type { ProviderCodeType, ProviderSelectedEvent };
export const ProviderCode: Readonly<Record<string, ProviderCodeType>> = Object.freeze({
	goskey: 'goskey',
	sesCom: 'ses-com',
	sesRuExpress: 'ses-ru-express',
	sesRu: 'ses-ru',
	external: 'external',
});

export type CompanyData = {
	id: ?number,
	provider: ?Provider
};

export type CompanySelectorOptions = {
	companyId: ?number,
	entityId: number;
	region: string;
	documentInitiatedType?: DocumentInitiatedType;
	loadCompanyPromise?: Promise<B2eCompanyList>;
	canCreateCompany?: boolean;
	canEditCompany?: boolean;
	isCompaniesDeselectable?: boolean,
	needOpenCrmSaveAndEditCompanySliders?: boolean,
};

export const HelpdeskCodes: $ReadOnly<{ [key: string]: string }> = Object.freeze({
	HowToChooseProvider: '19740650',
	GoskeyDetails: '19740688',
	SesRuDetails: '19740668',
	SesComDetails: '19740668',
	SesRuExpressDetails: '26311976',
	TaxcomDetails: '19740696',
	GoskeyApiKey: '19740816',
});

export class CompanySelector extends EventEmitter
{
	events = {
		onCompaniesLoad: 'onCompaniesLoad',
		onSelect: 'onSelect',
		onProviderSelect: 'onProviderSelect',
	};

	#api: Api;
	#layoutCache: MemoryCache<HTMLElement> = new MemoryCache();
	#companyList: Array<Company> = [];

	#reloadDelayForHide: Number = 1000;

	#company: CompanyData = {
		id: null,
		provider: {
			code: null,
			uid: null,
			timestamp: null,
		},
	};

	#loader: Loader = null;
	#dialog: Dialog = null;
	#showTaxId: boolean = true;
	#options: CompanySelectorOptions;
	#loadPromise: Promise<void>;
	#providerSelector: ProviderSelector;

	constructor(options: CompanySelectorOptions = {})
	{
		super();
		this.setEventNamespace('BX.Sign.V2.B2e.CompanySelector');
		this.#options = options;
		this.#api = new Api();
		this.#providerSelector = new ProviderSelector({
			region: options.region,
			documentInitiatedType: options.documentInitiatedType ?? DocumentInitiated.company,
		});

		this.#setEmptyState();
		this.#loadPromise = this.#load();
		this.#subscribeOnEvents();
	}

	#subscribeOnEvents(): void
	{
		const providerSelectorEvents = this.#providerSelector.events;
		this.#providerSelector.subscribe(
			providerSelectorEvents.onProviderSelect,
			(event: ProviderSelectedEvent) => {
				this.#onProviderSelect(event.getData().provider.id);
				this.emit(this.events.onProviderSelect, {
					provider: this.getSelectedCompanyProvider(event),
				});
			},
		);
		this.#providerSelector.subscribe(
			providerSelectorEvents.onProviderDeselect,
			() => this.#onProviderDeselect(),
		);
		this.#providerSelector.subscribe(
			providerSelectorEvents.providerConnectionSlider.onClose,
			() => this.#load(),
		);
		this.#providerSelector.subscribe(
			providerSelectorEvents.onProviderDisconnect,
			this.#onProviderDisconnect.bind(this),
		);
	}

	#showLoader(): void
	{
		hide(this.#getInfoLayout());
		hide(this.#getSelectorLayout());
		this.#getLoader().show(this.getLayout());
	}

	#hideLoader(): void
	{
		show(this.#getSelectorLayout());
		this.#getLoader().hide();
	}

	async load(companyUid: string, companyEntityId: number | null): Promise<void>
	{
		await this.#loadPromise;
		const company = this.#companyList.find(({ id, providers }) => {
			if (!Type.isNull(companyEntityId) && companyEntityId > 0)
			{
				return companyEntityId === id;
			}

			return providers.some(({ uid }) => {
				return uid === companyUid;
			});
		});
		if (Type.isUndefined(company))
		{
			return;
		}
		this.#company.id = company.id;
		this.#updateDialogItems();
		this.#selectProvider(companyUid);
	}

	async loadFirstCompany(): void
	{
		await this.#loadPromise;

		if (this.#companyList.length === 0)
		{
			return;
		}

		const company = this.#companyList[0];
		this.#company.id = company.id;
		this.#updateDialogItems();
		if (company.providers.length > 0)
		{
			this.#selectProvider(company.providers[0].uid);
		}
	}

	setOptions(options: Partial<CompanySelectorOptions>): void
	{
		this.#options = { ...this.#options, ...options };
	}

	getProviderLayout(): HTMLElement
	{
		return this.#providerSelector.getLayout();
	}

	getLayout(): HTMLDivElement
	{
		return this.#layoutCache.remember('layout', () => {
			const requireCrmPermissionLayout = this.#options.needOpenCrmSaveAndEditCompanySliders
				? this.#getCompanySaveAndEditRequireCrmPermissionLayout()
				: ''
			;
			if (!this.#showTaxId)
			{
				hide(this.#getInfoRqInnLayout());
			}

			return Tag.render`
				<div>
					<div class="sign-document-b2e-company">
						${(this.#getSelectorLayout())}
						${(this.#getInfoLayout())}
					</div>
					${requireCrmPermissionLayout}
				</div>
			`;
		});
	}

	#getInfoLayout(): HTMLElement
	{
		return this.#layoutCache.remember('infoLayout', () => {
			return Tag.render`
				<div class="sign-document-b2e-company-info">
					<div class="sign-document-b2e-company-info-img"></div>
					${this.#getInfoTitleLayout()}
					${this.#getInfoEditBtn()}
					${this.#getInfoRqBtnLayout()}
				</div>
			`;
		});
	}

	#getInfoRqBtnLayout(): HTMLElement | null
	{
		if (!(this.#options.canEditCompany ?? true))
		{
			return null;
		}

		return this.#layoutCache.remember('infoRqBtnLayout', () => {
			return Tag.render`
				<button class="ui-btn ui-btn-xs ui-btn-round ui-btn-success" onclick="${() => this.#editCompany()}">
					${Loc.getMessage('SIGN_B2E_COMPANIES_CHANGE_INN_1')}
				</button>
			`;
		});
	}

	#getInfoEditBtn(): HTMLElement | null
	{
		if (!(this.#options.canEditCompany ?? true))
		{
			return null;
		}

		return this.#layoutCache.remember('infoEditBtn', () => {
			return Tag.render`
				<div class="sign-document-b2e-company-info-edit" onclick="${() => this.#showEditMenu()}"></div>
			`;
		});
	}

	#getInfoTitleLayout(): HTMLElement
	{
		return this.#layoutCache.remember('infoTitleLayout', () => {
			return Tag.render`
				<div class="sign-document-b2e-company-info-title">
					<div class="sign-document-b2e-company-info-header">
						${this.#getInfoHeaderTitleNameLayout()}
						${this.#getCompanyInfoLabelLayout()}
						<div class="sign-document-b2e-company-info-dropdown-btn"
							onclick="${() => this.#onInfoDropDownBtnClick()}">
						</div>
					</div>
					${this.#getInfoRqInnLayout()}
				</div>
			`;
		});
	}

	#getInfoHeaderTitleNameLayout(): HTMLElement
	{
		return this.#layoutCache.remember('infoHeaderTitleNameLayout', () => {
			return Tag.render`
				<div class="sign-document-b2e-company-info-name"></div>
			`;
		});
	}

	#getInfoRqInnLayout(): HTMLElement
	{
		return this.#layoutCache.remember('infoRqInnLayout', () => {
			return Tag.render`
				<div class="sign-document-b2e-company-info-rq-inn"></div>
			`;
		});
	}

	#getSelectorBtnLayout(): HTMLElement
	{
		return this.#layoutCache.remember('selectorBtnLayout', () => {
			return Tag.render`
				<button class="ui-btn ui-btn-success ui-btn-xs ui-btn-round" onclick="${() => this.#onSelectorBtnClick()}">
					${Loc.getMessage('SIGN_B2E_COMPANIES_SELECT_BUTTON')}
				</button>
			`;
		});
	}

	#onSelectorBtnClick(): void
	{
		this.#getDialog().setTargetNode(this.getLayout());
		this.#getDialog().show();
	}

	#getSelectorLayout(): HTMLElement
	{
		return this.#layoutCache.remember('selectorLayout', () => {
			return Tag.render`
				<div class="sign-document-b2e-company-select">
					<span class="sign-document-b2e-company-select-text">
						${Loc.getMessage('SIGN_B2E_COMPANIES_NOT_CHANGED')}
					</span>
					${(this.#getSelectorBtnLayout())}
				</div>
			`;
		});
	}

	#setEmptyState(): void
	{
		hide(this.#getInfoLayout());
		hide(this.getProviderLayout());
		Dom.style(this.#getSelectorLayout(), 'display', 'flex');
	}

	#setInfoState(): void
	{
		Dom.style(this.#getInfoLayout(), 'display', 'flex');
		hide(this.#getSelectorLayout());
	}

	async #load(): Promise<void>
	{
		this.#showLoader();
		const loadCompanyPromise = this.#options.loadCompanyPromise
			?? this.#api.loadB2eCompanyList(this.#options.documentInitiatedType ?? DocumentInitiated.company)
		;

		let data = null;
		try
		{
			data = await loadCompanyPromise;
		}
		catch (error)
		{
			this.#hideLoader();
			console.error(error);

			return;
		}

		this.#hideLoader();
		if (Type.isObject(data.companies) && Type.isArray(data.companies))
		{
			this.#companyList = data.companies;
			this.#providerSelector.setCompanyList(this.#companyList);
			this.#showTaxId = Boolean(data?.showTaxId);
			this.#providerSelector.setShowTaxId(this.#showTaxId);
			this.#updateDialogItems();
			this.emit(this.events.onCompaniesLoad, { companies: this.#companyList });
		}
	}

	#getLoader(): Loader
	{
		if (this.#loader)
		{
			return this.#loader;
		}

		this.#loader = new BX.Loader({
			target: this.getLayout(),
			mode: 'inline',
			size: 40,
		});

		return this.#loader;
	}

	#getDialog(): Dialog
	{
		if (this.#dialog)
		{
			return this.#dialog;
		}

		let footer = null;
		if (this.#options.canCreateCompany ?? true)
		{
			footer = Tag.render`
				<span
					class="ui-selector-footer-link ui-selector-footer-link-add"
					onclick="${() => this.#createCompany()}"
				>
					${Loc.getMessage('SIGN_B2E_ADD_COMPANY')}
				</span>
			`;
		}
		this.#dialog = new Dialog({
			targetNode: this.getLayout(),
			width: 425,
			height: 363,
			items: this.#companyList.map((company) => {
				return {
					id: company.id,
					entityId: 'b2e-company',
					title: company.title,
					tabs: 'b2e-companies',
					deselectable: this.#options.isCompaniesDeselectable ?? true,
				};
			}),
			tabs: [
				{ id: 'b2e-companies', title: Loc.getMessage('SIGN_B2E_COMPANIES_TAB') },
			],
			showAvatars: false,
			dropdownMode: true,
			multiple: false,
			enableSearch: true,
			events: {
				'Item:OnSelect': (event) => {
					this.#onCompanySelectedHandler(event);
					this.#dialog.hide();
				},
				'Item:OnDeselect': (event) => {
					this.#onCompanyDeselectedHandler(event);
					this.#dialog.hide();
				},
			},
			footer,
		});

		return this.#dialog;
	}

	#selectProvider(id: string): void
	{
		this.#providerSelector.setProviderById(id);
	}

	#onProviderDeselect(): void
	{
		this.#company.provider = null;
	}

	async #onProviderDisconnect(event: BaseEvent<{ provider: Provider }>): void
	{
		const provider = event.getData().provider;
		const id = provider.uid;

		if (!id || provider.autoRegister)
		{
			return;
		}

		this.#showLoader();
		const company = this.#getCompanyById(this.#company.id ?? 0);
		try
		{
			await this.#api.deleteB2eCompany(id);
		}
		catch (e)
		{
			console.error(e);

			return;
		}

		const newCompanyProviders = company.providers.filter(({ uid }: Provider) => uid !== id);
		this.#company.providers = newCompanyProviders;
		this.#getCompanyById(this.#company.id).providers = newCompanyProviders;

		this.#providerSelector.setCompanyList(this.#companyList);

		this.#hideLoader();
		this.selectCompany(company.id);
	}

	#onProviderSelect(id: string): void
	{
		const company = this.#getCompanyById(this.#company.id ?? 0);
		this.#company.provider = company.providers.find((provider) => provider.uid === id);
	}

	getSelectedCompanyProvider(): Provider | null
	{
		return this.#getCurrentProvider() ?? null;
	}

	#onCompanySelectedHandler(event): void
	{
		if (!event.data || event.data.length === 0)
		{
			return;
		}
		const selectedItem = event.data.item;
		if (selectedItem?.id <= 0)
		{
			return;
		}

		this.selectCompany(selectedItem?.id);
	}

	selectCompany(id: number): void
	{
		const company = this.#getCompanyById(id);
		if (Type.isUndefined(company))
		{
			return;
		}

		this.#company.id = company.id;
		this.#company.provider = null;
		if (company?.providers?.length > 0)
		{
			const filteredProviders = company.providers
				?.filter((provider) => allowedSignatureProviders.includes(provider.code))
				?? []
			;
			if (filteredProviders.length > 0)
			{
				this.#company.provider = filteredProviders[0];
			}
		}
		this.#providerSelector.setCompany(this.#company);

		this.#refreshView();
		this.#getDialog().getItems()
			.find((item) => item.id === this.#company.id)
			?.select()
		;

		this.emit(this.events.onSelect, { companyId: this.#company.id });

		const event: CompanySelectedEvent = new BaseEvent({
			data: {
				companyId: this.#company.id,
				provider: this.getSelectedCompanyProvider(),
			},
		});
		this.emit(this.events.onSelect, event);
	}

	#refreshView(): void
	{
		const selectedItem = this.#getCompanyById(this.#company?.id);
		if (!selectedItem)
		{
			return;
		}

		this.#getInfoHeaderTitleNameLayout().innerText = selectedItem.title;
		show(this.#getInfoEditBtn());
		show(this.#getInfoRqBtnLayout());

		if (Type.isStringFilled(selectedItem.rqInn))
		{
			this.#getInfoRqInnLayout().innerText = Loc.getMessage(
				'SIGN_B2E_COMPANIES_INN',
				{ '%innValue%': TextFormat.encode(selectedItem.rqInn) },
			);
			Dom.style(this.#getInfoRqInnLayout(), 'display', this.#showTaxId ? '' : 'none');
			Dom.hide(this.#getCompanyInfoLabelLayout());

			hide(this.#getInfoRqBtnLayout());
		}
		else
		{
			this.#getInfoRqInnLayout().textContent = '';
			Dom.show(this.#getCompanyInfoLabelLayout());
			hide(this.#getInfoEditBtn());
		}

		this.#providerSelector.setProvider(selectedItem.rqInn, this.#getCompanyById(this.#company.id ?? 0));
		this.#setInfoState();
	}

	#updateDialogItems(): void
	{
		this.#dialog = null;
		this.#dialog = this.#getDialog();
		const item = this.#dialog.getItems().find(({ id }) => id === this.#company.id);
		item?.select();
	}

	#getCompanyById(id: number): Company | undefined
	{
		return this.#companyList.find((company) => id === company.id);
	}

	#onCompanyDeselectedHandler(event): void
	{
		this.#company.id = null;
		this.#company.provider = {
			key: null,
			uid: null,
		};

		this.#setEmptyState();
	}

	#showEditMenu()
	{
		const menu = new Menu({
			bindElement: this.#getInfoEditBtn(),
			cacheable: false,
		});
		menu.addMenuItem({
			text: Loc.getMessage('SIGN_B2E_COMPANIES_EDIT'),
			onclick: () => {
				this.#editCompany();
				menu.close();
			},
		});
		menu.show();
	}

	#createCompany(): void
	{
		if (this.#options.needOpenCrmSaveAndEditCompanySliders)
		{
			const companiesIdsBeforeSliderClose: Set<number> = new Set(this.#companyList.map((company) => company.id));

			BX.SidePanel.Instance.open(
				'/crm/company/details/0/?mycompany=y',
				{
					cacheable: false,
					events: {
						onClose: async () => {
							this.#dialog.hide();
							await this.#load();
							const newCompany = this.#companyList
								.find(({ id }) => !companiesIdsBeforeSliderClose.has(id))
							;
							if (!Type.isUndefined(newCompany))
							{
								this.selectCompany(newCompany.id);
							}
						},
					},
				},
			);

			return;
		}
		CompanyEditor.openSlider({
			mode: CompanyEditorMode.Create,
			documentEntityId: this.#options.entityId,
			layoutTitle: Loc.getMessage('SIGN_B2E_COMPANY_CREATE'),
			entityTypeId: DocumentEntityTypeId.B2e,
			guid: EditorTypeGuid.B2e,
			events: {
				onCompanySavedHandler: (companyId: number): void => {
					this.#company.id = companyId;
				},
			},
		}, {
			onCloseHandler: () => {
				this.#load();
				this.#dialog.hide();
			},
		});
	}

	#editCompany(): void
	{
		if (!Type.isInteger(this.#company.id))
		{
			return;
		}

		if (this.#options.needOpenCrmSaveAndEditCompanySliders)
		{
			BX.SidePanel.Instance.open(
				`/crm/company/details/${this.#company.id}/`,
				{
					cacheable: false,
					events: {
						onClose: () => this.#load(),
					},
				},
			);

			return;
		}

		CompanyEditor.openSlider({
			mode: CompanyEditorMode.Edit,
			documentEntityId: this.#options.entityId,
			companyId: this.#company.id,
			layoutTitle: Loc.getMessage('SIGN_B2E_COMPANY_EDIT'),
			entityTypeId: DocumentEntityTypeId.B2e,
			guid: EditorTypeGuid.B2e,
		}, {
			onCloseHandler: () => this.#load(),
		});
	}

	getCompanyId(): ?number
	{
		return this.#company?.id;
	}

	validate(): boolean
	{
		Dom.removeClass(this.getLayout(), '--invalid');
		Dom.removeClass(this.getProviderLayout().firstElementChild, '--invalid');

		const isProviderValid = this.#providerSelector.validate();
		const company = this.#getCompanyById(this.#company.id ?? 0);
		const isCompanyValid = Type.isObject(company) && company.id > 0 && company.rqInn > 0;
		const isValid = isCompanyValid && isProviderValid;

		if (!isCompanyValid)
		{
			Dom.addClass(this.getLayout(), '--invalid');
		}
		else if (!isProviderValid)
		{
			Dom.addClass(this.getProviderLayout().firstElementChild, '--invalid');
		}

		return isValid;
	}

	async save(documentId: string): Promise<any>
	{
		await this.#providerSelector.registerVirtualProviderIfNeed();
		const provider = this.#getCurrentProvider();

		if (Type.isNull(provider))
		{
			return Promise.reject();
		}

		if (Type.isNull(this.#company.id))
		{
			return Promise.reject();
		}

		return Promise.all([
			this.#api.modifyB2eCompany(documentId, provider.uid, this.#company.id),
			this.#api.modifyB2eDocumentScheme(documentId, this.#getDefaultSchemeByProviderCode(provider.code)),
		]);
	}

	/**
	 * This method is required for backward compatibility.
	 * It should be removed once the SES RU provider supports the default scheme.
	 *
	 * @param {ProviderCodeType} provider - The provider code.
	 * @returns {Scheme} - The signing scheme for the given provider.
	 */
	#getDefaultSchemeByProviderCode(provider: ProviderCodeType): Scheme
	{
		return provider === ProviderCode.sesRu && this.#options.documentInitiatedType === DocumentInitiated.company
			? SchemeType.Order
			: SchemeType.Default;
	}

	setInitiatedByType(initiatedByType: DocumentInitiatedType): void
	{
		this.setOptions({ documentInitiatedType: initiatedByType });
	}

	#getCompanyInfoLabelLayout(): HTMLElement
	{
		return this.#layoutCache.remember('companyInfoLabel', () => {
			return Tag.render`
				<div class="ui-label ui-label-orange ui-label-fill sign-document-b2e-company-info-label">
					<div class="ui-label-inner">${Loc.getMessage('SIGN_V2_B2E_COMPANY_SELECTOR_COMPANY_RQ_WARNING_LABEL')}</div>
				</div>
			`;
		});
	}

	#getCompanySaveAndEditRequireCrmPermissionLayout(): HTMLElement
	{
		return this.#layoutCache.remember('companySaveAndEditRequireCrmPermissionLayout', () => {
			const alert = new Alert({
				text: Loc.getMessage('SIGN_V2_B2E_COMPANY_SELECTOR_SAVE_AND_EDIT_REQUIRE_CRM_PERMISSION'),
				color: AlertColor.WARNING,
				size: AlertSize.XS,
				customClass: 'sign-document-b2e-company__alert',
			});

			return alert.render();
		});
	}

	#onInfoDropDownBtnClick(): void
	{
		this.#getDialog().setTargetNode(this.getLayout());
		this.#getDialog().show();
	}

	async reloadCompanyProviders(selectFirst: boolean = true): Promise<void>
	{
		await this.#load();
		if (selectFirst)
		{
			await this.loadFirstCompany();
		}
	}

	#getCurrentProvider(): ?Provider
	{
		return this.#providerSelector.getCurrentProvider();
	}
}
