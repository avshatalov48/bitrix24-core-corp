import { Event, ajax } from 'main.core';
import ErrorNotifierFactory from './error-notifier-factory';
import SuccessNotifier from './success-notifier';

export class Registration
{
	ready: Boolean = true;

	bind(node): this
	{
		Event.bind(node, 'click', this.send.bind(this));

		return this;
	}

	send(): Promise
	{
		return new Promise((resolve, reject) => {
			if (this.ready === false)
			{
				reject();
			}
			else
			{
				this.ready = false;

				ajax
					.runAction('baas.Host.register', { data: {} })
					.then((response) => {
						this.ready = true;
						(new SuccessNotifier()).show();
						resolve();
					})
					.catch((response) => {
						this.ready = true;
						ErrorNotifierFactory.createFromResponse(response).show();
						reject();
					})
				;
			}
		});
	}

	static #instance = null;

	static getInstance(): Registration
	{
		if (this.#instance === null)
		{
			this.#instance = new this();
		}

		return this.#instance;
	}
}
