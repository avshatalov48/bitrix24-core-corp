import {EventEmitter, BaseEvent} from 'main.core.events';
import {Cache, ajax} from 'main.core';

type BackendOptions = {
	events: {
		[eventName: string]: (event: BaseEvent) => void,
	},
};

export default class Backend extends EventEmitter
{
	#cache = new Cache.MemoryCache();

	constructor(options: BackendOptions)
	{
		super();
		this.setEventNamespace('BX.Sign.TemplateSelector.Backend');
		this.subscribeFromOptions(options?.events);
		this.setOptions(options);
	}

	setOptions(options: BackendOptions)
	{
		this.#cache.set('options', {...options});
	}

	getOptions(): BackendOptions
	{
		return this.#cache.get('options', {});
	}

	#request(action: string): Promise<any, Error>
	{
		return new Promise((resolve, reject) => {
			ajax
				.runAction(`sign.blank.${action}`)
				.then((result) => {
					resolve(result);
				})
				.catch((error) => {
					reject(error);
				});
		});
	}

	getTemplatesList(): Promise<Array<any>, Error>
	{
		return this.#request('list');
	}
}