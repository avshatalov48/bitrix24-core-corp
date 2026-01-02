import { BaseMainTool } from './base-main-tool';
import { Menu } from 'main.popup';
import { EventEmitter } from 'main.core.events';
import { Analytics } from '../analytics';

export class ExtensionTool extends BaseMainTool
{
	getIconClass(): string
	{
		return '--o-box';
	}

	onClick(): void
	{
		Analytics.send(Analytics.EVENT_CLICK_EXTENSION);
		this.#getMenu().toggle();
	}

	#getMenu(): Menu
	{
		return this.cache.remember('menu', () => {
			const menu = new Menu({
				bindElement: this.getIconElement(),
				items: this.options.items,
				angle: true,
				cachable: false,
				offsetLeft: 10,
				fixed: true,
			});
			EventEmitter.subscribe('SidePanel.Slider:onOpenStart', () => {
				menu.close();
			});

			return menu;
		});
	}

	getId(): string
	{
		return 'extension';
	}
}
