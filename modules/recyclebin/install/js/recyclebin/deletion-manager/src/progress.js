import { ajax as Ajax, Runtime, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Panel } from './panel';
import { State } from './state';

type Settings = {
	action: string;
	panel: Panel;
	emitter: EventEmitter;
	params?: Params;
	timeout?: number;
}

type Params = {
	entityIds?: number[];
	moduleId?: string;
}

export class Progress
{
	#id: string;
	#settings: Settings;
	#params: Params;
	#requestIsRunning: boolean;
	#state: number;
	#errors = [];
	#processedItemCount: number;
	#totalItemCount: number;
	emitter: EventEmitter;
	#panel: Panel;
	#action: string;

	constructor(id: string, settings: Settings)
	{
		this.#id = id;
		this.#settings = settings;
		this.#action = settings.action;
		this.#panel = settings.panel;
		this.emitter = settings.emitter;
		this.#params = settings.params ?? {};

		this.startRequest = this.startRequest.bind(this);
		this.closePanel = this.closePanel.bind(this);
		this.onRequestSuccess = this.onRequestSuccess.bind(this);
		this.onRequestFailure = this.onRequestFailure.bind(this);
	}

	setParams(params: Params): Progress
	{
		this.#params = Runtime.merge(this.#params, params);

		return this;
	}

	run(): Progress
	{
		if (this.#state === State.stopped)
		{
			this.#state = State.intermediate;
		}

		this.startRequest();
	}

	startRequest(): void
	{
		if (this.#state === State.stopped)
		{
			return;
		}

		if (this.#requestIsRunning)
		{
			return;
		}

		this.#requestIsRunning = true;
		this.#state = State.running;

		const data = {
			params: Type.isPlainObject(this.#params) ? this.#params : {},
		};

		Ajax.runAction(
			this.#action,
			{
				data,
			},
		).then(
			(result) => this.onRequestSuccess(result.data ?? {}),
			(result) => this.onRequestFailure(result.data ?? {}),
		).catch((error) => {
			console.error(error);
		});
	}

	onRequestSuccess(result): void
	{
		this.#requestIsRunning = false;
		if (this.#state === State.stopped)
		{
			return;
		}

		const { status, errors, processedItems, totalItems } = result;
		if (status === 'ERROR')
		{
			this.#state = State.error;
		}
		else if (status === 'COMPLETED')
		{
			this.#state = State.completed;
		}

		if (this.#state === State.error)
		{
			console.error(this.#errors);
		}
		else
		{
			this.#processedItemCount = processedItems ?? 0;
			this.#totalItemCount = totalItems ?? 0;
		}

		if (Type.isArrayFilled(errors))
		{
			this.#errors = [...this.#errors, ...errors];
		}

		this.refresh();
		if (this.#state === State.running)
		{
			setTimeout(this.startRequest, this.getTimeout());
		}
		else if (this.#state === State.completed && !Type.isArrayFilled(this.#errors))
		{
			setTimeout(this.closePanel, this.getTimeout());
		}

		this.emitStateChange();
	}

	onRequestFailure(data): void
	{
		this.#requestIsRunning = false;

		this.#state = State.error;

		this.refresh();

		this.emitStateChange();
	}

	emitStateChange(): void
	{
		this.emitter.emit('ON_AUTORUN_PROCESS_STATE_CHANGE', new BaseEvent({
			data: {
				state: this.#state,
				processedItemCount: this.#processedItemCount,
				totalItemCount: this.#totalItemCount,
				errors: this.#errors,
			},
		}));
	}

	refresh(): void
	{
		if (this.#state === State.running)
		{
			this.#panel.setProgress(this.#processedItemCount, this.#totalItemCount);
		}
		else if (this.#state === State.completed)
		{
			this.#panel
				.setProgress(this.#processedItemCount, this.#totalItemCount)
				.showResult(this.#errors)
			;
		}
		else if (this.#state === State.stopped)
		{
			this.closePanel();
		}
	}

	getTimeout(): number
	{
		const DEFAULT_TIMEOUT = 2000;

		return Type.isNumber(this.#settings.timeout) ? this.#settings.timeout : DEFAULT_TIMEOUT;
	}

	reset(): void
	{
		this.#errors = [];
		this.#processedItemCount = 0;
		this.#totalItemCount = 0;

		this.refresh();
	}

	closePanel(): void
	{
		this.#panel.close();
	}
}
