import { SettingsSection } from 'ui.form-elements.field';
import { BaseSettingsElement, SettingsRow } from 'ui.form-elements.field';
import { Dom } from 'main.core';
import { Row } from 'ui.section';
import 'ui.form';
import PortalDeleteFormType, { PortalDeleteForm } from './portal-delete-form';
import { PortalDeleteFormEmployee } from './portal-delete-form-employee';
import { PortalDeleteFormMail } from './portal-delete-form-mail';
import { PortalDeleteFormNotAdmin } from './portal-delete-form-not-admin';
import { PortalDeleteFormBound } from './portal-delete-form-bound';
import { PortalDeleteFormNetwork } from './portal-delete-form-network';

export type PortalDeleteSectionType = {
	parent: BaseSettingsElement,
	options: PortalDeleteOptionsType,
};

export type PortalDeleteOptionsType = {
	isFreeLicense: boolean,
	isEmployeesLeft: boolean,
	mailForRequest: string,
	portalUrl: string,
	verificationOptions: ?Object,
	isAdmin: boolean,
	isBound: boolean,
	networkUrl: ?string,
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

		if (!this.#options.isAdmin)
		{
			type = PortalDeleteFormType.NOT_ADMIN;
		}
		else if (!this.#options.isFreeLicense)
		{
			type = PortalDeleteFormType.MAIL;
		}
		else if (this.#options.isBound)
		{
			type = PortalDeleteFormType.BOUND;
		}
		else if (this.#options.isEmployeesLeft)
		{
			type = PortalDeleteFormType.EMPLOYEE;
		}
		else if (!this.#options.verificationOptions)
		{
			type = PortalDeleteFormType.NETWORK;
		}
		else
		{
			type = PortalDeleteFormType.DEFAULT;
		}

		this.#renderFormRow(type);
	}

	#renderFormRow(type: PortalDeleteFormType): void
	{
		if (this.#settingsRow)
		{
			this.removeChild(this.#settingsRow);
			Dom.remove(this.#settingsRow.render());
		}

		switch (type)
		{
			case PortalDeleteFormType.MAIL:
				this.#form = new PortalDeleteFormMail(this.#options.mailForRequest, this.#options.portalUrl);
				break;

			case PortalDeleteFormType.EMPLOYEE:
				this.#form = new PortalDeleteFormEmployee(this.#options.isFreeLicense);
				break;

			case PortalDeleteFormType.NOT_ADMIN:
				this.#form = new PortalDeleteFormNotAdmin();
				break;

			case PortalDeleteFormType.BOUND:
				this.#form = new PortalDeleteFormBound();
				break;

			case PortalDeleteFormType.NETWORK:
				this.#form = new PortalDeleteFormNetwork(this.#options.networkUrl);
				break;

			default:
				this.#form = new PortalDeleteForm(this.#options.verificationOptions);
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
