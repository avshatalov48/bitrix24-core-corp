import { UserMiniProfile } from './user-mini-profile';
import type { UserMiniProfileOptions } from './type';

export class UserMiniProfileManager
{
	static #instanceByIdMap: Map<string, UserMiniProfile> = new Map();
	static #instanceByBindElementMap: Map<HTMLElement, UserMiniProfile> = new Map();

	static getById(id: string): ?UserMiniProfile
	{
		return this.#instanceByIdMap.get(id);
	}

	static create(options: UserMiniProfileOptions & { id: string }): UserMiniProfile
	{
		const { id, bindElement } = options;

		// If other widget was already binded to element, we need to unbind it
		if (this.#instanceByBindElementMap.has(bindElement))
		{
			const instanceByElement = this.#instanceByBindElementMap.get(bindElement);
			const instanceById = this.#instanceByIdMap.get(id);
			if (instanceById !== instanceByElement)
			{
				instanceByElement.setBindElement(null);
				this.#instanceByBindElementMap.delete(bindElement);
			}
		}

		if (this.#instanceByIdMap.has(id))
		{
			const instance = this.#instanceByIdMap.get(id);

			const previousBindElement = instance.getBindElement();
			if (previousBindElement !== bindElement)
			{
				this.#instanceByBindElementMap.delete(previousBindElement);
				instance.setBindElement(bindElement);
			}

			this.#instanceByBindElementMap.set(bindElement, instance);

			return instance;
		}

		const instance = new UserMiniProfile(options);
		this.#instanceByIdMap.set(id, instance);
		this.#instanceByBindElementMap.set(bindElement, instance);

		return instance;
	}
}
