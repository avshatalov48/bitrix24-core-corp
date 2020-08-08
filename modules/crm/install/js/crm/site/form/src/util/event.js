import 'main.polyfill.customevent';

class Event
{
	#namespace: Array = [];
	#subscribers: Array = [];
	#emittedOnce: Array = [];

	setGlobalEventNamespace(...args)
	{
		this.#namespace = args;
	}

	emitOnce(type: string, data: Object)
	{
		if (this.#emittedOnce.indexOf(type) < 0)
		{
			this.emit(type, data);
		}
	}

	emit(type: string, data: Object)
	{
		this.#emittedOnce.push(type);
		this.#subscribers.forEach(subscriber => {
			if (!subscriber.type || subscriber.type === type)
			{
				subscriber.callback.call(this, data, this, type);
			}
		});

		if (this.#namespace.length === 0)
		{
			return;
		}

		window.dispatchEvent(new window.CustomEvent(
			[...this.#namespace, type].join(':'),
			{
				detail: {object: this, type, data}
			}
		));
	}

	subscribe(type: string, callback: Function)
	{
		if (!type || typeof callback !== 'function')
		{
			return;
		}

		this.#subscribers.push({type, callback});
	}

	subscribeAll(callback: Function)
	{
		this.#subscribers.push({type: null, callback});
	}

	unsubscribe(type: string, callback: Function)
	{
		this.#subscribers = this.#subscribers.filter(subscriber => {
			return (
				subscriber.type !== type
				||
				subscriber.callback !== callback
			);
		});
	}

	unsubscribeAll()
	{
		this.#subscribers = [];
	}
}

export default Event;