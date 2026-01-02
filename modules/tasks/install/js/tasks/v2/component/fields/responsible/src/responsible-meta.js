import { Loc } from 'main.core';
import { Core } from 'tasks.v2.core';
import { EntitySelectorEntity, TaskField } from 'tasks.v2.const';

export const responsibleMeta = Object.freeze({
	id: TaskField.Responsible,
	title: Loc.getMessage('TASKS_V2_RESPONSIBLE_TITLE'),
	dialogOptions: (context: string = 'tasks-card-participant') => {
		const limits = Core.getParams().limits;

		return {
			context,
			multiple: false,
			enableSearch: true,
			entities: [
				{
					id: EntitySelectorEntity.User,
					options: {
						emailUsers: true,
						inviteGuestLink: true,
						analyticsSource: 'tasks',
						lockGuestLink: !limits.mailUserIntegration,
						lockGuestLinkFeatureId: limits.mailUserIntegrationFeatureId,
					},
				},
				{
					id: 'department',
				},
			],
		};
	},
});
