import { ActionFactory } from './group-actions/group-action-factory';

export type ActionParamsType = {
	actionId: string,
	gridId: string,
	filter: Array,
	isCloud: boolean,
}

export class Panel
{
	static executeAction(params: ActionParamsType) {
		try
		{
			const action = ActionFactory.createAction(params.actionId, {
				grid: BX.Main.gridManager.getById(params.gridId)?.instance,
				filter: params.filter,
				isCloud: params.isCloud,
			});
			action.execute();
		}
		catch (error)
		{
			console.error('Error executing action:', error);
		}
	}
}