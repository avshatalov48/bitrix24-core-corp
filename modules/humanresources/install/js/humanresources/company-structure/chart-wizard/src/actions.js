import { useChartStore, UserService } from 'humanresources.company-structure.chart-store';
import type { DepartmentData } from './types';

export const chartWizardActions = {
	createDepartment: async (departmentData: DepartmentData): void => {
		const { departments, structureMap } = useChartStore();
		const { id: departmentId, parentId, entityType } = departmentData;
		const parent = departments.get(parentId);
		parent.children = [...parent.children ?? [], departmentId];
		structureMap.set(departmentId, {
			id: departmentId,
			parentId,
			entityType,
		});
		departments.set(departmentId, {
			...departmentData,
			id: departmentId,
			chats: null,
			channels: null,
		});

		await UserService.refreshMultipleUsers();
	},
	moveUsersToRootDepartment: (removedUsers: number[], userMovedToRootIds: number[]): void => {
		const { departments } = useChartStore();
		const rootEmployees = removedUsers.filter((user) => userMovedToRootIds.includes(user.id));
		const rootNode = [...departments.values()].find((department) => department.parentId === 0);
		departments.set(rootNode.id, {
			...rootNode,
			employees: [...(rootNode.employees || []), ...rootEmployees],
			userCount: rootNode.userCount + rootEmployees.length,
		});

		UserService.refreshMultipleUsers();
	},
	refreshDepartments: (ids: number[]): void => {
		const store = useChartStore();
		store.refreshDepartments(ids);
	},
	tryToAddCurrentDepartment(departmentData: DepartmentData, departmentId: number): void
	{
		const store = useChartStore();
		const { heads, employees } = departmentData;
		const isCurrentUserAdd = [...heads, ...employees].some((user) => {
			return user.id === store.userId;
		});
		if (isCurrentUserAdd)
		{
			store.changeCurrentDepartment(0, departmentId);
		}
	},
};
