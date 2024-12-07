import { PermissionEntityIdentifier } from './store';

export function entityHash(entity): string
{
	if (entity.stageField)
	{
		return `${entity.entityCode}__${entity.stageField}__${entity.stageCode}`;
	}

	return entity.entityCode;
}

export function hashIdentifier(identifier: PermissionEntityIdentifier): string
{
	let hash = `${identifier.entityCode}__${identifier.permissionCode}`;

	if (identifier.stageField)
	{
		hash = `${hash}__${identifier.stageField}__${identifier.stageCode}`;
	}

	return btoa(hash);
}