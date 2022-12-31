import { Reflection } from "main.core";

const EntityType = Reflection.getClass('BX.CrmEntityType');

const DefaultSort: {[key: number]: {column: string, order: 'asc' | 'desc'}} = {};

if (EntityType)
{
	DefaultSort[EntityType.enumeration.deal] = {
		column: 'DATE_CREATE',
		order: 'desc',
	};
}

Object.freeze(DefaultSort);

export {
	DefaultSort,
}
