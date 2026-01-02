import { PermissionChecker } from 'humanresources.company-structure.permission-checker';
type AvailablePhrases = 'hintText' | 'createText' | 'removeText' | 'warningText';

export class AbstractSelectorDictionary
{
	phrases: Record<AvailablePhrases, { team: string, default: string }> = {};
	permissionAction: { team: string, department: string } = { team: '', department: '' };

	getPhrase(key: AvailablePhrases, isTeamEntity: boolean): string
	{
		const phraseSet = this.phrases[key];

		if (!phraseSet)
		{
			throw new Error(`Phrase set for key "${key}" is not defined in ${this.constructor.name} selector dictionary.`);
		}

		return isTeamEntity ? phraseSet.team : phraseSet.default;
	}

	getEntityName(): string
	{
		return 'im-chat-only';
	}

	getTagId(chat): string | number
	{
		return `chat${chat.id}`;
	}

	getItemId(tag): string | number
	{
		return Number(tag.id.replace('chat', ''));
	}

	getEntity(): Object {}

	getDialogEvents(entityId: number, isTeamEntity: boolean, isEditMode: boolean): Object
	{
		return {
			onLoad: (event: BaseEvent) => {
				const dialog: Dialog = event.getTarget();
				if (!this.canEdit(entityId, isTeamEntity, isEditMode))
				{
					dialog.getTagSelector().lock();
				}
			},
		};
	}

	getTestId(blueprint: string): string {}

	canEdit(entityId: number, isTeamEntity: boolean, isEditMode: boolean): boolean
	{
		if (!isEditMode)
		{
			return true;
		}

		const permissionChecker = PermissionChecker.getInstance();
		if (!permissionChecker)
		{
			return false;
		}

		const permissionAction = isTeamEntity
			? this.permissionAction.team
			: this.permissionAction.department
		;

		return permissionChecker.hasPermission(permissionAction, entityId);
	}

	getRemovePhrase(hasCurrentUser: boolean, isTeamEntity: boolean): string
	{
		throw new Error('getRemovePhrase must be implemented in inheritor');
	}
}
