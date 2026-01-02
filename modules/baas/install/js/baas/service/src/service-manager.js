import { Service } from './service';

export class ServiceManager
{
	static #services = [];

	static getByCode(code: string): Service
	{
		if (!this.#services[code])
		{
			this.#services[code] = new Service(code);
		}

		return this.#services[code];
	}
}
