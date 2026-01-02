import { ButtonManager } from 'ui.buttons';
import { Roles, type Params as RolesParams } from './roles';

type Params = {
	filterId: string,
	createNode: HTMLElement,
	roles: RolesParams,
	showPresetTourGuide: boolean,
	isV2Form: boolean,
	groupId?: number,
	analytics: Object,
};

export class TasksInterfaceFilter
{
	#params: Params;

	constructor(params: Params)
	{
		this.#params = params;

		this.#initAddButton();
		this.#initRoles();
		this.#showPresetTourGuide();
	}

	#initAddButton(): void
	{
		if (!this.#params.isV2Form || !this.#params.createNode)
		{
			return;
		}

		const button = ButtonManager.createFromNode(this.#params.createNode);
		button.getMainButton().bindEvent('click', () => BX.Tasks.V2.Application.TaskCard.showCompactCard({
			groupId: this.#params.groupId,
			analytics: this.#params.analytics,
		}));
	}

	#initRoles(): void
	{
		new Roles(this.#params.roles);
	}

	#showPresetTourGuide(): void
	{
		if (!this.#params.showPresetTourGuide)
		{
			return;
		}

		BX.Tasks.Preset.Aha = new BX.Tasks.Preset({
			filterId: this.#params.filterId,
		});
		BX.Tasks.Preset.Aha.payAttention();
	}
}
