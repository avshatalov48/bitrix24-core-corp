import { PermissionActions } from 'humanresources.company-structure.permission-checker';
import { getColorCode } from 'humanresources.company-structure.utils';
import { Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Actions } from 'ui.icon-set.api.core';
import { events } from '../../../../consts';
import { AbstractMenuItem } from '../abstract-menu-item';
import { MenuActions } from '../../menu-actions';

export class TeamRightsMenuItem extends AbstractMenuItem
{
	constructor()
	{
		super({
			id: MenuActions.teamRights,
			title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_TEAM_RIGHTS_TITLE'),
			description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_DETAIL_EDIT_MENU_TEAM_RIGHTS_SUBTITLE'),
			bIcon: {
				name: Actions.SETTINGS_2,
				size: 20,
				color: getColorCode('paletteBlue50'),
			},
			permissionAction: PermissionActions.teamSettingsEdit,
			dataTestId: 'hr-company-structure_menu__team-rights-item',
		});
	}

	invoke({ entityId, analyticSource, entityType }): void
	{
		EventEmitter.emit(events.HR_ENTITY_SHOW_WIZARD, {
			nodeId: entityId,
			isEditMode: true,
			type: 'teamRights',
			source: analyticSource,
		});
	}
}
