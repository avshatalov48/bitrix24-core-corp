import { Text, Type } from 'main.core';
import { BaseHandler } from './../base-handler';
import { addItemsToCallList } from './internals/functions';

export class AddItemsToCallList extends BaseHandler
{
	#entityTypeId: number;
	#callListId: number;
	#context: string;

	constructor({ entityTypeId, callListId, context })
	{
		super();

		this.#entityTypeId = Text.toInteger(entityTypeId);
		if (!BX.CrmEntityType.isDefined(this.#entityTypeId))
		{
			throw new Error('entityTypeId is required');
		}

		this.#callListId = Text.toInteger(callListId);
		if (this.#callListId <= 0)
		{
			throw new Error('callListId is required');
		}

		this.#context = String(context);
		if (!Type.isStringFilled(this.#context))
		{
			throw new Error('context is required');
		}
	}

	static getEventName(): string
	{
		return 'CallList:addItemsToCallList';
	}

	execute(grid, selectedIds, forAll)
	{
		if (selectedIds.length === 0 && !forAll)
		{
			return;
		}

		addItemsToCallList(
			this.#entityTypeId,
			selectedIds,
			this.#callListId,
			this.#context,
			grid.getId(),
			forAll,
		);
	}
}
