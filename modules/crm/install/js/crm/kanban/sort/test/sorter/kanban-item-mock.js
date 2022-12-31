import { SortParams } from "../../src";

export class KanbanItemMock
{
	data: {sort: SortParams};

	constructor(data: {sort: SortParams})
	{
		this.data = data;
	}

	getData(): Object
	{
		return this.data;
	}

	getId(): number
	{
		return this.data.sort.id;
	}

	getLastActivityTimestamp(): number
	{
		return this.data.sort.lastActivityTimestamp;
	}

	static create(id: number, lastActivityTimestamp: number): KanbanItemMock
	{
		return new KanbanItemMock({
			sort: {
				id,
				lastActivityTimestamp,
			}
		});
	}
}
