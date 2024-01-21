import { Extension } from 'main.core';

const aliases: {[key: number]: string} = Extension.getSettings('crm.settings-button-extender').get('createTimeAliases', {});

const DefaultSort: {[key: number]: {column: string, order: 'asc' | 'desc'}} = {};

for (const entityTypeId in aliases)
{
	DefaultSort[entityTypeId] = {
		column: aliases[entityTypeId],
		order: 'desc',
	};
}

Object.freeze(DefaultSort);

export {
	DefaultSort,
}
