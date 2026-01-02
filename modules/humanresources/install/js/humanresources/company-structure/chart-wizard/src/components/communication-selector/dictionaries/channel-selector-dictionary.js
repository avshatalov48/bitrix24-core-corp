import { getChannelDialogEntity } from 'humanresources.company-structure.structure-components';
import { AbstractSelectorDictionary } from '../selector-dictionary';
import { PermissionActions } from 'humanresources.company-structure.permission-checker';

export class ChannelSelectorDictionary extends AbstractSelectorDictionary
{
	phrases = {
		hintText: {
			team: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_CHANNELS_ADD_CHECKBOX_HINT',
			default: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHANNELS_ADD_CHECKBOX_HINT_MSGVER_1',
		},
		createText: {
			team: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_CHANNELS_ADD_CHECKBOX_LABEL_MSGVER_1',
			default: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHANNELS_ADD_CHECKBOX_LABEL_MSGVER_2',
		},
		warningText: {
			team: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_CHANNELS_ADD_CHECKBOX_WARNING',
			default: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHANNELS_ADD_CHECKBOX_WARNING',
		},
	};

	permissionAction = {
		department: PermissionActions.departmentChannelEdit,
		team: PermissionActions.teamChannelEdit,
	};

	getEntity(): Object
	{
		return getChannelDialogEntity();
	}

	getTestId(blueprint: string): string
	{
		return blueprint.replace('chat', 'channel');
	}

	getRemovePhrase(hasCurrentUser: boolean, isTeamEntity: boolean): string
	{
		if (hasCurrentUser)
		{
			return isTeamEntity
				? 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_CHANNELS_REMOVE_CHECKBOX_LABEL_MSGVER_1'
				: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHANNELS_REMOVE_CHECKBOX_LABEL_MSGVER_1'
			;
		}

		return isTeamEntity
			? 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_CHANNELS_REMOVE_CHECKBOX_LABEL_NOT_INSIDE'
			: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHANNELS_REMOVE_CHECKBOX_LABEL_NOT_INSIDE'
		;
	}
}
