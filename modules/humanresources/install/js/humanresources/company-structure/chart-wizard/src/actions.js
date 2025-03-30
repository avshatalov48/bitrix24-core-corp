import { useChartStore } from 'humanresources.company-structure.chart-store';
import type { DepartmentData } from './types';

export const chartWizardActions = {
	createDepartment: (departmentData: DepartmentData): void => {
		const { departments } = useChartStore();
		const { id: departmentId, parentId } = departmentData;
		const parent = departments.get(parentId);
		parent.children = [...parent.children ?? [], departmentId];
		departments.set(departmentId, {
			...departmentData,
			id: departmentId,
		});
	},
	editDepartment: (departmentData: DepartmentData): void => {
		const { id, parentId } = departmentData;
		const { departments } = useChartStore();
		departments.set(id, { ...departmentData });
		const prevParent = [...departments.values()].find((department) => {
			return department.children?.includes(id);
		});
		if (parentId !== 0 && prevParent.id !== parentId)
		{
			prevParent.children = prevParent.children.filter((childId) => childId !== id);
			const newParent = departments.get(parentId);
			newParent.children = [...(newParent.children ?? []), id];
			departments.set(id, { ...departmentData, prevParentId: prevParent.id });
		}
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
