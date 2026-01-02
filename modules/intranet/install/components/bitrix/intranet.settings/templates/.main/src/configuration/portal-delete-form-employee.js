import PortalDeleteFormType, { PortalDeleteForm } from './portal-delete-form';
import {ajax, Loc, Tag} from 'main.core';

export class PortalDeleteFormEmployee extends PortalDeleteForm
{
	#isFreeLicense: boolean;

	constructor(isFreeLicense: boolean) {
		super();
		this.#isFreeLicense = isFreeLicense;
	}

	getDescription(): HTMLElement
	{
		return Tag.render`
			${Loc.getMessage('INTRANET_SETTINGS_SECTION_CONFIGURATION_DESCRIPTION_DELETE_PORTAL_EMPLOYEE', {
				'#MORE_DETAILS#': this.getMoreDetails(),
			})}
		`;
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
								this.sendChangeFormEvent(this.#isFreeLicense ? PortalDeleteFormType.DEFAULT : PortalDeleteFormType.MAIL);
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