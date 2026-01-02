import { PortalDeleteForm } from './portal-delete-form';
import { Loc, Tag, Type } from 'main.core';
import { Button } from 'ui.buttons';

export class PortalDeleteFormNetwork extends PortalDeleteForm
{
	#networkUrl: ?string;

	constructor(networkUrl: ?string) {
		super();
		this.#networkUrl = networkUrl;
	}

	getButtonContainer(): ?HTMLElement
	{
		if ((Type.isStringFilled(this.#networkUrl)))
		{
			return super.getButtonContainer();
		}

		return null;
	}

	getConfirmButton(): Button
	{
		if (!this.confirmButton)
		{
			this.confirmButton = new Button({
				tag: Button.Tag.LINK,
				link: this.#networkUrl,
				text: Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_DELETE_PORTAL_NETWORK_LINK'),
				noCaps: true,
				round: true,
				className: '--confirm',
				props: {
					'data-bx-role': 'delete-portal-confirm',
					'target': '_top',
				},
			});
		}

		return this.confirmButton;
	}

	getDescription(): HTMLElement
	{
		return Tag.render`
			${Loc.getMessage('INTRANET_SETTINGS_SECTION_CONFIGURATION_DESCRIPTION_DELETE_PORTAL_NETWORK', {
				'#MORE_DETAILS#': this.getMoreDetails(),
			})}
		`;
	}
}