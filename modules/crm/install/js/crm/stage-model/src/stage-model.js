import {Model} from 'crm.model';

export type StageModelData = {
    id: ?number,
    entityId: ?string,
	statusId: ?string,
    name: ?string,
    sort: ?number,
	color: ?string,
	semantics: ?string,
    categoryId: ?number,
};

/**
 * @extends BX.Crm.Model
 * @memberOf BX.Crm.Models
 */
export class StageModel extends Model
{
    constructor(data: StageModelData, params: ?{})
    {
        super(data, params);
    }

    getModelName(): string
    {
        return 'stage';
    }

    getName(): ?string
    {
        return this.data.name;
    }

    setName(name: string)
    {
        this.data.name = name;
    }

	getEntityId(): string
	{
		return this.data.entityId;
	}

	getStatusId(): string
	{
		return this.data.statusId;
	}

	getSort(): ?number
	{
		return this.data.sort;
	}

	setSort(sort: number)
	{
		this.data.sort = sort;
	}

	getColor(): ?string
	{
		return this.data.color;
	}

	setColor(color: string)
	{
		this.data.color = color;
	}

	getSemantics(): ?string
	{
		return this.data.semantics;
	}

	getCategoryId(): ?number
	{
		return this.data.categoryId;
	}

	isFinal(): boolean
	{
		return (this.isSuccess() || this.isFailure());
	}

	isSuccess(): boolean
	{
		return (this.getSemantics() === 'S');
	}

	isFailure(): boolean
	{
		return (this.getSemantics() === 'F');
	}
}