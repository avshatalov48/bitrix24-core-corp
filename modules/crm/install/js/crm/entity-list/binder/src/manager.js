import { Binder } from './binder';
import { EntitySelector } from './entity-selector';

let instance = null;

export class Manager
{
	#binders: Object<string, Binder> = {};

	static get Instance(): Manager
	{
		if (instance === null)
		{
			instance = new Manager();
		}

		return instance;
	}

	initializeBinder(
		parentEntityTypeId: number,
		parentEntityId: number,
		childEntityTypeId: number,
		gridId: string,
	): Binder
	{
		const binder = new Binder(parentEntityTypeId, parentEntityId, childEntityTypeId, gridId);

		this.#binders[binder.getId()] = binder;

		return binder;
	}

	getBinder(binderId: string): ?Binder
	{
		return this.#binders[binderId];
	}

	createRelatedSelector(binderId: string, target: ?string = null): ?EntitySelector
	{
		const binder = this.getBinder(binderId);
		if (!binder)
		{
			console.error('Binder with given id not found', binderId, this);

			return null;
		}

		if (!binder.getParenEntityTypeId() || !binder.getParentEntityId() || !binder.getChildEntityTypeId())
		{
			console.error('Not well configured binder', binderId, binder);

			return null;
		}

		return new EntitySelector(binder, document.getElementById(target));
	}
}
