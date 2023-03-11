import {EventEmitter} from "main.core.events";
import {Extension, Type, Runtime} from 'main.core';

import {Pool} from "./debounced-queue-pool";
import {Status} from "./debounced-queue-status";

class DebouncedQueue extends EventEmitter
{
	#status:string = Status.NONE;

	#timeout = 0;

	#pool: Pool = new Pool();
	#debounce = null;

	constructor(options = {})
	{
		super();
		this.setEventNamespace('BX.Tasks.DebounceQueue');

		options = Type.isPlainObject(options) ? options : {};
		this.subscribeFromOptions(options.events);

		this.#timeout = options.timeout ?? 1000;
		this.#debounce = Runtime.debounce(
			() => {
				this.#status = Status.RUN;
				this.commit()
				.then(() => this.#status = Status.NONE)
			},
			this.#timeout
		)
	}

	push(fields, index = 'default')
	{
		this.#pool.add(index, fields);
		// console.log('pool', this.#pool.getItems());
		// console.log(JSON.stringify(this.#pool.items));
		this.commitWithDebounce();
	}

	commit(): Promise
	{
		return new Promise((resolve, reject) =>
		{
			if(this.#pool.isEmpty() === false)
			{
				// console.log('count', this.#pool.count());

				const poolItems = this.#pool.getItems();
				this.#pool.clean();

				this.emitAsync('onCommitAsync', { poolItems })
					.then(() => this.commit()
						.then(() => resolve()))
					.catch();
			}
			else
			{
				resolve();
			}
		});
	}

	commitWithDebounce()
	{
		if(this.#status === Status.NONE)
		{
			this.#debounce();
		}
	}
}

export
{
	DebouncedQueue
}