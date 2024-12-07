import { ProgressBarRepository } from 'crm.autorun';
import { SettingsCollection } from 'main.core.collections';
import { type BaseEvent, EventEmitter } from 'main.core.events';
import { BaseHandler } from './handlers/base-handler';

type BaseHandlerConstructor = typeof BaseHandler;
type SubscriptionHandler = (event: BaseEvent) => void;

export class Router
{
	static #handlers: Set<BaseHandlerConstructor> = new Set([]);

	#grid: BX.Main.grid;
	#progressBarRepo: ProgressBarRepository;
	#extensionSettings: SettingsCollection;

	#subscriptions: Map<string, SubscriptionHandler> = new Map();

	constructor(grid: BX.Main.grid, progressBarRepo: ProgressBarRepository, extensionSettings: SettingsCollection)
	{
		if (!(grid instanceof BX.Main.grid))
		{
			throw new TypeError('expected grid to be instance of BX.Main.grid');
		}
		this.#grid = grid;

		if (!(progressBarRepo instanceof ProgressBarRepository))
		{
			throw new TypeError('expected progressBarRepo to be instance of ProgressBarRepository');
		}
		this.#progressBarRepo = progressBarRepo;

		if (!(extensionSettings instanceof SettingsCollection))
		{
			throw new TypeError('expected extensionSettings to be instance of SettingsCollection');
		}
		this.#extensionSettings = extensionSettings;
	}

	static registerHandler(handler: BaseHandlerConstructor): void
	{
		this.#handlers.add(handler);
	}

	startListening(): void
	{
		for (const HandlerClass of this.constructor.#handlers)
		{
			const eventName = `BX.Crm.EntityList.Panel:${HandlerClass.getEventName()}`;

			const subscriptionHandler = this.#createSubscriptionHandler(HandlerClass);

			this.#subscriptions.set(eventName, subscriptionHandler);

			EventEmitter.subscribe(eventName, subscriptionHandler);
		}
	}

	#createSubscriptionHandler(HandlerClass: BaseHandlerConstructor): SubscriptionHandler
	{
		return (event: BaseEvent) => {
			const eventHandler: BaseHandler = new HandlerClass(event.getData());

			eventHandler.injectDependencies(this.#progressBarRepo, this.#extensionSettings);

			eventHandler.execute(
				this.#grid,
				this.#grid.getRows().getSelectedIds(),
				this.#grid.getActionsPanel()?.getForAllCheckbox()?.checked ?? false,
			);
		};
	}

	stopListening(): void
	{
		for (const [eventName, subscriptionHandler] of this.#subscriptions.entries())
		{
			EventEmitter.unsubscribe(eventName, subscriptionHandler);
		}
	}
}
