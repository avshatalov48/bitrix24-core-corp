import { Dom } from 'main.core';
import { EventEmitter } from 'main.core.events';

export class Footer
{
	constructor()
	{
		EventEmitter.subscribe('Kanban.Grid:onFixedModeStart', () => {
			this.hide();
		});
	}

	show(): void
	{
		Dom.removeClass(this.getContainer(), 'hidden');
	}

	hide(): void
	{
		Dom.addClass(this.getContainer(), 'hidden');
	}

	getContainer(): HTMLElement
	{
		return document.getElementById('air-footer');
	}
}
