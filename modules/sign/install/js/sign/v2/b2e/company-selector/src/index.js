import { Dom, Event, Loc, Tag, Text as TextFormat, Type, Uri } from 'main.core';
import { MemoryCache } from 'main.core.cache';
import { EventEmitter } from 'main.core.events';
import { DateTimeFormat } from 'main.date';
import { Loader } from 'main.loader';
import { Menu } from 'main.popup';
import { Guide } from 'sign.tour';
import type { B2eCompanyList, Company } from 'sign.v2.api';
import { type Provider, Api } from 'sign.v2.api';
import { HcmLinkCompanySelector } from 'sign.v2.b2e.hcm-link-company-selector';
import { type Scheme, SchemeType } from 'sign.v2.b2e.scheme-selector';
import { CompanyEditor, CompanyEditorMode, DocumentEntityTypeId, EditorTypeGuid } from 'sign.v2.company-editor';
import { DocumentInitiated, ProviderCode } from 'sign.type';
import type { DocumentInitiatedType, ProviderCodeType } from 'sign.type';
import { Helpdesk, Link } from 'sign.v2.helper';
import { Alert, AlertColor, AlertSize } from 'ui.alerts';
import { Dialog } from 'ui.entity-selector';
import { Label, LabelColor } from 'ui.label';

import './style.css';

type CompanyData = {
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
	isHcmLinkAvailable: boolean,
	needOpenCrmSaveAndEditCompanySliders?: boolean,
};

const allowedSignatureProviders: Array<ProviderCodeType> = ['goskey', 'external', 'ses-ru', 'ses-com'];
const sesComLearnMoreLink = new Uri('https://www.bitrix24.com/terms/esignature-for-hr-rules.php');

export const HelpdeskCodes: $ReadOnly<{ [key: string]: string }> = Object.freeze({
	HowToChooseProvider: '19740650',
	GoskeyDetails: '19740688',
	SesRuDetails: '19740668',
	SesComDetails: '19740668',
	TaxcomDetails: '19740696',
	GoskeyApiKey: '19740816',
});

export class CompanySelector extends EventEmitter
{
	events = {
		onCompaniesLoad: 'onCompaniesLoad',
		onSelect: 'onSelect',
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
	#providerMenu: Dialog = null;
	#dialog: Dialog = null;
	#showTaxId: boolean = true;
	#isHcmLinkAvailable: boolean = false;
	#integrationSelector: HcmLinkCompanySelector;

	#ui = {
		container: HTMLDivElement = null,
		info: {
			container: HTMLDivElement = null,
			title: {
				container: HTMLDivElement = null,
				header: {
					container: HTMLDivElement = null,
					name: HTMLDivElement = null,
					dropdownButton: HTMLDivElement = null,
				},
				rqInn: HTMLDivElement = null,
			},
			editButton: HTMLDivElement = null,
			setRqInnButton: HTMLButtonElement = null,
		},
		select: {
			container: HTMLDivElement = null,
			text: HTMLSpanElement = null,
			button: HTMLButtonElement = null,
		},
		provider: {
			container: HTMLDivElement = null,
			info: HTMLParagraphElement = null,
			connected: {
				container: HTMLDivElement = null,
				nameContainer: HTMLElement,
				nameLabelContainer: HTMLElement,
				name: HTMLSpanElement,
				description: HTMLSpanElement,
				selectDropdownButton: HTMLSpanElement,
				connectDropdownButton: HTMLSpanElement,
			},
			disconnected: {
				container: HTMLDivElement = null,
				emptyProvider: HTMLSpanElement = null,
				button: HTMLButtonElement = null,
			},
			unset: {
				container: HTMLDivElement = null,
				text: HTMLSpanElement = null,
				button: HTMLButtonElement = null,
			},
		},
	};

	#isSubscribedIframeCloseEvent = false;
	#isSubscribedIframeConnectedEvent = false;
	#registerIframe: HTMLElement | null;
	#iframeConnectInterval = null;
	#options: CompanySelectorOptions;
	#providerExpiresDaysToShowInfo: Number = 45;
	#loadPromise: Promise<void>;

	constructor(options: CompanySelectorOptions = {})
	{
		super();
		this.setEventNamespace('BX.Sign.V2.B2e.CompanySelector');
		this.#api = new Api();
		this.#integrationSelector = new HcmLinkCompanySelector();
		this.setIntegrationSelectorAvailability(options.isHcmLinkAvailable);
		this.#isHcmLinkAvailable = options.isHcmLinkAvailable;
		this.#options = options;
		this.#ui.provider.container = this.getProviderLayout();
		this.#ui.container = this.getLayout();
		this.#setEmptyState();
		this.#bindEvents();
		this.#loadPromise = this.#load();
	}

