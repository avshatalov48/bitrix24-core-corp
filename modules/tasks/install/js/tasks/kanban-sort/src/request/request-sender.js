import { KanbanAjaxComponent } from '../kanban-component/kanban-ajax-component';

export class RequestSender
{
	#ajaxComponent: KanbanAjaxComponent;

	constructor()
	{
		this.#init();
	}

	setOrder(order: string = '')
	{
		BX.ajax.runComponentAction('bitrix:tasks.kanban', 'setNewTaskOrder', {
			mode: 'class',
			data: {
				order: order,
				params: this.#ajaxComponent.getParams(),
			},
		}).then(
			(response) => {
				const data = response.data;
				BX.onCustomEvent(this, 'onTaskSortChanged', [data]);
			},
		);
	}

	#init()
	{
		this.#ajaxComponent = BX.Tasks.KanbanAjaxComponent.Parameters;
	}
}