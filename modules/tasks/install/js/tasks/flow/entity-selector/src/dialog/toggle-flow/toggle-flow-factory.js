import { TaskEditToggleFlow, type TaskEditToggleFlowParams } from './type/task-edit-toggle-flow';
import { TaskViewToggleFlow, type TaskViewToggleFlowParams } from './type/task-view-toggle-flow';
import { Scope } from './dictionary/scope';

import type { AbstractToggleFlow } from './abstract-toggle-flow';

export class ToggleFlowFactory
{
	static get(toggleFlowParams: TaskViewToggleFlowParams | TaskEditToggleFlowParams): AbstractToggleFlow
	{
		// eslint-disable-next-line sonarjs/no-small-switch
		switch (toggleFlowParams.scope)
		{
			case Scope.taskView:
				return new TaskViewToggleFlow(toggleFlowParams);

			default:
				return new TaskEditToggleFlow(toggleFlowParams);
		}
	}
}
