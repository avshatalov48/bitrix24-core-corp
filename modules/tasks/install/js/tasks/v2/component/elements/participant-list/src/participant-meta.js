import { Core } from 'tasks.v2.core';
import { EntitySelectorEntity } from 'tasks.v2.const';

export const participantMeta = Object.freeze({
	dialogOptions: (taskId: number, idSalt: string) => {
		const limits = Core.getParams().limits;

		return {
			id: `tasks-user-selector-dialog-${idSalt}-${taskId}`,
			context: 'tasks-card',
			multiple: true,
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
