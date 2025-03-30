import { Loc, Tag, Type, Dom } from 'main.core';
import { Dialog } from 'ui.entity-selector';
import { Api } from 'sign.v2.api';
import { Loader } from 'main.loader';
import { CompanyConnectPage } from 'humanresources.hcmlink.company-connect-page';

import './style.css';

export class HcmLinkCompanySelector
{
	#isAvailable: boolean = false;
	#companyId: number | null = null;
	#selectedId: number | null = null;
	#lastSavedId: ?number = undefined;

	#dialog: Dialog | null = null;
	#loader: Loader | null = null;
	isLayoutExisted: boolean = false;

	#api: Api;

	#ui = {
		container: HTMLDivElement = null,
		active: HTMLButtonElement = null,
		inactive: HTMLButtonElement = null,
		unselect: HTMLButtonElement = null,
		dropdownButton: HTMLSpanElement = null,
		loaderContainer: HTMLElement = null,
		info: {
			title: HTMLSpanElement = null,
			subtitle: HTMLDivElement = null,
		},
	};

	#integrationList: Array<{ id: number, title: string, subtitle: string }> = [];

	constructor()
	{
		this.#api = new Api();
	}

	setAvailability(value: boolean): void
	{
		this.#isAvailable = value;
	}

	setCompanyId(id: number | null): void
	{
		if (!this.#isAvailable)
		{
			return;
		}

		if (this.#companyId === id && this.isLayoutExisted)
		{
			return;
		}

		this.#companyId = id;
		this.#selectedId = null;

		this.#dialog = null;

		if (!this.#companyId)
		{
			this.hide();

			return;
		}

		this.#showLoader();
		this.#api.checkCompanyHrIntegration(this.#companyId)
			.then((data) => {
				this.#getLoader().destroy();

				if (data.length <= 0)
				{
					this.#ui.inactive.style.display = 'flex';
					return;
				}

				this.#integrationList = data;

				if (this.#lastSavedId === null)
				{
					this.#lastSavedId = undefined;
					this.#ui.unselect.style.display = 'flex';

					return;
				}

				let itemToSelect = data[0];
				if (this.#lastSavedId)
				{
					itemToSelect = this.#integrationList.find(
						(item) => item.id === this.#lastSavedId,
					) ?? data[0];
				}

				this.#select(itemToSelect);
				this.#ui.active.style.display = 'flex';
			})
		;
	}

	setLastSavedId(integrationId: number | null): void
	{
		this.#lastSavedId = integrationId;
	}

	getSelectedId(): number | null
	{
		if (!this.#selectedId)
		{
			return null;
		}

		return this.#selectedId;
	}

	hide(): void
	{
		if (!this.#isAvailable && !this.isLayoutExisted)
		{
			return;
		}

		BX.hide(this.#ui.container);
		this.#dialog?.hide();
	}

	show(): void
	{
		if (!this.#isAvailable && !this.isLayoutExisted)
		{
			return;
		}

		Dom.style(this.#ui.container, { display: 'flex' });
	}

	render(): HTMLElement
	{
		if (this.#ui.container)
		{
			return this.#ui.container;
		}

		this.#ui.info.title = Tag.render`
			<span class="sign-document-b2e-company__hcmlink-select-text"></span>
		`;

		this.#ui.info.subtitle = Tag.render`
			<div class="sign-document-b2e-company__hcmlink-select-subtitle"></div>
		`;

		this.#ui.active = Tag.render`
			<div class="sign-document-b2e-company__hcmlink-select --active" data-companies="[]">
				<div class="sign-document-b2e-company__hcmlink-name-container">
					<div class="sign-document-b2e-company__hcmlink-select-header">
						${this.#ui.info.title}
						<span class="sign-document-b2e-company-info-dropdown-btn"
							onclick="${() => {
			this.#showDialog();
		}}"></span>
						${this.#ui.dropdownButton}
					</div>
					${this.#ui.info.subtitle}	
				</div>		
			</div>
		`;

		this.#ui.unselect = Tag.render`
			<div class="sign-document-b2e-company__hcmlink-select --inactive">
				<div class="sign-document-b2e-company__hcmlink-name-container">
					<span class="sign-document-b2e-company__hcmlink-select-text">
						${Loc.getMessage('SIGN_B2E_INTEGRATION_INTEGRATION_UNSELECTED')}
					</span>
				</div>
				<button class="ui-btn ui-btn-xs ui-btn-round ui-btn-light-border"
					onclick="${() => this.#showDialog()}">
					${Loc.getMessage('SIGN_B2E_COMPANIES_SELECT_BUTTON')}
				</button>
			</div>
		`;

		this.#ui.inactive = Tag.render`
			<div class="sign-document-b2e-company__hcmlink-select --inactive">
				<div class="sign-document-b2e-company__hcmlink-name-container">
					<span class="sign-document-b2e-company__hcmlink-select-text">
						${Loc.getMessage('SIGN_B2E_COMPANY_INTEGRATION_TITLE')}
					</span>
				</div>
				<button class="ui-btn ui-btn-xs ui-btn-round ui-btn-light-border"
					onclick="${() => this.#showIntegrationMarketSlider()}">
					${Loc.getMessage('SIGN_B2E_COMPANY_INTEGRATION_BUTTON_CONNECT')}
				</button>
			</div>
		`;
		this.#ui.loaderContainer = Tag.render`
			<div class="sign-document-b2e-company__hcmlink-loader"><div>
		`;

		this.#ui.icon = Tag.render`<div class="sign-document-b2e-company__hcmlink-info-img"></div>`;
		this.#ui.container = Tag.render`
			<div class="sign-document-b2e-company__hcmlink">
				${this.#ui.icon}
				${this.#ui.loaderContainer}
				${this.#ui.active}
				${this.#ui.inactive}
				${this.#ui.unselect}
			</div>
		`;

		this.hide();

		return this.#ui.container;
	}

	#select(item: { id: number } | null): void
	{
		this.#selectedId = item?.id ?? null;
		BX.hide(this.#ui.unselect);
		BX.hide(this.#ui.inactive);
		this.#ui.active.style.display = 'flex';
		this.#setIntegrationTitle(this.#selectedId);
		Dom.addClass(this.#ui.icon, '--active');
	}

	#deselect(item: { id: number } | null): void
	{
		Dom.removeClass(this.#ui.icon, '--active');
		this.#selectedId = null;
		BX.hide(this.#ui.active);
		BX.hide(this.#ui.inactive);
		this.#ui.unselect.style.display = 'flex';
	}

	#showDialog(): void
	{
		this.#getDialog()?.show();
	}

	#getDialog(): Dialog
	{
		if (this.#dialog)
		{
			return this.#dialog;
		}

		const items = this.#integrationList.map((integration) => {
			return {
				id: integration.id,
				entityId: 'hrm-integration',
				title: integration.title,
				subtitle: integration.subtitle,
				tabs: 'hrm-integrations',
			};
		});

		this.#dialog = new Dialog({
			targetNode: this.#ui.container,
			width: 425,
			height: 363,
			items,
			tabs: [
				{ id: 'hrm-integrations', title: Loc.getMessage('SIGN_B2E_INTEGRATION_TAB') },
			],
			showAvatars: false,
			dropdownMode: true,
			multiple: false,
			enableSearch: true,
			events: {
				'Item:OnSelect': (event) => {
					this.#select(event.data.item);
				},
				'Item:OnDeselect': (event) => {
					this.#deselect(event.data.item);
				},
			},
			hideOnSelect: true,
			hideOnDeselect: true,
		});

		if (this.#selectedId)
		{
			const item = this.#dialog.getItems().find((item) => item.id === this.#selectedId);
			item?.select();
		}

		return this.#dialog;
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

	#showLoader(): void
	{
		BX.hide(this.#ui.active);
		BX.hide(this.#ui.inactive);
		BX.hide(this.#ui.unselect);
		this.#getLoader().show(this.#ui.loaderContainer);
		this.#ui.container.style.display = 'flex';
		Dom.removeClass(this.#ui.icon, '--active');
	}

	// refactor later
	#showIntegrationMarketSlider(): void
	{
		CompanyConnectPage.openSlider({}, {
			onCloseHandler: () => this.setCompanyId(this.#companyId),
		});
	}

	#setIntegrationTitle(itemId: number | null): void
	{
		if (Type.isNumber(itemId))
		{
			const item = this.#integrationList.find((integration) => integration.id === itemId);
			if (
				item
				&& Type.isDomNode(this.#ui.info.title)
				&& Type.isDomNode(this.#ui.info.subtitle)
			)
			{
				this.#ui.info.title.innerHTML = item?.title ?? '';
				this.#ui.info.subtitle.innerHTML = item?.subtitle?.toUpperCase() ?? '';
			}
		}
	}
}
