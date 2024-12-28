import { Core } from 'im.v2.application.core';

export const SessionManager = {
	findGroupByStatus(sessionStatusName: string): string | null
	{
		const { sessionStatusMap } = Core.getApplicationData();

		const groupByStatusName = Object.entries(sessionStatusMap).find(([groupName, groupStatuses]) => {
			return sessionStatusName in groupStatuses;
		});

		return groupByStatusName ? groupByStatusName[0] : null;
	},
};
