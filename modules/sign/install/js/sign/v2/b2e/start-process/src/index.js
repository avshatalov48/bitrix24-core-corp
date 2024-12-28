import { Loc, Tag, Type } from 'main.core';
import { MemoryCache } from 'main.core.cache';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Loader } from 'main.loader';
import type { B2eCompanyList, Template, TemplateField } from 'sign.v2.api';
import { Api } from 'sign.v2.api';
import { CompanySelector } from 'sign.v2.b2e.company-selector';
import { type ItemOptions, SignDropdown } from 'sign.v2.b2e.sign-dropdown';

type TemplateCompany = Template['company'];
type LoadCompanyPromise = Promise<B2eCompanyList>;

const dropdownTemplateEntityId = 'sign-b2e-start-process-type';
const dropdownProcessTabId = 'sign-b2e-start-process-types';

export class StartProcess extends EventEmitter
{
	events = {
		onProcessTypeSelect: 'onProcessTypeSelect',
	};

	#resettableCache: MemoryCache<any> = new MemoryCache();
	#api: Api = new Api();

	#templatesList: Promise<Template[]> = this.#api.template.getList();

	constructor()
	{
		super();
		this.setEventNamespace('BX.V2.B2e.StartProcess');

		void this.#getProcessTypeLayoutLoader().show();
	}

	getLayout(): HTMLElement
	{
		return this.#resettableCache.remember('layout', () => {
			return Tag.render`
				<div>
					<h1 class="sign-b2e-settings__header">${Loc.getMessage('SIGN_START_PROCESS_HEAD')}</h1>
					<div class="sign-b2e-settings__item">
						<p class="sign-b2e-settings__item_title">
							${Loc.getMessage('SIGN_START_PROCESS_COMPANY')}
						</p>
						${this.#getCompanySelector().getLayout()}
					</div>
					<div class="sign-b2e-settings__item">
						<p class="sign-b2e-settings__item_title">
							${Loc.getMessage('SIGN_START_PROCESS_TYPE')}
						</p>
						${this.#getProcessTypeDropdown().getLayout()}
					</div>
				</div>
			`;
		});
	}

	getSelectedTemplateUid(): string
	{
		return this.#getProcessTypeDropdown().getSelectedId();
	}

	getTemplates(): Promise<Template[]>
	{
		return this.#templatesList;
	}

	getFields(templateUid: string): Promise<{ fields: TemplateField[] }>
	{
		return this.#api.template.getFields(templateUid);
	}

	#getProcessTypeLayoutLoader(): Loader
	{
		return this.#resettableCache.remember(
			'processTypeLayoutLoader',
			() => new Loader({ target: this.#getProcessTypeDropdown().getLayout() }),
		);
	}

	#getProcessTypeDropdown(): SignDropdown
	{
		return this.#resettableCache.remember(
			'processTypeDropdown',
			() => {
				const signDropdown = new SignDropdown({
					tabs: [{ id: dropdownProcessTabId, title: ' ' }],
					entities: [
						{
							id: dropdownTemplateEntityId,
						},
					],
					items: [],
					isEnableSearch: true,
				});
				signDropdown.subscribe(
					signDropdown.events.onSelect,
					(event) => this.emit(this.events.onProcessTypeSelect, event),
				);

				return signDropdown;
			},
		);
	}

	#getCompanySelector(): CompanySelector
	{
		return this.#resettableCache.remember(
			'companySelector',
			() => {
				const companySelector = new CompanySelector({
					loadCompanyPromise: this.#getCompanySelectorLoadCompanyPromise(),
					canCreateCompany: false,
					canEditCompany: false,
					isCompaniesDeselectable: false,
				});
				companySelector.subscribe(
					companySelector.events.onCompaniesLoad,
					() => this.#onCompaniesSelectorCompaniesLoad(),
				);
				companySelector.subscribe(
					companySelector.events.onSelect,
					(event) => this.#onCompanySelectorSelect(event),
				);

				return companySelector;
			},
		);
	}

	#createProcessTypeDropdownItemByTemplate(template: Template): ItemOptions
	{
		return {
			id: template.uid,
			title: template.title,
			entityId: dropdownTemplateEntityId,
			tabs: dropdownProcessTabId,
			deselectable: false,
		};
	}

	async #getCompanySelectorLoadCompanyPromise(): LoadCompanyPromise
	{
		const uniqueCompanies = await this.#getUniqueCompanies();

		const companySelectorCompanies = uniqueCompanies.map(({ id, name, taxId }) => ({
			id,
			title: name,
			rqInn: taxId,
		}));

		// todo: get actual showTaxId
		return { companies: companySelectorCompanies, showTaxId: true };
	}

	async #getUniqueCompanies(): Promise<Array<TemplateCompany>>
	{
		const templates = await this.#templatesList;
		const companies = templates.map((template) => template.company);
		const uniqCompanyIds: Set<number> = new Set(companies.map(({ id }) => id));

		return [...uniqCompanyIds].map(
			(id) => companies.find((company) => company.id === id),
		);
	}

	async #onCompaniesSelectorCompaniesLoad(): Promise<void>
	{
		const companySelector = this.#getCompanySelector();
		const templates = await this.#templatesList;
		const lastUsedTemplate = templates.find(({ isLastUsed }) => isLastUsed);

		let selectedCompanyId = lastUsedTemplate?.company?.id;
		if (Type.isUndefined(selectedCompanyId))
		{
			const companies = await this.#getUniqueCompanies();
			selectedCompanyId = companies.at(0)?.id;
		}

		if (Type.isUndefined(selectedCompanyId))
		{
			return;
		}

		companySelector.selectCompany(selectedCompanyId);
	}

	async #onCompanySelectorSelect(event: BaseEvent<{ companyId: number }>): void
	{
		void this.#getProcessTypeLayoutLoader().show();

		const companyId = event.getData().companyId;
		const templates = await this.#templatesList;

		const processTypeItems = templates
			.filter(({ company }: Template) => company.id === companyId)
			.map((template) => this.#createProcessTypeDropdownItemByTemplate(template))
		;
		const signDropdown = this.#getProcessTypeDropdown();
		signDropdown.removeItems();
		signDropdown.addItems(processTypeItems);
		signDropdown.selectFirstItem();

		void this.#getProcessTypeLayoutLoader().hide();
	}

	resetCache(): void
	{
		this.#resettableCache = new MemoryCache();
	}
}
