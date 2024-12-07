import { PortalDeleteForm } from './portal-delete-form';
import { Loc, Tag } from 'main.core';
import PortalDeleteFormTypes from './portal-delete-form';

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

	getBodyClass(): string
	{
		return PortalDeleteFormTypes.WARNING;
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
		const moreDetails = `
			<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=19566456')">
				${Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
			</a>
		`;

		return Tag.render`
			${Loc.getMessage('INTRANET_SETTINGS_SECTION_CONFIGURATION_DESCRIPTION_DELETE_PORTAL_MAIL', {
				'#MAIL#': this.#mailForRequest, 
				'#MAIL_LINK#': this.#getMailLink(),
				'#MORE_DETAILS#': moreDetails
			})}
		`;
	}
}