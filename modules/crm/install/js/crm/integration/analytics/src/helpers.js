import { Extension, Type } from 'main.core';
import type { SettingsCollection } from 'main.core.collections';
import type { CrmMode } from './types';

let extensionSettings: ?SettingsCollection = null;

export function getAnalyticsEntityType(entityType: number | string): ?string
{
	let entityTypeName = null;
	if (BX.CrmEntityType.isDefined(entityType))
	{
		entityTypeName = BX.CrmEntityType.resolveName(entityType);
	}
	else if (BX.CrmEntityType.isDefinedByName(entityType))
	{
		entityTypeName = entityType;
	}

	if (!Type.isStringFilled(entityTypeName))
	{
		return null;
	}

	if (BX.CrmEntityType.isDynamicTypeByName(entityTypeName))
	{
		return 'dynamic';
	}

	return entityTypeName.toLowerCase();
}

export function getCrmMode(): CrmMode
{
	if (!extensionSettings)
	{
		extensionSettings = Extension.getSettings('crm.integration.analytics');
	}

	return `crmMode_${extensionSettings.get('crmMode', '').toLowerCase()}`;
}

export function filterOutNilValues(object: Object): Object
{
	const result = {};

	Object.entries(object).forEach(([key, value]) => {
		if (!Type.isNil(value))
		{
			result[key] = value;
		}
	});

	return result;
}
