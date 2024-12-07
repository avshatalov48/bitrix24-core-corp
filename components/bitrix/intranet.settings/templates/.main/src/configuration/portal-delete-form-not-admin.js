import PortalDeleteFormTypes, {PortalDeleteForm} from "./portal-delete-form";
import {Loc, Tag} from "main.core";

export class PortalDeleteFormNotAdmin extends PortalDeleteForm
{
	getButtonContainer() {}

	getBodyClass(): string
	{
		return PortalDeleteFormTypes.WARNING;
	}

	getDescription(): HTMLElement
	{
		return Tag.render`
			${Loc.getMessage('INTRANET_SETTINGS_SECTION_CONFIGURATION_DESCRIPTION_DELETE_PORTAL_NOT_ADMIN')}
			<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=19566456')">
				${Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
			</a>
		`;
	}
}