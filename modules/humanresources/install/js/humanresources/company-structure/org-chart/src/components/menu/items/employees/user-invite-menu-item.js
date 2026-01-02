import { PermissionActions } from 'humanresources.company-structure.permission-checker';
import { getColorCode } from 'humanresources.company-structure.utils';
import { Loc } from 'main.core';
import { Main } from 'ui.icon-set.api.core';
import { AbstractMenuItem } from '../abstract-menu-item';
import { MenuActions } from '../../menu-actions';

export class UserInviteMenuItem extends AbstractMenuItem
{
	constructor()
	{
		super({
			id: MenuActions.userInvite,
			title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_USER_INVITE_TITLE'),
			description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_USER_INVITE_SUBTITLE'),
			bIcon: {
				name: Main.PERSON_LETTER,
				size: 20,
				color: getColorCode('paletteBlue50'),
			},
			permissionAction: PermissionActions.inviteToDepartment,
			dataTestId: 'hr-company-structure_menu__user-invite-item',
		});
	}

	invoke({ entityId }): void
	{
		BX.SidePanel.Instance.open(
			'/bitrix/services/main/ajax.php?action=getSliderContent'
			+ '&c=bitrix%3Aintranet.invitation&mode=ajax'
			+ `&departments[]=${Number(entityId)}&firstInvitationBlock=invite-with-group-dp`,
			{ cacheable: false, allowChangeHistory: false, width: 1100 },
		);
	}
}
