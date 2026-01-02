import {PortalDeleteForm} from "./portal-delete-form";
import {Loc, Tag} from "main.core";

export class PortalDeleteFormBound extends PortalDeleteForm
{
	getButtonContainer() {}

	getDescription(): HTMLElement
	{
		return Tag.render`
			${Loc.getMessage('INTRANET_SETTINGS_SECTION_CONFIGURATION_DESCRIPTION_DELETE_PORTAL_BOUND', {
				'#MORE_DETAILS#': this.getMoreDetails(),
			})}
		`;
	}
}