import {EventEmitter, BaseEvent} from 'main.core.events';
import {Cache, ajax, Type} from 'main.core';

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
		this.setEventNamespace('BX.Sign.BlankSelector.Backend');
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

	#request(action: string, data: any = {}): Promise<any, Error>
	{
		return new Promise((resolve, reject) => {
			ajax
				.runAction(`sign.api.blank.${action}`, {data})
				.then((result) => {
					resolve(result);
				})
				.catch((error) => {
					reject(error);
				});
		});
	}

	getBlanksList(options: {countPerPage: number, page: number}): Promise<Array<any>, Error>
	{
		return this.#request('list', Type.isPlainObject(options) ? options : {});
	}

	getBlankById(id: number | string): Promise<any>
	{
		return this.#request('getById', {id});
	}
}