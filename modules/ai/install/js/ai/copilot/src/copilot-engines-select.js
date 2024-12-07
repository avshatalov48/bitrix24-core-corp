import { Tag, Dom, Text, bindOnce } from 'main.core';
import { EventEmitter, BaseEvent } from 'main.core.events';
import { CopilotMenu, CopilotMenuEvents } from './copilot-menu';
import type { CopilotMenuItem, CopilotMenuSelectEventData } from './copilot-menu';
import type { EngineInfo } from './types/engine-info';

import './css/copilot-engines-select.css';
import { CopilotProvidersMenuItems } from './menu-items/copilot-providers-menu-items';

export type CopilotEnginesSelectOptions = {
	engines: EngineInfo[];
	selectedEngineCode: string;
}

export type SelectEngineEventData = {
	code: string;
}

export const CopilotEnginesSelectEvents = Object.freeze({
	selectEngine: 'selectEngine',
	showEnginesMenu: 'showEnginesMenu',
	hideEnginesMenu: 'hideEnginesMenu',
});

export class CopilotEnginesSelect extends EventEmitter
{
	#engines: EngineInfo[] = [];
	#container: HTMLElement;
	#menu: CopilotMenu;
	#selectedEngineCode: string;

	constructor(options: CopilotEnginesSelectOptions) {
		super(options);
		this.#engines = options.engines;
		this.#selectedEngineCode = options.selectedEngineCode || this.#getSelectedEngine().code;

		this.#initContainer();

		this.setEventNamespace('AI.Copilot.EnginesSelect');
	}

	render(): HTMLElement
	{
		if (!this.#container)
		{
			this.#initContainer();
		}

		return this.#container;
	}

	getContainer(): HTMLElement
	{
		return this.#container;
	}

	getSelectedEngineCode(): string | null
	{
		return this.#getSelectedEngine()?.code ?? null;
	}

	showMenu(): void
	{
		if (!this.#menu)
		{
			this.#initMenu();
		}

		if (!this.#menu.isShown())
		{
			this.#menu.show();
			this.#menu.setBindElement(this.#container, {
				left: -20,
				top: 20,
			});
			this.#menu.adjustPosition();
		}
	}

	hideMenu(): void
	{
		if (this.#menu && this.#menu.isShown())
		{
			this.#menu.hide();
			this.#menu = null;
		}
	}

	getMenu(): CopilotMenu | null
	{
		return this.#menu;
	}

	isMenuShown(): boolean
	{
		return this.#menu && this.#menu.isShown();
	}

	setSelectedEngine(engineCode: string)
	{
		this.#selectedEngineCode = engineCode;

		const newSelectedEngine: EngineInfo = this.#engines.find((engine) => engine.code === engineCode);
		this.#changeSelectedItemTitleWithAnimation(newSelectedEngine?.title);
	}

	#getSelectedEngine(): EngineInfo
	{
		if (this.#engines.length === 0)
		{
			return null;
		}

		const engineWithSelectedFlag = this.#engines.find((engine) => {
			return this.#selectedEngineCode ? this.#selectedEngineCode === engine.code : engine.selected;
		});

		return engineWithSelectedFlag ?? this.#engines[0];
	}

	#initContainer(): HTMLElement
	{
		this.#container = Tag.render`
			<div class="ai__copilot_engines-select">
				${this.#renderSelectedElem(this.#getSelectedEngine().title)}
			</div>
		`;

		return this.#container;
	}

	#renderSelectedElem(selectedText: string): HTMLElement
	{
		return Tag.render`
			<div class="ai__copilot_engines-select_selected">
				${Text.encode(selectedText)}
			</div>
		`;
	}

	#initMenu(): void
	{
		this.#menu = new CopilotMenu({
			bindElement: this.#container,
			items: this.#getMenuItems(),
			cacheable: false,
		});

		this.#menu.subscribe(CopilotMenuEvents.select, this.#handleSelectMenuEvent.bind(this));
		this.#menu.subscribe(CopilotMenuEvents.show, this.#handleShowMenuEvent.bind(this));
		this.#menu.subscribe(CopilotMenuEvents.hide, this.#handleHideMenuEvent.bind(this));
	}

	#handleSelectMenuEvent(e: BaseEvent<CopilotMenuSelectEventData>): void
	{
		const isEngineMenuItem = this.#engines.some((elem) => elem.code === e.getData().command);
		if (isEngineMenuItem)
		{
			this.#selectedEngineCode = e.getData().command;
			this.#changeSelectedItemTitleWithAnimation(this.#getSelectedEngine().title);

			this.emit(CopilotEnginesSelectEvents.selectEngine, new BaseEvent({
				data: {
					code: e.getData().command,
				},
			}));
		}
	}

	#handleShowMenuEvent(): void
	{
		Dom.addClass(this.#container, '--menu-show');
		this.emit(CopilotEnginesSelectEvents.showEnginesMenu, new BaseEvent());
	}

	#handleHideMenuEvent(): void
	{
		Dom.removeClass(this.#container, '--menu-show');
		this.emit(CopilotEnginesSelectEvents.hideEnginesMenu, new BaseEvent());
	}

	#getMenuItems(): CopilotMenuItem
	{
		return CopilotProvidersMenuItems.getMenuItems({
			engines: this.#engines,
			selectedEngineCode: this.#selectedEngineCode,
		});
	}

	#changeSelectedItemTitleWithAnimation(engineTitle: string): void
	{
		const oldSelectedItem = this.#container.firstElementChild;
		const newSelectedItem = this.#renderSelectedElem(engineTitle);

		if (oldSelectedItem.innerText === newSelectedItem.innerText)
		{
			return;
		}

		Dom.style(this.#container, {
			width: `${this.#container.offsetWidth}px`,
		});

		Dom.style(newSelectedItem, {
			position: 'absolute',
			top: 0,
			left: 0,
			opacity: 0,
		});

		bindOnce(oldSelectedItem, 'transitionend', () => {
			Dom.remove(oldSelectedItem);
		});

		bindOnce(newSelectedItem, 'transitionend', () => {
			Dom.style(newSelectedItem, null);
		});

		Dom.append(newSelectedItem, this.#container);

		requestAnimationFrame(() => {
			Dom.style(this.#container, {
				width: `${newSelectedItem.scrollWidth}px`,
			});

			Dom.style(oldSelectedItem, {
				opacity: 0,
			});

			Dom.style(newSelectedItem, {
				opacity: 1,
			});
		});
	}
}
