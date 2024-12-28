import { Tag, Loc, Dom, Cache, Event, Text } from 'main.core';
import { Layout } from 'ui.sidepanel.layout';

import { CompanyConnectPage } from 'humanresources.hcmlink.company-connect-page';
import type { CompanyData, CompanyManagerOptions } from './types';

import "./style.css";

const maxCounterValue = 99;

export class CompaniesManager {
	#companies: Array<CompanyData>;

	#root: HTMLElement | null;
	#container: HTMLElement | null;

	#cache = new Cache.MemoryCache();

	constructor(options: CompanyManagerOptions)
	{
		this.#companies = options.companies ?? [];
	}

	renderTo(root: HTMLElement): void
	{
		this.#root = root;

		this.#container = Tag.render`
			<div class="hr-hcmlink-company-manager-container">
				<div class="hr-hcmlink-company-manager-header">
					<div class="hr-hcmlink-company-manager-header__title">
						${Loc.getMessage('HUMANRESOURCES_HCMLINK_COMPANY_LIST_TITLE')}
					</div>
					<div class="hr-hcmlink-company-manager-header__hint">
						${Loc.getMessage('HUMANRESOURCES_HCMLINK_COMPANY_LIST_HINT')}
					</div>
				</div>
				${this.#getConnectCompanyList()}
				${this.#companies.length ? this.#getConnectedCompanyList() : ''}
			</div>
		`;

		if (BX.UI.Hint)
		{
			BX.UI.Hint.init(this.#container);
		}

		Dom.append(this.#container, this.#root);
	}

	#getConnectCompanyList(): HTMLElement
	{
		return this.#cache.remember('connect-company-list', () =>
			Tag.render`
				<div class="hr-hcmlink-company-manager-list-container --connect">
					<div class="hr-hcmlink-company-manager-list__title">
						${Loc.getMessage('HUMANRESOURCES_HCMLINK_COMPANY_LIST_INTEGRATION_TITLE')}
					</div>
					<div class="hr-hcmlink-company-manager-list">
						${this.#getConnectCompanyItem()}
					</div>
				</div>
			`
		);
	}

	#getConnectCompanyItem(): HTMLElement
	{
		return this.#cache.remember('connect-company-item', () => {
			const element = Tag.render`
				<div class="hr-hcmlink-company-manager-list__item --connect">
					<div class="hr-hcmlink-company-manager-list__item-content">
						<div class="hr-hcmlink-company-manager-list__item-title">
							${Loc.getMessage('HUMANRESOURCES_HCMLINK_COMPANY_LIST_CONNECT')}
						</div>
					</div>
				</div>
			`;

			Event.bind(element, 'click', () => this.#connectCompany());

			return element;
		});
	}

	#getConnectedCompanyList(): HTMLElement
	{
		const elements = this.#companies.map(
			(item: CompanyData) => this.#makeConnectedCompanyItem(item)
		);

		return Tag.render`
			<div class="hr-hcmlink-company-manager-list-container">
				<div class="hr-hcmlink-company-manager-list__title">
					${Loc.getMessage('HUMANRESOURCES_HCMLINK_COMPANY_LIST_MY_COMPANIES')}
				</div>
				<div class="hr-hcmlink-company-manager-list">
					${elements}
				</div>
			</div>
		`;
	}

	#makeConnectedCompanyItem(company: CompanyData): HTMLElement
	{
		const item = Tag.render`
			<div class="hr-hcmlink-company-manager-list__item">
				<div class="hr-hcmlink-company-manager-list__item-content">
					<div class="hr-hcmlink-company-manager-list__item-title">
						<div class="hr-hcmlink-company-manager-list__item-title-text"
							title="${Text.encode(company.title)}"
						>
							${Text.encode(company.title)}
						</div>
					</div>
				</div>
			</div>
		`;

		if (company.notMappedCount > 0)
		{
			let value = company.notMappedCount;
			if (company.notMappedCount > maxCounterValue)
			{
				value = maxCounterValue + '+';
			}

			Dom.prepend(this.#makeCounter(value), item);
		}

		Event.bind(item, 'click', () => this.#openCompany(company.id));

		return item;
	}

	#makeCounter(value: string): HTMLElement
	{
		return Tag.render`
			<div class="hr-hcmlink-company-manager-list__item-counter ui-counter ui-counter-danger"
				data-hint="${Loc.getMessage('HUMANRESOURCES_HCMLINK_COMPANY_LIST_NOT_SYNCED_HINT')}"
				data-hint-no-icon
			>
    			<div class="ui-counter-inner">${value}</div>
			</div>
		`;
	}

	#reload(): void
	{
		top.BX.SidePanel.Instance.getSliderByWindow(window)?.reload();
	}

	#connectCompany(): void
	{
		BX.SidePanel.Instance.open('humanresources:hcmlink-connect-slider', {
			contentCallback: () => {
				return Layout.createContent({
					extensions: ['humanresources.hcmlink.company-connect-page'],
					design: {
						section: false,
						margin: true,
					},
					content() {
						return new CompanyConnectPage().getLayout();
					},
					buttons() {
						return [];
					},
				});
			},
			animationDuration: 200,
			width: 920,
			cacheable: false,
			events: {
				onClose: (): void => this.#reload(),
			},
		});
	}

	#openCompany(companyId: number): void
	{
		BX.SidePanel.Instance.open(`/bitrix/components/bitrix/humanresources.hcmlink.mapped.users/slider.php?entity_id=${companyId}`, {
			cacheable: false,
			width: 900,
			animationDuration: 200,
			events: {
				onClose: (): void => this.#reload(),
			},
		});
	}
}