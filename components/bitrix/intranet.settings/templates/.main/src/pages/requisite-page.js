import { AnalyticSettingsEvent } from '../analytic';
import { ButtonBar } from '../requisite/button-bar';
import { Card } from '../requisite/card';
import { LandingButtonFactory } from '../requisite/landing-button-factory';
import { LandingCard } from '../requisite/landing-card';
import { Event, Dom, Loc, Tag, Type } from 'main.core';
import { Section, Row } from 'ui.section';
import {BaseSettingsPage, SettingsRow, SettingsSection} from 'ui.form-elements.field';
import 'ui.icon-set.crm';

export class RequisitePage extends BaseSettingsPage
{
	constructor()
	{
		super();
		this.titlePage = Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_REQUISITE');
		this.descriptionPage = Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_REQUISITE');

		top.BX.addCustomEvent('onLocalStorageSet', (params) => {
			let eventName = params?.key ?? null;
			if (eventName === 'onCrmEntityUpdate' || eventName === 'onCrmEntityCreate' || eventName === 'BX.Crm.RequisiteSliderDetails:onSave')
			{
				this.reload();
			}
		});
	}

	getType(): string
	{
		return 'requisite';
	}

	appendSections(contentNode: HTMLElement): void
	{
		if (!this.hasValue('sectionRequisite'))
		{
			return;
		}

		let reqSection = new Section(this.getValue('sectionRequisite'));
		const sectionField = new SettingsSection({
			parent: this,
			section: reqSection,
		});

		const description = new BX.UI.Alert({
			text: `
				${Loc.getMessage('INTRANET_SETTINGS_SECTION_REQUISITE_DESCRIPTION')}
				<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=18213326')">
					${Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
				</a>
			`,
			inline: true,
			size: BX.UI.Alert.Size.SMALL,
			color: BX.UI.Alert.Color.PRIMARY,
			animated: true,
		});

		const descriptionRow = new Row({
			content: description.getContainer(),
		});

		reqSection.append(descriptionRow.render());

		if (this.hasValue('COMPANY'))
		{
			let companies = this.getValue('COMPANY');
			const requisites = this.getValue('REQUISITES');
			const phones = this.getValue('PHONES');
			const sites = this.getValue('SITES');
			const emails = this.getValue('EMAILS');
			const landings = this.getValue('LANDINGS');
			const landingsData = this.getValue('LANDINGS_DATA');

			if (!Type.isArray(companies) || companies.length <= 0)
			{
				const defaultCompanyRow = new Row({
					content: this.cardRender({
						company: { ID: 0, TITLE: this.getValue('BITRIX_TITLE') },
						fields: this.getValue('EMPTY_REQUISITE'),
						phone: [],
						email: [],
						site: [],
					}),
				});

				reqSection.append(defaultCompanyRow.render());
			}

			for (let company of companies)
			{
				const fields = !Type.isNil(requisites[company.ID])
					? requisites[company.ID]
					: this.getValue('EMPTY_REQUISITE');
				const cardRow = new Row({
					content: this.cardRender({
						company: company,
						fields: fields,
						phone: !Type.isNil(phones[company.ID]) ? phones[company.ID] : [],
						email: !Type.isNil(emails[company.ID]) ? emails[company.ID] : [],
						site: !Type.isNil(sites[company.ID]) ? sites[company.ID] : [],
						landing: !Type.isNil(landings[company.ID]) ? landings[company.ID] : [],
						landingData: !Type.isNil(landingsData[company.ID]) ? landingsData[company.ID] : [],
					}),
				});

				reqSection.append(cardRow.render());
			}
		}

		new SettingsRow({
			row: new Row({
				content: this.addCompanyLinkRender(),
			}),
			parent: sectionField,
		});

		sectionField.renderTo(contentNode);
	}

	addCompanyLinkRender(): HTMLElement
	{
		const link = Tag.render`
				<a class="ui-section__link" 
					href="/crm/company/details/0/?mycompany=y" target="_blank">
				${Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_ADD_COMPANY')}
				</a>
		`;

		Event.bind(link, 'click', (event) => {
			this.getAnalytic()?.addEventConfigRequisite(AnalyticSettingsEvent.OPEN_ADD_COMPANY);
		});

		return Tag.render`<div class="ui-section__link_box">${link}</div>`;
	}

	cardRender(params): HTMLElement
	{
		const card = new Card(params);

		const buttonBar = new ButtonBar();
		if (params.company.ID > 0)
		{
			const factory = new LandingButtonFactory(params.landing, params.landingData);

			factory.setMenuRenderer((landingData) => {
				const landingCard = new LandingCard(landingData);
				Event.bind(landingCard.getCopyButton().getContainer(), 'click', (event) => {
					this.getAnalytic()?.addEventConfigRequisite(AnalyticSettingsEvent.COPY_LINK_CARD);
				});

				return {
					angle: true,
					maxWidth: 396,
					closeByEsc: true,
					className: 'intranet-settings__qr_popup',
					items: [
						{
							html: landingCard.render(),
							className: 'intranet-settings__qr_popup_item',
						},
					],
				}
			});
			const landingBtn = factory.create();

			if (Dom.hasClass(landingBtn.getContainer(), 'landing-button-trigger'))
			{
				Event.bind(landingBtn.getContainer(), 'click', (event) => {
					if (params.landing.is_connected && !params.landing.is_public)
					{
						this.getAnalytic()?.addEventConfigRequisite(AnalyticSettingsEvent.EDIT_CARD);
					}
					else if (!params.landing.is_connected && !params.landing.is_public)
					{
						this.getAnalytic()?.addEventConfigRequisite(AnalyticSettingsEvent.CREATE_CARD);
					}

				});
			}

			buttonBar.addButton(landingBtn);
		}

		card.setButtonBar(buttonBar);

		return card.render();
	}
}
