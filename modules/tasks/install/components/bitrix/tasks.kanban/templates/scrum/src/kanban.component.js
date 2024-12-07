export type AjaxComponentParams = {
	USER_ID: number,
	GROUP_ID: number,
	GROUP_ID_FORCED: number,
	SPRINT_ID: number,
	PERSONAL: string,
	TIMELINE_MODE: string
}

type Params = {
	filterId: string,
	defaultPresetId: string,
	ajaxComponentPath: string,
	ajaxComponentParams: AjaxComponentParams
}

export class KanbanComponent
{
	constructor(params: Params)
	{
		this.filterId = params.filterId;
		this.defaultPresetId = params.defaultPresetId;
		this.ajaxComponentPath = params.ajaxComponentPath;
		this.ajaxComponentParams = params.ajaxComponentParams;
	}

	onClickSort(item: HTMLElement, order?: string)
	{
		if (!Dom.hasClass(item, 'menu-popup-item-accept'))
		{
			this.refreshIcons(item);
			this.saveSelection(order);
		}
	}

	refreshIcons(item: HTMLElement)
	{
		item.parentElement.childNodes.forEach((element) => {
			Dom.removeClass(element, 'menu-popup-item-accept');
		})

		Dom.addClass(item, 'menu-popup-item-accept');
	}

	saveSelection(order?: string)
	{
		BX.ajax.runComponentAction('bitrix:tasks.kanban', 'setNewTaskOrder', {
			mode: 'class',
			data: {
				order: (order ? order : 'desc'),
				params: this.ajaxComponentParams,
			},
		}).then(
			(response) => {
				const data = response.data;
				BX.onCustomEvent(this, 'onTaskSortChanged', [data]);
			},
		);
	}
}