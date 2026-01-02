import { Cache, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Counter } from 'ui.cnt';

export class InvitationWidgetCounter
{
	#cache = new Cache.MemoryCache();
	static #instance: InvitationWidgetCounter;

	constructor()
	{
		EventEmitter.subscribeOnce('HR.company-structure:first-popup-showed', this.#onFirstWatchNewStructure.bind(this));
	}

	static getInstance(): InvitationWidgetCounter
	{
		if (!this.#instance)
		{
			this.#instance = new this();
		}

		return this.#instance;
	}

	setOptions(options: {}): InvitationWidgetCounter
	{
		this.#cache.set('options', options);

		return this;
	}

	getOptions(): {}
	{
		return this.#cache.get('options', {});
	}

	show(): void
	{
		this.#getCounter().renderTo(this.#getCounterWrapper());
	}

	onReceiveCounterValue(value): void
	{
		if (this.getOptions().shouldShowStructureCounter)
		{
			value++;
		}

		this.#getCounter().update(value);
		this.getOptions().invitationCounter = value;

		if (value > 0)
		{
			this.#getCounter().renderTo(this.#getCounterWrapper());
		}
		else
		{
			this.#getCounter().destroy();
			this.#cache.delete('counter');
		}
	}

	#getCounterWrapper(): HTMLElement
	{
		return this.#cache.remember('counter-wrapper', () => {
			return this.getOptions().button.querySelector('.invitation-widget-counter');
		});
	}

	#getCounter(): Counter
	{
		return this.#cache.remember('counter', () => {
			return new Counter({
				value: this.#getCounterValue(),
				color: Counter.Color.DANGER,
			});
		});
	}

	#getCounterValue(): number
	{
		let counterValue = Number(this.getOptions().invitationCounter);
		if (this.getOptions().shouldShowStructureCounter ?? false)
		{
			counterValue++;
		}

		return counterValue;
	}

	#onFirstWatchNewStructure(): void
	{
		let value = this.#getCounter().value;

		if (!Type.isNumber(value))
		{
			return;
		}

		if (!this.getOptions().shouldShowStructureCounter)
		{
			return;
		}

		value--;
		this.getOptions().shouldShowStructureCounter = false;
		this.#getCounter().update(value);
		this.getOptions().invitationCounter = value;

		if (value > 0)
		{
			this.#getCounter().renderTo(this.#getCounterWrapper());
		}
		else
		{
			this.#getCounter().destroy();
			this.#cache.delete('counter');
		}
	}
}
