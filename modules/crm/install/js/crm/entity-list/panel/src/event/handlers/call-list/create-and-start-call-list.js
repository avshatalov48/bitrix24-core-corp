import { Text } from 'main.core';
import { BaseHandler } from './../base-handler';
import { createCallListAndShowAlertOnErrors } from './internals/functions';

export class CreateAndStartCallList extends BaseHandler
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
		return 'CallList:createAndStartCallList';
	}

	execute(grid, selectedIds, forAll)
	{
		createCallListAndShowAlertOnErrors(this.#entityTypeId, selectedIds, true, grid.getId(), forAll);
	}
}
