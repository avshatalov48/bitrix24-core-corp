import {Model} from 'crm.model';

declare type CategoryModelData = {
    id: ?number,
    entityTypeId: ?number,
    name: ?string,
    sort: ?number,
    isDefault: boolean,
};

/**
 * @memberOf BX.Crm.Models
 */
export class CategoryModel extends Model
{
    constructor(data: CategoryModelData, params: ?{})
    {
        super(data, params);
    }

    getModelName(): string
    {
        return 'category';
    }

    getName(): ?string
    {
        return this.data.name;
    }

    setName(name: string)
    {
        this.data.name = name;
    }

    getSort(): ?number
    {
        return this.data.sort;
    }

    setSort(sort: number)
    {
        this.data.sort = sort;
    }

    isDefault(): boolean
    {
        return this.data.isDefault;
    }

    setDefault(isDefault: boolean)
    {
        this.data.isDefault = isDefault;
    }

    getGetParameters(action: string)
    {
        return {
            ...super.getGetParameters(action),
            ...{
                entityTypeId: this.getEntityTypeId(),
            }
        };
    }
}