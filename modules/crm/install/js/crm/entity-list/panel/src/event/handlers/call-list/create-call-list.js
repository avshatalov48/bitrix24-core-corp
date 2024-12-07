import { Text } from 'main.core';
import { BaseHandler } from '../base-handler';
import { createCallListAndShowAlertOnErrors } from './internals/functions';

export class CreateCallList extends BaseHandler
{
	#entityTypeId: number;

	constructor({ entityTypeId })
	{
		super();

		this.#entityTypeId = Text.toInteger(entityTypeId);
		if (!BX.CrmEntityType.isDefined(this.#entityTypeId))
		{
			throw new Error('entityTypeId is required');
		}
	}

	static getEventName(): string
	{
		return 'CallList:createCallList';
	}

	execute(grid, selectedIds, forAll)
	{
		createCallListAndShowAlertOnErrors(this.#entityTypeId, selectedIds, false, grid.getId(), forAll);
	}
}
