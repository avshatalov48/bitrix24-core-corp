import { Loc } from 'main.core';
import { Core } from 'tasks.v2.core';
import { GroupType, Model, TaskField } from 'tasks.v2.const';

export const groupMeta = Object.freeze({
	id: TaskField.Group,
	title: Loc.getMessage('TASKS_V2_GROUP_TITLE'),
	getTitle: (groupId: number) => {
		const group = Core.getStore().getters[`${Model.Groups}/getById`](groupId);

		return {
			[GroupType.Collab]: Loc.getMessage('TASKS_V2_GROUP_TITLE_COLLAB'),
			[GroupType.Scrum]: Loc.getMessage('TASKS_V2_GROUP_TITLE_SCRUM'),
		}[group?.type] ?? Loc.getMessage('TASKS_V2_GROUP_TITLE');
	},
});
