import { SettingsSection } from 'ui.form-elements.field';
import { BaseSettingsElement, SettingsRow } from 'ui.form-elements.field';
import { Loc, Dom } from 'main.core';
import { Row, Section } from 'ui.section';
import 'ui.form';
import { PortalDeleteForm } from './portal-delete-form';
import { PortalDeleteFormEmployee } from "./portal-delete-form-employee";
import {PortalDeleteFormCheckword} from "./portal-delete-form-checkword";
import {PortalDeleteFormMail} from "./portal-delete-form-mail";
import {PortalDeleteFormNotAdmin} from "./portal-delete-form-not-admin";

export type PortalDeleteSectionType = {
	parent: BaseSettingsElement,
	options: PortalDeleteOptionsType,
};

export type PortalDeleteOptionsType = {
	isFreeLicense: boolean,
	isEmployeesLeft: boolean,
	checkWord: string,
	mailForRequest: string,
	portalUrl: string,
}

export class PortalDeleteSection extends SettingsSection
{
	#options: PortalDeleteOptionsType;
	#settingsRow: ?SettingsRow;
	#form: PortalDeleteForm;
	#defaultBodyClass: string
	#bodyClass: string;

	constructor(params: PortalDeleteSectionType) {
		super(params);

		this.#defaultBodyClass = this.getSectionView().className.bodyActive;
		this.#options = params.options;
		let type;

		if (!this.#options.isFreeLicense)
		{
			type = 'mail';
		}
		else if (this.#options.isEmployeesLeft)
		{
			type = 'employee';
		}
		else
		{
			type = 'default';
		}

		this.#renderFormRow(type);
	}

	#renderFormRow(type: 'checkword'|'mail'|'employee'|'default'|'not_admin'): void
	{
		if (this.#settingsRow)
		{
			this.removeChild(this.#settingsRow);
			Dom.remove(this.#settingsRow.render());
		}

		switch (type)
		{
			case 'checkword':
				this.#form = new PortalDeleteFormCheckword(this.#options.checkWord);
				break;

			case 'mail':
				this.#form = new PortalDeleteFormMail(this.#options.mailForRequest, this.#options.portalUrl);
				break;

			case 'employee':
				this.#form = new PortalDeleteFormEmployee(this.#options.isFreeLicense);
				break;

			case 'not_admin':
				this.#form = new PortalDeleteFormNotAdmin();
				break;

			default:
				this.#form = new PortalDeleteForm();
				break;
		}

		this.#updateSectionBodyClass();
		this.#bindFormEvents();

		const formRow = new Row({
			content: this.#form.getContainer(),
		});
		this.#settingsRow = new SettingsRow({
			row: formRow,
		});

		this.addChild(this.#settingsRow);
		this.render();
	}

	#bindFormEvents(): void
	{
		this.#form.subscribe('closeForm', () => {
			this.getSectionView().toggle(false);
		});

		this.#form.subscribe('updateForm', (event) => {
			if (event.data.type)
			{
				this.#renderFormRow(event.data.type);
			}
		})
	}

	#updateSectionBodyClass(): void
	{
		Dom.removeClass(this.getSectionView().getContent(), this.#bodyClass);
		this.#bodyClass = this.#form.getBodyClass();
		this.getSectionView().className.bodyActive = this.#defaultBodyClass + ' ' + this.#bodyClass;

		if (this.getSectionView().isOpen)
		{
			Dom.addClass(this.getSectionView().getContent(), this.#bodyClass);
		}
	}
}