import { EntitySelectorEntity } from 'tasks.v2.const';
import type { CrmItemModel } from 'tasks.v2.model.crm-items';
import type { CrmItemDto } from './types';

const entityPrefixMap = {
	[EntitySelectorEntity.Deal]: 'D',
	[EntitySelectorEntity.Contact]: 'C',
	[EntitySelectorEntity.Company]: 'CO',
	[EntitySelectorEntity.Lead]: 'L',
	[EntitySelectorEntity.SmartInvoice]: 'SI',
};

const prefixEntityMap = Object.fromEntries(Object.entries(entityPrefixMap).map((it) => it.reverse()));

export function mapDtoToModel(crmItemDto: CrmItemDto): CrmItemModel
{
	return {
		id: crmItemDto.id,
		entityId: crmItemDto.entityId,
		type: crmItemDto.type,
		typeName: crmItemDto.typeName,
		title: crmItemDto.title,
		link: crmItemDto.link,
	};
}

export function mapId(entityId: string, id: number): string
{
	if (!entityPrefixMap[entityId])
	{
		const [typeId, itemId] = id.split(':');

		return `T${Number(typeId).toString(16)}_${itemId}`;
	}

	return `${entityPrefixMap[entityId]}_${id}`;
}

export function splitId(id: string): string
{
	const [prefix, entityId] = id.split('_');
	if (!prefixEntityMap[prefix])
	{
		return [EntitySelectorEntity.DynamicMultiple, `${parseInt(prefix.slice(1), 16)}:${entityId}`];
	}

	return [prefixEntityMap[prefix], Number(entityId)];
}
