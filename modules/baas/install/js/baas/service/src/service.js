import { Extension, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';

export class Service extends EventEmitter
{
	#code: string;
	#available: boolean = false;
	#active: boolean = false;

	constructor(code: string)
	{
		super();
		this.setEventNamespace('BX.Baas');
		this.#code = code;
		const props = Extension.getSettings('baas.service').services[this.#code];
		if (Type.isPlainObject(props))
		{
			this.#available = props.isAvailable;
			this.#active = props.isActive;
		}

		if (BX.PULL && Extension.getSettings('baas.service').pull)
		{
			BX.PULL.extendWatch(Extension.getSettings('baas.service').pull.channelName);

			EventEmitter.subscribe('onPullEvent-baas', (event: BaseEvent) => {
				const [command: string, params] = event.getData();
				if (command === 'updateService' && this.#code === params.service.code)
				{
					if (this.#active !== params.service.isActive)
					{
						this.#active = params.service.isActive;
						this.emit('onServiceActivityChanged', new BaseEvent({
							data: {
								code: this.#code,
								activity: this.#active,
							},
						}));
					}

					if (this.#available !== params.service.isAvailable)
					{
						this.#available = params.service.isAvailable;
						this.emit('onServiceAvailabilityChanged', new BaseEvent({
							data: {
								code: this.#code,
								availability: this.#available,
							},
						}));
					}
				}
			});
		}
	}

	isAvailable(): boolean
	{
		return this.#available;
	}

	isActive(): boolean
	{
		return this.#active;
	}
}
