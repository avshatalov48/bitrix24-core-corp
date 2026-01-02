import { PortalDeleteForm } from './portal-delete-form';
import { Loc, Tag } from 'main.core';

export class PortalDeleteFormMail extends PortalDeleteForm
{
	#mailForRequest: string;
	#portalUrl: string;
	#mailLink: ?string;
	constructor(mailForRequest: string, portalUrl: string) {
		super();

		this.#mailForRequest = mailForRequest;
		this.#portalUrl = portalUrl;
	}

	getConfirmButtonText(): ?string
	{
		return Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_DELETE_PORTAL_MAIL', {
			'#MAIL#': this.#mailForRequest
		});
	}

	onConfirmEventHandler(): void
	{
		top.window.location.href = this.#getMailLink();
	}

	#getMailLink(): string
	{
		if (!this.#mailLink)
		{
			const mailBody = Loc.getMessage('INTRANET_SETTINGS_PORTAL_DELETE_MAIL_BODY', {'#PORTAL_URL#': this.#portalUrl});
			const mailSubject = Loc.getMessage('INTRANET_SETTINGS_PORTAL_DELETE_MAIL_SUBJECT', {'#PORTAL_URL#':  this.#portalUrl});
			this.#mailLink = `mailto:${this.#mailForRequest}?body=${mailBody}&subject=${mailSubject}`;
		}

		return this.#mailLink;
	}

	getDescription(): HTMLElement
	{
		return Tag.render`
			${Loc.getMessage('INTRANET_SETTINGS_SECTION_CONFIGURATION_DESCRIPTION_DELETE_PORTAL_MAIL', {
				'#MAIL#': this.#mailForRequest, 
				'#MAIL_LINK#': this.#getMailLink(),
				'#MORE_DETAILS#': this.getMoreDetails(),
			})}
		`;
	}
}