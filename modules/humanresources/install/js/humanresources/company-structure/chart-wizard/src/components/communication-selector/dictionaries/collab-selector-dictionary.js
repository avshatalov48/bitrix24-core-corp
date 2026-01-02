import { getCollabDialogEntity } from 'humanresources.company-structure.structure-components';
import { AbstractSelectorDictionary } from '../selector-dictionary';
import { PermissionActions } from 'humanresources.company-structure.permission-checker';

export class CollabSelectorDictionary extends AbstractSelectorDictionary
{
	phrases = {
		hintText: {
			team: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_COLLAB_ADD_CHECKBOX_HINT',
			default: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_COLLAB_ADD_CHECKBOX_HINT',
		},
		createText: {
			team: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_COLLAB_ADD_CHECKBOX_LABEL',
			default: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_COLLAB_ADD_CHECKBOX_LABEL',
		},
		warningText: {
			team: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_COLLAB_ADD_CHECKBOX_WARNING',
			default: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_COLLAB_ADD_CHECKBOX_WARNING',
		},
	};

	permissionAction = {
		department: PermissionActions.departmentCollabEdit,
		team: PermissionActions.teamCollabEdit,
	};

	getEntityName(): string
	{
		return 'project';
	}

	getEntity(): Object
	{
		return getCollabDialogEntity();
	}

	getDialogEvents(entityId: number, isTeamEntity: boolean, isEditMode: boolean): Object
	{
		return {
			onLoad: (event: BaseEvent) => {
				const dialog: Dialog = event.getTarget();
				dialog.removeTab('projects');
				if (!this.canEdit(entityId, isTeamEntity, isEditMode))
				{
					dialog.getTagSelector().lock();
				}
			},
		};
	}

	getTagId(chat): string | number
	{
		return Number(chat.id);
	}

	getItemId(tag): string | number
	{
		return tag.id;
	}

	getTestId(blueprint: string): string
	{
		return blueprint.replace('chat', 'collab');
	}

	getRemovePhrase(hasCurrentUser: boolean, isTeamEntity: boolean): string
	{
		if (hasCurrentUser)
		{
			return isTeamEntity
				? 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_COLLAB_REMOVE_CHECKBOX_LABEL'
				: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_COLLAB_REMOVE_CHECKBOX_LABEL'
			;
		}

		return isTeamEntity
			? 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_SELECT_COLLAB_REMOVE_CHECKBOX_LABEL_NOT_INSIDE'
			: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_COLLAB_REMOVE_CHECKBOX_LABEL_NOT_INSIDE'
		;
	}
}
