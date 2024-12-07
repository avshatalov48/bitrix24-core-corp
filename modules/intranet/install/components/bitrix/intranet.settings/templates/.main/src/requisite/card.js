import { Dom, Loc, Tag, Type } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { Button } from 'ui.buttons';
import { ButtonBar } from './button-bar';
import { LandingButtonFactory } from './landing-button-factory';

type CardOptions = {
	company: Object,
	fields: Array,
	phone: Array,
	email: Array,
	site: Array,
	landing: Array,
	landingData: Array,
};

export class Card
{
	#options: CardOptions;
	#cardElement: ?HTMLElement;
	#requisiteFieldsElement: ?HTMLElement;
	#buttonBar: ?ButtonBar;

	constructor(options: CardOptions)
	{
		this.#options = options;
	}

	render(): HTMLElement
	{
		if (this.#cardElement)
		{
			return this.#cardElement;
		}

		this.#cardElement = Tag.render`
		<div class="intranet-settings__req_background">
			<div class="intranet-settings__req-card_wrapper">
				<div class="intranet-settings__header">
					<div class="intranet-settings__title"> <span class="ui-section__title-icon ui-icon-set --city"></span> <span>${this.#options.company.TITLE}</span></div>
					<div class="intranet-settings__contact_bar"> 
						<span class="intranet-settings__contact_bar_item">
							${
			this.#buildCompanyField(
				Type.isStringFilled(this.#options.phone)
					? this.#options.phone
					: Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_EMPTY_FIELD_STUB_PHONE')
			)
		}
						</span> 
						<span class="intranet-settings__contact_bar_item">
							${
			this.#buildCompanyField(
				Type.isStringFilled(this.#options.email)
					? this.#options.email
					: Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_EMPTY_FIELD_STUB_EMAIL')
			)
		}
						</span> 
						<span class="intranet-settings__contact_bar_item">
							${
			this.#buildCompanyField(
				Type.isStringFilled(this.#options.site)
					? this.#options.site
					: Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_EMPTY_FIELD_STUB_SITE')
			)
		}
						</span> 
					</div>
				</div>
				${this.requisiteFieldsRender()}
				${this.getButtonsBar().render()}
			</div>
		</div>
		`;

		return this.#cardElement;
	}

	#buildCompanyField(label: string): HTMLElement
	{
		return Dom.create('a', {
			text: label,
			attrs: {
				href: this.getCompanyUrl(),
			}
		});
	}

	#buildField(label: string): HTMLElement
	{
		return Dom.create('a', {
			text: label,
			attrs: {
				href: this.getRequisiteUrl(),
			}
		});
	}

	getRequisiteUrl(): string
	{
		const requisiteId = this.#options.landingData.requisite_id;

		if (requisiteId)
		{
			return '/crm/company/requisite/' + requisiteId + '/';
		}
		else
		{
			return '/crm/company/requisite/0/?itemId=' + this.#options.company.ID;
		}
	}

	getCompanyUrl(): string
	{
		if (this.#options.company.ID === 0)
		{
			return '/crm/company/details/0/?mycompany=y&TITLE=' + this.#options.company.TITLE;
		}
		else
		{
			return '/crm/company/details/' + this.#options.company.ID + '/';
		}
	}

	requisiteFieldsRender(): HTMLElement
	{
		if (this.#requisiteFieldsElement)
		{
			return this.#requisiteFieldsElement;
		}
		const fields = this.#options.fields;
		this.#requisiteFieldsElement = Tag.render`<div class="intranet-settings__req-table_wrap"></div>`;
		for (let field of fields)
		{
			const renderField = Tag.render`
				<div class="intranet-settings__req-table_row">
					<div class="intranet-settings__table-cell">${field.TITLE}</div>
					<div class="intranet-settings__table-cell">
						${!this.#options.company.ID
				? this.#buildCompanyField(Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_EMPTY_FIELD_STUB'))
				: Type.isStringFilled(field.VALUE)
					? this.#buildField(
						field.VALUE
					)
					: this.#buildField(
						Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_EMPTY_FIELD_STUB')
					)
			}
					</div>
				</div>
			`;
			Dom.append(renderField, this.#requisiteFieldsElement);
		}

		return this.#requisiteFieldsElement;
	}

	getButtonsBar(): ButtonBar
	{
		if (this.#buttonBar)
		{
			return this.#buttonBar;
		}

		this.#buttonBar = new ButtonBar();

		return this.#buttonBar;
	}

	setButtonBar(buttonBar: ButtonBar)
	{
		this.#buttonBar = buttonBar;
	}
}