import { useChartStore } from 'humanresources.company-structure.chart-store';
import { ChatTypes } from 'humanresources.company-structure.utils';
import type { CommunicationDetailed } from 'humanresources.company-structure.utils';

export const DepartmentContentActions = {
	updateEmployees: (departmentId: number, employees: Array): void => {
		const { departments } = useChartStore();
		const department = departments.get(departmentId);

		if (!department)
		{
			return;
		}

		departments.set(departmentId, { ...department, employees });
	},
	updateHeads: (departmentId: number, heads: Array): void => {
		const { departments } = useChartStore();
		const department = departments.get(departmentId);

		if (!department)
		{
			return;
		}

		departments.set(departmentId, { ...department, heads });
	},
	updateEmployeeListOptions: (
		departmentId: number,
		options: { page?: number, shouldUpdateList?: boolean, isListUpdated?: boolean },
	): void => {
		const { departments } = useChartStore();
		const department = departments.get(departmentId);

		if (!department)
		{
			return;
		}

		department.employeeListOptions = {
			...department.employeeListOptions,
			...options,
		};

		departments.set(departmentId, department);
	},
	setChatsAndChannels: (
		nodeId: number,
		chats: Array<CommunicationDetailed>,
		channels: Array<CommunicationDetailed>,
		collabs: Array<CommunicationDetailed>,
		chatsNoAccess: number = 0,
		channelsNoAccess: number = 0,
		collabsNoAccess: number = 0,
	): void => {
		const store = useChartStore();
		const department = store.departments.get(nodeId);
		if (!department)
		{
			return;
		}

		department.channelsDetailed = channels;
		department.chatsDetailed = chats;
		department.collabsDetailed = collabs;
		department.channelsNoAccess = channelsNoAccess;
		department.chatsNoAccess = chatsNoAccess;
		department.collabsNoAccess = collabsNoAccess;
		department.communicationsCount = chats.length + channels.length + collabs.length
			+ chatsNoAccess + channelsNoAccess + collabsNoAccess
		;
	},
	unbindChatFromNode: (nodeId: number, chatId: number, type: string): void => {
		const store = useChartStore();
		const department = store.departments.get(nodeId);
		if (!department)
		{
			return;
		}

		if (type === ChatTypes.collab)
		{
			department.collabsDetailed = department.collabsDetailed.filter((chat) => chat.id !== chatId);
		}
		else
		{
			department.channelsDetailed = department.channelsDetailed.filter((chat) => chat.id !== chatId);
			department.chatsDetailed = department.chatsDetailed.filter((chat) => chat.id !== chatId);
		}
		department.communicationsCount--;
	},
};
