import { ajax } from 'main.core';
import { KanbanAjaxComponent } from '../kanban-component/kanban-ajax-component';

export class RequestSender
{
	static #ACTION = 'setNewTaskOrder';
	#ajaxComponent: KanbanAjaxComponent;

	constructor()
	{
		this.#init();
	}

	setOrder(order: string = '')
	{
		ajax({
			method: 'POST',
			dataType: 'json',
			url: this.#ajaxComponent.getPath(),
			data: {
				action: RequestSender.#ACTION,
				order: order,
				params: this.#ajaxComponent.getParams(),
				sessid: BX.bitrix_sessid(),
			},
			onsuccess: function(data) {
				BX.onCustomEvent(this, 'onTaskSortChanged', [data]);
			},
		});
	}

	#init()
	{
		this.#ajaxComponent = BX.Tasks.KanbanAjaxComponent.Parameters;
	}
}