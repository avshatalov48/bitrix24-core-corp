import { Reflection } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';

export class CollaborationMenu
{
	constructor()
	{
		EventEmitter.subscribe('onImUpdateCounterMessage', this.#handleCounterUpdate.bind(this));
		EventEmitter.subscribe('onCounterDecrement', this.#handleLiveFeedCounterDecrement.bind(this));
	}

	getMenu(): typeof(BX.Main.interfaceButtons) | null
	{
		/**
		 *
		 * @type {BX.Main.interfaceButtonsManager}
		 */
		const menuManager = Reflection.getClass('BX.Main.interfaceButtonsManager');
		if (menuManager)
		{
			return menuManager.getById('top_menu_id_collaboration');
		}

		return null;
	}

	#handleCounterUpdate(event: BaseEvent): void
	{
		const menu = this.getMenu();
		const [counter] = event.getCompatData();
		menu?.updateCounter('im-message', counter);
	}

	#handleLiveFeedCounterDecrement(event: BaseEvent): void
	{
		const [decrement] = event.getCompatData();
		const menu = this.getMenu();
		if (menu)
		{
			const item = menu.getItemById('menu_live_feed');
			if (item)
			{
				const itemData = menu.getItemData(item);
				const { COUNTER, COUNTER_ID } = itemData;

				menu?.updateCounter(COUNTER_ID, Math.max(0, COUNTER - decrement));
			}
		}
	}
}