	setIntegrationSelectorAvailability(isAvailable: boolean): void
	{
		this.#integrationSelector.setAvailability(isAvailable);

		if (isAvailable)
		{
			Dom.append(this.#integrationSelector.render(), this.#ui.container);
			this.#integrationSelector.setCompanyId(this.#company.id);
			this.#integrationSelector.isLayoutExisted = true;
			this.#isHcmLinkAvailable = true;

			return;
		}

		this.#integrationSelector.hide();
		this.#integrationSelector.isLayoutExisted = false;
		this.#isHcmLinkAvailable = false;
	}

	#showLoader(): void
	{
		BX.hide(this.#ui.info.container);
		BX.hide(this.#ui.select.container);
		this.#getLoader().show(this.#ui.container);
	}

	#hideLoader(): void
	{
		this.#setEmptyState();
		if (this.#options.isHcmLinkAvailable && !Type.isNull(this.getCompanyId()))
		{
			this.#integrationSelector.show();
		}
		this.#getLoader().hide();
	}

	async load(companyUid: string): Promise<void>
	{
		await this.#loadPromise;
		const company = this.#companyList.find((company) => {
			return company.providers.some((provider) => {
				return provider.uid === companyUid;
			});
		});
		if (Type.isUndefined(company))
		{
			return;
		}
		this.#company.id = company.id;
		this.#integrationSelector.setCompanyId(this.#company.id);
		this.#updateDialogItems();
		this.#selectProvider(companyUid);
	}

	setOptions(options: Partial<CompanySelectorOptions>): void
	{
		this.#options = { ...this.#options, ...options };
	}

	getProviderLayout(): HTMLElement
	{
		if (this.#ui.provider.container)
		{
			return this.#ui.provider.container;
		}

		this.#ui.provider.disconnected.emptyProvider = Tag.render`
			<span class="sign-document-b2e-company-select-text">
				${Loc.getMessage('SIGN_B2E_COMPANY_NOT_CONNECTED_PROVIDER_STATUS')}
			</span>
		`;
		this.#ui.provider.disconnected.button = Tag.render`
			<button
				class="ui-btn ui-btn-success ui-btn-xs ui-btn-round"
				onclick="${() => this.#openProvidersConnectionSlider()}"
			>
				${Loc.getMessage('SIGN_B2E_PROVIDER_CONNECT')}
			</button>
		`;
		this.#ui.provider.disconnected.container = Tag.render`
			<div class="sign-document-b2e-company-select --provider">
				${this.#ui.provider.disconnected.emptyProvider}
				${this.#ui.provider.disconnected.button}
			</div>
		`;
		this.#ui.provider.connected.name = Tag.render`
			<span class="sign-document-b2e-company__provider_name"></span>
		`;
		this.#ui.provider.connected.nameLabelContainer = Tag.render`
			<div class="sign-document-b2e-company__provider_name_label"></div>
		`;
		this.#ui.provider.connected.nameContainer = Tag.render`
			<div class="sign-document-b2e-company__provider_name_container">
				${this.#ui.provider.connected.name}
				${this.#ui.provider.connected.nameLabelContainer}
			</div>
		`;
		this.#ui.provider.connected.description = Tag.render`
			<span class="sign-document-b2e-company__provider_descr"></span>
		`;
		this.#ui.provider.connected.selectDropdownButton = Tag.render`
			<span
				class="sign-document-b2e-company-info-dropdown-btn sign-document-b2e-company__provider_dropdown-btn"
				onclick="${() => this.#providerMenu.show()}"
			></span>
		`;
		this.#ui.provider.connected.connectDropdownButton = Tag.render`
			<span
				class="sign-document-b2e-company-info-edit"
				onclick="${() => this.#showConnectMenu()}"
			></span>
		`;
		this.#ui.provider.connected.container = Tag.render`
			<div class="sign-document-b2e-company__provider_selected">
				<div class="sign-document-b2e-company__provider_selected__external-image-container">
					<img class="sign-document-b2e-company__provider_selected__external-img"
						referrerpolicy="no-referrer"
					>
				</div>
				<div>
					${this.#ui.provider.connected.nameContainer}
					${this.#ui.provider.connected.description}
				</div>
				${this.#ui.provider.connected.selectDropdownButton}
				${this.#ui.provider.connected.connectDropdownButton}
			</div>
		`;
		this.#ui.provider.info = Tag.render`
			<div class="sign-document-b2e-company__provider_info"></div>
		`;
		this.#ui.provider.unset.text = Tag.render`
			<span class="sign-document-b2e-company-select-text">
				${Loc.getMessage('SIGN_B2E_COMPANY_NOT_SET_PROVIDER_STATUS')}
			</span>
		`;
		this.#ui.provider.unset.button = Tag.render`
			<button
				class="ui-btn ui-btn-success ui-btn-xs ui-btn-round"
				onclick="${() => this.#providerMenu.show()}"
			>
				${Loc.getMessage('SIGN_B2E_COMPANIES_SELECT_BUTTON')}
			</button>
		`;
		this.#ui.provider.unset.container = Tag.render`
			<div class="sign-document-b2e-company-select --provider">
				${this.#ui.provider.unset.text}
				${this.#ui.provider.unset.button}
			</div>
		`;
		this.#ui.provider.container = Tag.render`
			<div class="sign-document-b2e-company__provider">
				<div class="sign-document-b2e-company__provider_content">
					${this.#ui.provider.connected.container}
					${this.#ui.provider.disconnected.container}
					${this.#ui.provider.unset.container}
				</div>
				${this.#ui.provider.info}
			</div>
		`;

		return this.#ui.provider.container;
	}

	getLayout(): HTMLDivElement
	{
		if (this.#ui.container)
		{
			return this.#ui.container;
		}

		this.#ui.info.title.header.dropdownButton = Tag.render`
			<div class="sign-document-b2e-company-info-dropdown-btn"></div>
		`;
		this.#ui.info.title.header.name = Tag.render`
			<div class="sign-document-b2e-company-info-name"></div>
		`;

		if (this.#options.canEditCompany ?? true)
		{
			this.#ui.info.editButton = Tag.render`
				<div class="sign-document-b2e-company-info-edit"></div>
			`;
			this.#ui.info.setRqInnButton = Tag.render`
				<button class="ui-btn ui-btn-xs ui-btn-round ui-btn-success">
					${Loc.getMessage('SIGN_B2E_COMPANIES_CHANGE_INN_1')}
				</button>
			`;
		}

		this.#ui.info.title.header.container = Tag.render`
			<div class="sign-document-b2e-company-info-header">
				${this.#ui.info.title.header.name}
				${this.#getCompanyInfoLabelLayout()}
				${this.#ui.info.title.header.dropdownButton}
			</div>
		`;

		this.#ui.info.title.rqInn = Tag.render`
			<div class="sign-document-b2e-company-info-rq-inn"></div>
		`;
		if (!this.#showTaxId)
		{
			this.#ui.info.title.rqInn.style.display = 'none';
		}
		this.#ui.info.title.container = Tag.render`
			<div class="sign-document-b2e-company-info-title">
				${this.#ui.info.title.header.container}
				${this.#ui.info.title.rqInn}
			</div>
		`;

		this.#ui.info.container = Tag.render`
			<div class="sign-document-b2e-company-info">
				<div class="sign-document-b2e-company-info-img"></div>
				${this.#ui.info.title.container}
				${this.#ui.info.editButton}
				${this.#ui.info.setRqInnButton}
			</div>
		`;

		this.#ui.select.text = Tag.render`
			<span class="sign-document-b2e-company-select-text">
				${Loc.getMessage('SIGN_B2E_COMPANIES_NOT_CHANGED')}
			</span>
		`;
		this.#ui.select.button = Tag.render`
			<button class="ui-btn ui-btn-success ui-btn-xs ui-btn-round">
				${Loc.getMessage('SIGN_B2E_COMPANIES_SELECT_BUTTON')}
			</button>
		`;
		this.#ui.select.container = Tag.render`
			<div class="sign-document-b2e-company-select">
				${this.#ui.select.text}
				${this.#ui.select.button}
			</div>
		`;
		const requireCrmPermissionLayout = this.#options.needOpenCrmSaveAndEditCompanySliders
			? this.#getCompanySaveAndEditRequireCrmPermissionLayout()
			: ''
		;
		this.#ui.container = Tag.render`
			<div>
				<div class="sign-document-b2e-company">
					${this.#ui.select.container}
					${this.#ui.info.container}
				</div>
				${requireCrmPermissionLayout}
			</div>
		`;

		if (this.#options.isHcmLinkAvailable)
		{
			Dom.append(this.#integrationSelector.render(), this.#ui.container);
		}

		return this.#ui.container;
	}

	#tryStartProviderTour(): void
	{
		const guide = new Guide({
			id: 'sign-b2e-provider-tour',
			onEvents: true,
			autoSave: true,
			steps: [
				{
					target: this.#ui.provider.connected.selectDropdownButton,
					title: `
						<p class="sign-document-b2e-company__provider_tour-step-head">
							${Loc.getMessage('SIGN_B2E_TOUR_HEAD')}
						</p>
					`,
					text: `
						<p class="sign-document-b2e-company__provider_tour-step-text">
							${Loc.getMessage('SIGN_B2E_TOUR_TEXT')}
						</p>
						<span class="sign-document-b2e-company__provider_tour-step-icon"></span>
					`,
					condition: {
						top: true,
						bottom: false,
						color: 'primary',
					},
				},
			],
			popupOptions: {
				width: 380,
				autoHide: true,
				className: 'sign-document-b2e-company__provider_popup-tour',
				centerAngle: true,
			},
		});
		guide.startOnce();
	}

	#showConnectMenu()
	{
		const menu = new Menu({
			bindElement: this.#ui.provider.connected.connectDropdownButton,
			cacheable: false,
		});
		menu.addMenuItem({
			text: Loc.getMessage('SIGN_B2E_PROVIDER_DISCONNECT'),
			onclick: () => {
				this.#disconnectCurrentProvider();
				menu.close();
			},
		});
		menu.show();
	}

	#setEmptyState(): void
	{
		BX.hide(this.#ui.info.container);
		BX.hide(this.#ui.provider.container);
		this.#integrationSelector.hide();
		this.#ui.select.container.style.display = 'flex';
	}

	#setInfoState(): void
	{
		this.#ui.info.container.style.display = 'flex';
		BX.hide(this.#ui.select.container);
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
			console.log(error);

			return;
		}

		this.#hideLoader();
		if (Type.isObject(data.companies) && Type.isArray(data.companies))
		{
			this.#companyList = data.companies;
			this.#showTaxId = Boolean(data?.showTaxId);
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
			target: this.#ui.container,
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
			targetNode: this.#ui.container,
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

	#getProviderMenu(): Dialog
	{
		if (this.#providerMenu)
		{
			return this.#providerMenu;
		}

		this.#providerMenu = new Dialog({
			width: 425,
			height: 363,
			targetNode: this.#ui.provider.container.firstElementChild,
			items: [],
			showAvatars: true,
			dropdownMode: true,
			multiple: false,
			autoHide: true,
			tabs: [
				{ id: 'b2e-providers', title: Loc.getMessage('SIGN_B2E_PROVIDERS_TAB') },
			],
			events: {
				'Item:OnSelect': ({ data }) => {
					this.#onProviderSelect(data.item.id);
				},
				'Item:OnDeselect': () => this.#onProviderDeselect(),
			},
			footer: this.#getProviderAddButton(),
		});

		return this.#providerMenu;
	}

	#getProviderAddButton(): ?HTMLElement
	{
		const company = this.#getCompanyById(this.#company?.id);
		if (company?.registerUrl)
		{
			return Tag.render`
				<span
					class="ui-selector-footer-link ui-selector-footer-link-add"
					onclick="${() => {
						this.#providerMenu.hide();
						this.#openProvidersConnectionSlider();
					}}"
				>
					${Loc.getMessage('SIGN_B2E_PROVIDER_CONNECT_SELECTOR')}
				</span>
			`;
		}

		return null;
	}

	#selectProvider(id: string): void
	{
		const providerMenu = this.#getProviderMenu();
		const providers = providerMenu.getItems();
		const currentProvider = providers.find((provider) => provider.id === id);
		currentProvider?.select();
	}

	#onProviderDeselect(): void
	{
		this.#ui.provider.unset.container.style.display = 'flex';
		BX.hide(this.#ui.provider.connected.container);
		this.#renderProviderInfo();
		this.#providerMenu.hide();
		this.#company.provider = null;
	}

	#onProviderSelect(id: string): void
	{
		const company = this.#getCompanyById(this.#company.id ?? 0);
		const provider = company.providers.find((provider) => provider.uid === id);
		BX.hide(this.#ui.provider.unset.container);
		BX.show(this.#ui.provider.connected.container);
		BX.show(this.#ui.provider.info);
		this.#chooseProvider(provider, company.rqInn);
		this.#providerMenu.hide();
	}

	#resetProviderClasses()
	{
		const providerClasses = allowedSignatureProviders.map((provider) => `--${provider}`);
		providerClasses.push('--expired');
		Dom.removeClass(this.#ui.provider.connected.container, providerClasses);
	}

	#resetProvider()
	{
		this.#providerMenu = null;
		this.#resetProviderClasses();
	}

	#updateProviderMenu(): void
	{
		this.#resetProvider();
		const menu = this.#getProviderMenu();
		const company = this.#getCompanyById(this.#company.id);

		company.providers.forEach((provider: Provider) => {
			const { providerName, description } = this.#getConnectedName(provider, company.rqInn);

			menu.addItem({
				id: provider.uid,
				title: providerName,
				subtitle: description,
				avatar: this.#getEntityAvatar(provider),
				entityId: 'b2e-provider',
				tabs: 'b2e-providers',
				badges: this.#getEntityBadges(provider),
			});
		});
		const [firstItem] = menu.getItems();
		firstItem.select();

		const nothingToSelect = !company?.registerUrl && company?.providers?.length < 2;
		this.#ui.provider.connected.selectDropdownButton.style.display = nothingToSelect
			? 'none'
			: 'block'
		;
	}

	#renderProviderInfo(provider: Provider | null = null): void
	{
		const code = provider?.code ?? '';
		Dom.clean(this.#ui.provider.info);
		if (!provider)
		{
			const firstParagraph = Tag.render`
				<p>
					${Loc.getMessage('SIGN_B2E_COMPANIES_UNSET_PROVIDER_PARAGRAPH_1')}
				</p>
			`;
			const secondParagraph = Tag.render`
				<p>
					${Loc.getMessage('SIGN_B2E_COMPANIES_UNSET_PROVIDER_PARAGRAPH_2')}
				</p>
			`;
			const thirdParagraph = Tag.render`
				<p>
					${Helpdesk.replaceLink(
				Loc.getMessage('SIGN_B2E_COMPANIES_UNSET_PROVIDER_MORE'),
				HelpdeskCodes.HowToChooseProvider,
			)}
				</p>
			`;
			Dom.append(firstParagraph, this.#ui.provider.info);
			Dom.append(secondParagraph, this.#ui.provider.info);
			Dom.append(thirdParagraph, this.#ui.provider.info);

			return;
		}

		if (code === ProviderCode.external)
		{
			const element = Tag.render`
				<p> ${TextFormat.encode(provider.description)} </p>
			`;

			Dom.append(element, this.#ui.provider.info);

			return;
		}

		if (code === ProviderCode.sesCom)
		{
			let providerInfo = Loc.getMessage('SIGN_B2E_COMPANY_SES_COM_INFO') ?? '';
			providerInfo = Helpdesk.replaceLink(
				providerInfo,
				HelpdeskCodes.SesComDetails,
			);
			providerInfo = Link.replaceInLoc(
				providerInfo,
				sesComLearnMoreLink,
			);

			const text = Tag.render`<span>${providerInfo}</span>`;
			// Waiting for ready an article
			text.firstElementChild.style.display = 'none';
			Dom.append(text, this.#ui.provider.info);

			return;
		}

		const providerCodeToProviderInfoTextMap: { [key: ProviderCodeType]: ?string } = {
			goskey: Loc.getMessage('SIGN_B2E_COMPANY_GOSKEY_INFO'),
			'ses-ru': Loc.getMessage('SIGN_B2E_COMPANY_SES_RU_INFO'),
		};
		const providerCodeToHelpdeskCodeMap: { [key: ProviderCodeType]: string } = {
			goskey: HelpdeskCodes.GoskeyDetails,
			'ses-ru': HelpdeskCodes.SesRuDetails,
		};

		const text = Tag.render`<span>${Helpdesk.replaceLink(
			providerCodeToProviderInfoTextMap[code] ?? '',
			providerCodeToHelpdeskCodeMap[code] ?? '',
		)}</span>`;
		Dom.append(text, this.#ui.provider.info);

		if (this.#isProviderExpiresSoon(provider) || this.#isProviderExpired(provider))
		{
			Dom.append(this.#getProviderAlert(provider).render(), this.#ui.provider.info);
		}
	}

	#chooseProvider(provider: Provider, rqInn: number): void
	{
		if (!allowedSignatureProviders.includes(provider.code))
		{
			return;
		}

		const { providerName, description } = this.#getConnectedName(provider, rqInn);
		this.#ui.provider.connected.name.textContent = providerName;
		this.#ui.provider.connected.description.textContent = description;

		this.#renderProviderInfo(provider);
		this.#resetProviderClasses();
		Dom.addClass(this.#ui.provider.connected.container, `--with-icon --${provider.code}`);
		if (provider.code === ProviderCode.external)
		{
			this.#setProviderImage(provider);
		}

		Dom.clean(this.#ui.provider.connected.nameLabelContainer);
		if (this.#isProviderExpired(provider))
		{
			Dom.addClass(this.#ui.provider.connected.container, '--expired');
			Dom.append(this.#makeLabel().render(), this.#ui.provider.connected.nameLabelContainer);
		}
		this.#ui.provider.connected.connectDropdownButton.style.display = provider.autoRegister ? 'none' : ' flex';
		this.#company.provider = provider;
	}

	getSelectedCompanyProvider(): Provider | null
	{
		return this.#company.provider ?? null;
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

		this.#refreshView();
		this.#getDialog().getItems()
			.find((item) => item.id === this.#company.id)
			?.select()
		;
		this.#integrationSelector.setCompanyId(this.#company.id);
		this.emit(this.events.onSelect, { companyId: this.#company.id });
	}

	#refreshView(): void
	{
		const selectedItem = this.#getCompanyById(this.#company?.id);
		if (!selectedItem)
		{
			return;
		}

		this.#ui.info.title.header.name.innerText = selectedItem.title;
		if (this.#ui.info.editButton)
		{
			BX.show(this.#ui.info.editButton);
		}

		if (this.#ui.info.setRqInnButton)
		{
			BX.show(this.#ui.info.setRqInnButton);
		}
		if (Type.isStringFilled(selectedItem.rqInn))
		{
			this.#ui.info.title.rqInn.innerText = Loc.getMessage(
				'SIGN_B2E_COMPANIES_INN',
				{ '%innValue%': TextFormat.encode(selectedItem.rqInn) },
			);
			this.#ui.info.title.rqInn.style.display = this.#showTaxId ? '' : 'none';
			Dom.hide(this.#getCompanyInfoLabelLayout());

			if (this.#ui.info.setRqInnButton)
			{
				BX.hide(this.#ui.info.setRqInnButton);
			}
		}
		else
		{
			this.#ui.info.title.rqInn.textContent = '';
			Dom.show(this.#getCompanyInfoLabelLayout());
			if (this.#ui.info.editButton)
			{
				BX.hide(this.#ui.info.editButton);
			}
		}

		this.#resetProviderState();
		this.#toggleProviderState(selectedItem.rqInn);
		this.#setInfoState();
	}

	#resetProviderState()
	{
		BX.show(this.#ui.provider.container);
		BX.show(this.#ui.provider.info);
		BX.show(this.#ui.provider.disconnected.button);
		this.#ui.provider.connected.container.style.display = 'flex';
		this.#ui.provider.disconnected.container.style.display = 'flex';
	}

	#toggleProviderState(rqInn: ?string)
	{
		const company = this.#getCompanyById(this.#company.id ?? 0);
		if (company?.providers?.length > 0)
		{
			BX.hide(this.#ui.provider.disconnected.container);
			this.#updateProviderMenu();
			if (this.#options.region === 'ru' && this.#options.documentInitiatedType !== DocumentInitiated.employee)
			{
				this.#tryStartProviderTour();
			}

			return;
		}

		BX.hide(this.#ui.provider.connected.container);
		BX.hide(this.#ui.provider.unset.container);
		BX.hide(this.#ui.provider.info);
		if (!rqInn)
		{
			BX.hide(this.#ui.provider.disconnected.button);
		}
	}

	#updateDialogItems(): void
	{
		this.#dialog = null;
		this.#dialog = this.#getDialog();
		const item = this.#dialog.getItems().find((item) => item.id === this.#company.id);
		item?.select();
	}

	#getCompanyById(id: number): Company | undefined
	{
		return this.#companyList.find((company) => id === company.id);
	}

	#getConnectedName(provider: Provider, rqInn: number): string
	{
		const providerName = provider.code !== 'external'
			? this.#getProviderNameByCode(provider.code)
			: provider.name
		;
		const description = this.#getProviderConnectedDescription(provider, rqInn);

		return { providerName, description };
	}

	#getProviderConnectedDescription(provider: Provider, rqInn: number): string
	{
		if (provider.autoRegister)
		{
			return this.#showTaxId
				? Loc.getMessage('SIGN_B2E_SELECT_PROVIDER_WITHOUT_DATE', {
					'#RQINN#': rqInn,
				})
				: Loc.getMessage('SIGN_B2E_SELECT_PROVIDER_WITHOUT_INN_DATE')
			;
		}

		const formattedDate = DateTimeFormat.format(
			DateTimeFormat.getFormat('FORMAT_DATE'),
			provider.timestamp,
		);

		return this.#showTaxId
			? Loc.getMessage('SIGN_B2E_SELECT_PROVIDER', {
				'#RQINN#': rqInn,
				'#DATE#': formattedDate,
			})
			: Loc.getMessage('SIGN_B2E_SELECT_PROVIDER_WITHOUT_INN', { '#DATE#': formattedDate })
		;
	}

	#getProviderNameByCode(code): string
	{
		switch (code)
		{
			case 'goskey':
				return Loc.getMessage('SIGN_B2E_PROVIDER_GOSKEY_NAME');
			case 'taxcom':
				return Loc.getMessage('SIGN_B2E_PROVIDER_TAXCOM_NAME');
			case 'ses-ru':
				return Loc.getMessage('SIGN_B2E_PROVIDER_SES_NAME');
			case 'ses-com':
				return Loc.getMessage('SIGN_B2E_PROVIDER_SES_COM_NAME');
			default:
				return '';
		}
	}

	#openProvidersConnectionSlider(): void
	{
		const company = this.#getCompanyById(this.#company?.id);
		if (company && company.registerUrl)
		{
			const url = new URL(company.registerUrl);
			const allowedOrigin = url.origin;
			BX.SidePanel.Instance.open('sign:stub', {
				width: 1100,
				cacheable: false,
				allowCrossDomain: true,
				allowChangeHistory: false,
				contentCallback: () => {
					const frameStyles = 'position: absolute; left: 0; top: 0; padding: 0;'
						+ ' border: none; margin: 0; width: 100%; height: 100%;';
					this.#registerIframe = Tag.render`<iframe src="${company.registerUrl}" style="${frameStyles}"></iframe>`;

					return this.#registerIframe;
				},
				events: {
					onClose: () => this.#load(),
				},
			});

			this.#initIframeConnect(allowedOrigin);
			this.#subscribeIframeConnectedEvent(allowedOrigin);
			this.#subscribeIframeCloseEvent(allowedOrigin);
		}
	}

	async #disconnectCurrentProvider(): Promise
	{
		if (!this.#company.provider)
		{
			return;
		}
		const company = this.#getCompanyById(this.#company.id ?? 0);
		if (!company)
		{
			return;
		}
		const id = this.#company.provider.uid;
		if (!id || this.#company.provider.autoRegister)
		{
			return;
		}

		this.#showLoader();

		try
		{
			await this.#api.deleteB2eCompany(id);
			company.providers = company.providers.filter((provider: Provider) => provider.uid !== id);
			this.#company.provider = null;
		}
		catch (e)
		{
			console.error(e);
		}
		this.#hideLoader();
		this.selectCompany(company.id);
	}

	#bindEvents(): void
	{
		Event.bind(this.#ui.select.button, 'click', () => {
			this.#getDialog().setTargetNode(this.#ui.container);
			this.#getDialog().show();
		});
		Event.bind(this.#ui.info.title.header.dropdownButton, 'click', () => {
			this.#getDialog().setTargetNode(this.#ui.container);
			this.#getDialog().show();
		});
		if (this.#ui.info.editButton)
		{
			Event.bind(this.#ui.info.editButton, 'click', () => this.#showEditMenu());
		}

		Event.bind(this.#ui.info.setRqInnButton, 'click', () => this.#editCompany());
	}

	#onCompanyDeselectedHandler(event): void
	{
		this.#company.id = null;
		this.#integrationSelector.setCompanyId(this.#company.id);
		this.#company.provider = {
			key: null,
			uid: null,
		};

		this.#setEmptyState();
	}

	#initIframeConnect(allowedOrigin)
	{
		this.#iframeConnectInterval = setInterval(() => {
			if (this.#registerIframe && this.#registerIframe.contentWindow)
			{
				this.#registerIframe.contentWindow.postMessage('Event:b2e-crossorigin:initConnection', allowedOrigin);
			}
		}, 500);
	}

	#showEditMenu()
	{
		const menu = new Menu({
			bindElement: this.#ui.info.editButton,
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
					this.#integrationSelector.setCompanyId(this.#company.id);
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

	#subscribeIframeConnectedEvent(allowedOrigin)
	{
		if (this.#isSubscribedIframeConnectedEvent)
		{
			return;
		}

		window.addEventListener('message', (event) => {
			if (event.origin === allowedOrigin && event.data === 'Event:b2e-crossorigin:connected')
			{
				clearInterval(this.#iframeConnectInterval);
				const company = this.#getCompanyById(this.#company.id ?? 0);
				if (company)
				{
					this.#registerIframe.contentWindow.postMessage({ companyName: company.title }, allowedOrigin);
				}
			}
		});

		this.#isSubscribedIframeConnectedEvent = true;
	}

	#subscribeIframeCloseEvent(allowedOrigin)
	{
		if (this.#isSubscribedIframeCloseEvent)
		{
			return;
		}
		window.addEventListener('message', (event) => {
			if (event.origin === allowedOrigin && event.data === 'Event:b2e-crossorigin:close-iframe')
			{
				BX.SidePanel.Instance.close();
			}
		});

		this.#isSubscribedIframeCloseEvent = true;
	}

	async #registerVirtualProviderIfNeed(): Promise<void>
	{
		if (!this.#company.provider.virtual)
		{
			return;
		}

		const selectedItem = this.#getCompanyById(this.#company.id);
		const { id } = await this.#api.registerB2eCompany(
			this.#company.provider.code,
			selectedItem.rqInn,
			this.#company.id,
			this.#company.provider.externalProviderId,
		);
		this.#company.provider.uid = id;
		this.#company.provider.virtual = false;
		setTimeout(() => this.#load(), this.#reloadDelayForHide);
	}

	getCompanyId(): ?number
	{
		return this.#company?.id;
	}

	getIntegrationId(): number | null
	{
		return this.#integrationSelector.getSelectedId();
	}

	setLastSavedIntegrationId(integrationId: number | null): void
	{
		this.#integrationSelector.setLastSavedId(integrationId);
	}

	validate(): boolean
	{
		Dom.removeClass(this.#ui.container, '--invalid');
		Dom.removeClass(this.#ui.provider.container.firstElementChild, '--invalid');
		const isProviderValid = Type.isObject(this.#company.provider)
			&& Type.isStringFilled(this.#company.provider.uid)
			&& !this.#isProviderExpired(this.#company.provider)
		;
		const company = this.#getCompanyById(this.#company.id ?? 0);
		const isCompanyValid = Type.isObject(company) && company.id > 0 && company.rqInn > 0;
		const isValid = isCompanyValid && isProviderValid;

		if (!isCompanyValid)
		{
			Dom.addClass(this.#ui.container, '--invalid');
		}
		else if (!isProviderValid)
		{
			Dom.addClass(this.#ui.provider.container.firstElementChild, '--invalid');
		}

		return isValid;
	}

	async save(documentId: string): Promise<any>
	{
		await this.#registerVirtualProviderIfNeed();
		const provider = this.#company.provider;

		if (this.#isHcmLinkAvailable)
		{
			await this.#api.changeIntegrationId(documentId, this.#integrationSelector?.getSelectedId());
		}

		return Promise.all([
			this.#api.modifyB2eCompany(documentId, provider.uid),
			this.#api.modifyB2eDocumentScheme(documentId, this.#getDefaultSchemeByProviderCode(provider.code)),
		]);
	}

	#isProviderExpiresSoon(provider: Provider): boolean
	{
		if (!provider.expires)
		{
			return false;
		}
		const daysLeft = this.#getProviderDaysLeft(provider.expires);

		return daysLeft <= this.#providerExpiresDaysToShowInfo && daysLeft >= 1;
	}

	#isProviderExpired(provider: Provider): boolean
	{
		return provider.expires && this.#getProviderDaysLeft(provider.expires) < 1;
	}

	#getProviderDaysLeft(expires: Number): Number
	{
		const now = Date.now() / 1000;

		return Math.floor((expires - now) / 86400);
	}

	#makeLabel(): Label
	{
		return new Label({
			text: Loc.getMessage('SIGN_B2E_GOSKEY_APIKEY_EXPIRED'),
			color: LabelColor.WARNING,
			fill: true,
			customClass: 'sign-document-b2e-company__provider_label',
		});
	}

	#getEntityAvatar(provider: Provider): string
	{
		if (provider.code === ProviderCode.goskey)
		{
			return this.#isProviderExpired(provider)
				? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzYiIGhlaWdodD0iMzYiIHZpZXdCb3g9IjAgMCAzNiAzNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjM2IiBoZWlnaHQ9IjM2IiByeD0iMTgiIGZpbGw9IiNCREMxQzYiLz4KPHBhdGggZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik0xNi4zMzA1IDE0Ljg5OTlMMTkuNzU3MiAxMS40NzMxQzIwLjM4ODEgMTAuODQyMyAyMS40MTA5IDEwLjg0MjMgMjIuMDQxNyAxMS40NzMxTDI0LjcwNyAxNC4xMzg0QzI1LjMzNzggMTQuNzY5MiAyNS4zMzc4IDE1Ljc5MiAyNC43MDcgMTYuNDIyOUwyMS4yODAyIDE5Ljg0OTZDMjAuODU5NyAyMC4yNzAyIDIwLjE3OTEgMjAuMjcxNSAxOS43NTg1IDE5Ljg1MDlMMTYuMzI5OCAxNi40MjIyQzE1LjkwOTMgMTYuMDAxNiAxNS45MDk5IDE1LjMyMDQgMTYuMzMwNSAxNC44OTk5Wk0yMS42NjEgMTUuNjYxNEMyMS4zNDU2IDE1Ljk3NjggMjAuODM0MiAxNS45NzY4IDIwLjUxODcgMTUuNjYxNEMyMC4yMDMzIDE1LjM0NiAyMC4yMDMzIDE0LjgzNDYgMjAuNTE4NyAxNC41MTkxQzIwLjgzNDIgMTQuMjAzNyAyMS4zNDU2IDE0LjIwMzcgMjEuNjYxIDE0LjUxOTFDMjEuOTc2NCAxNC44MzQ2IDIxLjk3NjQgMTUuMzQ2IDIxLjY2MSAxNS42NjE0WiIgZmlsbD0id2hpdGUiLz4KPHBhdGggZD0iTTE3LjA5MiAxNy45NDU5TDE4LjQyNDYgMTkuMjc4NUMxOC42MzQ5IDE5LjQ4ODggMTguNjM0OSAxOS44Mjk3IDE4LjQyNDYgMjAuMDRMMTcuMDU5MyAyMS40MDUzQzE2Ljk1ODQgMjEuNTA2MyAxNi44MjE0IDIxLjU2MyAxNi42Nzg2IDIxLjU2M0gxNS4zNzg2VjIyLjg2M0MxNS4zNzg2IDIzLjAwNTggMTUuMzIxOSAyMy4xNDI3IDE1LjIyMDkgMjMuMjQzN0wxNS4xNTU2IDIzLjMwOUMxNS4wNTQ2IDIzLjQxIDE0LjkxNzYgMjMuNDY2OCAxNC43NzQ4IDIzLjQ2NjhIMTMuNDc0OVYyNC43NjY3QzEzLjQ3NDkgMjQuOTA5NSAxMy40MTgxIDI1LjA0NjUgMTMuMzE3MiAyNS4xNDc1TDEyLjg3MTEgMjUuNTkzNUMxMi43NzAxIDI1LjY5NDUgMTIuNjMzMSAyNS43NTEzIDEyLjQ5MDMgMjUuNzUxM0gxMS4zMjVDMTEuMjM4OCAyNS43NTEzIDExLjE1NjEgMjUuNzE3IDExLjA5NTIgMjUuNjU2MUMxMS4wMzQyIDI1LjU5NTEgMTEgMjUuNTEyNSAxMSAyNS40MjYzVjIzLjY1NzFMMTUuMTg4MiAxOS40Njg5QzE0Ljk3OCAxOS4yNTg2IDE0Ljk3OCAxOC45MTc3IDE1LjE4ODIgMTguNzA3NEwxNS45NDk3IDE3Ljk0NTlDMTYuMjY1MiAxNy42MzA1IDE2Ljc3NjYgMTcuNjMwNSAxNy4wOTIgMTcuOTQ1OVoiIGZpbGw9IndoaXRlIi8+CjxwYXRoIGZpbGwtcnVsZT0iZXZlbm9kZCIgY2xpcC1ydWxlPSJldmVub2RkIiBkPSJNNS42MTUyNiAxNy4wNTY0VjE5LjMwMDJDNS42MTUyNiAyMC43MzA5IDUuNjE2ODIgMjEuNjk0NiA1LjY4NTA2IDIyLjQ1MjJDNS43NTAzMSAyMy4xNzY1IDUuODY4ODYgMjMuNTk0MiA2LjA0MzgxIDIzLjkzNDVDNi4yMTg3NyAyNC4yNzQ5IDYuNDg4MzggMjQuNjEyMyA3LjAzNjA3IDI1LjA4MDRDNy42MDg4NiAyNS41NyA4LjM4NjI5IDI2LjEyMTcgOS41NDE2OCAyNi45Mzg3TDExLjA0NTkgMjguMDAyNEwxMy41Nzc5IDI5LjQyMjRDMTQuODc2MiAzMC4xNTA1IDE1Ljc1MjYgMzAuNjQwMSAxNi40NzUgMzAuOTU4NUMxNy4xNjYgMzEuMjYzIDE3LjYwNCAzMS4zNTc5IDE3Ljk5OTkgMzEuMzU3OUMxOC4zOTU3IDMxLjM1NzkgMTguODMzNyAzMS4yNjMgMTkuNTI0NyAzMC45NTg1QzIwLjI0NzIgMzAuNjQwMSAyMS4xMjM1IDMwLjE1MDUgMjIuNDIxOSAyOS40MjI0TDI0Ljk1MzkgMjguMDAyNEwyNi40NTgxIDI2LjkzODdDMjcuNjEzNSAyNi4xMjE3IDI4LjM5MDkgMjUuNTcgMjguOTYzNyAyNS4wODA0QzI5LjUxMTQgMjQuNjEyMyAyOS43ODEgMjQuMjc0OSAyOS45NTU5IDIzLjkzNDVDMzAuMTMwOSAyMy41OTQyIDMwLjI0OTQgMjMuMTc2NSAzMC4zMTQ3IDIyLjQ1MjJDMzAuMzgyOSAyMS42OTQ2IDMwLjM4NDUgMjAuNzMwOSAzMC4zODQ1IDE5LjMwMDJWMTcuMDU2NEMzMC4zODQ1IDE1LjYyNTcgMzAuMzgyOSAxNC42NjE5IDMwLjMxNDcgMTMuOTA0NEMzMC4yNDk0IDEzLjE4IDMwLjEzMDkgMTIuNzYyMyAyOS45NTU5IDEyLjQyMkMyOS43ODEgMTIuMDgxNiAyOS41MTE0IDExLjc0NDIgMjguOTYzNyAxMS4yNzYxQzI4LjM5MDkgMTAuNzg2NSAyNy42MTM1IDEwLjIzNDkgMjYuNDU4MSA5LjQxNzgzTDI0LjkxOTggOC4zMzAwNEwyMi44MjczIDcuMDA5OEMyMS40MTc3IDYuMTIwNDIgMjAuNDYzNCA1LjUyMDc0IDE5LjY3MzQgNS4xMzA3QzE4LjkxNzMgNC43NTczMyAxOC40MzY4IDQuNjQyMDYgMTcuOTk5OSA0LjY0MjA2QzE3LjU2MyA0LjY0MjA2IDE3LjA4MjUgNC43NTczMyAxNi4zMjYzIDUuMTMwN0MxNS41MzYzIDUuNTIwNzQgMTQuNTgyMSA2LjEyMDQyIDEzLjE3MjUgNy4wMDk4TDExLjA4IDguMzMwMDNMOS41NDE2NyA5LjQxNzgzQzguMzg2MjkgMTAuMjM0OSA3LjYwODg2IDEwLjc4NjUgNy4wMzYwNyAxMS4yNzYxQzYuNDg4MzggMTEuNzQ0MiA2LjIxODc3IDEyLjA4MTYgNi4wNDM4MSAxMi40MjJDNS44Njg4NiAxMi43NjIzIDUuNzUwMzEgMTMuMTggNS42ODUwNiAxMy45MDQ0QzUuNjE2ODIgMTQuNjYxOSA1LjYxNTI2IDE1LjYyNTcgNS42MTUyNiAxNy4wNTY0Wk0xMC4xOTIyIDYuOTU3NTNMMTIuMzIwNiA1LjYxNDY0QzE1LjA4MzMgMy44NzE1NSAxNi40NjQ2IDMgMTcuOTk5OSAzQzE5LjUzNTEgMyAyMC45MTY1IDMuODcxNTUgMjMuNjc5MiA1LjYxNDY0TDI1LjgwNzYgNi45NTc1M0wyNy4zODA2IDguMDY5ODZDMjkuNjQzOCA5LjY3MDI5IDMwLjc3NTQgMTAuNDcwNSAzMS4zODc3IDExLjY2MTVDMzEuOTk5OSAxMi44NTI1IDMxLjk5OTkgMTQuMjUzOCAzMS45OTk5IDE3LjA1NjRWMTkuMzAwMkMzMS45OTk5IDIyLjEwMjcgMzEuOTk5OSAyMy41MDQgMzEuMzg3NyAyNC42OTVDMzAuNzc1NCAyNS44ODYgMjkuNjQzOCAyNi42ODYyIDI3LjM4MDYgMjguMjg2N0wyNS44MDc2IDI5LjM5OUwyMy4yMDIzIDMwLjg2MDFDMjAuNjU4NiAzMi4yODY3IDE5LjM4NjggMzMgMTcuOTk5OSAzM0MxNi42MTMgMzMgMTUuMzQxMiAzMi4yODY3IDEyLjc5NzUgMzAuODYwMUwxMC4xOTIyIDI5LjM5OUw4LjYxOTE3IDI4LjI4NjZDNi4zNTU5MyAyNi42ODYyIDUuMjI0MzEgMjUuODg2IDQuNjEyMDkgMjQuNjk1QzMuOTk5ODggMjMuNTA0IDMuOTk5ODggMjIuMTAyNyAzLjk5OTg4IDE5LjMwMDJWMTcuMDU2NEMzLjk5OTg4IDE0LjI1MzggMy45OTk4OCAxMi44NTI1IDQuNjEyMDkgMTEuNjYxNUM1LjIyNDMxIDEwLjQ3MDUgNi4zNTU5NCA5LjY3MDI5IDguNjE5MTggOC4wNjk4NkwxMC4xOTIyIDYuOTU3NTNaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K'
				: 'data:image/png;base64,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'
			;
		}

		if (
			provider.code === ProviderCode.external
			&& Type.isStringFilled(provider.iconUrl)
		)
		{
			return provider.iconUrl;
		}

		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkCAYAAADhAJiYAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAANQSURBVHgBxZi/b9NAFMffnZOaFgopEkggEK5AohKgJKgLIETzD6B2YmAo3boBAyv9sXaAbh2QCgMzVQbWpELQBYErFpCQcAUqPyogoqipm9jHe5fYikMs2/n5lZxcchffJ+/dvXfPDCJqZK2o9VnWOFOUJAgxhlcCGEvITiEK2DaAgyHK9iovlVb0zJAR4fbAwg688PLvLc6VSQkRTbot7MV3Vw48DjM4EOj8i+0xRVGWsalBazIQbC4IzBcolfudsFV1BgfcgTZKADzkpjmHrixAWCCE0YSqPqMmdEYGM81Mo/XFfGBy0LqLmoJidTAJhHnbBRhHehXKdR+v7aU100UYUqo6pyvXQnJbM74MPRCzrIx+dTBPbddCCDMDPZKohJUKB72QdaANrpo+GYfnF/fBcTV0vHWkVRkqQJyx29CiCGb6REzCPDqnRoaSWYDeaZujF1uKNw6MI4IZPcij3QRTUvqNOMXteHwcWlA9DGnpcwmyWxZElbVbnOAsxq9Bk/KDWfpShmbEGEtysIUGTajdMFLoNo7ZToOI6ghMRQnuHq56D1MFiqCbx2KdhJHi8tgZQrSVc78s+LAjOgaDKsTQZQQU6DayzughBe6+N+HBiAq5n+V2w5AMLkCshhlJMGcHGMyfjsON9V14/ceG+TN9nohMwdCJ1o6uH1HkFUbIshHDba9j7pgMGpz9XpLuIhCa0IHJDHFY2CjDIM55T4tXII7GYMEoyc8OXHarCMFieZbCskYI8QlCiqzgwNCayhz2/vvtsoDBGPN8Jtc+/RrsXgyMw/KXybViLkx5Q+6if79pCrj/cU9ai1xEoYAmJqvUujL7A0G+WbIvhPT1ywNpCZTC1C9CHs5oHW3ugWcSmnzbgrATNxQT9pSOJZJr2+SrHXKbBr2RgdYZpoYbGPEYOQU9Elpnzmm7QHSmFVjyQpeFMIt6TTXrSR18rzSL0UCH7smAgf2ztV94gKg+YoxPyIFdgMFtntHTrOALJKEu9cuBHYbSJQzOVd/RMNtLKNNMd2JN0Zph/f0NYWR/0A2qMaodFa1BO9kpCP0UulapgFG5FLlCyaNVnujtemD1HxjmPsBHeoJTcYDncToCex/p0ZVHa6yDoqz4ucZP/wB0m3kbYruWeAAAAABJRU5ErkJggg==';
	}

	#getEntityBadges(provider: Provider): Array<Object>
	{
		if (provider.code === ProviderCode.goskey && this.#isProviderExpired(provider))
		{
			return [
				{
					title: Loc.getMessage('SIGN_B2E_GOSKEY_APIKEY_EXPIRED'),
					textColor: 'var(--ui-color-palette-white-base)',
					bgColor: 'var(--ui-color-palette-orange-60)',
				},
			];
		}

		return [];
	}

	#getProviderAlert(provider: Provider): Alert
	{
		return new Alert({
			text: this.#getProviderAlertMessage(provider),
			customClass: 'sign-document-b2e-company__provider_alert',
		});
	}

	#getProviderAlertMessage(provider: Provider): string
	{
		if (this.#isProviderExpired(provider))
		{
			return Helpdesk.replaceLink(
				Loc.getMessage('SIGN_B2E_GOSKEY_APIKEY_EXPIRED_MORE_MSGVER_1'),
				HelpdeskCodes.GoskeyApiKey,
			);
		}

		const daysLeft = this.#getProviderDaysLeft(provider.expires);
		const alertText = Loc.getMessagePlural('SIGN_B2E_GOSKEY_APIKEY_EXPIRES_MSGVER_1', daysLeft, {
			'#DAYS#': daysLeft,
		});

		return Helpdesk.replaceLink(alertText, HelpdeskCodes.GoskeyApiKey);
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
			: SchemeType.Default
		;
	}

	setInitiatedByType(initiatedByType: DocumentInitiatedType): void
	{
		this.setOptions({ documentInitiatedType: initiatedByType });
	}

	#setProviderImage(provider: Provider): void
	{
		if (!Type.isStringFilled(provider.iconUrl))
		{
			return;
		}

		const imgClassName = 'sign-document-b2e-company__provider_selected__external-img';
		const img = this.#ui.provider.container?.getElementsByClassName(imgClassName)[0] ?? null;
		if (!img)
		{
			return;
		}

		img.src = provider.iconUrl;
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

	async reloadCompanyProviders(): Promise<void>
	{
		await this.#load();
	}
}
