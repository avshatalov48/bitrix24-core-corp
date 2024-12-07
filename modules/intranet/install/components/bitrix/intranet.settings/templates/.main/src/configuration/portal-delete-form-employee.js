import { PortalDeleteForm } from './portal-delete-form';
import {ajax, Loc, Tag} from 'main.core';
import PortalDeleteFormTypes from './portal-delete-form';

export class PortalDeleteFormEmployee extends PortalDeleteForm
{
	#isFreeLicense: boolean;

	constructor(isFreeLicense: boolean) {
		super();
		this.#isFreeLicense = isFreeLicense;
	}

	getDescription(): HTMLElement
	{
		const moreDetails = `
			<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=19566456')">
				${Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
			</a>
		`;

		return Tag.render`
			${Loc.getMessage('INTRANET_SETTINGS_SECTION_CONFIGURATION_DESCRIPTION_DELETE_PORTAL_EMPLOYEE', {
				'#MORE_DETAILS#': moreDetails
			})}
		`;
	}

	getBodyClass(): string
	{
		return PortalDeleteFormTypes.WARNING;
	}

	getConfirmButtonText(): ?string
	{
		return Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_DELETE_PORTAL_FIRE_EMPLOYEE');
	}

	onConfirmEventHandler(): void
	{
		this.getConfirmButton().setWaiting(true);

		BX.SidePanel.Instance.open('/company/?apply_filter=Y&FIRED=N',{
			events: {
				onCloseComplete: () => {
					ajax.runAction('bitrix24.portal.getActiveUserCount')
						.then((response) => {
							this.getConfirmButton().setWaiting(false);

							if (response.data <= 1)
							{
								this.sendChangeFormEvent(this.#isFreeLicense ? 'default' : 'mail');
							}
						})
						.catch((reject) => {
							this.getConfirmButton().setWaiting(false);
							reject.errors.forEach((error) => {
								console.log(error.message);
							})
						});
				}
			}
		});
	}
}