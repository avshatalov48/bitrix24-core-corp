type AjaxComponentParameters = {
	ajaxComponentPath: string;
	ajaxComponentParams: any;
}
export class KanbanAjaxComponent
{
	#ajaxComponentPath: string;
	#ajaxComponentParams: any;

	constructor(parameters: AjaxComponentParameters = {})
	{
		this.#ajaxComponentPath = parameters?.ajaxComponentPath;
		this.#ajaxComponentParams = parameters?.ajaxComponentParams;
	}

	getPath()
	{
		return this.#ajaxComponentPath;
	}

	getParams()
	{
		return this.#ajaxComponentParams;
	}
}