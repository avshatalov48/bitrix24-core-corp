import {Item} from '../item/item';

type Params = {
	parentItem: Item
}

export class Decomposition
{
	constructor(params: Params)
	{
		this.parentItem = params.parentItem;

		this.count = 1;
	}

	getParentItem(): Item
	{
		return this.parentItem;
	}

	addNumberDecompositionsPerformed()
	{
		this.count++;
	}

	getNumberDecompositionsPerformed(): number
	{
		return this.count;
	}
}
