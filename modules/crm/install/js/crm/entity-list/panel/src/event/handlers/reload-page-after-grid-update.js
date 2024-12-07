import { Text, Type } from 'main.core';
import { type BaseEvent, EventEmitter } from 'main.core.events';
import { BaseHandler } from './base-handler';

export class ReloadPageAfterGridUpdate extends BaseHandler
{
	#isReloadOnlyIfForAll: boolean = false;

	constructor({ isReloadOnlyIfForAll })
	{
		super();

		if (!Type.isNil(isReloadOnlyIfForAll))
		{
			this.#isReloadOnlyIfForAll = Text.toBoolean(isReloadOnlyIfForAll);
		}
	}

	static getEventName(): string
	{
		return 'reloadPageAfterGridUpdate';
	}

	execute(grid, selectedIds, forAll)
	{
		if (!forAll && this.#isReloadOnlyIfForAll)
		{
			return;
		}

		EventEmitter.subscribe('Grid::updated', (event: BaseEvent<Array>) => {
			const [eventSender] = event.getData();

			this.#reloadPageAfterEvent(grid, eventSender);
		});
	}

	#reloadPageAfterEvent(target: BX.Main.grid, eventSender: BX.Main.grid): void
	{
		if (target !== eventSender)
		{
			return;
		}

		setTimeout(() => window.location.reload(), 0);
	}
}
