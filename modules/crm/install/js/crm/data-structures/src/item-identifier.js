import { Text } from 'main.core';

export class ItemIdentifier
{
	#entityTypeId: number;
	#entityId: number;

	constructor(entityTypeId: number, entityId: number)
	{
		// noinspection AssignmentToFunctionParameterJS
		entityTypeId = Text.toInteger(entityTypeId);
		// noinspection AssignmentToFunctionParameterJS
		entityId = Text.toInteger(entityId);

		if (!BX.CrmEntityType.isDefined(entityTypeId))
		{
			throw new Error('entityTypeId is not a valid crm entity type');
		}

		if (entityId <= 0)
		{
			throw new Error('entityId must be greater than 0');
		}

		this.#entityTypeId = entityTypeId;
		this.#entityId = entityId;
	}

	static fromJSON(data: Object): ?ItemIdentifier
	{
		try
		{
			return new ItemIdentifier(Text.toInteger(data?.entityTypeId), Text.toInteger(data?.entityId));
		}
		catch (e)
		{
			return null;
		}
	}

	get entityTypeId(): number
	{
		return this.#entityTypeId;
	}

	get entityId(): number
	{
		return this.#entityId;
	}

	get hash(): string
	{
		return `type_${this.entityTypeId}_id_${this.entityId}`;
	}

	isEqualTo(another: ItemIdentifier): boolean
	{
		if (!(another instanceof ItemIdentifier))
		{
			return false;
		}

		return this.hash === another.hash;
	}
}
