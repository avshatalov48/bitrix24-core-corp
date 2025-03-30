import { useChartStore } from 'humanresources.company-structure.chart-store';
import { memberRoles } from 'humanresources.company-structure.api';
import type { TreeItem } from './types';
import type { UserData } from 'humanresources.company-structure.utils';

export const OrgChartActions = {
	applyData: (departments: Map<Number, TreeItem>, currentDepartments: number[], userId: number): void => {
		const store = useChartStore();
		store.$patch({
			departments,
			currentDepartments,
			userId,
			searchedUserId: userId,
		});
	},
	focusDepartment: (departmentId: number): void => {
		const store = useChartStore();
		store.focusedNode = departmentId;
	},
	searchUserInDepartment: (userId: number): void => {
		const store = useChartStore();
		store.searchedUserId = userId;
	},
	moveSubordinatesToParent: (removableDepartmentId: number): void => {
		const store = useChartStore();
		const { departments, currentDepartments } = store;
		const removableDepartment = departments.get(removableDepartmentId);
		const {
			parentId,
			children: removableDeparmentChildren = [],
			userCount: removableDepartmentUserCount,
			heads: removableDeparmentHeads,
			employees: removableDeparmentEmployees = [],
		} = removableDepartment;
		removableDeparmentChildren.forEach((childId) => {
			const department = departments.get(childId);
			department.parentId = parentId;
		});
		const parentDepartment = departments.get(parentId);
		if (removableDepartmentUserCount > 0)
		{
			const parentDepartmentUsersIds = new Set([
				...parentDepartment.heads,
				...(parentDepartment.employees ?? []),
			].map((user) => user.id));
			const removableDeparmentUsers = [...removableDeparmentHeads, ...removableDeparmentEmployees];
			const movableUsers = removableDeparmentUsers.filter((user) => {
				return !parentDepartmentUsersIds.has(user.id);
			});
			for (const user of movableUsers)
			{
				user.role = memberRoles.employee;
			}
			parentDepartment.userCount += movableUsers.length;
			parentDepartment.employees = [
				...(parentDepartment.employees ?? []),
				...movableUsers,
			];
		}

		parentDepartment.children = [...parentDepartment.children, ...removableDeparmentChildren];
		if (currentDepartments.includes(removableDepartmentId))
		{
			store.changeCurrentDepartment(removableDepartmentId, parentId);
		}
	},
	markDepartmentAsRemoved: (removableDepartmentId: number): void => {
		const { departments } = useChartStore();
		const removableDepartment = departments.get(removableDepartmentId);
		const { parentId } = removableDepartment;
		const parentDepartment = departments.get(parentId);
		parentDepartment.children = parentDepartment.children.filter((childId) => {
			return childId !== removableDepartmentId;
		});
		delete removableDepartment.parentId;
		departments.set(removableDepartmentId, { ...removableDepartment, prevParentId: parentId });
	},
	removeDepartment: (departmentId: number): void => {
		const { departments } = useChartStore();
		departments.delete(departmentId);
	},
	inviteUser: (userData: UserData): void => {
		const { nodeId, ...restData } = userData;
		const { departments } = useChartStore();
		const department = departments.get(nodeId);
		if (department.employees)
		{
			departments.set(nodeId, {
				...department,
				employees: [...department.employees, { ...restData }],
				userCount: department.userCount + 1,
			});
		}
	},
};
