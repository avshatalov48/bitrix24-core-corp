import { Text, Type } from 'main.core';
import { Receiver } from '../receiver';
import { ItemIdentifier } from 'crm.data-structures';

export function extractReceivers(item: ItemIdentifier, entityData: ?Object): Receiver[]
{
	const receivers = [];
	if (entityData?.hasOwnProperty('MULTIFIELD_DATA'))
	{
		receivers.push(...extractReceiversFromMultifieldData(item, entityData));
	}
	if (entityData?.hasOwnProperty('CLIENT_INFO'))
	{
		receivers.push(...extractReceiversFromClientInfo(item, entityData.CLIENT_INFO));
	}

	return unique(receivers);
}

function extractReceiversFromMultifieldData(item: ItemIdentifier, entityData: Object): Receiver[]
{
	const receivers: Receiver[] = [];

	const multifields = entityData.MULTIFIELD_DATA;
	for (const multifieldTypeId in multifields)
	{
		if (!multifields.hasOwnProperty(multifieldTypeId) || !Type.isPlainObject(multifields[multifieldTypeId]))
		{
			continue;
		}

		for (const itemSlug in multifields[multifieldTypeId])
		{
			if (
				!multifields[multifieldTypeId].hasOwnProperty(itemSlug)
				|| !Type.isArrayFilled(multifields[multifieldTypeId][itemSlug])
			)
			{
				continue;
			}

			const [entityTypeId, entityId] = itemSlug.split('_');
			let addressSource: ItemIdentifier;
			try
			{
				addressSource = new ItemIdentifier(Text.toInteger(entityTypeId), Text.toInteger(entityId));
			}
			catch (e)
			{
				continue;
			}

			const addressSourceTitle = getAddressSourceTitle(item, addressSource, entityData);

			for (const singleMultifield of multifields[multifieldTypeId][itemSlug])
			{
				try
				{
					receivers.push(new Receiver(
						item,
						addressSource,
						{
							id: Text.toInteger(singleMultifield.ID),
							typeId: String(multifieldTypeId),
							valueType: stringOrUndefined(singleMultifield.VALUE_TYPE),
							value: stringOrUndefined(singleMultifield.VALUE),
							valueFormatted: stringOrUndefined(singleMultifield.VALUE_FORMATTED),
						},
						{
							title: addressSourceTitle,
						},
					));
				}
				catch (e)
				{

				}
			}
		}
	}

	return receivers;
}

function getAddressSourceTitle(rootSource: ItemIdentifier, addressSource: ItemIdentifier, entityData: ?Object): string
{
	if (rootSource.isEqualTo(addressSource))
	{
		return entityData?.TITLE ?? entityData.FORMATTED_NAME ?? '';
	}

	const clientDataKey = `${BX.CrmEntityType.resolveName(addressSource.entityTypeId)}_DATA`;
	if (Type.isArrayFilled(entityData?.CLIENT_INFO?.[clientDataKey]))
	{
		const client = entityData.CLIENT_INFO[clientDataKey].find(clientInfo => {
			return Text.toInteger(clientInfo.id) === addressSource.entityId;
		});

		if (Type.isString(client?.title))
		{
			return client.title;
		}
	}

	return '';
}

function extractReceiversFromClientInfo(item: ItemIdentifier, clientInfo: Object): Receiver[]
{
	const receivers = [];

	for (const clientsOfSameType of Object.values(clientInfo))
	{
		if (!Type.isArrayFilled(clientsOfSameType))
		{
			continue;
		}

		for (const singleClient of clientsOfSameType)
		{
			if (!Type.isPlainObject(singleClient))
			{
				continue;
			}

			let addressSource: ItemIdentifier;
			try
			{
				addressSource = new ItemIdentifier(BX.CrmEntityType.resolveId(singleClient.typeName), singleClient.id);
			}
			catch (e)
			{
				continue;
			}

			const multifields = singleClient.advancedInfo?.multiFields;
			if (!Type.isArrayFilled(multifields))
			{
				continue;
			}

			for (const singleMultifield of multifields)
			{
				try
				{
					receivers.push(new Receiver(
						item,
						addressSource,
						{
							id: Text.toInteger(singleMultifield.ID),
							typeId: stringOrUndefined(singleMultifield.TYPE_ID),
							valueType: stringOrUndefined(singleMultifield.VALUE_TYPE),
							value: stringOrUndefined(singleMultifield.VALUE),
							valueFormatted: stringOrUndefined(singleMultifield.VALUE_FORMATTED),
						},
						{
							title: stringOrUndefined(singleClient.title),
						},
					));
				}
				catch (e)
				{
				}
			}
		}
	}

	return receivers;
}

function stringOrUndefined(value: ?string): string | undefined
{
	return Type.isNil(value) ? undefined : String(value);
}

function unique(receivers: Receiver[]): Receiver[]
{
	return receivers.filter((receiver, index) => {
		const anotherIndex = receivers.findIndex(anotherReceiver => receiver.isEqualTo(anotherReceiver));

		return anotherIndex === index;
	});
}
