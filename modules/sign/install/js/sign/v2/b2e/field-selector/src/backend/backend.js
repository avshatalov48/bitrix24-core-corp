import { EventEmitter, BaseEvent } from 'main.core.events';
import { ajax, Type } from 'main.core';

type BackendOptions = {
	customSettings: CustomBackendSettings | null,
	events: {
		onError: (event: BaseEvent) => void,
	},
};

export type CustomBackendSettings = {
	uri: string | null,
	requestOptions: { [key: string]: any } | null,
};

const DefaultUri = 'sign.api_v1.b2e.fields.load';

export default class Backend extends EventEmitter
{
	#options: BackendOptions;

	constructor(options: BackendOptions)
	{
		super();
		this.#options = options;
		this.setEventNamespace('BX.Sign.B2E.FieldsSelector.Backend');
		this.subscribeFromOptions(this.#options.events);
	}

	#request(requestOptions: { data: { [key: string]: any } }): Promise<any>
	{
		return new Promise((resolve, reject) => {
			ajax
				.runAction(
					this.#getUri(),
					{
						json: requestOptions.data,
					},
				)
				.then(resolve)
				.catch(reject);
		});
	}

	#getUri(): string
	{
		if (Type.isStringFilled(this.#options.customSettings?.uri))
		{
			return this.#options.customSettings.uri;
		}

		return DefaultUri;
	}

	setCustomSettings(customSettings: CustomBackendSettings | null): void
	{
		this.#options = {
			...this.#options,
			customSettings,
		};
	}

	getData(requestOptions = {}): Promise<any>
	{
		return this.#request({
			data: {
				...requestOptions,
				...(this.#options.customSettings?.requestOptions ?? {}),
			},
		});
	}
}