import { Type } from 'main.core';
import {MenuManager, Menu} from 'main.popup';
import {EventEmitter} from 'main.core.events';
import {Store} from 'ui.vue3.vuex';
import {RestClient} from 'rest.client';

import {Core} from 'im.v2.application.core';
import {EventType} from 'im.v2.const';

import type {MenuItem} from '../type/menu';

const EVENT_NAMESPACE = 'BX.Messenger.v2.Lib.Menu';

export class BaseMenu extends EventEmitter
{
	menuInstance: Menu;
	context: Object;
	target: HTMLElement;
	store: Store;
	restClient: RestClient;
	id: String = 'im-base-context-menu';

	static events = {
		onCloseMenu: 'onCloseMenu'
	};

	constructor()
	{
		super();
		this.setEventNamespace(EVENT_NAMESPACE);

		this.store = Core.getStore();
		this.restClient = Core.getRestClient();

		this.onClosePopupHandler = this.onClosePopup.bind(this);
	}

	// public
	openMenu(context: Object, target: HTMLElement)
	{
		if (this.menuInstance)
		{
			this.close();
		}
		this.context = context;
		this.target = target;
		this.menuInstance = this.getMenuInstance();
		this.menuInstance.show();

		EventEmitter.subscribe(EventType.dialog.closePopup, this.onClosePopupHandler);
	}

	getMenuInstance(): Menu
	{
		return MenuManager.create(this.getMenuOptions());
	}

	getMenuOptions(): Object
	{
		return {
			id: this.id,
			bindOptions: {forceBindPosition: true, position: 'bottom'},
			targetContainer: document.body,
			bindElement: this.target,
			cacheable: false,
			className: this.getMenuClassName(),
			items: this.#prepareMenuItems(),
			events: {
				onClose: () => {
					this.emit(BaseMenu.events.onCloseMenu);
					this.close();
				}
			}
		};
	}

	getMenuItems(): MenuItem[]
	{
		return [];
	}

	getMenuClassName(): string
	{
		return '';
	}

	onClosePopup()
	{
		this.close();
	}

	close()
	{
		EventEmitter.unsubscribe(EventType.dialog.closePopup, this.onClosePopupHandler);
		if (!this.menuInstance)
		{
			return;
		}

		this.menuInstance.destroy();
		this.menuInstance = null;
	}

	destroy()
	{
		this.close();
	}

	getCurrentUserId(): number
	{
		return Core.getUserId();
	}

	#prepareMenuItems(): MenuItem[]
	{
		return this.#filterExcessDelimiters(this.getMenuItems());
	}

	#filterExcessDelimiters(menuItems: MenuItem[]): MenuItem[]
	{
		const menuItemsWithoutDuplicates = this.#filterDuplicateDelimiters(menuItems);

		return this.#filterFinishingDelimiter(menuItemsWithoutDuplicates);
	}

	#filterDuplicateDelimiters(menuItems: MenuItem[]): MenuItem[]
	{
		let previousElement = null;

		return menuItems.filter((element) => {
			if (this.#isDelimiter(previousElement) && this.#isDelimiter(element))
			{
				return false;
			}

			if (element !== null)
			{
				previousElement = element;
			}

			return true;
		});
	}

	#filterFinishingDelimiter(menuItems: MenuItem[]): MenuItem[]
	{
		let previousElement = null;

		return menuItems.reverse().filter((element) => {
			if (previousElement === null && this.#isDelimiter(element))
			{
				return false;
			}

			if (element !== null)
			{
				previousElement = element;
			}

			return true;
		}).reverse();
	}

	#isDelimiter(element: ?MenuItem): boolean
	{
		return Type.isObjectLike(element) && element.delimiter === true;
	}
}
