import { Tag, Type, Dom, Loc, Text as TextFormat } from 'main.core';
import { Loader } from 'main.loader';
import type { MemberRoleType } from 'sign.type';
import { Dialog } from 'ui.entity-selector';
import { Api } from 'sign.v2.api';

export type ItemData = {
	entityType: string,
	entityId: number,
	role?: MemberRoleType,
};

type ItemViewData = {
	header: string,
	footer: string,
	avatar: ?string,
};

export const EntityTypes = Object.freeze({
	User: 'user',
	Company: 'company'
});

export class Item
{
	#api: Api;
	#ui = {
		container: HTMLDivElement = null,
		avatar: HTMLDivElement = null,
		title: {
			container: HTMLDivElement = null,
			header: HTMLDivElement = null,
			footer: HTMLDivElement = null,
		}
	};

	#userDialog: ?Dialog = null;
	#loader: ?Loader = null;

	#data: ?ItemData = null;
	#viewData: ?ItemViewData = null;

	constructor(data: ItemData)
	{
		this.#api = new Api();
		this.#data = data;
		this.#ui.container = this.getLayout();
		if (Type.isStringFilled(data?.entityType) && Type.isInteger(data?.entityId))
		{
			this.#load();
		}
	}

	setItemData(data: ItemData): void
	{
		if (this.#data.entityId === data.entityId && this.#data.entityType === data.entityType)
		{
			return;
		}

		this.#data = data;
		if (Type.isStringFilled(data?.entityType) && Type.isInteger(data?.entityId))
		{
			this.#load();
		}
	}

	getLayout(): HTMLDivElement
	{
		if (this.#ui.container)
		{
			return this.#ui.container;
		}

		const modifier = this.#data.entityType === EntityTypes.User ? ' --user' : '';
		this.#ui.avatar = Tag.render`<div class="sign-b2e-send__party_item-info-avatar${modifier}"></div>`;
		this.#ui.title.header = Tag.render`<div class="sign-b2e-send__party_item-info-header"></div>`;
		this.#ui.title.footer = Tag.render`<div class="sign-b2e-send__party_item-info-footer"></div>`;
		this.#ui.title.container = Tag.render`
			<div class="sign-b2e-send__party_item-info-title">
				${this.#ui.title.header}
				${this.#ui.title.footer}
			</div>
		`;

		this.#ui.container = Tag.render`
			<div class="sign-b2e-send__party_item-info">
				${this.#ui.avatar}
				${this.#ui.title.container}
			</div>
		`;

		return this.#ui.container;
	}

	#loadCompany(): Promise
	{
		return this.#api.loadB2eCompanyList()
			.then(data => {
				if (Type.isObject(data.companies) && Type.isArray(data.companies))
				{
					const company = data.companies.filter(company => company.id === this.#data.entityId)[0] ?? null;
					if (company === null)
					{
						return;
					}

					const footer = Type.isBoolean(data?.showTaxId) && data?.showTaxId && company?.rqInn
						? Loc.getMessage('SIGN_DOCUMENT_SUMMARY_COMPANY_INN',
							{ '%innValue%' : TextFormat.encode(company?.rqInn) }
						)
						: null
					;

					this.#viewData = {
						header: company?.title,
						footer: footer,
						avatar: null
					};

					this.#refreshView();
				}

				this.#hideLoader();
			}).catch((response) => {
				console.log(response);
			})
		;
	}

	#loadUser(): Promise
	{
		 return new Promise((resolve) => {
			this.#userDialog = new Dialog({
				entities: [
					{ id: EntityTypes.User }
				],
				events: {
					'onLoad': (event) => {
						const user = this.#userDialog.getSelectedItems()[0] ?? null;
						if (Type.isObject(user))
						{
							const lastName = user?.customData?.get('lastName') ?? '';
							this.#viewData = {
								header: user?.customData?.get('name') + ' ' + lastName,
								footer: user?.customData?.get('position') ?? '',
								avatar: user?.avatar ?? null,
							};
							this.#refreshView();
						}

						this.#hideLoader();
						resolve();
					},
				},
				preselectedItems: [[EntityTypes.User, this.#data.entityId]]
			});

			this.#userDialog.load();
		});
	}

	async #load(): Promise<void>
	{
		this.#showLoader();
		if (this.#data.entityType === EntityTypes.Company)
		{
			await this.#loadCompany();
		}
		else if (this.#data.entityType === EntityTypes.User)
		{
			await this.#loadUser();
		}
		this.#refreshView();
		this.#hideLoader();
	}

	#refreshView(): void
	{
		if (this.#viewData?.avatar !== null)
		{
			this.#ui.avatar.style.backgroundImage = `url("${this.#viewData.avatar}")`;
		}

		this.#ui.title.header.innerText = this.#viewData.header;
		this.#ui.title.header.title = this.#viewData.header;
		this.#ui.title.footer.innerText = this.#viewData.footer;
		this.#ui.title.footer.title = this.#viewData.footer;
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
			size: 40
		});

		return this.#loader;
	}

	#hideLoader(): void
	{
		this.#ui.title.container.style.display = 'flex';
		this.#ui.avatar.style.display = 'block';
		this.#getLoader().hide();
	}

	#showLoader(): void
	{
		this.#ui.avatar.style.display = 'none';
		this.#ui.title.container.style.display = 'none';
		this.#getLoader().show(this.#ui.container);
	}
}
