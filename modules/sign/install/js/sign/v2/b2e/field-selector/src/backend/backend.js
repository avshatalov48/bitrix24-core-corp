import {EventEmitter, BaseEvent} from 'main.core.events';
import {ajax} from 'main.core';

type BackendOptions = {
	events: {
		onError: (event: BaseEvent) => void,
	},
};

export default class Backend extends EventEmitter
{
	constructor(options: BackendOptions)
	{
		super();
		this.setEventNamespace('BX.Sign.B2E.FieldsSelector.Backend');
		this.subscribeFromOptions(options.events);
	}

	#request(requestOptions: {action: string, data: {[key: string]: any}}): Promise<any>
	{
		return new Promise((resolve, reject) => {
			ajax
				.runAction(
					`sign.api_v1.b2e.fields.${requestOptions.action}`,
					{
						json: requestOptions.data,
					},
				)
				.then(resolve)
				.catch(reject);
		});
	}

	getData(requestOptions = {}): Promise<any>
	{
		return this.#request({
			action: 'load',
			data: requestOptions,
		});
	}
}